# AquaSphere - Water Delivery System

A complete water delivery management system built with PHP and Python backends, featuring user registration, authentication, order management, and admin dashboard.

## Features

### Public Features
- **Easy Ordering**: Quick product browsing and simple order placement
- **Delivery Scheduling**: Choose ASAP or set preferred delivery date & time
- **Real-Time Delivery Tracking**: Live status updates (Pending → Preparing → Out for Delivery → Delivered)
- **Secure Payment Options**: Cash on Delivery (COD) and online payment support
- **Product Details**: Prices, container types, descriptions
- **Customer Support**: Built-in support messaging and feedback system
- **Account Management**: User profile management and order history

### Admin Features
- **Dashboard**: Overview of users, orders, and system statistics
- **Settings Management**: Configure email service, system settings, and security options
- **User Management**: View and manage user accounts
- **Order Management**: Track and update order statuses

## Technology Stack

- **Frontend**: HTML5, CSS3, Bootstrap 5.3.2, JavaScript
- **Backend**: PHP 8.2, Python 3 (Flask)
- **Database**: PostgreSQL (production) / SQLite (local development)
- **Email Service**: Brevo (formerly Sendinblue) API
- **Hosting**: Railway (with PostgreSQL support)

## Project Structure

```
finalprojectvd/
├── api/                    # Backend API files
│   ├── admin/             # Admin API endpoints
│   │   ├── activity.php
│   │   ├── get_settings.php
│   │   ├── save_settings.php
│   │   ├── stats.php
│   │   └── test_email.php
│   ├── app.py            # Python Flask API
│   ├── check_email.php
│   ├── check_username.php
│   ├── database.php      # Database connection and functions
│   ├── email_service.php
│   ├── get_pending_email.php
│   ├── health.php
│   ├── init.php          # Database initialization
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   ├── resend_otp.php
│   └── verify_otp.php
├── admin/                 # Admin panel pages
│   ├── dashboard.html
│   └── settings.html
├── assets/               # Frontend assets
│   ├── css/
│   ├── js/
│   └── img/
├── dashboard.html        # User dashboard
├── index.html            # Landing page
├── login.html            # Login page
├── register.html         # Registration page
├── verify.html           # OTP verification page
├── Dockerfile            # Docker configuration
├── requirements.txt      # Python dependencies
├── RAILWAY_SETUP.md      # Railway deployment guide
└── README.md             # This file
```

## Database Schema

### Users Table
- id, username, password_hash, email, first_name, last_name, gender, date_of_birth
- is_admin, created_at, last_login

### Orders Table
- id, user_id, order_date, delivery_date, delivery_time, delivery_address
- total_amount, payment_method, status, created_at, updated_at

### Order Items Table
- id, order_id, product_name, product_price, quantity, subtotal

### System Settings Table
- id, setting_key, setting_value, created_at, updated_at, updated_by

### OTP Verification Table
- id, email, otp_code, username, password_hash, first_name, last_name
- gender, date_of_birth, created_at, expires_at, is_verified

## Installation

### Local Development

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd finalprojectvd
   ```

2. **Set up PHP environment**
   - Ensure PHP 8.2+ is installed
   - Install SQLite extension (usually included)

3. **Set up Python environment**
   ```bash
   pip install -r requirements.txt
   ```

4. **Initialize the database**
   - Visit `http://localhost/api/init.php` in your browser
   - Or the database will auto-initialize on first use

5. **Start the development server**
   ```bash
   # PHP server
   php -S localhost:8080
   
   # Python API (optional, in separate terminal)
   python api/app.py
   ```

### Railway Deployment

See [RAILWAY_SETUP.md](RAILWAY_SETUP.md) for detailed deployment instructions.

**Quick Steps:**
1. Push code to GitHub
2. Connect Railway to your repository
3. Add PostgreSQL database service
4. Deploy!

## Admin Access

**Default Admin Credentials:**
- Username: `admin`
- Password: `admin123`

**Note:** This will automatically redirect to `admin/dashboard.html` after login.

## API Endpoints

### Authentication
- `POST /api/register.php` - User registration
- `POST /api/login.php` - User login
- `POST /api/verify_otp.php` - Verify OTP code
- `POST /api/resend_otp.php` - Resend OTP code
- `GET /api/logout.php` - User logout

### User Management
- `GET /api/check_username.php?username={username}` - Check username availability
- `GET /api/check_email.php?email={email}` - Check email availability
- `GET /api/get_pending_email.php` - Get pending registration email

### Admin
- `GET /api/admin/stats.php` - Get dashboard statistics
- `GET /api/admin/activity.php` - Get recent activity
- `GET /api/admin/get_settings.php` - Get system settings
- `POST /api/admin/save_settings.php` - Save system settings
- `POST /api/admin/test_email.php` - Test email configuration

### Python API
- `GET /api/python/health` - Health check
- `GET /api/python/orders?user_id={id}` - Get user orders
- `POST /api/python/orders` - Create new order
- `PUT /api/python/orders/{id}/status` - Update order status

### System
- `GET /api/health.php` - System health check
- `GET /api/init.php` - Initialize database

## Database Support

The system automatically detects and uses:
- **PostgreSQL** when `DATABASE_URL` environment variable is set (Railway/production)
- **SQLite** for local development (creates `aquasphere.db` file)

No code changes needed - the system adapts automatically!

## Email Configuration

1. Sign up for a Brevo account at [brevo.com](https://brevo.com)
2. Get your API key from Brevo dashboard
3. Log in as admin (`admin` / `admin123`)
4. Go to Settings page
5. Configure:
   - Brevo API Key
   - Sender Email
   - Sender Name
   - Enable Email Notifications

## Security Features

- Password hashing using PHP's `password_hash()`
- OTP verification for email confirmation
- Session-based authentication
- SQL injection protection via prepared statements
- XSS protection via proper output escaping

## Development Notes

- The system uses a dual-database approach: SQLite for local dev, PostgreSQL for production
- Admin login bypasses database check for quick access
- OTP codes expire after 10 minutes
- Email service gracefully degrades if not configured (development mode)

## License

This project is for educational purposes.

## Support

For issues or questions, please check the Railway deployment guide or contact the development team.
