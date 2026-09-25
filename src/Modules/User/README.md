# User

Customer and admin account management, including profiles, roles, addresses, and 2FA.

## What it does

This module manages every user account on the site — both storefront customers and admin/staff accounts — with roles and permissions, account status (pending, active, blocked), avatar, contact details, and optional two-factor authentication. Customers get a self-service account area where they can update their profile and password, and manage a list of saved delivery addresses with one marked as the default/main address. Admins get a full user management screen in the back office with filtering, status control, and role assignment, alongside dedicated views showing each user's cart and wishlist contents at a glance.

## Key features

- Unified account model for both admin and customer users, distinguished by user type
- Account status workflow: pending, active, blocked
- Self-service profile and password update for logged-in customers
- Multiple saved addresses per user with a single designated main/default address
- Role- and permission-based access control (via Spatie roles/permissions)
- Optional Google Authenticator (2FA) support for account security
- Admin views of a customer's cart and wishlist directly from their user record
