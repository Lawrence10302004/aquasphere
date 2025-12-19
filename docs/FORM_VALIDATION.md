# Form Validation - Client-Side and Server-Side

## Overview

The AquaSphere system implements a two-layer validation approach: **client-side validation** using JavaScript for immediate user feedback and **server-side validation** using PHP for security and data integrity. This defense-in-depth strategy ensures data quality, improves user experience, and prevents malicious or invalid data from reaching the database.

---

## Client-Side Validation (JavaScript)

### Purpose
Client-side validation provides immediate feedback to users as they fill out forms, improving user experience by catching errors before form submission and reducing server load.

### Implementation Features

#### 1. **Real-Time Field Validation**
- **Event Listeners**: Validation triggers on `input`, `blur`, and `change` events
- **Immediate Feedback**: Errors displayed instantly as users type or leave fields
- **Visual Indicators**: 
  - Red borders and error messages for invalid fields
  - Green borders for valid fields
  - Error messages displayed below each field

**Example** (`registration.html`, `login.html`):
```javascript
// Validate on input (real-time)
field.addEventListener('input', () => validateField(fieldName));

// Validate on blur (when field loses focus)
field.addEventListener('blur', () => validateField(fieldName));
```

#### 2. **Validation Rules**

**Username Validation**:
- Required field check
- Length validation (4-64 characters)
- Real-time availability checking (debounced API call)
- Character restrictions

**Email Validation**:
- Required field check
- Format validation using regex: `/^[^\s@]+@[^\s@]+\.[^\s@]+$/`
- Real-time uniqueness checking (debounced API call)
- Domain validation

**Password Validation**:
- Required field check
- Minimum length (8 characters)
- Complexity requirements:
  - At least one uppercase letter
  - At least one lowercase letter
  - At least one number
  - At least one special character
- Password confirmation matching

**Name Validation**:
- Required field check
- Pattern validation (letters, spaces, apostrophes, hyphens only)
- No numbers allowed
- Unicode support for international names

**Date of Birth Validation**:
- Required field check
- Date format validation (YYYY-MM-DD)
- Age calculation (must be 18+ years old)
- Future date prevention

**Gender Validation**:
- Required field check
- Valid option check (male/female only)

#### 3. **Form Submission Validation**
- **Pre-Submit Check**: `validateForm()` function validates all fields before submission
- **Prevents Submission**: Form submission blocked if validation fails
- **Error Summary**: Scrolls to first error field
- **Visual Feedback**: Highlights all invalid fields simultaneously

**Example**:
```javascript
form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!validateForm()) {
        // Scroll to first error
        const firstError = form.querySelector('.error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
    }
    
    // Proceed with form submission
});
```

#### 4. **Async Validation**
- **Username Availability**: Debounced API call to check if username exists
- **Email Uniqueness**: Debounced API call to check if email is taken
- **Debouncing**: Delays API calls by 150-300ms to reduce server requests
- **Loading States**: Shows loading indicators during async checks

#### 5. **Error Display System**
- **Inline Error Messages**: Red text displayed below each field
- **No Background Colors**: Clean design with red text only (no red backgrounds)
- **Dynamic Error Updates**: Errors shown/hidden based on validation state
- **Field-Specific Messages**: Custom error messages for each validation rule

**Error Message Format**:
```javascript
function showError(fieldName, message) {
    const errorElement = document.getElementById(`${fieldName}-error`);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
    // Add error class to input field
    const input = document.getElementById(fieldName);
    if (input) {
        input.classList.add('error');
    }
}
```

#### 6. **Input Sanitization Guards**
- **XSS Prevention**: Real-time scrubbing of dangerous patterns (`<script>`, `javascript:`, etc.)
- **SQL Injection Prevention**: Blocks SQL injection patterns (`' or 1=1`, `--`, etc.)
- **Pattern Removal**: Automatically removes malicious content as user types

---

## Server-Side Validation (PHP)

