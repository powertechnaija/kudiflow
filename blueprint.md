# Blueprint: Laravel POS & Inventory Management API

## 1. Overview

This project is a robust and scalable RESTful API for a Point of Sale (POS) and Inventory Management system. Built with Laravel and Sanctum, it provides a secure and feature-rich backend for a modern retail application.

## 2. Features Implemented

*   **Authentication:**
    *   User registration with store creation.
    *   Token-based login (`/api/login`) with Sanctum.
    *   "Remember Me" functionality with configurable token lifetimes (1 week for "remembered" sessions, 2 hours otherwise).
    *   Secure logout (`/api/logout`) that revokes the current token.
    *   Route protection using `auth:sanctum` middleware.
    *   **Correctly configured for API-only authentication using the `sanctum` driver.**
*   **User & Role Management:**
    *   Spatie Laravel Permission for role-based access control.
    *   `Admin` and `Manager` roles with distinct permissions.
    *   API endpoints for user management, protected by roles.
*   **Inventory & Product Management:**
    *   Endpoints for creating, updating, and retrieving products.
    *   Bulk update functionality for products.
    *   Product history tracking.
    *   Barcode lookup endpoint for mobile scanners.
*   **Sales & Operations:**
    *   POS sales endpoint (`/api/orders`).
    *   Purchase and procurement endpoint (`/api/inventory/purchase`).
    *   Handling of product returns.
*   **Accounting & Finance:**
    *   Automatic seeding of a default Chart of Accounts for new stores.
    *   Endpoints for managing Chart of Accounts and the General Ledger.
    *   Petty cash expense tracking.
*   **Customer Management:**
    *   API endpoints for managing customer data.
*   **Reporting:**
    *   Profit & Loss and Balance Sheet reporting (role-protected).

## 3. Design & Style

This is a backend API, so there is no visual design component. The API is designed to be used by a separate frontend client (e.g., a Single Page Application or mobile app).

## 4. Current Change: Authentication Fixes

This section documents the resolution of the `401 Unauthorized` errors.

*   **Problem:** All authenticated API routes were returning a `401 Unauthorized` error, even with a valid Sanctum token. This was due to a misconfigured authentication guard and a stale configuration cache.
*   **Solution:**
    1.  **Corrected Auth Guard:** The `config/auth.php` file was updated to set the default authentication guard to `api` and to define an `api` guard that uses the `sanctum` driver.
    2.  **Cleared Configuration Cache:** The command `php artisan config:clear` was run to ensure that the server loaded the updated authentication configuration.
    This ensures that Laravel uses token-based authentication for all API requests, resolving the unauthorized errors.

## 5. Current Change: CORS Fix

This section documents the resolution of the Cross-Origin Resource Sharing (CORS) issue.

*   **Problem:** The frontend application was being blocked from making requests to the backend API due to a CORS policy error.
*   **Solution:**
    1.  **Updated CORS Configuration:** The `config/cors.php` file was modified to allow requests from all origins (`'allowed_origins' => ['*']`). This is a common practice for development environments to simplify frontend development.