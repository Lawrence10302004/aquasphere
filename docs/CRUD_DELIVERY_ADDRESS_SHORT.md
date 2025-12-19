# CRUD Operations - Delivery Address Module (Short Description)

## Overview

The Delivery Address module implements complete CRUD operations allowing users to manage their delivery addresses for order placement. Addresses are stored as JSON in the `users` table and synchronized between client-side localStorage and server-side database.

---

## CRUD Operations Summary

### **CREATE (Insert)**
- **Function**: `saveAddress()` in `cart.html`
- **Process**: User fills address form (full name, phone, region, province, city, barangay, postal code, street address, coordinates)
- **Storage**: Saves to database via `api/user_state_save.php` using `UserState.setDeliveryAddress()`
- **Database**: Updates `delivery_address` JSON column in `users` table
- **Features**: Form validation, coordinate capture from map, address labeling

### **READ (Select)**
- **Function**: `loadAddress()` in `cart.html`
- **Process**: Retrieves saved address from `api/user_state_get.php`
- **Storage**: Fetches `delivery_address` JSON from `users` table
- **Display**: Shows address in cart page with map view, coordinates, and edit/delete options
- **Features**: Auto-loads on page load, syncs with localStorage

### **UPDATE**
- **Function**: `saveAddress()` (same as CREATE)
- **Process**: Replaces existing address with new address data
- **Storage**: Updates `delivery_address` JSON column via `api/user_state_save.php`
- **Features**: Same validation and processing as CREATE operation

### **DELETE**
- **Function**: `deleteAddress()` in `cart.html`
- **Process**: Sets delivery address to `null` via `UserState.setDeliveryAddress(null)`
- **Storage**: Updates `delivery_address` to NULL in database
- **Features**: Confirmation modal, resets delivery fee and time predictions, disables checkout button

---

## Technical Implementation

**API Endpoints**:
- `api/user_state_save.php` - CREATE/UPDATE/DELETE (POST)
- `api/user_state_get.php` - READ (GET)

**Database Schema**:
- Table: `users`
- Column: `delivery_address` (JSON/TEXT)
- Storage: JSON object containing address fields

**Client-Side**:
- `cart.html` - Address form and management UI
- `js/user_state.js` - State management helper functions
- localStorage - Client-side caching for quick access

**Security**:
- Session-based authentication required
- Input sanitization via `sanitize_array_recursive()`
- Prepared statements for database operations
- User data isolation (only own address accessible)

---

## Short Description for Documentation

**"The Delivery Address module implements full CRUD operations for managing user delivery addresses. Users can CREATE new addresses through a form with location fields and map coordinates, READ saved addresses displayed in the cart page, UPDATE addresses by saving new information, and DELETE addresses with confirmation. All operations are synchronized between client-side localStorage and server-side database storage in the users table as JSON data, ensuring persistent address management across sessions."**

