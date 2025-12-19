
## Architecture Components

### 1. **Database Schema**
The OTP verification system uses a dedicated database table to store verification data:

**Table: `otp_verification`**
- `id` - Primary key
- `email` - User's email address (NOT NULL)
- `otp_code` - 6-digit verification code (NOT NULL)
- `username` - Username for registration
- `password_hash` - Hashed password (stored temporarily)
- `first_name`, `last_name`, `gender`, `date_of_birth` - User profile data
- `created_at` - Timestamp when OTP was created
- `expires_at` - Timestamp when OTP expires (10 minutes from creation)
- `is_verified` - Boolean flag (0 = not verified, 1 = verified)

**Key Features:**
- Only one active OTP per email (old OTPs are deleted when new ones are generated)
- OTPs expire after 10 minutes
- Once verified, OTP is marked as `is_verified = 1` and cannot be reused

---

### 2. **Brevo API Integration**

#### Configuration
Brevo settings are stored in the `system_settings` table:
- `brevo_api_key` - Your Brevo API key
- `brevo_sender_email` - Verified sender email address
- `brevo_sender_name` - Display name for emails (default: "AquaSphere")
- `enable_email_notifications` - Toggle to enable/disable email service

#### BrevoEmailService Class (`api/email_service.php`)
A PHP class that handles all email communication with Brevo:

**API Endpoint:** `https://api.brevo.com/v3/smtp/email`

**Key Methods:**
- `send_email($to_email, $subject, $html_content, $text_content, $to_name)` - Sends email via Brevo API
- Uses cURL to make POST requests with JSON payload
- Headers include:
  - `accept: application/json`
  - `api-key: {your_brevo_api_key}`
  - `content-type: application/json`

**Request Payload Structure:**
```json
{
  "sender": {
    "email": "sender@example.com",
    "name": "AquaSphere"
  },
  "to": [{
    "email": "recipient@example.com",
    "name": "Username"
  }],
  "subject": "Email Subject",
  "htmlContent": "<html>...</html>"
}
```

**Response Handling:**
- HTTP 201 = Success (email sent)
- Other status codes = Error (returns error message from Brevo)

---

### 3. **OTP Generation Flow**

#### Registration Process (`api/register.php`)

1. **User submits registration form** with:
   - Username, email, password, first name, last name, gender, date of birth

2. **Generate 6-digit OTP:**
   ```php
   $otp_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
   ```

3. **Set expiration time:**
   ```php
   $expires_at = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 minutes
   ```

4. **Hash password:**
   ```php
   $password_hash = password_hash($password, PASSWORD_DEFAULT);
   ```

5. **Store OTP in database:**
   - Deletes any existing OTP for the email
   - Inserts new OTP with all user data
   - Function: `store_otp_verification()`

6. **Send OTP email via Brevo:**
   - Function: `send_otp_email_brevo($email, $otp_code, $username)`
   - Creates HTML email template with OTP code
   - Sends via Brevo API
   - Returns success/failure status

7. **Store email in session:**
   - `$_SESSION['pending_email']` - For verification page
   - `$_SESSION['pending_username']` - For display purposes

8. **Response to client:**
   - Success: Redirects to `verify.html`
   - Development mode: If Brevo not configured, OTP is logged to server logs

---

### 4. **OTP Email Template**

The email sent to users includes:

**Subject:** "AquaSphere - Email Verification Code"

**HTML Content:**
- Header with AquaSphere branding (blue gradient)
- Personalized greeting with username
- Large, prominent OTP code display (36px font, blue color, letter-spacing)
- Instructions to enter code on verification page
- Warning box with:
  - Code expires in 10 minutes
  - Do not share code
  - If not requested, ignore email
- Footer with automated message notice

**Text Content:** Plain text version for email clients that don't support HTML

---

### 5. **OTP Verification Flow**

#### Verification Process (`api/verify_otp.php`)

1. **User submits OTP code** on verification page

2. **Validate input:**
   - Email and OTP code are required
   - Sanitized using `sanitize_email()` and `assert_safe_string()`

3. **Verify OTP:**
   - Function: `verify_otp_code($email, $otp_code)`
   - Queries database for matching OTP:
     ```sql
     SELECT * FROM otp_verification 
     WHERE email = ? 
       AND otp_code = ? 
       AND is_verified = 0 
       AND expires_at > CURRENT_TIMESTAMP
     ORDER BY created_at DESC
     LIMIT 1
     ```

4. **If OTP is valid:**
   - Mark OTP as verified (`is_verified = 1`)
   - Retrieve stored user data (username, password hash, profile info)
   - Create user account in `users` table
   - Clean up session variables
   - Clean up expired OTP records
   - Return success response

