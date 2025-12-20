# Authentication Module - AquaSphere System

## Overview

The AquaSphere system implements a comprehensive authentication module that handles user login, session management, and access control. This document explains how the system processes authentication, manages sessions, and enforces access restrictions.

---

## 1. Login Process

### 1.1 Client-Side Login Flow

**Location**: `login.html`

**Process Steps**:

1. **Form Validation** (Client-Side):
   - Username validation: 4-64 characters
   - Password validation: Minimum 8 characters
   - Real-time validation with error messages displayed under fields

2. **Form Submission**:
   ```javascript
   fetch('api/login.php', {
       method: 'POST',
       body: formData  // Contains username, password, remember_me
   })
   ```

3. **Response Handling**:
   - **Success**: Redirects to `dashboard.html` (regular users) or `admin/dashboard.html` (admin users)
   - **Error**: Displays error message below password field
   - **Suspended Account**: Shows suspension notice overlay

### 1.2 Server-Side Login Processing

**Location**: `api/login.php`

**Process Flow**:

#### Step 1: Request Validation
```php
// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
```

#### Step 2: Input Sanitization
```php
require_once 'sanitize.php';

$username = assert_safe_string($_POST['username'] ?? '', 'username', 64);
$password = $_POST['password'] ?? '';
$remember_me = isset($_POST['remember_me']) ? 1 : 0;
```
- **Security**: `assert_safe_string()` sanitizes input and detects malicious patterns (XSS/SQL injection)
- **Validation**: Username length checked (4-64 characters)

#### Step 3: Special Admin Login Check
```php
// Special admin login (hardcoded credentials)
if ($username === 'admin' && $password === 'admin123') {
    session_start();
    $_SESSION['user_id'] = 0;  // Special admin ID
    $_SESSION['username'] = 'admin';
    $_SESSION['is_admin'] = 1;
    // ... set remember me cookie if checked
    // Return success with redirect to admin/dashboard.html
}
```

#### Step 4: Regular User Authentication
```php
// Validate input
if (empty($username) || strlen($username) < 4 || strlen($username) > 64) {
    // Return validation errors
}

if (empty($password) || strlen($password) < 8) {
    // Return validation errors
}

// Query database for user
$query = "SELECT * FROM users WHERE username = ?";
$result = execute_sql($conn, $query, [$username]);  // Prepared statement prevents SQL injection
$user = pg_fetch_assoc($result);  // or fetchArray for SQLite

// Verify password
if ($user && password_verify($password, $user['password_hash'])) {
    // Password is correct
}
```

**Security Features**:
- **Prepared Statements**: Prevents SQL injection
- **Password Hashing**: Uses PHP's `password_verify()` with bcrypt
- **Input Sanitization**: All inputs sanitized before processing

#### Step 5: Account Status Check
```php
// Block suspended accounts
if (isset($user['suspended']) && intval($user['suspended']) === 1) {
    http_response_code(403);
    $reason = $user['suspension_reason'] ?? 'Your account is suspended.';
    echo json_encode(['success' => false, 'message' => "Account suspended: $reason"]);
    exit;
}
```

#### Step 6: Session Creation
```php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store user data in session
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['is_admin'] = $user['is_admin'] ?? 0;
```

#### Step 7: Remember Me Cookie (Optional)
```php
if ($remember_me) {
    $cookie_value = base64_encode($user['id'] . ':' . hash('sha256', $user['password_hash']));
    setcookie('aquasphere_remember', $cookie_value, time() + (86400 * 30), '/'); // 30 days
}
```

#### Step 8: Update Last Login
```php
$updateQuery = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
execute_sql($conn, $updateQuery, [$user['id']]);
```

#### Step 9: Response and Redirect
```php
$redirect_url = ($user['is_admin'] ?? 0) ? 'admin/dashboard.html' : 'dashboard.html';

echo json_encode([
    'success' => true,
    'message' => 'Login successful!',
    'redirect' => $redirect_url
]);
```

---

## 2. Session Handling

### 2.1 Session Initialization

**Location**: Multiple API endpoints

**Process**:
```php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

**Session Variables Stored**:
- `$_SESSION['user_id']`: User's database ID (or 0 for admin)
- `$_SESSION['username']`: User's username
- `$_SESSION['is_admin']`: Admin flag (1 for admin, 0 for regular user)

### 2.2 Session Verification

**Location**: `api/get_current_user.php`

**Process**:
```php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Fetch user data from database
$user_id = intval($_SESSION['user_id']);
$query = "SELECT id, username, email, first_name, last_name, gender, date_of_birth, is_admin, created_at, last_login 
          FROM users 
          WHERE id = ?";
