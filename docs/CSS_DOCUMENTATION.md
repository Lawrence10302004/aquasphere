## External CSS Files

The system uses two external CSS files:

1. `assets/css/dark-mode.css`
   Handles the dark mode theme using CSS variables defined in `:root`, theme switching, and component overrides. This file is linked across all pages and contains over 2,400 lines of dark mode styling.

2. `navbar.css`
   Contains shared navbar styles such as fixed positioning, backdrop blur effects, and navigation link styling. It is used on most pages that include the navbar component.

Note:
The file `assets/css/style.css` exists in the codebase and includes utility classes such as `.text-gradient`, `.shadow-custom`, and `.loader`, along with custom scrollbar styles. However, it is not currently linked in any HTML files. Most styles are embedded directly in `<style>` blocks within individual HTML files for easier page-specific management.

Additional external CSS libraries used:

* Bootstrap 5.3.0 (CDN) for grid layout and utility classes
* Font Awesome 6.4.0 (CDN) for icons
* AOS Animate On Scroll (CDN) for scroll animations

------------------------------------------------

## Common Classes

### Layout Classes

* Bootstrap grid: `.container`, `.row`, `.col-*` (e.g., `.col-lg-5`, `.col-md-8`)
* Flexbox utilities: `.d-flex`, `.justify-content-*`, `.align-items-*`, `.flex-column`
* Spacing utilities: `.mb-*`, `.mt-*`, `.p-*`

### Navigation Classes

* `.navbar`, `.navbar-brand`, `.navbar-nav`, `.navbar-toggler`
* `.nav-link`, `.nav-item`, `.nav-link.active`
* `.navbar.scrolled` for scroll-based effects

### Content Classes

* `.main-content` – Main wrapper using `flex: 1` for sticky footer behavior
* `.page-title`, `.page-subtitle` – Page heading styles
* `.hero-section` – Hero section with background image and centered content

### Cards and Containers

* `.login-card`, `.register-card` – Authentication form containers
* `.profile-container` – User profile layout
* `.order-card` – Order display cards
* `.product-card` – Product display cards

### Forms

* `.form-control`, `.form-select` – Bootstrap form elements
* `.custom-input` – Custom styled inputs
* `.input-group-custom` – Custom input group wrapper
* `.form-label` – Form field labels

### Validation

* `.custom-error` – Error message styling
* `.error` – Applied to invalid form fields
* `.success` – Applied to valid form fields

### Buttons

* `.btn-primary` – Primary action button
* `.btn-delete-confirm`, `.btn-delete-cancel` – Delete modal buttons
* `.btn-add-cart` – Add-to-cart button

### Modals

* `.modal` – Base Bootstrap modal class
* `.delete-confirm-modal` – Delete confirmation modal
* `.password-change-modal` – Password change success modal

### Dark Mode

* `.dark` – Applied to the `body` element to enable dark mode
* Dark mode overrides use the `body.dark` selector

--------------------------------------------------

## Common IDs

### Navigation

* `#navbarNav` – Navigation collapse container
* `#themeToggle` – Dark mode toggle button
* `#cartCount`, `#ordersCount`, `#notificationCount` – Badge counters
* `#authNav` – Authentication navigation container

### Forms

* `#username`, `#password`, `#email` – Input fields
* `#loginForm`, `#addProductForm` – Forms
* `#productLabel`, `#productDescription`, `#productPrice` – Product form inputs

### Content

* `#productsTableBody` – Products table body
* `#paginationContainer` – Pagination wrapper
* `#productsGrid` – Product grid container

### Modals

* `#deleteConfirmModal` – Delete confirmation modal
* `#passwordChangeModal` – Password change modal

---------------------------------------------------

## Styling Methods

### CSS Variables

Defined in the `:root` selector within `dark-mode.css`.

Color variables include:

* Light mode: background, text, borders
* Dark mode: background, panels, text, borders
* Accent colors: blue variants

Purpose:
Allows dynamic theme switching without modifying individual styles through JavaScript.

### Naming Convention

* Component-based naming such as `.navbar-brand`, `.order-card`
* Modifier classes like `.nav-link.active`, `.product-card:hover`
* Follows a BEM-like structure

### Utility Classes

* Bootstrap utilities for layout, alignment, and spacing
* Custom utility classes defined within embedded styles

### Specificity Strategy

* Dark mode overrides use `!important` when necessary
* Scoped selectors such as `body.dark .navbar`
* Transition overrides used to prevent visual flashing

### Responsive Design

* Bootstrap grid system for responsive layouts
* Media queries within page-level styles
* Flexible units such as `rem`, `%`, `vh`, and `vw`
* Mobile-first approach with larger breakpoints added as needed

------------------------------------------------------

## Layout Techniques

### Flexbox (Primary Method)

Used extensively for layout alignment and page structure, including:

* Sticky footer layout
* Navbar alignment
* Content centering
* Modal positioning
* Card internal layouts

Flexbox appears throughout pages such as dashboard, login, and profile.

### CSS Grid (Secondary Method)

Used mainly for structured layouts such as:

* Product grids
* Two-column layouts in cart, profile, and payment pages
* Responsive auto-fit columns

### Bootstrap Grid System

Provides the base page structure using containers, rows, and columns.
Used for:

* Page layout consistency
* Responsive forms
* Breakpoint-based column adjustments

### Positioning Techniques

* Fixed positioning for the navbar
* Absolute positioning for icons, badges, and overlays
* Relative positioning as a reference for absolute elements

----------------------------------------------------------

## Summary

The AquaSphere system follows a hybrid CSS architecture:

* External CSS files for shared styling
* Embedded styles for page-specific customization
* Flexbox as the primary layout method
* CSS Grid for structured multi-column layouts
* Bootstrap grid for responsiveness
* CSS variables for theming and dark mode support

This approach ensures a responsive, maintainable, and scalable design system that works across devices and user preferences.

