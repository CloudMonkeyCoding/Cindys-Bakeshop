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
- `userSide/` – customer‑facing pages
- `user_faces/` – uploaded user face images
- `vendor/` – Composer dependencies

## Database Schema Summary
The SQL dump defines the following tables:

- **blacklist** – banned users with reason and IP (`Blacklist_ID`, `User_ID`, `Blacklist_reason`, `IP_Address`)
- **cart_item** – items placed in a shopping cart (`Cart_Item_ID`, `Cart_ID`, `Product_ID`, `Quantity`)
- **delivery** – delivery status for orders (`Delivery_ID`, `Order_ID`, `Status`, `Delivery_Date`, `Delivery_Personnel`)
- **delivery_personnel** – links delivery staff to user accounts (`Delivery_Personnel_ID`, `User_ID`)
- **inventory** – stock levels for products (`Inventory_ID`, `Product_ID`, `Stock_Quantity`)
- **order** – customer orders (`Order_ID`, `User_ID`, `Order_Date`, `Status`)
- **order_item** – products within an order (`Order_Item_ID`, `Order_ID`, `Product_ID`, `Quantity`, `Subtotal`)
- **product** – product catalog (`Product_ID`, `Name`, `Description`, `Price`, `Stock_Quantity`, `Category`, `Image_Path`)
- **shopping_cart** – cart ownership (`Cart_ID`, `User_ID`)
- **store_staff** – identifies staff accounts (`Store_Staff_ID`, `User_ID`)
- **transaction** – payment records (`Transaction_ID`, `Order_ID`, `Payment_Method`, `Payment_Status`, `Payment_Date`, `Amount_Paid`, `Reference_Number`)
- **user** – registered users and preferences (`User_ID`, `Name`, `Email`, `Password`, `Address`, `Language`, `Theme`, `Notify_Order_Status`, `Notify_Promotions`, `Notify_Feedback`, `Warning_Count`, `Face_Image_Path`)
- **product_ratings** – aggregated product reviews (`Rating_ID`, `Product_Name`, `Average_Rating`, `Total_Review`, `Comments`)
- **favorites** – user-saved products (`Favorite_ID`, `User_ID`, `Product_ID`)
- **order_cancellation** – cancellation requests (`Cancellation_ID`, `Order_ID`, `User_ID`, `Reason`, `Cancellation_Date`, `Status`)

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
- Browse the customer-facing site at `http://localhost:8000/userSide`.
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


