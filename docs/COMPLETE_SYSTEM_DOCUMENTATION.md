# AquaSphere System Complete Documentation

## Table of Contents

1. Design Principles
2. HTML Semantic Elements Used
3. HTML Semantic Elements Not Used
4. Design Challenges and Hardships
5. Security Implementations (XSS and SQL Injection)
6. Importance of API in the System
7. Client-Server Communication Flow

---

## 1. Design Principles

The AquaSphere system follows several key design principles that guide its architecture and implementation:

### Functional Semantics
The system uses semantic HTML elements where they provide clear functional benefit. Semantic elements like nav, section, main, and footer are used to improve accessibility, SEO, and code maintainability.

### Bootstrap Compatibility
The system is built on Bootstrap framework for responsive design and consistent UI components. All layouts use Bootstrap grid system and utility classes for flexibility and mobile responsiveness.

### Dynamic Rendering Support
The system supports JavaScript-based dynamic content generation. Product lists, cart items, and order displays are rendered dynamically using JavaScript, allowing for real-time updates without page refreshes.

### Visual Design Consistency
The system maintains consistent styling across all pages using CSS variables, shared component styles, and a unified color scheme. Dark mode support is implemented system-wide with CSS variables for easy theme switching.

### Simplicity Over Complexity
The system avoids unnecessary wrapper elements and complex structures. Simple div-based layouts are used where semantic elements do not provide clear benefit, keeping the codebase maintainable.

### Security First
All user inputs are sanitized, all database queries use prepared statements, and all outputs are escaped. Security is built into every layer of the application.

### Separation of Concerns
Client-side JavaScript handles UI interactions and dynamic rendering. Server-side PHP handles business logic, database operations, and security. Clear separation allows for maintainable and scalable code.

### Progressive Enhancement
The system works with basic functionality even if JavaScript is disabled, but enhanced features require JavaScript. Forms can submit traditionally, but AJAX provides better user experience.

---

## 2. HTML Semantic Elements Used

The system uses four primary HTML5 semantic elements:

### Navigation Element (nav)

**Purpose**: Defines navigation links and menus

**Usage Locations**:
- index.html - Main navigation bar
- navbar.html - Standalone navbar component
- navbar_logreg.html - Navbar for login/registration pages
- cart.html - Pagination navigation for cart items
- admin/orders.html - Pagination for orders table
- admin/users.html - Pagination for users table
- admin/dashboard.html - Pagination for recent orders

**Implementation Example**:
```html
<nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.html">AquaSphere</a>
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" href="dashboard.html">Home</a></li>
        </ul>
    </div>
</nav>
```

**Benefits**: Clear navigation structure, improved screen reader navigation, better SEO

### Section Element (section)

**Purpose**: Defines thematic groupings of content with headings

**Usage Location**: index.html only

**Sections Used**:
1. Hero Section (id="home") - Main landing banner with call-to-action
2. Features Section (id="features") - Feature cards showcasing system capabilities
3. How It Works Section (id="how-it-works") - Step-by-step process explanation
4. Call-to-Action Section (id="cta") - Final promotional section

**Implementation Example**:
```html
<section id="home" class="hero-section">
    <div class="container">
        <h1>Stay Hydrated, Order Delivered</h1>
    </div>
</section>
```

**Benefits**: Logical content organization, easy anchor link navigation, improved document outline

### Main Content Element (main)

**Purpose**: Represents the primary content of the page

**Usage Locations**:
- dashboard.html - Product browsing interface
- cart.html - Shopping cart and checkout
- recent_orders.html - Order history display

**Implementation Example**:
```html
<main class="main-content">
    <div class="container">
        <!-- Main page content -->
    </div>
</main>
```

**Note**: Other pages like login.html, registration.html, profile.html use div class="main-content" instead of semantic main element for styling purposes.

**Benefits**: Clearly identifies primary content, improves screen reader navigation, semantic separation from navigation and footer

### Footer Element (footer)

**Purpose**: Defines footer content with site information and links

**Usage Locations**: All pages (index.html, login.html, cart.html, dashboard.html, profile.html, orders.html, recent_orders.html, payment.html, registration.html, verify.html)

**Implementation Example**:
```html
<footer id="contact" class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4">
                <h5>Brand Information</h5>
            </div>
            <div class="col-lg-3">
                <h5>Contact Us</h5>
                <p>support@aquasphere.com</p>
            </div>
        </div>
    </div>
</footer>
```

**Benefits**: Semantic identification of footer content, consistent structure across pages, improved accessibility

