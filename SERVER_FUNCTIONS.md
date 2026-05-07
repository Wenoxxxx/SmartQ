# SmartQ Server-Side Documentation

This document provides a comprehensive overview of the server-side architecture and the specific functions of each component in the SmartQ system.

## 📁 Directory Structure
The server-side logic is located in the `/server` directory, organized as follows:
- `/api`: Core API endpoints for frontend-backend communication.
- `/config`: Database connection and environment configurations.
- `/utils`: Helper functions and common utilities.
- `/vendor`: Composer dependencies.

---

## ⚙️ Core Configuration & Utilities

### [database.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/config/database.php)
- **Function**: Manages the PDO database connection.
- **Key Features**: 
  - Uses hardcoded credentials for seamless deployment (optimized for InfinityFree).
  - Implements the Singleton-like pattern for connection retrieval via `getConnection()`.
  - Sets UTF-8 character encoding and PDO error modes to Exception.

### [cors.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/utils/cors.php)
- **Function**: Handles Cross-Origin Resource Sharing (CORS) headers.
- **Key Features**:
  - Allows requests from any origin (`*`).
  - Specifies allowed methods (GET, POST, PUT, DELETE, OPTIONS).
  - Handles pre-flight OPTIONS requests.

### [mailer.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/utils/mailer.php)
- **Function**: Provides email sending capabilities using PHPMailer.
- **Key Features**:
  - Configures SMTP settings (Gmail/Custom SMTP).
  - Supports HTML templates for notifications.

---

## 🔐 Authentication & Identity

### [login.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/auth/login.php) / [login_handler.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/auth/login_handler.php)
- **Function**: Handles user authentication for both Admins and Students.
- **Logic**: Checks the `admin` table first; if no match, checks the `students` table. Supports both plaintext and `password_verify()` hashes.

### [logout.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/auth/logout.php)
- **Function**: Destroys the user session and clears authentication cookies.

### [signup_handler.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/auth/signup_handler.php)
- **Function**: Registers new student accounts.
- **Logic**: Validates input data, hashes passwords, and initializes default status (Not Validated).

### [upload_avatar.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/users/upload_avatar.php)
- **Function**: Processes profile picture uploads for all users.
- **Logic**: Validates file types (JPG, PNG, WEBP), resizes/compresses images, and updates the database path.

---

## 📅 Queue & Schedule Management

### [create_schedule.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/events/create_schedule.php)
- **Function**: Allows admins to create new validation time slots.
- **Parameters**: Date, Start/End Time, Slot Limit, and Room Location.

### [book_schedule.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/queue/book_schedule.php)
- **Function**: Enables students to reserve a spot in a specific schedule.
- **Validation**: 
  - Checks if the schedule is full.
  - Ensures the student doesn't already have an active booking.
  - Blocks booking if the schedule time has already passed.
- **Result**: Generates a unique queue number and updates student status to "Pending Review".

### [advance_queue.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/events/advance_queue.php)
- **Function**: Moves the queue forward during a live validation session.
- **Logic**: Updates the current "now serving" number and notifies the next student.

### [cancel_booking.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/queue/cancel_booking.php)
- **Function**: Allows students to release their spot, making it available for others.

---

## 🎓 Student & Validation Logic

### [update_status.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/students/update_status.php)
- **Function**: The primary engine for validation approval/rejection.
- **Logic**: When an admin validates a student, it updates their `status_id` (1=Validated, 2=Rejected/Not Validated, 3=Pending) and logs the admin ID who performed the action.

### [get_student_details.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/students/get_student_details.php)
- **Function**: Fetches comprehensive profile data, including course, college, and validation history.

### [send_reminder.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/students/send_reminder.php)
- **Function**: Triggers an automated notification to students who are next in line or have an upcoming schedule.

---

## 📊 Reporting & Analytics

### [get_report_data.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/reports/get_report_data.php)
- **Function**: Aggregates data for the Admin Dashboard charts (e.g., validations per day, college distribution).

### [download.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/reports/download.php) / [download_report.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/events/download_report.php)
- **Function**: Generates downloadable CSV or PDF reports of validation sessions and student lists.

---

## ✉️ Contact System

### [send_message.php](file:///c:/xampp/htdocs/SmartQ%20-%20Copy/server/api/contact/send_message.php)
- **Function**: Processes messages from the landing page contact form.
- **Logic**: Stores the message in the database and optionally forwards it to the admin's email.

---

## 💡 Key Concepts & Technologies

This section explains some of the core technical concepts used throughout the SmartQ backend.

### 🔌 PDO (PHP Data Objects)
- **Definition**: PDO is a database access layer that provides a uniform way to interact with different databases (MySQL, PostgreSQL, etc.) using PHP.
- **Why we use it**: 
  - **Security**: It is the primary tool for preventing **SQL Injection** attacks through the use of Prepared Statements.
  - **Abstraction**: It allows the application to be more flexible. If the database type changes, only the connection string usually needs to be updated.
  - **Error Handling**: It provides robust exception handling, making it easier to debug database issues.
- **Analogy**: Think of PDO as a **Universal Power Adapter**. No matter if the wall socket is from the US, UK, or EU (different databases), the adapter ensures your device (your code) gets the power it needs without you having to rebuild the device for every country.

### 🛡️ Prepared Statements
- **Concept**: Instead of sending a full SQL query with data mixed in, we send a "template" of the query to the database, and then send the data (parameters) separately.
- **Benefit**: This ensures that user-provided data is never treated as executable code by the database, effectively blocking hackers from manipulating your database queries.
- **Analogy**: It's like a **Fill-in-the-Blanks Form**. The database receives the form first. Even if a user tries to write a "malicious command" in one of the blanks, the database treats it strictly as "text" inside that box, never as a new instruction to change the form itself.

### 📦 JSON (JavaScript Object Notation)
- **Concept**: A lightweight data-interchange format that is easy for humans to read and write, and easy for machines to parse.
- **Role in SmartQ**: All API endpoints in this project communicate using JSON. The server sends JSON data to the frontend, which then uses JavaScript to update the UI without reloading the page.
- **Analogy**: JSON is like a **Takeout Menu**. It’s a simple, organized list that both the Chef (Server) and the Customer (Client) can read. It tells the customer exactly what’s available in a format that's easy to understand.

### 🌐 CORS (Cross-Origin Resource Sharing)
- **Concept**: A security feature that controls which "origins" (websites) are allowed to access the server's resources.
- **Implementation**: Managed via `cors.php` to allow the client-side application to communicate with the server-side API securely.
- **Analogy**: Think of CORS as a **Bouncer at a VIP Club**. He has a list of "approved guests" (websites). If a website isn't on the list, the bouncer won't let it inside to talk to the VIPs (the Server's data).

### 🏛️ Singleton-like Pattern (Database Connection)
- **Concept**: A design pattern that ensures a class has only one instance and provides a global point of access to it.
- **Role in SmartQ**: Used in `database.php` via `getConnection()` to ensure we don't open multiple, redundant connections to the database for a single request, which saves server resources.
- **Analogy**: It's like having a **Single Office Water Cooler**. Instead of every employee buying their own private water bottle (which is wasteful), everyone goes to the same cooler. This ensures resources aren't wasted and everyone gets exactly what they need from one reliable source.
