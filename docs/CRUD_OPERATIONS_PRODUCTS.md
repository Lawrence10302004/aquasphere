# CRUD Operations - Products Module

## Overview

The Products module is a core CRUD (Create, Read, Update, Delete) system in AquaSphere that allows administrators to manage the product catalog. This document explains in detail how each CRUD operation is implemented.

**Module Location**: Admin Product Management (`admin/products.html`, `api/admin/*.php`)

**Access Control**: Admin-only (requires `$_SESSION['is_admin'] == 1`)

---

## 1. CREATE (Insert) - Add New Product

### 1.1 Client-Side Implementation

**Location**: `admin/products.html`

**Form Structure**:
```html
<form id="addProductForm">
    <input type="text" id="productLabel" required>
    <input type="number" id="productPrice" required step="0.01" min="0">
    <textarea id="productDescription" required></textarea>
    <input type="file" id="productImage" accept="image/*">
    <select id="productCategory" required>
        <option value="refill gallon">Refill Gallon</option>
        <option value="gallon">Gallon</option>
        <option value="bottle">Bottle</option>
    </select>
    <input type="text" id="productUnit" required>
    <button type="submit">Add Product</button>
</form>
```

**JavaScript Submission**:
```javascript
document.getElementById('addProductForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData();
    formData.append('label', document.getElementById('productLabel').value);
    formData.append('description', document.getElementById('productDescription').value);
    formData.append('price', document.getElementById('productPrice').value);
    formData.append('category', document.getElementById('productCategory').value);
    formData.append('unit', document.getElementById('productUnit').value);
    formData.append('image', document.getElementById('productImage').files[0]);
    
    // Show loading state
    addProductBtn.disabled = true;
    addProductText.style.display = 'none';
    addProductLoading.classList.add('show');
    
    // Submit to API
    const response = await fetch('api/admin/add_product.php', {
        method: 'POST',
        body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
        // Show success notification
        showNotification('Product added successfully!', 'success');
        // Reset form
        document.getElementById('addProductForm').reset();
        // Reload product list
        loadProducts();
    } else {
        // Show error notification
        showNotification(data.message || 'Failed to add product', 'error');
    }
    
    // Reset button state
    addProductBtn.disabled = false;
    addProductText.style.display = 'inline';
    addProductLoading.classList.remove('show');
});
```

### 1.2 Server-Side Implementation

**Location**: `api/admin/add_product.php`

**Process Flow**:

#### Step 1: Authentication Check
```php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
```

#### Step 2: Input Sanitization
```php
require_once '../sanitize.php';

$label = sanitize_string($_POST['label'] ?? '', 255);
$description = sanitize_string($_POST['description'] ?? '', 1000);
$price = floatval($_POST['price'] ?? 0);
$category = sanitize_string($_POST['category'] ?? '', 100);
$unit = sanitize_string($_POST['unit'] ?? '', 50);
```

**Security Features**:
- `sanitize_string()` removes HTML tags and limits length
- `floatval()` ensures price is numeric
- All inputs validated before processing

#### Step 3: Input Validation
```php
if (empty($label) || empty($description) || $price <= 0 || empty($category) || empty($unit)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}
```

#### Step 4: Image Upload Processing
```php
if (isset($_FILES['image'])) {
    // Check upload errors
    $upload_error = $_FILES['image']['error'];
    if ($upload_error !== UPLOAD_ERR_OK) {
        // Handle error (file too large, no file, etc.)
        echo json_encode(['success' => false, 'message' => 'Image upload error']);
        exit;
    }
    
    // Determine upload directory (Railway volume or web root)
    $upload_dir = determine_upload_directory();
    
    // Validate file type
    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
        exit;
    }
    
    // Generate unique filename
    $filename = uniqid('product_', true) . '.' . $file_extension;
    $file_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
        $image_url = 'uploads/products/' . $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit;
    }
}
```

**Image Upload Features**:
- Supports Railway volume storage (persistent) or web root (ephemeral)
- Validates file type (JPG, PNG, GIF, WEBP only)
- Generates unique filenames to prevent conflicts
- Creates upload directory if it doesn't exist
- Checks directory permissions

