# Security Protections - XSS and SQL Injection

## Overview

This document lists all security measures implemented in the AquaSphere system to protect against **Cross-Site Scripting (XSS)** and **SQL Injection** attacks.

---

## SQL Injection Protection

### 1. Prepared Statements with Parameterized Queries

**Location**: `api/database.php`

**Implementation**:
- **PostgreSQL**: Uses `pg_query_params()` function
  ```php
  function execute_sql($conn, $query, $params = null) {
      if ($params) {
          // Replace ? with $1, $2, etc. for PostgreSQL
          $pg_query = preg_replace_callback('/\?/', function() use (&$param_count) {
              return '$' . $param_count++;
          }, $query);
          $result = pg_query_params($conn, $pg_query, $params);
      }
  }
  ```

- **SQLite**: Uses `prepare()` and `bindValue()` methods
  ```php
  function execute_sql($conn, $query, $params = null) {
      $stmt = $conn->prepare($query);
      if ($params) {
          foreach ($params as $index => $param) {
              $stmt->bindValue($index + 1, $param);
          }
      }
      return $stmt->execute();
  }
  ```

**Usage Examples**:
- `api/login.php`: `execute_sql($conn, "SELECT * FROM users WHERE username = ?", [$username])`
- `api/admin/delete_product.php`: `execute_sql($conn, $query, [$product_id])`
- `api/admin/add_product.php`: `execute_sql($conn, $query, [$label, $description, $price, $image_url, $category, $unit])`

**Protection Level**: ✅ **Strong** - All user input is separated from SQL query structure, preventing SQL injection attacks.

---

## XSS Protection

### Server-Side Protections

#### 1. Input Sanitization Functions

**Location**: `api/sanitize.php`

**Functions Implemented**:

##### a. `sanitize_string($value, $max_len = 255)`
- **Purpose**: Removes HTML tags and limits string length
- **Implementation**:
  ```php
  function sanitize_string($value, $max_len = 255) {
      if ($value === null) return null;
      $v = trim((string)$value);
      $v = strip_tags($v);  // Removes HTML tags
      if ($max_len > 0) {
          $v = mb_substr($v, 0, $max_len);
      }
      return $v;
  }
  ```
- **Used in**: `api/admin/add_product.php`, `api/predict_delivery.php`, `api/create_order.php`

##### b. `sanitize_email($value, $max_len = 255)`
- **Purpose**: Sanitizes email addresses
- **Implementation**:
  ```php
  function sanitize_email($value, $max_len = 255) {
      $v = sanitize_string($value, $max_len);
      return filter_var($v, FILTER_SANITIZE_EMAIL);
  }
  ```

##### c. `sanitize_int($value)`
- **Purpose**: Converts input to integer
- **Implementation**:
  ```php
  function sanitize_int($value) {
      return intval($value);
  }
  ```
- **Used in**: `api/get_orders.php`, `api/create_order.php`

##### d. `sanitize_float($value)`
- **Purpose**: Converts input to float
- **Implementation**:
  ```php
  function sanitize_float($value) {
      return floatval($value);
  }
  ```

##### e. `sanitize_array_recursive($data)`
- **Purpose**: Recursively sanitizes arrays and objects
- **Implementation**:
  ```php
  function sanitize_array_recursive($data) {
      if (is_array($data)) {
          $clean = [];
          foreach ($data as $k => $v) {
              $clean[$k] = sanitize_array_recursive($v);
          }
          return $clean;
      }
      // ... handles objects and primitives
  }
  ```
- **Used in**: `api/predict_delivery.php`, `api/create_order.php`

#### 2. Malicious Payload Detection

**Location**: `api/sanitize.php`

**Function**: `has_malicious_payload($value)`

**XSS Patterns Detected**:
- `<script>`, `</script>`
- `<img>`, `<svg>`, `<iframe>`, `<object>`, `<embed>`
- `javascript:` protocol
- Event handlers: `onerror=`, `onload=`, `onclick=`, `onmouseover=`, `onfocus=`

**SQL Injection Patterns Detected**:
- `' or 1=1`, `" or 1=1`
- `' or '1'='1`, `" or "1"="1`
- ` or 1=1`, ` and 1=1`
- SQL comments: `--`, `/*`, `*/`, `;--`

**Implementation**:
```php
function has_malicious_payload($value) {
    // XSS patterns
    $xss_needles = [
        '<script', '</script', '<img', '<svg', '<iframe', '<object', '<embed',
        'javascript:', 'onerror=', 'onload=', 'onclick=', 'onmouseover=', 'onfocus='
    ];
    // SQL injection patterns
    $sql_needles = [
        "' or 1=1", '" or 1=1', "' or '1'='1", '" or "1"="1',
        ' or 1=1', ' and 1=1', '--', '/*', '*/', ';--'
    ];
    // ... detection logic
}
```

#### 3. Assert Safe String Function

**Location**: `api/sanitize.php`

**Function**: `assert_safe_string($value, $field = 'input', $max_len = 255)`

**Purpose**: Combines sanitization and malicious payload detection, exits with 400 error if malicious content detected

**Implementation**:
```php
function assert_safe_string($value, $field = 'input', $max_len = 255) {
    $clean = sanitize_string($value, $max_len);
    if (has_malicious_payload($clean)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => "Invalid {$field}"]);
        exit;
    }
    return $clean;
}
```

**Used in**:
- `api/login.php`: `$username = assert_safe_string($_POST['username'] ?? '', 'username', 64)`
- `api/register.php`: Multiple fields (username, first_name, last_name, gender, birthday)

#### 4. Output Escaping in Email Templates

**Location**: `api/email_service.php`

