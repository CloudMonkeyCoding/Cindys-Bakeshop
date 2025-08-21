# Cindy's Bakeshop

## Overview
Cindy's Bakeshop is a lightweight PHP/MySQL application for running an online
bakery. Customers can register, browse products, place orders, and track
deliveries while administrators manage the catalogue and fulfilment.

### Features
- Product browsing with images and descriptions
- User registration and login
- Cart and order submission
- Administrative dashboard for inventory, orders and deliveries

## Requirements
- **PHP 8** or higher with the PDO MySQL extension enabled
- **MySQL/MariaDB** server
- A web server such as Apache/Nginx (or PHP's built-in server for testing)

## Directory Structure
- `Database/` – SQL dump (`cindys_bakeshop.sql`) for database schema and sample data
- `PHP/` – reusable PHP scripts that implement database functions
- `adminSide/` – administrator dashboard pages
- `user-cindysbakeshop/` – customer‑facing pages
- `Admin Cindys/`, `UpdatedUser/`, `UpdatedUser1/` – legacy/alternate versions kept for reference

## Installation
1. Create a database named `cindysdb` in your MySQL server.
2. Import the SQL dump using phpMyAdmin or the command line:
   ```sh
   mysql -u root -p cindysdb < Database/cindys_bakeshop.sql
   ```
   Adjust the credentials or database name as needed.
3. Update `PHP/db_connect.php` with your database credentials.

### Running MySQL as a Service (Windows/XAMPP)
To avoid manually launching the XAMPP control panel each time:

1. Open the XAMPP Control Panel.
2. Check the **Svc** box next to **MySQL** (and Apache if desired) and confirm
   the service installation.
3. MySQL will now start automatically on boot. You can manage it from the
   Control Panel, the Windows *Services* applet (`services.msc`), or with
   `net start mysql` / `net stop mysql`.

## Running Locally
Place the repository inside your web server's document root or start a local
development server:
```sh
php -S localhost:8000
```
- Browse the customer-facing site at `http://localhost:8000/user-cindysbakeshop`.
- Access the admin dashboard at `http://localhost:8000/adminSide`.

## Firebase Configuration
Firebase settings are served from a dedicated endpoint rather than being
hardcoded into client pages. The endpoint `PHP/firebase_config.php` reads values
from environment variables and returns them as JSON to authorized requests.

Set the following variables in your environment or server configuration:

- `FIREBASE_API_KEY`
- `FIREBASE_AUTH_DOMAIN`
- `FIREBASE_PROJECT_ID`
- `FIREBASE_STORAGE_BUCKET`
- `FIREBASE_MESSAGING_SENDER_ID`
- `FIREBASE_APP_ID`
- `FIREBASE_MEASUREMENT_ID` (optional)
- `FIREBASE_CONFIG_TOKEN` (optional shared secret; if set, clients must send an
  `X-Firebase-Config-Token` header matching this value)

Client-side pages such as `userSide/LOGIN_SIGNUP/user_login.html` and
`userSide/LOGIN_SIGNUP/signup.html` fetch this endpoint to obtain the Firebase
configuration at runtime.

## Contributing
Pull requests are welcome. Please ensure that any modified PHP files pass
`php -l` for syntax errors before submitting changes.


