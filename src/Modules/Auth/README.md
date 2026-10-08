# Auth

Complete authentication system: login, registration, password reset and email verification.

## What it does

This module handles everything related to user accounts logging in and staying secure. It provides standard email/password registration and login (both as web pages and as a JSON API for a headless/SPA frontend), "forgot password" email flows, email address verification. Repeated failed login and password-reset attempts are automatically throttled to protect against brute-force attacks. When a guest checks out or builds a wishlist and then logs in or registers, their cart and wishlist are automatically merged into their new account so nothing is lost.

## Key features

- Email/password registration and login, with a matching JSON API for SPA/mobile clients
- Forgot-password email flow with secure, expiring reset links
- Email verification flow with signed, expiring verification links
- Automatic brute-force throttling on login and password-reset requests
- Seamless merging of a guest's cart and wishlist into their account on login/registration
- Session-based (web) and token-based (Sanctum API) authentication guards
- Localized pages: when the host app configures `mcamara/laravel-localization` (`config/laravellocalization.php`), the login, registration, forgot-password and reset-password pages live under the locale prefix (`/en/login`, `/en/register`, …) with English and Ukrainian texts (`auth::pub.*`); form posts use the same prefix but never redirect between languages. Without that config the pages stay on the plain URLs. The JSON API (`/api/login`, `/me`, …) and the signed email-verification links are never prefixed.
