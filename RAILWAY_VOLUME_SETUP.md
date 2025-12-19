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
   - Set the mount path: `/data/uploads` (or `/persistent/uploads`)
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
   - Set the mount path: `/data/uploads` (or your preferred path)
   - Click **"Add"**

### Step 3: Set Environment Variable

1. **Go to Variables Tab**
   - In your app service, go to **"Variables"** tab
   - Click **"+ New Variable"**

2. **Add Volume Path Variable**
   - Name: `RAILWAY_VOLUME_PATH`
   - Value: `/data/uploads` (or whatever mount path you used in Step 2)
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
   - The image should be stored in `/data/uploads/uploads/products/`
   - You can verify this by checking Railway logs or using the Railway CLI

3. **Test Persistence**
   - Redeploy your application (push a small change)
   - Check that the product image still displays
   - Images should now persist across redeployments! ✅

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
1. `RAILWAY_VOLUME_PATH` environment variable (Railway Volume)
2. `UPLOAD_DIR` environment variable (Custom path)
3. Falls back to web root `uploads/` directory (ephemeral)

## Troubleshooting

### Images Still Disappearing?
- Verify `RAILWAY_VOLUME_PATH` is set correctly
- Check that the volume is mounted in your app service settings
- Ensure the mount path matches the environment variable value
- Check Railway logs for upload errors

### Permission Errors?
- Railway volumes should have correct permissions automatically
- If issues persist, check volume mount settings

### Volume Not Showing?
- Make sure you're on a Railway plan that supports volumes
- Free tier may have limitations - check Railway documentation

## Quick Checklist

- [ ] Volume created in Railway project
- [ ] Volume mounted to app service at `/data/uploads` (or your path)
- [ ] `RAILWAY_VOLUME_PATH` environment variable set
- [ ] Application redeployed
- [ ] Test: Upload product image → Redeploy → Image still exists ✅

## Notes

- **Volume Size**: Railway volumes have size limits based on your plan
- **Backups**: Consider backing up important images separately
- **Performance**: Volumes provide fast local storage, better than cloud storage for frequently accessed files
- **Cost**: Check Railway pricing for volume storage costs