#### Step 5: Database Insert
```php
require_once '../database.php';

init_db();
$conn = get_db_connection();

$query = "INSERT INTO products (label, description, price, image_url, category, unit, created_at, updated_at) 
          VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

$result = execute_sql($conn, $query, [$label, $description, $price, $image_url, $category, $unit]);
```

**Security Features**:
- **Prepared Statement**: Uses `?` placeholders to prevent SQL injection
- **Parameter Binding**: All values passed as parameters, never concatenated

#### Step 6: Response
```php
if ($result !== false) {
    $product_id = last_insert_id($conn, 'products');
    close_connection($conn);
    echo json_encode([
        'success' => true, 
        'message' => 'Product added successfully', 
        'product_id' => $product_id, 
        'image_url' => $image_url
    ]);
} else {
    $error_msg = pg_last_error($conn) ?? 'Database error';
    echo json_encode(['success' => false, 'message' => 'Failed to add product: ' . $error_msg]);
}
```

**Database Schema**:
```sql
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    label VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(500),
    category VARCHAR(100),
    unit VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 2. READ (Select) - Retrieve Products

### 2.1 Public Product Retrieval (Customer View)

**Location**: `api/get_products.php`

**Purpose**: Retrieve all products for display on customer dashboard

**Implementation**:
```php
require_once 'database.php';

init_db();
$conn = get_db_connection();

$query = "SELECT id, label, description, price, image_url, category, unit, created_at, updated_at 
          FROM products 
          ORDER BY created_at DESC";

$result = execute_sql($conn, $query);

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

close_connection($conn);
echo json_encode(['success' => true, 'products' => $products]);
```

**Features**:
- **No Authentication Required**: Public endpoint for customer browsing
- **Ordered by Date**: Newest products first
- **All Fields**: Returns complete product information
- **Database Agnostic**: Works with both PostgreSQL and SQLite

**Client-Side Usage** (`dashboard.html`):
```javascript
async function loadProducts() {
    try {
        const response = await fetch('api/get_products.php');
        const data = await response.json();
        
        if (data.success && data.products) {
            displayProducts(data.products);
        } else {
            showError('Failed to load products');
        }
    } catch (error) {
        showError('Error loading products: ' + error.message);
    }
}
```

### 2.2 Admin Product Retrieval

**Location**: `api/admin/get_products.php`

**Purpose**: Retrieve all products for admin management interface

**Implementation**:
```php
session_start();
require_once '../database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

init_db();
$conn = get_db_connection();

$query = "SELECT * FROM products ORDER BY created_at DESC";
$result = execute_sql($conn, $query);

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

close_connection($conn);
echo json_encode(['success' => true, 'products' => $products]);
```

**Features**:
- **Admin Authentication Required**: Checks session and admin flag
- **Complete Data**: Returns all product fields including internal IDs
- **Same Ordering**: Newest products first

**Client-Side Usage** (`admin/products.html`):
```javascript
let allProducts = [];
let currentPage = 1;
const itemsPerPage = 5;