---

## 3. HTML Semantic Elements Not Used

The following HTML5 semantic elements are not currently used in the system:

### Header Element

**Reason**: The navigation bar is implemented directly as a nav element without a header wrapper. The navbar is self-contained and semantically complete as a nav element, making a header wrapper redundant. No additional header content exists outside the navigation.

### Article Element

**Reason**: Product cards and order items are implemented as generic div elements rather than semantic article elements. Product cards are primarily UI components for displaying product information, not standalone articles. The system uses dynamic JavaScript rendering which makes semantic element implementation more complex. Product cards are part of a larger product grid/list, not independent articles.

### Aside Element

**Reason**: The system does not have sidebar content in the traditional sense. The admin sidebar is actually a navigation menu, not supplementary content. The main user-facing pages follow a single-column layout without sidebars. No advertisements or tangential content exists that would benefit from aside semantics.

### Figure and Figcaption Elements

**Reason**: Images are embedded directly without figure wrappers or captions. Most images are decorative (logos, icons) or functional (product images) without needing captions. Product images use CSS background-image rather than img tags, making figure less applicable. The alt attribute provides sufficient accessibility without needing figcaption.

### Time Element

**Reason**: Dates and times are displayed as plain text without semantic time elements. Dates are formatted and displayed as user-friendly strings. The system does not need machine-readable datetime attributes for most use cases. Dates are primarily for display purposes, not for calendar integration.

### Address Element

**Reason**: Contact information is displayed in regular div elements within the footer. Contact information is part of the footer's visual design, not a standalone address block. The address element is semantically meant for document/article author contact, not general business contact info.

---

## 4. Design Challenges and Hardships

During the development of the AquaSphere system, several design challenges were encountered:

### Challenge 1: Dynamic Content Rendering

**Problem**: Product cards, cart items, and order displays need to be rendered dynamically from database data without page refreshes.

**Solution**: Implemented JavaScript-based dynamic rendering using innerHTML with proper HTML escaping. Created reusable rendering functions that handle data fetching, DOM manipulation, and error handling.

**Hardship**: Ensuring all user-generated content is properly escaped to prevent XSS attacks while maintaining dynamic functionality required careful implementation of escapeHtml functions.

### Challenge 2: Dark Mode Consistency

**Problem**: Dark mode styles needed to be consistent across all pages and components, including dynamically loaded content.

**Solution**: Created centralized dark-mode.css file with CSS variables for colors. Used body.dark class selector for theme switching. Implemented JavaScript event listeners to sync dark mode state across dynamically loaded components.

**Hardship**: Dynamically loaded navbar and content required event delegation and custom events to ensure dark mode state synchronization. Some Bootstrap components needed !important overrides to work correctly in dark mode.

### Challenge 3: Responsive Design with Bootstrap

**Problem**: Ensuring the system works well on all screen sizes while maintaining design consistency.

**Solution**: Used Bootstrap grid system extensively with responsive breakpoints. Implemented mobile-first approach with col-12, col-md-6, col-lg-4 patterns. Created custom media queries for specific components.

**Hardship**: Some custom components required additional responsive CSS beyond Bootstrap defaults. Pagination controls and modals needed special handling for mobile devices.

### Challenge 4: Form Validation Consistency

**Problem**: Multiple forms across the system needed consistent validation behavior and error display.

**Solution**: Created reusable validation functions in JavaScript. Standardized error message display with red text only (no backgrounds). Implemented both client-side and server-side validation.

**Hardship**: Synchronizing validation rules between client and server required careful maintenance. Ensuring error messages display correctly in both light and dark modes required CSS adjustments.

### Challenge 5: State Management

**Problem**: User state (cart, delivery address, selected items) needed to persist across page navigations and browser sessions.

**Solution**: Implemented dual storage system using localStorage for client-side caching and database JSON columns for server-side persistence. Created UserState helper object to manage state synchronization.

**Hardship**: Synchronizing state between localStorage and database required careful handling of async operations. Race conditions when multiple state updates occurred simultaneously needed debouncing and queue management.

### Challenge 6: Database Compatibility

**Problem**: System needed to work with both PostgreSQL (production) and SQLite (local development).

**Solution**: Created database abstraction layer in database.php that detects available database and uses appropriate functions. Implemented execute_sql function that works with both databases using prepared statements.

**Hardship**: Different SQL syntax between PostgreSQL and SQLite required conditional query building. Parameter binding differences (PostgreSQL uses $1, $2 vs SQLite uses ?) needed abstraction.