**Implementation**:
```php
htmlspecialchars($reason)  // Escapes HTML special characters in email content
```

---

### Client-Side Protections

#### 1. Real-Time Input Guards

**Location**: 
- `assets/js/script.js` (global)
- `cart.html` (page-specific)
- `profile.html` (page-specific)
- `registration.html` (page-specific)

**Purpose**: Blocks dangerous patterns as user types or pastes

**Patterns Blocked**:
- XSS: `<`, `>`, `javascript:`, `onerror=`, `onload=`, `onclick=`, `onmouseover=`
- SQL Injection: `--`, `/*`, `*/`, `' or 1=1`, `" or 1=1`

**Implementation**:
```javascript
const BAD_PATTERNS = [
    /<|>/g,
    /javascript:/gi,
    /onerror\s*=/gi,
    /onload\s*=/gi,
    /onclick\s*=/gi,
    /onmouseover\s*=/gi,
    /--/g,
    /\/\*/g,
    /\*\//g,
    /'\s*or\s*1=1/gi,
    /"\s*or\s*1=1/gi
];

function scrub(value) {
    let v = value;
    BAD_PATTERNS.forEach(re => {
        v = v.replace(re, '');
    });
    return v;
}

// Attached to all text inputs, email inputs, search inputs, password inputs, and textareas
```

**Event Handlers**:
- `input` event: Scrubs as user types
- `paste` event: Scrubs pasted content

#### 2. HTML Output Escaping

**Location**: 
- `dashboard.html`
- `cart.html`
- `admin/products.html`

**Function**: `escapeHtml(text)`

**Two Implementations**:

**Method 1** (DOM-based - `dashboard.html`, `admin/products.html`):
```javascript
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;  // Automatically escapes HTML
    return div.innerHTML;
}
```

**Method 2** (Regex-based - `cart.html`):
```javascript
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
```

**Usage Examples**:
- `dashboard.html`: 
  ```javascript
  <h3 class="product-title">${escapeHtml(product.label)}</h3>
  <p class="product-description">${escapeHtml(product.description)}</p>
  ```
- `admin/products.html`:
  ```javascript
  <td><strong>${escapeHtml(product.label)}</strong></td>
  <td class="description-cell">${escapeHtml(product.description || 'No description')}</td>
  ```
- `cart.html`:
  ```javascript
  <div class="item-name">${escapeHtml(item.name)}</div>
  ```

#### 3. JSON Response Escaping

**Location**: All API endpoints

**Implementation**: PHP's `json_encode()` automatically escapes special characters in JSON responses

**Examples**:
- `api/login.php`: `echo json_encode(['success' => true, 'message' => 'Login successful!'])`
- `api/register.php`: `echo json_encode(['success' => false, 'errors' => $errors])`
- All API responses use `json_encode()` which prevents XSS in JSON data

---

## Security Layers Summary

### SQL Injection Protection Layers:
1. ✅ **Prepared Statements** (PostgreSQL: `pg_query_params`, SQLite: `prepare` + `bindValue`)
2. ✅ **Parameterized Queries** (All user input passed as parameters, never concatenated)

### XSS Protection Layers:
1. ✅ **Server-Side Input Sanitization** (`sanitize_string()`, `strip_tags()`)
2. ✅ **Server-Side Malicious Pattern Detection** (`has_malicious_payload()`)
3. ✅ **Server-Side Assertion** (`assert_safe_string()` - exits on malicious input)
4. ✅ **Client-Side Real-Time Input Guards** (Blocks dangerous patterns as user types)
5. ✅ **Client-Side Output Escaping** (`escapeHtml()` function)
6. ✅ **JSON Response Escaping** (`json_encode()` automatic escaping)
7. ✅ **Email Output Escaping** (`htmlspecialchars()` in email templates)

---

## Files Using Security Functions

### Server-Side Files:
- `api/sanitize.php` - All sanitization functions
- `api/database.php` - Prepared statement execution
- `api/login.php` - Uses `assert_safe_string()`
- `api/register.php` - Uses `assert_safe_string()` and `sanitize_email()`
- `api/admin/add_product.php` - Uses `sanitize_string()`
- `api/get_orders.php` - Uses `sanitize_int()`
- `api/predict_delivery.php` - Uses `sanitize_string()` and `sanitize_array_recursive()`
- `api/create_order.php` - Uses `sanitize_int()` and `sanitize_array_recursive()`
- `api/email_service.php` - Uses `htmlspecialchars()`

### Client-Side Files:
- `assets/js/script.js` - Global input guards
- `dashboard.html` - `escapeHtml()` function and usage
- `cart.html` - Input guards and `escapeHtml()` function
- `admin/products.html` - `escapeHtml()` function and usage
- `profile.html` - Input guards
- `registration.html` - Input guards

---

## Best Practices Followed

1. ✅ **Never concatenate user input directly into SQL queries**
2. ✅ **Always use prepared statements for database queries**
3. ✅ **Sanitize all user input on server-side**
4. ✅ **Escape all output before displaying in HTML**
5. ✅ **Use JSON encoding for API responses**
6. ✅ **Implement defense-in-depth (multiple layers of protection)**
7. ✅ **Validate and sanitize on both client and server side**

---

## Conclusion

The AquaSphere system implements **comprehensive, multi-layered security protections** against both SQL Injection and XSS attacks:

- **SQL Injection**: Protected through prepared statements and parameterized queries
- **XSS**: Protected through 7 layers of defense (server-side sanitization, pattern detection, client-side guards, output escaping, JSON escaping, and email escaping)

All user input is sanitized, validated, and escaped before being stored in the database or displayed to users.

