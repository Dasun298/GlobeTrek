# GlobeTrek — Travel Management System

**Portfolio project · PHP 8.2+ · MySQL/MariaDB · HTML/CSS · JavaScript**

GlobeTrek is a role-based travel booking and administration web application, developed as a full-stack learning/portfolio project. It includes a responsive public website and separate Customer, Staff, and Admin modules.

> **Portfolio/demo, not a production payment service.** The booking/payment fields record requests and statuses only: no real card charge or online payment verification is implemented. Do not enter real card or identity details in a public demo.

## Features

| Role | Features |
| --- | --- |
| Guest | Browse tour packages and destinations; send a contact inquiry; register or log in |
| Customer | Request travel bookings, request hotel stays with vehicle selection, view and (while pending) edit/cancel eligible bookings, update profile/password |
| Staff | Dashboard, package/hotel catalog administration with image uploads, booking management, vehicles, inquiry notes, reports, profile |
| Admin | Dashboard and analytics, tour/hotel bookings, catalog and vehicle management, customer/staff management, payment record review, inquiries, reports |

**Security-related improvements in this public edition:** no published real account/password/payment data; hashed-only login; role checks; prepared statements and CSRF validation on the maintained booking/admin/staff routes; safe legacy-route redirects; and PHP execution blocked in uploads on Apache. This is **not** a complete security certification.

## Screenshots

To showcase your own running installation, capture: Homepage, Tour Packages, Booking Form, Customer Bookings, Admin Dashboard, Staff Dashboard. Save them to `docs/screenshots/` and add Markdown image references here. Never include real customer details in screenshots.

## Getting started (Windows 10 / XAMPP)

1. Install **XAMPP (PHP 8.2+ recommended)** and start **Apache** and **MySQL**. Ensure MySQLi is enabled in your PHP installation (the PHP CLI in XAMPP normally includes it).
2. Copy the folder `GlobeTrek` into `C:\xampp\htdocs\` so `C:\xampp\htdocs\GlobeTrek\index.php` exists.
3. Open `http://localhost/phpmyadmin/`, import `database/globetrek_demo.sql` into a **fresh, empty** environment. It uses its own `globetrek_portfolio` database, separate from your existing `globetrek` database. The SQL creates a separate `globetrek_portfolio` database and seeds catalog data; it **contains no customer or admin accounts**. **Never import it over your current database without taking a backup.**
4. Copy `includes/db.example.php` to `includes/db.local.php`. Adjust host, database name, username, password, and port for YOUR local MySQL configuration. `db.local.php` is intentionally excluded from Git.
5. In Windows Command Prompt / PowerShell, create your own administrator and staff passwords with:

   ```powershell
   cd C:\xampp\htdocs\GlobeTrek
   C:\xampp\php\php.exe scripts\create_account.php admin
   C:\xampp\php\php.exe scripts\create_account.php staff
   ```

   The script prompts for a username and **unique 12–72 character password**; nothing is preinstalled. Windows terminals may display typed passwords, so do not reuse a personal password.
6. Optional: test DB setup with `C:\xampp\php\php.exe scripts\check_setup.php`.
7. Open `http://localhost/GlobeTrek/`. Customer accounts can be created through **Register**. On **Login**, select Customer/Staff/Admin as appropriate.

## Key pages

- Public: `index.php`, `packages.php`, `contact.php`, `login.php`, `register.php`
- Customer: `booking.php`, `hotel_booking.php` (POST handler), `my_bookings.php`, `update_profile.php`
- Staff: `staff/dashboard.php`, `staff/packages.php`, `staff/hotels.php`, `staff/bookings.php`, `staff/profile.php`
- Admin: `admin/dashboard.php`, `admin/catalog.php`, `admin/bookings.php`, `admin/payments.php`, `admin/analytics.php`

The old `dashboard.php`, `payment.php`, `edit_booking.php`, `cancel_booking.php`, and `edit_package.php` routes are retained **only as safe navigation redirects** to the maintained flows. They do not accept legacy GET commands to change database records.

## Local test checklist

- Home and tour cards/images load; package search works.
- Register a customer, log in, book a tour, inspect **My Bookings**, then edit/cancel a pending request.
- Make a hotel request using an available hotel and available vehicle.
- Send a Contact message (subject required); check the inquiry in Staff/Admin.
- Create Admin/Staff through the CLI script; check their dashboards and role boundaries.
- Use synthetic/dummy customer data; test package add/edit, booking status changes, and reports.

## Limits, privacy & attribution

- There is **no payment gateway, online card charge, receipt verification, email sending service, SMS service, or production hosting** in this version. Payment records are manual status entries.
- The sample prices and hotel/vehicle records are demonstration values, not live prices or availability guarantees.
- Original travel photographs/image filenames from the provided project are included to preserve the visual design. **Review image licenses and permissions before making this repository public.** Replace any photos you do not have permission to redistribute with your own/appropriately licensed alternatives.
- The real/private `globetrek.sql` dump has deliberately been **excluded**. No live accounts, passwords, contact inquiries, personal ID numbers, or payment history are supplied.
- Before production hosting, do a complete security review, implement server-side global login rate limiting, HTTPS and secure sessions, audit logs, privacy controls, email delivery, and a real compliant payment gateway where applicable.

## Project structure

```text
GlobeTrek/
├── admin/        # Admin-only operations
├── staff/        # Staff-only operations
├── css/          # Page styles
├── js/           # Frontend interactions
├── includes/     # Shared DB and authentication helpers
├── database/     # Clean schema and public sample catalog
├── scripts/      # CLI account creation helper
├── uploads/      # Existing site imagery (review licenses)
├── README.md
└── index.php
```

**Built with:** PHP, MySQL/MariaDB, HTML5, CSS3, JavaScript, XAMPP and VS Code.