async function loadProducts() {
    try {
        const response = await fetch('api/admin/get_products.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success && data.products) {
            allProducts = data.products;
            currentPage = 1;
            displayProducts(); // Display with pagination
        }
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

function displayProducts() {
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const currentProducts = allProducts.slice(startIndex, endIndex);
    
    // Render products in table with pagination
    // ... rendering logic
}
```

**Pagination Features**:
- **Client-Side Pagination**: 5 products per page
- **Dynamic Rendering**: Updates table without page refresh
- **Page Controls**: Previous/Next buttons and page numbers

---

## 3. UPDATE - Modify Existing Product

### Status: **NOT CURRENTLY IMPLEMENTED**

**Note**: The Products module does not currently have an Update operation implemented. Products can only be created and deleted.

**Potential Implementation** (if needed in future):

```php
// api/admin/update_product.php
session_start();

// Check admin authentication
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$product_id = sanitize_int($input['id'] ?? 0);
$label = sanitize_string($input['label'] ?? '', 255);
$description = sanitize_string($input['description'] ?? '', 1000);
$price = floatval($input['price'] ?? 0);
$category = sanitize_string($input['category'] ?? '', 100);
$unit = sanitize_string($input['unit'] ?? '', 50);

// Validate
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

// Build dynamic update query
$update_fields = [];
$update_params = [];

if (!empty($label)) {
    $update_fields[] = "label = ?";
    $update_params[] = $label;
}
if (!empty($description)) {
    $update_fields[] = "description = ?";
    $update_params[] = $description;
}
if ($price > 0) {
    $update_fields[] = "price = ?";
    $update_params[] = $price;
}
if (!empty($category)) {
    $update_fields[] = "category = ?";
    $update_params[] = $category;
}
if (!empty($unit)) {
    $update_fields[] = "unit = ?";
    $update_params[] = $unit;
}

$update_fields[] = "updated_at = CURRENT_TIMESTAMP";
$update_params[] = $product_id;

$query = "UPDATE products SET " . implode(', ', $update_fields) . " WHERE id = ?";
$result = execute_sql($conn, $query, $update_params);

if ($result !== false) {
    echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update product']);
}
```

**Why Not Implemented**:
- Current system design focuses on product creation and deletion
- Admins can delete and recreate products if changes are needed
- Simpler workflow for product management

---

## 4. DELETE - Remove Product

### 4.1 Client-Side Implementation

**Location**: `admin/products.html`

**Delete Button**:
```html
<button class="btn btn-danger btn-sm" onclick="deleteProduct(<?php echo $product['id']; ?>)">
    <i class="fas fa-trash"></i> Delete
</button>
```

**JavaScript Function**:
```javascript
async function deleteProduct(productId) {
    // Show confirmation modal
    const modal = document.getElementById('deleteConfirmModal');
    modal.classList.add('show');
    
    // Set up confirm button
    document.getElementById('deleteConfirmBtn').onclick = async function() {
        // Show loading state
        this.disabled = true;
        document.getElementById('deleteLoading').classList.add('show');
        document.querySelector('.delete-confirm-text').style.display = 'none';
        
        try {
            const response = await fetch('api/admin/delete_product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: productId }),
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Remove product from array
                allProducts = allProducts.filter(p => p.id !== productId);
                
                // Adjust current page if needed
                const totalPages = Math.ceil(allProducts.length / itemsPerPage);
                if (currentPage > totalPages && totalPages > 0) {
                    currentPage = totalPages;
                }
                
                // Refresh display
                displayProducts();
                
                // Show success notification
                showNotification('Product deleted successfully', 'success');
                
                // Close modal
                modal.classList.remove('show');
            } else {
                showNotification(data.message || 'Failed to delete product', 'error');
            }
        } catch (error) {
            showNotification('Error deleting product: ' + error.message, 'error');
        } finally {
            // Reset button state
            this.disabled = false;
            document.getElementById('deleteLoading').classList.remove('show');
            document.querySelector('.delete-confirm-text').style.display = 'inline';
        }
    };
}
```

**Features**:
- **Confirmation Modal**: Custom modal with warning icon
- **Loading State**: Shows spinner during deletion
- **AJAX Update**: Removes product from list without page refresh
- **Pagination Adjustment**: Adjusts current page if needed
- **Error Handling**: Displays error messages on failure

### 4.2 Server-Side Implementation

**Location**: `api/admin/delete_product.php`

**Process Flow**:

#### Step 1: Authentication Check
```php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
```

#### Step 2: Request Method Validation
```php
// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
```

#### Step 3: Input Validation
```php
// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$product_id = intval($input['id']);

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}
```

**Security Features**:
- **Type Casting**: `intval()` ensures product ID is integer
- **Validation**: Checks for positive ID value
- **JSON Parsing**: Safely parses JSON input

#### Step 4: Database Deletion
```php
require_once '../database.php';

init_db();
$conn = get_db_connection();

$query = "DELETE FROM products WHERE id = ?";
$result = execute_sql($conn, $query, [$product_id]);
```

**Security Features**:
- **Prepared Statement**: Uses `?` placeholder to prevent SQL injection
- **Parameter Binding**: Product ID passed as parameter

#### Step 5: Response
```php
if ($result !== false) {
    if ($GLOBALS['use_postgres']) {
        $affected = pg_affected_rows($result);
    } else {
        $affected = $conn->changes();
    }
    
    close_connection($conn);
    
    if ($affected > 0) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
} else {
    close_connection($conn);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
}
```

**Features**:
- **Affected Rows Check**: Verifies if product was actually deleted
- **404 Handling**: Returns 404 if product doesn't exist
- **Error Handling**: Catches database errors

**Note**: The system does not currently delete associated image files when a product is deleted. Image files remain in the upload directory.

---

## 5. CRUD Operations Summary

| Operation | Endpoint | Method | Auth Required | Status |
|-----------|----------|--------|---------------|--------|
| **Create** | `api/admin/add_product.php` | POST | Admin | ✅ Implemented |
| **Read (Public)** | `api/get_products.php` | GET | None | ✅ Implemented |
| **Read (Admin)** | `api/admin/get_products.php` | GET | Admin | ✅ Implemented |
| **Update** | N/A | N/A | N/A | ❌ Not Implemented |
| **Delete** | `api/admin/delete_product.php` | POST | Admin | ✅ Implemented |

---

## 6. Security Features

### 6.1 Input Sanitization
- All string inputs sanitized with `sanitize_string()`
- Removes HTML tags to prevent XSS
- Limits string length to prevent buffer overflow
- Numeric inputs validated with `floatval()` or `intval()`

### 6.2 SQL Injection Prevention
- **Prepared Statements**: All queries use `?` placeholders
- **Parameter Binding**: Values passed as parameters, never concatenated
- **Database Agnostic**: Works with both PostgreSQL and SQLite

### 6.3 Authentication & Authorization
- **Session-Based Auth**: Uses PHP sessions for authentication
- **Admin-Only Access**: Create, Delete operations require admin flag
- **Public Read Access**: Product listing available to all users

### 6.4 File Upload Security
- **File Type Validation**: Only allows image formats (JPG, PNG, GIF, WEBP)
- **Unique Filenames**: Prevents filename conflicts and overwrites
- **Directory Permissions**: Checks and sets proper permissions
- **Error Handling**: Comprehensive upload error handling

---

## 7. Database Schema

```sql
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    label VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(500),
    category VARCHAR(100),
    unit VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Fields**:
- `id`: Primary key, auto-increment
- `label`: Product name/label (max 255 chars)
- `description`: Product description (TEXT, unlimited)
- `price`: Product price (DECIMAL with 2 decimal places)
- `image_url`: Path to product image (max 500 chars)
- `category`: Product category (max 100 chars)
- `unit`: Price unit (e.g., "/refill", "/unit") (max 50 chars)
- `created_at`: Timestamp of creation
- `updated_at`: Timestamp of last update

---

## 8. Error Handling

### 8.1 Client-Side Errors
- **Network Errors**: Caught with try-catch blocks
- **API Errors**: Displayed via notification system
- **Validation Errors**: Shown inline on form fields

### 8.2 Server-Side Errors
- **Output Buffering**: Prevents premature output
- **HTTP Status Codes**: Proper status codes (400, 401, 404, 500)
- **Error Logging**: Errors logged to PHP error log
- **JSON Responses**: All errors returned as JSON

---

## 9. Files Involved

### Server-Side (PHP):
- `api/admin/add_product.php` - Create operation
- `api/admin/get_products.php` - Admin read operation
- `api/get_products.php` - Public read operation
- `api/admin/delete_product.php` - Delete operation
- `api/database.php` - Database connection and query execution
- `api/sanitize.php` - Input sanitization functions

### Client-Side (HTML/JavaScript):
- `admin/products.html` - Admin product management interface
- `dashboard.html` - Customer product browsing interface
- `navbar.js` - Navigation and user data loading

---

## 10. Summary

The Products CRUD module implements:

1. **CREATE**: ✅ Fully implemented with image upload, validation, and sanitization
2. **READ**: ✅ Implemented for both public and admin views with pagination
3. **UPDATE**: ❌ Not currently implemented
4. **DELETE**: ✅ Fully implemented with confirmation modal and AJAX updates

**Key Features**:
- Secure authentication and authorization
- Input sanitization and SQL injection prevention
- File upload handling with validation
- Client-side pagination for admin view
- AJAX-based operations (no page refresh)
- Comprehensive error handling

The module follows security best practices and provides a solid foundation for product management in the AquaSphere system.

