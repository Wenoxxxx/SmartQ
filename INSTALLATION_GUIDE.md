# 🚀 SmartQ Manual Installation Guide

This guide provides step-by-step instructions for setting up the **SmartQ** system on a local development environment (e.g., XAMPP) or a production server.

---

## 🛠️ Prerequisites

Ensure you have the following installed on your system:
- **Web Server:** XAMPP, WAMP, or any Apache/Nginx environment.
- **PHP:** Version 8.0 or higher.
- **Database:** MySQL/MariaDB.
- **Dependency Manager:** [Composer](https://getcomposer.org/) (required for PHPMailer and Dotenv).

---

## 📦 Installation Steps

### 1. Clone or Copy the Project
Place the project folder inside your web server's root directory:
- **XAMPP:** `C:\xampp\htdocs\SmartQ`
- **Linux/Nginx:** `/var/www/html/SmartQ`

### 2. Install Dependencies
Navigate to the `server/` directory and run the following command to install required PHP libraries:
```bash
cd server
composer install
```

### 3. Database Setup
1. Open **phpMyAdmin** or your preferred SQL client.
2. Create a new database named `smartq_db`.
3. Import the `database.sql` file located in the project root into your new database.

### 4. Environment Configuration
1. Locate the `.env.example` file in the root directory.
2. Rename it to `.env` (or create a new file named `.env`).
3. Open `.env` and fill in your local credentials:

```env
# Database
DB_HOST=localhost
DB_NAME=smartq_db
DB_USER=root
DB_PASS=

# SMTP (Gmail Example)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password
SMTP_FROM=your_email@gmail.com
SMTP_FROM_NAME="SmartQ Admin"

# Google Auth (Optional)
GOOGLE_CLIENT_ID=your_id_here
GOOGLE_CLIENT_SECRET=your_secret_here

# reCAPTCHA (Optional)
RECAPTCHA_SITE_KEY=your_key_here
RECAPTCHA_SECRET_KEY=your_secret_here
```

---

## 🔑 External Service Configuration

### 1. SMTP (Email Notifications)
If using Gmail, you must:
1. Enable **2-Step Verification** on your Google Account.
2. Generate an **App Password** (Select "Mail" and "Other").
3. Use this 16-character password in the `SMTP_PASS` field.

### 2. Google OAuth (Sign in with Google)
1. Go to the [Google Cloud Console](https://console.cloud.google.com/).
2. Create a project and navigate to **APIs & Services > Credentials**.
3. Create an **OAuth 2.0 Client ID** (Web Application).
4. Add `http://localhost` to **Authorized JavaScript origins**.
5. Copy the Client ID and Secret to your `.env`.

### 3. Google reCAPTCHA v2
1. Go to the [reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin).
2. Register a new site with **reCAPTCHA v2 ("I'm not a robot" Checkbox)**.
3. Add `localhost` to the list of domains.
4. Copy the Site Key and Secret Key to your `.env`.

---

## 🚦 Testing the System
1. Start your Apache and MySQL modules.
2. Open your browser and visit: `http://localhost/SmartQ/client/index.php`
3. Try logging in with the default admin account (if provided in `database.sql`) or sign up as a new student.

---

## 🛠️ Troubleshooting
- **White Page / 500 Error:** Check if the `vendor/` folder exists in the `server/` directory. Run `composer install` if missing.
- **Database Connection Error:** Verify your `.env` credentials and ensure the MySQL service is running.
- **Email Not Sending:** Check your SMTP settings and ensure your firewall allows outgoing traffic on port 587.