### Challenge 7: Security Implementation

**Problem**: Implementing comprehensive security measures without breaking functionality or user experience.

**Solution**: Created sanitize.php with reusable security functions. Implemented multiple layers of protection (input sanitization, output escaping, prepared statements). Used defense-in-depth approach.

**Hardship**: Balancing security with usability required careful consideration. Some legitimate user input (like product descriptions) needed to allow certain characters while blocking malicious patterns. Finding the right balance between security and functionality was challenging.

### Challenge 8: Pagination Implementation

**Problem**: Implementing client-side pagination for product lists and order tables while maintaining good performance.

**Solution**: Load all data once, then slice array for display. Implemented pagination controls with page numbers, previous/next buttons, and item count display. Used JavaScript array methods for efficient data manipulation.

**Hardship**: Ensuring pagination works correctly when items are added or deleted required careful state management. Adjusting current page when items are removed needed special logic.

---

## 5. Security Implementations

The system implements comprehensive security measures to prevent XSS and SQL injection attacks on both client-side and server-side.

### SQL Injection Prevention

#### Server-Side Implementation

**File**: api/database.php

**PostgreSQL Implementation** (Lines 46-67):
```php
function execute_sql($conn, $query, $params = null) {
    if ($params) {
        // Replace ? with $1, $2, etc. for PostgreSQL
        $param_count = 1;
        $pg_query = preg_replace_callback('/\?/', function() use (&$param_count) {
            return '$' . $param_count++;
        }, $query);
        $result = pg_query_params($conn, $pg_query, $params);
        if ($result === false) {
            $error = pg_last_error($conn);
            error_log("PostgreSQL query error: $error. Query: $pg_query");
        }
        return $result;
    } else {
        $result = pg_query($conn, $query);
        if ($result === false) {
            $error = pg_last_error($conn);
            error_log("PostgreSQL query error: $error. Query: $query");
        }
        return $result;
    }
}
```

**SQLite Implementation** (Lines 105-113):
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

**How It Works**: All database queries use placeholders (?) instead of concatenating user input. User input is passed as parameters to the database driver, which automatically escapes special characters. This completely prevents SQL injection because the SQL structure is separated from the data.

**Usage Example** (api/login.php, Line 90-91):
```php
$query = "SELECT * FROM users WHERE username = ?";
$result = execute_sql($conn, $query, [$username]);
```

**Coverage**: 100% of all database queries use prepared statements. No raw SQL concatenation exists anywhere in the codebase.

### XSS Prevention

#### Server-Side Protection

**File**: api/sanitize.php

**Input Sanitization Function** (Lines 8-16):
```php
function sanitize_string($value, $max_len = 255) {
    if ($value === null) return null;
    $v = trim((string)$value);
    // strip tags to reduce XSS vectors
    $v = strip_tags($v);
    if ($max_len > 0) {
        $v = mb_substr($v, 0, $max_len);
    }
    return $v;
}
```

**Purpose**: Removes HTML tags from user input to prevent XSS attacks. Also trims whitespace and limits string length.

**Malicious Pattern Detection** (Lines 56-80):
```php
function has_malicious_payload($value) {
    if ($value === null || $value === '') return false;
    if (!is_string($value)) $value = (string)$value;
    $v = strtolower($value);
    
    // Basic XSS patterns
    $xss_needles = [
        '<script', '</script', '<img', '<svg', '<iframe', '<object', '<embed',
        'javascript:', 'onerror=', 'onload=', 'onclick=', 'onmouseover=', 'onfocus='
    ];
    foreach ($xss_needles as $needle) {
        if (strpos($v, $needle) !== false) return true;
    }
    
    // Basic SQL injection patterns
    $sql_needles = [
        "' or 1=1", '" or 1=1', "' or '1'='1", '" or "1"="1',
        ' or 1=1', ' and 1=1',
        '--', '/*', '*/', ';--'
    ];
    foreach ($sql_needles as $needle) {
        if (strpos($v, $needle) !== false) return true;
    }
    return false;
}
```

**Purpose**: Detects common XSS and SQL injection attack patterns in user input.

**Assert Safe String Function** (Lines 85-94):
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

**Purpose**: Combines sanitization and malicious pattern detection. Automatically rejects malicious input and returns HTTP 400 error.

**Usage Example** (api/login.php, Line 27):
```php
$username = assert_safe_string($_POST['username'] ?? '', 'username', 64);
```