### Purpose
Server-side validation is the final security layer that ensures data integrity, prevents malicious input, and validates business rules that cannot be trusted from client-side alone.

### Implementation Features

#### 1. **Input Sanitization**
All inputs are sanitized before validation using security functions:

**String Sanitization**:
```php
$username = assert_safe_string($_POST['username'] ?? '', 'username', 64);
$first_name = assert_safe_string($_POST['first_name'] ?? '', 'first_name', 128);
```

**Email Sanitization**:
```php
$email = sanitize_email($_POST['email'] ?? '', 128);
```

**Functions Used**:
- `sanitize_string()`: Removes HTML tags, trims whitespace, limits length
- `sanitize_email()`: Validates and sanitizes email format
- `assert_safe_string()`: Sanitizes and checks for malicious patterns (XSS/SQL injection)
- `sanitize_int()`: Converts to integer
- `floatval()`: Converts to float

#### 2. **Comprehensive Validation Rules**

**Username Validation** (`api/register.php`, `api/login.php`):
```php
if (empty($username)) {
    $errors['username'] = 'Username is required.';
} elseif (strlen($username) < 4 || strlen($username) > 64) {
    $errors['username'] = 'Username must be between 4 and 64 characters long.';
} else {
    // Check if username exists
    $existing_user = get_user_by_username($username);
    if ($existing_user) {
        $errors['username'] = 'Username already exists.';
    }
}
```

**Email Validation**:
```php
if (empty($email)) {
    $errors['email'] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email.';
} else {
    // Check if email exists
    $existing_email = get_user_by_email($email);
    if ($existing_email) {
        $errors['email'] = 'Email already exists.';
    }
}
```