5. **If OTP is invalid:**
   - Returns error: "Invalid or expired verification code"
   - Logs failure for debugging

---

### 6. **Resend OTP Functionality**

#### Resend Process (`api/resend_otp.php`)

1. **User requests resend** (e.g., didn't receive email, code expired)

2. **Validate session:**
   - Checks if `$_SESSION['pending_email']` matches request email

3. **Check for pending OTP:**
   - Function: `get_pending_otp($email)`
   - Verifies OTP exists and hasn't expired

4. **Generate new OTP:**
   - New 6-digit code
   - New expiration time (10 minutes from now)

5. **Update database:**
   - Function: `update_otp_code($email, $otp_code, $expires_at)`
   - Updates existing OTP record (doesn't create new one)

6. **Send new OTP email:**
   - Same email template as initial registration
   - Uses Brevo API

7. **Response:**
   - Success: "Verification code has been resent to your email"
   - Development mode: Returns OTP in response if Brevo not configured

---

## Key Functions Reference

### Database Functions (`api/database.php`)

- `store_otp_verification($email, $otp_code, $username, $password_hash, $first_name, $last_name, $gender, $date_of_birth, $expires_at)`
  - Stores OTP and user data temporarily
  - Deletes any existing OTP for the email first

- `verify_otp_code($email, $otp_code)`
  - Verifies OTP code
  - Returns user data if valid, null if invalid/expired
  - Marks OTP as verified upon success

- `get_pending_otp($email)`
  - Retrieves active (unexpired, unverified) OTP for email

- `update_otp_code($email, $otp_code, $expires_at)`
  - Updates OTP code for resend functionality

### Email Functions (`api/email_service.php`)

- `get_brevo_service()`
  - Retrieves Brevo configuration from database
  - Returns `BrevoEmailService` instance if configured, null otherwise

- `send_otp_email_brevo($email, $otp_code, $username)`
  - Sends OTP verification email via Brevo
  - Returns true on success, false on failure
  - Logs OTP to error log if Brevo not configured (development mode)

---

## Security Features

1. **OTP Expiration:** Codes expire after 10 minutes
2. **One-Time Use:** Once verified, OTP cannot be reused (`is_verified = 1`)
3. **Email Validation:** Only one active OTP per email address
4. **Password Hashing:** Passwords are hashed before storage (never stored in plain text)
5. **Input Sanitization:** All inputs are sanitized before database operations
6. **Session Management:** Email stored in session for verification page access

---

## Development vs Production Mode

**Development Mode (Brevo not configured):**
- OTP codes are logged to server error logs
- Registration still proceeds (for testing)
- OTP may be returned in API response for testing
- Email sending is skipped

**Production Mode (Brevo configured):**
- OTP codes are sent via email only
- Registration requires successful email delivery
- OTP never returned in API response
- Full email service functionality

---

## Setup Requirements

1. **Brevo Account:**
   - Sign up at https://www.brevo.com
   - Get API key from account settings
   - Verify sender email address

2. **Database Configuration:**
   - Store Brevo settings in `system_settings` table:
     - `brevo_api_key`
     - `brevo_sender_email`
     - `brevo_sender_name` (optional)
     - `enable_email_notifications` = '1'

3. **PHP Requirements:**
   - cURL extension enabled
   - JSON extension enabled
   - Database connection (PostgreSQL or SQLite)

---

## API Endpoints

- **POST `/api/register.php`** - Register user and send OTP
- **POST `/api/verify_otp.php`** - Verify OTP and create account
- **POST `/api/resend_otp.php`** - Resend OTP code

All endpoints return JSON responses with `success` boolean and `message` string.

---

## Error Handling

- **Email Service Unavailable:** Falls back to development mode (logs OTP)
- **Invalid OTP:** Returns error message, logs for debugging
- **Expired OTP:** Treated as invalid, user must request resend
- **Database Errors:** Logged to error log, returns generic error to client

---

## Testing Checklist

1. ✅ Registration generates OTP and stores in database
2. ✅ OTP email is sent via Brevo API
3. ✅ OTP verification creates user account
4. ✅ Expired OTPs are rejected
5. ✅ Used OTPs cannot be reused
6. ✅ Resend OTP generates new code
7. ✅ Only one active OTP per email
8. ✅ Development mode works without Brevo configuration

---

## Notes

- OTP codes are 6 digits (000000-999999)
- OTP expiration: 10 minutes
- Email template is HTML with plain text fallback
- All user data is stored temporarily in `otp_verification` table until verification
- After successful verification, user data is moved to `users` table and OTP record is marked as verified