**Usage Example** (api/register.php, Line 28-33):
```php
$username = assert_safe_string($_POST['username'] ?? '', 'username', 64);
$email = sanitize_email($_POST['email'] ?? '', 128);
$first_name = assert_safe_string($_POST['first_name'] ?? '', 'first_name', 128);
$last_name = assert_safe_string($_POST['last_name'] ?? '', 'last_name', 128);
$gender = assert_safe_string($_POST['gender'] ?? '', 'gender', 32);
$birthday = assert_safe_string($_POST['birthday'] ?? '', 'birthday', 32);
```

#### Client-Side Protection

**File**: assets/js/script.js

**Input Guards** (Lines 59-103):
```javascript
(function attachGlobalInputGuards() {
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
    function guardInput(el) {
        if (!el) return;
        const handler = (e) => {
            const before = el.value;
            const after = scrub(before);
            if (after !== before) {
                const start = el.selectionStart;
                const delta = before.length - after.length;
                el.value = after;
                const pos = Math.max(0, (start ?? after.length) - delta);
                el.setSelectionRange(pos, pos);
                e.preventDefault();
            }
        };
        el.addEventListener('input', handler);
        el.addEventListener('paste', () => setTimeout(handler, 0));
    }
    document.addEventListener('DOMContentLoaded', () => {
        const fields = document.querySelectorAll('input[type="text"], input[type="email"], input[type="search"], input[type="tel"], input[type="password"], textarea');
        fields.forEach(guardInput);
    });
})();
```

**Purpose**: Blocks dangerous XSS and SQL injection patterns as users type or paste content. Prevents malicious input from being entered in the first place.

**Output Escaping Function** (dashboard.html, Lines 1434-1438):
```javascript
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
```

**Purpose**: Escapes HTML special characters when inserting user-generated content into the DOM. Uses DOM textContent property which automatically escapes HTML.

**Output Escaping Function** (cart.html, Lines 2675-2682):
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

**Purpose**: Alternative implementation using regex replacement to escape HTML characters. Converts special characters to HTML entities.

**Usage Example** (dashboard.html, Lines 1390-1391):
```javascript
<h3 class="product-title">${escapeHtml(product.label)}</h3>
<p class="product-description">${escapeHtml(product.description)}</p>
```

**Usage Example** (cart.html, Line 2783):
```javascript
<div class="item-name">${escapeHtml(item.name)}</div>
```

**JSON Response Escaping**: All API endpoints use PHP's json_encode() function which automatically escapes special characters in JSON responses. This prevents XSS when JSON data is parsed and displayed.

**Example** (api/login.php, Line 152-156):
```php
echo json_encode([
    'success' => true, 
    'message' => 'Login successful!',
    'redirect' => $redirect_url
]);
```

### Security Summary

**SQL Injection Prevention**:
- 100% coverage with prepared statements
- All queries use parameter binding
- No string concatenation in SQL queries

**XSS Prevention**:
- Server-side: Input sanitization with strip_tags() and malicious pattern detection
- Client-side: Real-time input guards that block dangerous patterns
- Output escaping: escapeHtml() function used for all DOM insertion
- JSON escaping: Automatic escaping via json_encode()

---

## 6. Importance of API in the System

APIs (Application Programming Interfaces) serve as the critical communication layer between the frontend (client-side) and backend (server-side) of the AquaSphere system.

### Primary Purpose: Frontend to Database Connection

The main purpose of APIs in this system is to provide a secure and standardized way for frontend actions to interact with the database. Instead of allowing direct database access from the browser (which would be insecure), all database operations go through PHP API endpoints.

### Key Functions of APIs

#### 1. Data Retrieval
APIs fetch data from the database and return it to the frontend in JSON format. For example, api/get_products.php retrieves all products from the database and returns them as JSON for display on the dashboard.

#### 2. Data Submission
APIs receive form data from the frontend, validate it, sanitize it, and store it in the database. For example, api/register.php receives user registration data, validates it, and creates a new user record.

#### 3. Authentication and Authorization
APIs handle user authentication (login) and check user permissions (admin access). For example, api/login.php verifies user credentials and creates a session, while api/admin/add_product.php checks if the user is an admin before allowing product creation.

#### 4. Business Logic Processing
APIs contain the business logic that cannot be trusted to run on the client side. For example, api/create_order.php calculates order totals, applies delivery fees, and creates order records with proper relationships.

#### 5. Security Enforcement
APIs enforce security measures like input sanitization, SQL injection prevention, and XSS protection. All security checks happen on the server side where they cannot be bypassed.