**Password Validation**:
```php
if (empty($password)) {
    $errors['password'] = 'Password is required.';
} else {
    $strong = preg_match('/[a-z]/', $password) &&
              preg_match('/[A-Z]/', $password) &&
              preg_match('/\d/', $password) &&
              preg_match('/[ !"#$%&\'()*+,\-\.\/:;<=>?@\[\]^_`{|}~]/', $password) &&
              strlen($password) >= 8;
    if (!$strong) {
        $errors['password'] = 'Password must be at least 8 characters and include upper, lower, number, and special character.';
    }
}
```

**Name Validation**:
```php
if (empty($first_name)) {
    $errors['first_name'] = 'First name is required.';
} elseif (preg_match('/\d/', $first_name) || !preg_match("/^[\p{L}\s'-]+$/u", $first_name)) {
    $errors['first_name'] = 'First name can only contain letters, spaces, apostrophes, and hyphens.';
}
```

**Date of Birth Validation**:
```php
if (empty($birthday)) {
    $errors['birthday'] = 'Birthday is required.';
} else {
    $dob = DateTime::createFromFormat('Y-m-d', $birthday);
    $errorsDate = DateTime::getLastErrors();
    if (!$dob || $errorsDate['warning_count'] > 0 || $errorsDate['error_count'] > 0) {
        $errors['birthday'] = 'Invalid birthday format.';
    } else {
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        if ($age < 18) {
            $errors['birthday'] = 'You must be at least 18 years old.';
        }
    }
}
```

**Password Confirmation**:
```php
if (empty($confirm_password)) {
    $errors['confirm_password'] = 'Password confirmation is required.';
} elseif ($password !== $confirm_password) {
    $errors['confirm_password'] = 'Passwords do not match.';
}
```

#### 3. **Database Uniqueness Checks**
- **Username Uniqueness**: Queries database to check if username already exists
- **Email Uniqueness**: Queries database to check if email is already registered
- **Prevents Duplicates**: Ensures no duplicate usernames or emails in system

#### 4. **Error Response Format**
All validation errors are returned as JSON with field-specific error messages:

```php
if (!empty($errors)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    ob_end_flush();
    exit;
}
```

**Response Structure**:
```json
{
    "success": false,
    "errors": {
        "username": "Username must be between 4 and 64 characters long.",
        "email": "Email already exists.",
        "password": "Password must be at least 8 characters..."
    }
}
```

#### 5. **Security Features**
- **Malicious Pattern Detection**: `has_malicious_payload()` detects XSS and SQL injection patterns
- **Input Length Limits**: Prevents buffer overflow attacks
- **Type Validation**: Ensures correct data types (string, integer, float, email)
- **SQL Injection Prevention**: Prepared statements used for all database queries
- **XSS Prevention**: HTML tags stripped from all string inputs

#### 6. **Request Method Validation**
```php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
```

#### 7. **Session Authentication Checks**
```php
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}
```

---

## Validation Flow

### Complete Validation Process

1. **User Fills Form** → Client-side validation triggers on input/blur
2. **Real-Time Feedback** → JavaScript shows/hides errors immediately
3. **Form Submission** → Client-side `validateForm()` checks all fields
4. **If Client Validation Passes** → Form data sent to server via AJAX
5. **Server Receives Data** → Input sanitization applied
6. **Server-Side Validation** → PHP validates all fields and business rules
7. **If Server Validation Fails** → JSON error response returned
8. **Client Displays Errors** → JavaScript shows server errors in form
9. **If All Validation Passes** → Data saved to database

---

## Key Differences: Client vs Server

| Aspect | Client-Side (JavaScript) | Server-Side (PHP) |
|--------|-------------------------|-------------------|
| **Purpose** | User experience, immediate feedback | Security, data integrity |
| **Speed** | Instant (no network delay) | Requires server round-trip |
| **Security** | Can be bypassed | Cannot be bypassed |
| **Validation Rules** | Basic format checks | Comprehensive business rules |
| **Database Checks** | Async API calls (debounced) | Direct database queries |
| **Error Display** | Inline, real-time | Returned as JSON, then displayed |
| **Trust Level** | Not trusted (can be manipulated) | Trusted (final authority) |

---

## Security Best Practices

### Defense in Depth
- **Multiple Layers**: Both client and server validation required
- **Never Trust Client**: Server always re-validates all input
- **Sanitize First**: Input sanitization before validation
- **Validate Business Rules**: Server validates business logic (age, uniqueness, etc.)

### Input Handling
- **Whitelist Approach**: Only allow valid characters/patterns
- **Length Limits**: Prevent buffer overflow attacks
- **Type Checking**: Ensure correct data types
- **Malicious Pattern Detection**: Block XSS and SQL injection attempts

### Error Handling
- **No Information Leakage**: Error messages don't reveal system internals
- **Consistent Format**: All errors returned in same JSON structure
- **Field-Specific**: Errors tied to specific form fields
- **User-Friendly**: Clear, actionable error messages

---

## Files Involved

### Client-Side Validation:
- `registration.html` - Registration form validation
- `login.html` - Login form validation
- `profile.html` - Profile update validation
- `cart.html` - Address form validation
- `js/user_state.js` - State management helpers

### Server-Side Validation:
- `api/register.php` - Registration validation
- `api/login.php` - Login validation
- `api/update_profile.php` - Profile update validation
- `api/sanitize.php` - Sanitization functions
- `api/user_state_save.php` - Address validation

---

## Summary

The AquaSphere system implements **comprehensive two-layer validation**:

**Client-Side (JavaScript)**:
- Real-time field validation with immediate feedback
- Visual error indicators (red borders, error messages)
- Form submission prevention if validation fails
- Async validation for username/email availability
- Input sanitization guards for XSS/SQL injection prevention

**Server-Side (PHP)**:
- Input sanitization before validation
- Comprehensive business rule validation
- Database uniqueness checks
- Malicious pattern detection
- Security-focused validation that cannot be bypassed

Both layers work together to ensure data quality, improve user experience, and maintain system security. Client-side validation provides immediate feedback, while server-side validation ensures data integrity and security regardless of client-side manipulation.

