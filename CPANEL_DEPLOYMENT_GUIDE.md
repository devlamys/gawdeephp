# Gawdee Storefront - cPanel Deployment Guide

This guide details how to deploy the Gawdee PHP application on any **cPanel web hosting server** using **MySQL / MariaDB**.

---

## 📋 Pre-requisites

- A cPanel hosting account with **MySQL Database** access and **phpMyAdmin**.
- PHP 8.1+ enabled on your cPanel domain/subdomain.
- FTP / File Manager access to cPanel.

---

## 🚀 Step 1: Create MySQL Database in cPanel

1. Log into your **cPanel Account**.
2. Under the **Databases** section, click **MySQL® Database Wizard**.
3. **Create a Database**:
   - Enter a database name (e.g. `cpaneluser_gawdee`) & click **Next Step**.
4. **Create Database User**:
   - Enter a username (e.g. `cpaneluser_gawdeeuser`) & enter a strong password.
   - Click **Create User**.
5. **Assign Privileges**:
   - Check **ALL PRIVILEGES** & click **Make Changes**.
   - Save your **Database Name**, **Username**, and **Password** for the next step.

---

## 📥 Step 2: Import Database Data via phpMyAdmin

1. In cPanel, open **phpMyAdmin** from the Databases section.
2. Select your newly created database from the left menu (`cpaneluser_gawdee`).
3. Click on the **Import** tab at the top.
4. Click **Choose File** and select `gawdee_mysql_dump.sql` from your project folder.
5. Click **Import** (or **Go** at the bottom).
6. You will see a success message: *"Import has been successfully finished."*

---

## ⚙️ Step 3: Upload Files & Setup `.env` Configuration

1. Upload all project files to your cPanel directory (e.g. `public_html` or a subdomain folder).
2. Create or copy `.env.example` to `.env` in the root folder of your project on cPanel.
3. Edit the `.env` file and set your MySQL credentials:

```env
APP_NAME="Gawdee Storefront"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Enable MySQL Database Driver
DB_DRIVER=mysql

# cPanel MySQL Connection Details
DB_HOST=localhost
DB_PORT=3306
DB_NAME=cpaneluser_gawdee
DB_USER=cpaneluser_gawdeeuser
DB_PASSWORD=your_secure_database_password
DB_CHARSET=utf8mb4

# Secret Encryption Key
SESSION_SECRET=change_this_to_a_random_secret_key
```

---

## 🔄 Optional: Run Live Data Sync Script

If you make changes locally in SQLite and want to export or auto-sync data to MySQL at any time:

1. **Via Browser**:
   Open `https://your-domain.com/scripts/migrate-sqlite-to-mysql.php` in your browser.
2. **Via Terminal / SSH**:
   Run `php scripts/migrate-sqlite-to-mysql.php`.

---

## ✅ Step 4: Verification

1. Open your website in the browser: `https://your-domain.com`.
2. Verify products, categories, homepage CMS, and checkout function cleanly.
3. Access the Admin Panel at `https://your-domain.com/admin` to manage orders and settings.