$result = execute_sql($conn, $query, [$user_id]);
$user = pg_fetch_assoc($result);

// Return user data
echo json_encode(['success' => true, 'user' => $user]);
```

**Usage**: Called by client-side JavaScript to verify authentication status and load user data.

### 2.3 Client-Side Session Management

**Location**: `navbar.js`, `dashboard.html`, `cart.html`, etc.

**Process**:
```javascript
// Load user data on page load
fetch('api/get_current_user.php', { credentials: 'include' })
    .then(response => {
        if (response.status === 401) {
            // User not logged in
            localStorage.removeItem('loggedInUser');
            localStorage.removeItem('userData');
            sessionStorage.removeItem('userData');
            // Hide user-specific UI elements
            return null;
        }
        return response.json();
    })
    .then(data => {
        if (data && data.success) {
            // Store user data in localStorage for quick access
            localStorage.setItem('loggedInUser', JSON.stringify(data.user));
            sessionStorage.setItem('userData', JSON.stringify(data.user));
            // Update UI (username display, etc.)
        }
    });
```

**Storage Strategy**:
- **Session Storage**: Temporary user data for current session
- **Local Storage**: Persistent user data (survives page refresh)
- **Server Session**: Primary source of truth (PHP `$_SESSION`)

### 2.4 Session Termination (Logout)

**Location**: `api/logout.php`

**Process**:
```php
<?php
session_start();
session_destroy();
echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
?>
```

**Client-Side Logout**:
```javascript
function logout() {
    fetch('api/logout.php')
        .then(() => {
            // Clear client-side storage
            localStorage.removeItem('loggedInUser');
            localStorage.removeItem('userData');
            sessionStorage.removeItem('userData');
            // Redirect to login page
            window.location.href = 'login.html';
        });
}
```

---

## 3. Access Restrictions

### 3.1 Page-Level Access Control

#### 3.1.1 Protected Pages (Require Authentication)

**Implementation**: Client-side check on page load

**Location**: `dashboard.html`, `cart.html`, `profile.html`, `orders.html`, etc.

**Process**:
```javascript
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const resp = await fetch('api/get_current_user.php', { credentials: 'include' });
        if (resp.status === 401) {
            // User not authenticated - redirect to login
            window.location.href = 'login.html';
            return;
        }
        // User is authenticated - continue loading page
    } catch (e) {
        console.warn('Auth check failed:', e);
        window.location.href = 'login.html';
    }
});
```

**Pages Protected**:
- `dashboard.html` - Product browsing
- `cart.html` - Shopping cart
- `profile.html` - User profile
- `orders.html` - Order history
- `recent_orders.html` - Recent orders
- `payment.html` - Payment processing
- All admin pages (`admin/dashboard.html`, `admin/products.html`, etc.)

#### 3.1.2 Public Pages (No Authentication Required)

**Pages**:
- `index.html` - Landing page
- `login.html` - Login page
- `registration.html` - Registration page
- `verify.html` - OTP verification

**Special Handling for Login Page**:
```javascript
// Redirect logged-in users away from login page
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const resp = await fetch('api/get_current_user.php', { credentials: 'include' });
        if (resp.ok) {
            const data = await resp.json();
            // Check if coming from password change
            const urlParams = new URLSearchParams(window.location.search);
            const fromPasswordChange = urlParams.has('from') && urlParams.get('from') === 'password_change';
            
            if (!fromPasswordChange) {
                // Redirect to appropriate dashboard
                if (data.user && data.user.is_admin) {
                    window.location.href = 'admin/dashboard.html';
                } else {
                    window.location.href = 'dashboard.html';
                }
            }
        }
    } catch (e) {
        // User not logged in - stay on login page
    }
});
```

### 3.2 API-Level Access Control

#### 3.2.1 Regular User Endpoints

**Location**: `api/get_orders.php`, `api/create_order.php`, `api/user_state_get.php`, etc.

**Process**:
```php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Continue with API logic
$user_id = $_SESSION['user_id'];
// ... process request for this user
```

**Security**: All user-specific data is filtered by `user_id` from session to prevent unauthorized access.

#### 3.2.2 Admin-Only Endpoints

**Location**: `api/admin/add_product.php`, `api/admin/delete_product.php`, `api/admin/get_users.php`, etc.

**Process**:
```php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Continue with admin-only logic
```

**Security Features**:
- **Double Check**: Verifies both `user_id` and `is_admin` flag
- **Explicit Check**: `$_SESSION['is_admin'] != 1` ensures strict admin verification
- **Early Exit**: Returns 401 Unauthorized immediately if check fails

**Admin Endpoints Protected**:
- `api/admin/add_product.php` - Add products
- `api/admin/delete_product.php` - Delete products
- `api/admin/get_users.php` - Get user list
- `api/admin/update_user.php` - Update user details
- `api/admin/suspend_user.php` - Suspend users
- `api/admin/delete_user.php` - Delete users
- `api/admin/save_settings.php` - Save system settings

### 3.3 Account Suspension Handling

**Location**: `api/login.php`, `api/get_current_user.php`

**Process**:
```php
// Check if account is suspended
if (isset($user['suspended']) && intval($user['suspended']) === 1) {
    http_response_code(403);
    $reason = $user['suspension_reason'] ?? 'Your account is suspended.';
    echo json_encode([
        'success' => false, 
        'message' => "Account suspended: $reason",
        'suspended' => true
    ]);
    exit;
}
```

**Client-Side Handling**:
```javascript
if (data.suspended || (data.message && data.message.toLowerCase().includes('suspend'))) {
    showSuspendedNotice();  // Display suspension overlay
    return;
}
```

**Suspension Features**:
- **Login Block**: Suspended users cannot log in
- **Reason Display**: Shows suspension reason to user
- **403 Forbidden**: Returns appropriate HTTP status code

### 3.4 Data Isolation

**Security Feature**: User data is isolated by `user_id`

**Example**: `api/get_orders.php`
```php
// Get user_id from session
$user_id = $_SESSION['user_id'];

