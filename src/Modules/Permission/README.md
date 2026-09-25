# Permission

Core admin-access control module for defining fine-grained permissions.

## What it does

This is a foundational infrastructure module that lets administrators define named permissions and see which roles and users each permission is assigned to. It underpins the platform's role-based access control system, giving site owners fine-grained control over exactly what each admin user is allowed to do. Permissions can be viewed, created, edited, soft-deleted, and restored from a dedicated admin screen, with each permission showing its linked roles and users for easy auditing.

## Key features

- Central registry of named permissions used throughout the admin panel
- Multi-language display names
- View which roles and which users hold a given permission
- Soft-delete and restore support for safe permission management
- Works together with the Role module to power role-based access control
