# Role

Core admin-access control module for grouping permissions into assignable roles.

## What it does

This is a foundational infrastructure module that lets administrators define named roles — such as Editor, Manager, or Support — and see which permissions and which users each role includes. It's the other half of the platform's role-based access control system alongside the Permission module: instead of assigning individual permissions to every admin user one by one, admins group related permissions into a role and then assign that role to users. Roles can be viewed, created, edited, soft-deleted, and restored from a dedicated admin screen, with each role showing its linked permissions and members for easy auditing.

## Key features

- Central registry of named roles used throughout the admin panel
- Multi-language display names
- View which permissions and which users belong to a given role
- Soft-delete and restore support for safe role management
- Works together with the Permission module to power role-based access control