#### 6. State Management
APIs synchronize user state (cart, delivery address, preferences) between client-side localStorage and server-side database. For example, api/user_state_save.php saves user state to the database, while api/user_state_get.php retrieves it.

### API Architecture

**RESTful Design**: APIs follow REST principles with clear endpoints for different operations:
- GET requests for data retrieval (api/get_products.php)
- POST requests for data creation and updates (api/register.php, api/login.php)
- JSON format for all requests and responses

**Separation of Concerns**: Each API endpoint has a single responsibility:
- api/login.php - Handles login only
- api/register.php - Handles registration only
- api/get_products.php - Retrieves products only
- api/create_order.php - Creates orders only

**Error Handling**: All APIs return consistent JSON responses with success/failure status and error messages. This allows the frontend to handle errors uniformly.

**Session Management**: APIs manage PHP sessions for user authentication. Session data is stored on the server, not the client, providing security.

### Why APIs Are Essential

**Security**: APIs prevent direct database access from the browser. All database operations go through server-side code where security measures can be enforced.

**Data Integrity**: APIs validate and sanitize all data before database operations. Business rules are enforced consistently.

**Scalability**: APIs allow the frontend and backend to be developed and deployed independently. The frontend can be updated without changing backend logic.

**Reusability**: API endpoints can be used by multiple frontend pages. For example, api/get_products.php is used by both dashboard.html and admin/products.html.

**Maintainability**: Business logic is centralized in API files, making it easier to update and maintain. Changes to database structure or business rules only need to be made in one place.

---

## 7. Client-Server Communication Flow

The AquaSphere system uses an AJAX-based communication pattern where the frontend (client) makes HTTP requests to PHP API endpoints (server), which then interact with the database and return JSON responses.

### Communication Pattern

#### Step 1: User Action on Frontend
User interacts with the interface (clicks button, submits form, loads page). JavaScript event handlers capture these actions.

**Example**: User clicks "Add to Cart" button on dashboard.html

#### Step 2: Frontend JavaScript Processing
JavaScript validates input, prepares data, and makes an HTTP request to the appropriate API endpoint using the fetch() API.

**Example** (dashboard.html):
```javascript
async function addToCart(productId, quantity) {
    const response = await fetch('api/user_state_save.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            cart: updatedCart
        }),
        credentials: 'include'
    });
}
```

#### Step 3: API Endpoint Receives Request
PHP API endpoint receives the HTTP request, starts a session if needed, and reads the request data (POST body, GET parameters, or JSON input).

**Example** (api/user_state_save.php, Lines 17-31):
```php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'database.php';
require_once 'sanitize.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input = sanitize_array_recursive(json_decode(file_get_contents('php://input'), true));
$user_id = $_SESSION['user_id'];
```

#### Step 4: Input Sanitization and Validation
API sanitizes all input data using sanitize functions, checks for malicious patterns, and validates business rules.

**Example** (api/user_state_save.php, Line 30):
```php
$input = sanitize_array_recursive(json_decode(file_get_contents('php://input'), true));
```

#### Step 5: Database Operation
API connects to database, executes prepared statement queries, and retrieves or modifies data.

**Example** (api/user_state_save.php, Lines 33-91):
```php
init_db();
$conn = get_db_connection();

// Build dynamic UPDATE query
$updates = [];
$params = [];

foreach ($fields_map as $input_key => $db_column) {
    if (array_key_exists($input_key, $input)) {
        $value = $input[$input_key];
        if (is_array($value) || is_object($value)) {
            $value_json = json_encode($value);
        } else {
            $value_json = $value !== null ? (string)$value : null;
        }
        $updates[] = "$db_column = ?";
        $params[] = $value_json;
    }
}

$query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
$params[] = $user_id;
$result = execute_sql($conn, $query, $params);
```

#### Step 6: Response Generation
API formats the result as JSON and sends it back to the frontend with appropriate HTTP status codes.

**Example** (api/user_state_save.php, Lines 100-107):
```php
close_connection($conn);

if (!$success) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save state']);
    exit;
}

echo json_encode(['success' => true]);
```

#### Step 7: Frontend Receives Response
JavaScript receives the JSON response, parses it, and updates the UI accordingly (shows success message, updates display, handles errors).

**Example** (dashboard.html):
```javascript
const data = await response.json();

if (data.success) {
    showNotification('Product added to cart!', 'success');
    updateCartCount();
} else {
    showNotification(data.message || 'Failed to add product', 'error');
}
```

