# Railway PostgreSQL Setup Guide for AquaSphere

## Step-by-Step: Setting Up PostgreSQL in Railway

### Step 1: Add PostgreSQL Database Service

1. **Go to your Railway project dashboard**
   - Visit [railway.app](https://railway.app)
   - Open your AquaSphere project

2. **Add PostgreSQL Database**
   - Click the **"+ New"** button (or **"Add Service"**)
   - Select **"Database"**
   - Choose **"Add PostgreSQL"**
   - Railway will automatically create a PostgreSQL database for you

3. **Wait for Database to Start**
   - Railway will provision the database (takes 1-2 minutes)
   - You'll see a new service card for PostgreSQL

### Step 2: Connect Database to Your Application

1. **Get Database Connection String**
   - Click on the PostgreSQL service card
   - Go to the **"Variables"** tab
   - You'll see `DATABASE_URL` - this is your connection string
   - It looks like: `postgresql://postgres:password@hostname:5432/railway`

2. **Link Database to Your App Service**
   - Go back to your main application service
   - Click on your app service (the one running your PHP code)
   - Go to **"Variables"** tab
   - Railway should automatically add `DATABASE_URL` from the linked database
   - If not, manually add it:
     - Click **"+ New Variable"**
     - Name: `DATABASE_URL`
     - Value: Copy from PostgreSQL service's `DATABASE_URL`

### Step 3: Verify Connection

Your application will automatically:
- ✅ Detect PostgreSQL via `DATABASE_URL` environment variable
- ✅ Connect to the database
- ✅ Create tables on first use (via `init_db()` function)

### Step 4: Initialize Database (First Time)

After deployment, visit one of these URLs to initialize the database:

**Option 1: Use the init endpoint**
```
https://your-app.railway.app/api/init.php
```

**Option 2: Just use the app**
- The database will auto-initialize when you:
  - Register a new user
  - Login
  - Access admin panel

### Step 5: Verify Data Persistence

1. **Register a test user** on your deployed site
2. **Redeploy your application** (push a small change)
3. **Check that the user still exists** - data should persist!

## How Data Persistence Works

### ✅ What Persists (Stored in PostgreSQL):
- User accounts and passwords
- User profiles (name, email, etc.)
- System settings (Brevo API keys, etc.)
- Orders and order items
- All application data

### ❌ What Doesn't Persist (Resets on Redeploy):
- Session data (users will need to login again)
- Temporary files
- Logs

## Important Notes

### Database Backups
- Railway provides automatic backups for PostgreSQL
- Check the PostgreSQL service → "Backups" tab
- You can restore from backups if needed

### Database Size
- Free tier includes PostgreSQL with reasonable limits
- Monitor usage in Railway dashboard
- Upgrade if you need more storage

### Connection String Format
Railway's `DATABASE_URL` looks like:
```
postgresql://postgres:password@containers-us-west-xxx.railway.app:5432/railway
```

Your code automatically uses this - no manual configuration needed!

## Admin Login

To access the admin panel:
- Username: `admin`
- Password: `admin123`

This will automatically redirect you to `admin/dashboard.html`

## Troubleshooting

### Database Not Connecting?
1. Check that `DATABASE_URL` is set in your app's environment variables
2. Verify PostgreSQL service is running (green status)
3. Check Railway logs for connection errors

### Tables Not Created?
1. Visit `https://your-app.railway.app/api/init.php` to manually initialize
2. Or trigger initialization by registering a user

### Data Lost After Redeploy?
- This shouldn't happen! PostgreSQL is a separate service
- If it does, check that database is properly linked
- Verify `DATABASE_URL` is still set after redeploy

## Quick Checklist

- [ ] PostgreSQL service added to Railway project
- [ ] Database service is running (green status)
- [ ] `DATABASE_URL` is set in app service variables
- [ ] Database initialized (visit `/api/init.php` or register a user)
- [ ] Test: Register user → Redeploy → User still exists ✅
- [ ] Test: Admin login (admin/admin123) → Redirects to admin panel ✅

## Next Steps

After PostgreSQL is set up:
1. Test user registration on your live site
2. Test admin login (`admin` / `admin123`)
3. Configure Brevo email in admin panel
4. Your data will now persist across all redeployments! 🎉

