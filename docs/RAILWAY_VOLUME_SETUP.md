# Railway Volume Setup for Persistent Image Storage

## Problem
Product images are stored in the local filesystem, which gets wiped on each Railway redeployment. This causes images to disappear after redeployments.

## Solution: Railway Volumes
Railway Volumes provide persistent storage that survives redeployments. This guide will help you set up a volume for storing product images.

## Step-by-Step Setup

### Step 1: Add Volume to Your Railway Project

1. **Go to your Railway project dashboard**
   - Visit [railway.app](https://railway.app)
   - Open your AquaSphere project

2. **Add a Volume**
   - Click the **"+ New"** button (top right)
   - Select **"Volume"**
   - Name it: `uploads` (or any name you prefer)
   - Click **"Add"**

3. **Wait for Volume to be Created**
   - Railway will provision the volume (takes a few seconds)
   - You'll see a new volume service card

### Step 2: Link Volume to Your App Service

1. **Go to Your App Service**
   - Click on your main application service (the one running PHP)
   - Go to the **"Settings"** tab
   - Scroll down to **"Volumes"** section

2. **Mount the Volume**
   - Click **"+ Add Volume"**
   - Select the volume you created (`uploads`)
   - **CRITICAL**: Set the mount path to `/app/uploads` (this makes it accessible via the web server)
   - Click **"Add"**

### Step 3: Set Environment Variable

1. **Go to Variables Tab**
   - In your app service, go to **"Variables"** tab
   - Click **"+ New Variable"**

2. **Add Volume Path Variable**
   - Name: `RAILWAY_VOLUME_PATH`
   - Value: `/app/uploads` (must match the mount path from Step 2)
   - Click **"Add"**

### Step 4: Redeploy Your Application

After setting up the volume and environment variable:
- Railway will automatically redeploy, OR
- You can manually trigger a redeploy by pushing a commit

### Step 5: Verify It's Working

1. **Upload a Product Image**
   - Log in as admin
   - Go to Product Management
   - Add a new product with an image

2. **Check the Volume**
   - The image should be stored in `/app/uploads/products/`
   - The file will be accessible via the web at `https://your-app.railway.app/uploads/products/filename.jpg`
   - Check Railway logs for confirmation: `Image uploaded successfully: uploads/products/filename.jpg to /app/uploads/products/filename.jpg (Volume: Yes)`

3. **Test Persistence**
   - Redeploy your application (push a small change)
   - Check that the product image still displays
   - Images should now persist across redeployments! ✅

## Important Notes

### Mount Path Configuration
- **Mount Path**: `/app/uploads` (in Railway service settings)
- **Environment Variable**: `RAILWAY_VOLUME_PATH=/app/uploads`
- **File Storage**: Files are stored at `/app/uploads/products/` in the volume
- **Web URL**: Files are accessible at `https://your-app.railway.app/uploads/products/filename.jpg`

### Why This Works
- Railway volumes are mounted into your container's filesystem
- When mounted at `/app/uploads`, files stored there persist across redeployments
- The web server (Apache/Nginx) serves files from `/app/uploads/` as `/uploads/` in URLs
- The database stores relative paths like `uploads/products/filename.jpg`
- These paths work because the volume is mounted at the web root level

## Alternative: Using Cloud Storage

If you prefer not to use Railway Volumes, you can use cloud storage services:

### Option 1: Cloudinary (Recommended - Free Tier Available)

1. Sign up at [cloudinary.com](https://cloudinary.com)
2. Get your Cloud Name, API Key, and API Secret
3. Add environment variables:
   - `CLOUDINARY_CLOUD_NAME`
   - `CLOUDINARY_API_KEY`
   - `CLOUDINARY_API_SECRET`
4. Update `api/admin/add_product.php` to upload to Cloudinary instead of local storage

### Option 2: AWS S3

1. Create an S3 bucket
2. Get AWS credentials
3. Add environment variables:
   - `AWS_ACCESS_KEY_ID`
   - `AWS_SECRET_ACCESS_KEY`
   - `AWS_S3_BUCKET`
   - `AWS_REGION`
4. Update upload code to use S3

## Current Implementation

The code now checks for:
1. `RAILWAY_VOLUME_PATH` environment variable (Railway Volume) - **RECOMMENDED**
2. `UPLOAD_DIR` environment variable (Custom path)
3. Falls back to web root `uploads/` directory (ephemeral - will be lost on redeploy)

## Troubleshooting

### Images Still Disappearing?

1. **Verify Environment Variable**
   - Go to Railway → Your Service → Variables
   - Check that `RAILWAY_VOLUME_PATH` is set to `/app/uploads`
   - Make sure there are no typos or extra spaces

2. **Verify Volume Mount**
   - Go to Railway → Your Service → Settings → Volumes
   - Check that the volume is mounted at `/app/uploads`
   - The mount path must match the environment variable exactly

3. **Check Railway Logs**
   - After uploading an image, check logs for:
     - `Using Railway Volume path: /app/uploads`
     - `Image uploaded successfully: uploads/products/filename.jpg to /app/uploads/products/filename.jpg (Volume: Yes)`
   - If you see `Volume: No`, the environment variable is not set correctly

4. **Test File Persistence**
   - Upload an image
   - Check Railway logs to confirm it's using the volume
   - Redeploy the application
   - Check if the image still exists in the volume
   - Access the image URL directly: `https://your-app.railway.app/uploads/products/filename.jpg`

### Permission Errors?

- Railway volumes should have correct permissions automatically
- If issues persist, check volume mount settings
- The code automatically sets directory permissions to 0777

### Volume Not Showing?

- Make sure you're on a Railway plan that supports volumes
- Free tier may have limitations - check Railway documentation
- Volumes are only available on certain Railway plans

### Files Not Accessible via Web?

- Ensure the volume is mounted at `/app/uploads` (not `/data/uploads` or other paths)
- Check that your web server (Apache/Nginx) is configured to serve files from `/app/uploads/`
- Verify the `.htaccess` file exists in the `uploads/` directory
- Test by accessing the image URL directly in a browser

## Quick Checklist

- [ ] Volume created in Railway project
- [ ] Volume mounted to app service at `/app/uploads`
- [ ] `RAILWAY_VOLUME_PATH` environment variable set to `/app/uploads`
- [ ] Application redeployed after setting up volume
- [ ] Test: Upload product image → Check logs show "Volume: Yes" → Redeploy → Image still exists ✅

## Verification Commands (if you have SSH access)

If Railway provides SSH access to your container:

```bash
# Check if volume is mounted
ls -la /app/uploads

# Check if products directory exists
ls -la /app/uploads/products/

# Check environment variable
echo $RAILWAY_VOLUME_PATH

# Verify a specific file exists
ls -la /app/uploads/products/product_*.jpg
```

## Notes

- **Volume Size**: Railway volumes have size limits based on your plan
- **Backups**: Consider backing up important images separately
- **Performance**: Volumes provide fast local storage, better than cloud storage for frequently accessed files
- **Cost**: Check Railway pricing for volume storage costs
- **Migration**: If you already have images in ephemeral storage, you'll need to re-upload them after setting up the volume