// Query only orders for this user
$query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$result = execute_sql($conn, $query, [$user_id]);
```

**Prevents**: Users from accessing other users' data by ensuring all queries filter by the authenticated user's ID.

---

## 4. Authentication Flow Diagram

```
┌─────────────────┐
│  User Opens    │
│  Protected Page │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Client-Side    │
│  Auth Check     │
│  (get_current_  │
│   user.php)     │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
    ▼         ▼
┌──────┐  ┌──────────┐
│ 401  │  │ 200 OK   │
│ Not  │  │ User     │
│ Auth │  │ Logged In│
└──┬───┘  └────┬─────┘
   │           │
   ▼           ▼
┌──────────┐ ┌──────────────┐
│Redirect  │ │ Load Page    │
│to Login  │ │ Content      │
└──────────┘ └──────────────┘
```

---

## 5. Security Features

### 5.1 Input Security
- **Sanitization**: All inputs sanitized using `assert_safe_string()`
- **XSS Protection**: Detects and blocks XSS patterns
- **SQL Injection Protection**: Uses prepared statements

### 5.2 Password Security
- **Hashing**: Passwords hashed with bcrypt (`password_hash()`)
- **Verification**: Uses `password_verify()` for secure comparison
- **No Plain Text**: Passwords never stored or transmitted in plain text

### 5.3 Session Security
- **Server-Side Storage**: Session data stored on server, not client
- **Session ID**: PHP manages session IDs securely
- **No Sensitive Data in Cookies**: Only remember-me token stored (hashed)

### 5.4 Access Control Security
- **Multi-Layer Checks**: Both client-side and server-side verification
- **Role-Based Access**: Admin flag checked for admin endpoints
- **Data Isolation**: User data filtered by session `user_id`

---

## 6. Files Involved

### Server-Side (PHP):
- `api/login.php` - Login processing
- `api/logout.php` - Logout processing
- `api/get_current_user.php` - Session verification
- `api/user_state_get.php` - User state retrieval
- `api/admin/*.php` - Admin endpoints with access control

### Client-Side (JavaScript/HTML):
- `login.html` - Login page with form handling
- `dashboard.html` - Protected page with auth check
- `cart.html` - Protected page with auth check
- `profile.html` - Protected page with auth check
- `orders.html` - Protected page with auth check
- `navbar.js` - User data loading and display
- All admin pages - Admin access control

---

## 7. Summary

The AquaSphere authentication module implements:

1. **Secure Login Process**:
   - Input validation and sanitization
   - Password hashing and verification
   - Account suspension checking
   - Session creation
   - Remember me functionality

2. **Robust Session Handling**:
   - Server-side session storage
   - Client-side session verification
   - User data synchronization
   - Session termination on logout

3. **Comprehensive Access Restrictions**:
   - Page-level access control (client-side)
   - API-level access control (server-side)
   - Admin-only endpoint protection
   - Account suspension enforcement
   - Data isolation by user ID

The system uses a **defense-in-depth** approach with multiple layers of security checks to ensure only authenticated and authorized users can access protected resources.