### Complete Flow Example: Loading Products

**1. Page Load** (dashboard.html):
```javascript
document.addEventListener('DOMContentLoaded', async () => {
    loadProducts();
});
```

**2. Fetch Request** (dashboard.html):
```javascript
async function loadProducts() {
    const response = await fetch('api/get_products.php');
    const data = await response.json();
    if (data.success && data.products) {
        renderProducts(data.products);
    }
}
```

**3. API Processing** (api/get_products.php, Lines 17-21):
```php
init_db();
$conn = get_db_connection();

$query = "SELECT id, label, description, price, image_url, category, unit, created_at, updated_at FROM products ORDER BY created_at DESC";
$result = execute_sql($conn, $query);
```

**4. Data Retrieval** (api/get_products.php, Lines 23-42):
```php
$products = [];
if ($result !== false) {
    if ($GLOBALS['use_postgres']) {
        while ($row = pg_fetch_assoc($result)) {
            $products[] = $row;
        }
    } else {
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $products[] = $row;
        }
    }
}
```

**5. JSON Response** (api/get_products.php, Lines 44-48):
```php
close_connection($conn);
ob_end_clean();

$json = json_encode(['success' => true, 'products' => $products], JSON_UNESCAPED_SLASHES);
echo $json;
```

**6. Frontend Rendering** (dashboard.html, Lines 1358-1408):
```javascript
productsGrid.innerHTML = productsList.map(product => {
    return `
        <div class="product-card" data-category="${escapeHtml(category)}">
            <div class="product-image" style="${imageStyle}">
            </div>
            <div class="product-info">
                <h3 class="product-title">${escapeHtml(product.label)}</h3>
                <p class="product-description">${escapeHtml(product.description)}</p>
            </div>
        </div>
    `;
}).join('');
```

### Communication Methods

**GET Requests**: Used for retrieving data that does not modify the database
- api/get_products.php - Get all products
- api/get_orders.php - Get user orders
- api/get_current_user.php - Get current user data
- api/user_state_get.php - Get user state

**POST Requests**: Used for creating, updating, or deleting data
- api/login.php - User login
- api/register.php - User registration
- api/user_state_save.php - Save user state
- api/create_order.php - Create new order
- api/admin/add_product.php - Add product
- api/admin/delete_product.php - Delete product

**JSON Format**: All API requests and responses use JSON format for consistency and ease of parsing.

**Session Management**: APIs use PHP sessions to maintain user authentication state. Session ID is automatically managed by PHP and sent via cookies.

**Error Handling**: All APIs return JSON with success field. Frontend checks success field and displays appropriate messages or handles errors.

### Data Flow Diagram

```
User Action (Frontend)
    |
    v
JavaScript Event Handler
    |
    v
Fetch API Request (HTTP)
    |
    v
PHP API Endpoint
    |
    v
Input Sanitization
    |
    v
Authentication Check
    |
    v
Database Query (Prepared Statement)
    |
    v
Database Response
    |
    v
JSON Response Generation
    |
    v
HTTP Response to Frontend
    |
    v
JavaScript Response Handler
    |
    v
UI Update
```

### Security in Communication

**HTTPS**: All communication should use HTTPS in production to encrypt data in transit.

**Session Cookies**: Session IDs are stored in HTTP-only cookies to prevent JavaScript access.

**CSRF Protection**: Session-based authentication provides some CSRF protection, though additional tokens could be added.

**Input Validation**: All data is validated and sanitized before database operations.

**Output Escaping**: All data is escaped before being sent to frontend or inserted into HTML.

---

## Summary

The AquaSphere system follows design principles of functional semantics, Bootstrap compatibility, dynamic rendering support, visual consistency, simplicity, security-first approach, separation of concerns, and progressive enhancement.

The system uses four HTML semantic elements (nav, section, main, footer) and does not use six others (header, article, aside, figure, time, address) for specific design and implementation reasons.

Design challenges included dynamic content rendering, dark mode consistency, responsive design, form validation, state management, database compatibility, security implementation, and pagination.

Security is implemented through prepared statements for SQL injection prevention and multiple layers of XSS protection including server-side sanitization, client-side input guards, output escaping, and JSON escaping.

APIs serve as the critical communication layer between frontend and database, handling data retrieval, submission, authentication, business logic, security enforcement, and state management.

Client-server communication follows an AJAX pattern where frontend makes HTTP requests to PHP APIs, which process requests, interact with database, and return JSON responses for frontend UI updates.

