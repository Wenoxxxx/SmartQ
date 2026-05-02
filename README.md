# 🚀 SmartQ: Modern Student ID Validation & Queue System

[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.0-777bb4?style=for-the-badge&logo=php)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/mysql-%2300f.svg?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![JavaScript](https://img.shields.io/badge/javascript-%23323330.svg?style=for-the-badge&logo=javascript&logoColor=%23F7DF1E)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![Status](https://img.shields.io/badge/Status-Active-success?style=for-the-badge)](https://github.com/Wenoxxxx/SmartQ)

**SmartQ** is a premium, real-time *Student ID Validation & Queue Management System* designed specifically for campus logistics. It transforms traditional, stressful queuing into a seamless, transparent, and innovative digital experience.

> *Because queues should be smart, not stressful.*

[**📖 View the User Manual (Student & Admin Guide)**](./MANUAL.md)

---

## ✨ Key Features

### 🎓 For Students
- **Real-time Queue Tracking:** Monitor your position and estimated wait time live from your dashboard.
- **Smart Booking:** Select available validation schedules that fit your academic timetable.
- **Instant Alerts:** Get notified the moment you are next in line for validation.
- **Modern Roadmap:** A visual "How-to-Use" guide directly on the landing page.
- **Click-to-Copy Support:** Instant access to team member emails via clipboard integration.

### 🛡️ For Administrators
- **Secure ID Validation:** Approve or reject student validation requests with audit logging.
- **Event Management:** Create and manage schedules with capacity limits and auto-expiration.
- **Live Analytics:** Visual stat cards and trend charts showing queue flow and student distribution.
- **Comprehensive Reporting:** Generate and download detailed PDF/CSV reports for audits.
- **Team Management:** Full control over validation staff and system monitoring.

---

## 🛠️ Tech Stack

### Frontend
- **HTML5 & Vanilla CSS:** Custom-built design system with premium glassmorphism and mesh-gradient aesthetics.
- **JavaScript (ES6+):** Core logic for dynamic UI and real-time interactions.
- **jQuery:** Powering smooth AJAX communications and form processing.
- **Font Awesome:** High-quality vector icons for a professional look.
- **Google Fonts:** Utilizing 'Outfit' and 'Inter' for modern campus typography.

### Backend
- **PHP 8.0+:** Robust server-side logic and API handling.
- **MySQL:** Relational database for persistent and secure data storage.
- **PHPMailer:** SMTP-based email notification system.

---

## 📸 Preview

*Check out the live landing page featuring:*
- **Animated Hero Section:** 3D dashboard mockups and interactive mesh-gradient blobs.
- **Student Roadmap:** A horizontal visual guide to the system workflow.
- **Premium Toast Notifications:** Real-time feedback for all user actions.

---

## 📥 Installation & Setup

Follow these steps to get SmartQ running locally:

### 1. Prerequisites
- Install [XAMPP](https://www.apachefriends.org/) or [WAMP](https://www.wampserver.com/en/).
- Ensure PHP 8.0 or higher is enabled.

### 2. Clone the Repository
```bash
git clone https://github.com/Wenoxxxx/SmartQ.git
```

### 3. Database Setup
- Open **phpMyAdmin** (`localhost/phpmyadmin`).
- Create a new database named `smartq_db`.
- Import the provided SQL file (if available) or run the migration scripts in `scratch/`.
- Configure your database credentials in `server/config/database.php`.

### 4. Running the App
- Move the `SmartQ` folder to your `htdocs` directory.
- Start Apache and MySQL from your XAMPP Control Panel.
- Access the landing page at `http://localhost/SmartQ/client/index.php`.

---

## 👥 Meet the Team

| Name | Role | Focus |
| :--- | :--- | :--- |
| **Althea Hassel Daing** | UI/UX Designer | Premium interfaces & Intuitive design |
| **Alejandra Bernasol** | System Analyst | Workflow design & Efficiency |
| **Ged Shareef Diayon** | Project Hustler | Team coordination & Success |
| **Owen Jerusalem** | Project Lead | Full-stack development & Reliability |

---

## 🤝 Contributing

We welcome contributions to make SmartQ even better!
1. **Fork** the project.
2. Create your **Feature Branch** (`git checkout -b feature/AmazingFeature`).
3. **Commit** your changes (`git commit -m 'Add some AmazingFeature'`).
4. **Push** to the branch (`git push origin feature/AmazingFeature`).
5. Open a **Pull Request**.

---

## 📄 License

This project is intended for institutional and academic use within Bukidnon State University (BukSU).

---

© 2024 SmartQ System. Built with excellence for BukSU.