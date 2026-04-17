<<<<<<< HEAD
# 🌌 EcoTrack - Household Resource Tracker
EcoTrack is a premium, full-stack web application designed to help households monitor and manage their consumption of vital resources like **Electricity, Water, and Gas**. With a focus on modern aesthetics and real-time analytics, EcoTrack empowers users to reduce their carbon footprint and save on utility costs.
![Registration Preview](images/registration.png)
![Login page](images/login.png)
![Reset password page](reset.png)
![Reset password action page](images/resetpass.png)
![pass reset   ](images/passresetlink.png)
![set new pass](images/newpasspg.png)
![dashboards ](images/dashboard1.png)
![dashboards ](images/dashboard2.png)
![dashboards ](images/dashboard3.png)
![dashboards ](images/dashboard4.png)
![device management](images/devicemanagement.png)
![Setup limits popup](images/alert.png)   
![notification mailer](images/notificationmail.png)
## 🚀 Key Features

- **📊 Dynamic Consumption Analytics**: Visualize your resource usage over time with high-fidelity, interactive charts powered by Chart.js.
- **🔌 Device-Level Monitoring**: Register individual household devices (appliances, faucets, etc.) and track their specific consumption based on custom rates.
- **🔔 Smart Alerts & Limits**: Set daily consumption thresholds for each resource type and receive automated email notifications when you exceed them.
- **🕒 Detailed History**: Access comprehensive hourly breakdowns and recent activity logs to identify usage peaks.
- **🔐 Secure User Management**: Robust authentication system with JWT-based sessions, secure registration, and email-based password recovery.
- **✨ Premium UI/UX**: A state-of-the-art "Gravity" dark theme with glassmorphism, smooth animations, and a responsive mobile-first design.

## 🛠️ Tech Stack

- **Backend**: PHP 8.x (RESTful API architecture)
- **Frontend**: HTML5, Vanilla JavaScript (Fetch API), CSS3 (Custom Gravity Framework)
- **Design Framework**: Bootstrap 5.3 + Custom Styling
- **Visualization**: Chart.js
- **Database**: MySQL / MariaDB
- **Emailing**: PHPMailer

## 📋 Prerequisites

Before you begin, ensure you have the following installed:
- [XAMPP](https://www.apachefriends.org/index.html) or any PHP & MySQL environment.
- [Composer](https://getcomposer.org/) (for PHPMailer dependencies).

## ⚙️ Installation & Setup

1. **Clone the Repository**
   ```bash
   git clone https://github.com/swarya29/household-resource-tracker.git
   cd household-resource-tracker
   ```

2. **Database Configuration**
   - Open phpMyAdmin or your SQL client.
   - Create a new database named `resource_tracker`.
   - Import the `resource_tracker.sql` file provided in the root directory.

3. **Environment Variables**
   - Rename `.env.example` to `.env`.
   - Update the database credentials and SMTP settings for the alert engine:
     ```env
     DB_HOST=localhost
     DB_NAME=resource_tracker
     DB_USER=root
     DB_PASS=
     
     # SMTP Settings (for PHPMailer)
     SMTP_HOST=smtp.gmail.com
     SMTP_USER=your-email@gmail.com
     SMTP_PASS=your-app-password
     SMTP_PORT=587
     ```

4. **Install Dependencies**
   - Run the following command to install PHPMailer:
     ```bash
     php install_phpmailer.php
     ```

5. **Run the Application**
   - Move the project folder to `xampp/htdocs`.
   - Start Apache and MySQL from the XAMPP Control Panel.
   - Open your browser and navigate to `http://localhost/household-resource-tracker`.

## 📂 Project Structure

- `/api`: Core logic, endpoints (devices, logs, limits), and the alert engine.
- `/assets`: UI assets and previews.
- `index.php`: The main dashboard and analytics hub.
- `gravity.css`: The custom design system powering the application's unique look.
- `/vendor`: PHPMailer and other dependencies (generated after installation).

## 🛡️ Security

EcoTrack implements several security best practices:
- **Prepared Statements**: Protection against SQL Injection.
- **Input Sanitization**: Cleansing of all user-submitted data.
- **Session Management**: Secure, server-side session handling.
- **Environment Isolation**: Sensitive configuration stored in `.env`.

## 👥 Contributors

- **Swarya Patil CEB 446**: Project Lead & Backend Architecture. Responsible for the core system design and logic.
- **Ishan Pishordy CEB 450**: API Developer. Developed the asynchronous REST endpoints and calculation engines.
- **Tanishq Pote CEB 451 **: Frontend & UI Designer. Created the custom "Gravity" theme and interactive dashboard components.
- **Adarsh ThayilCEB 467**: Database & Security. Managed DB schema design and implemented security best practices.

---

Developed with ❤️ for a sustainable future.
=======
 🌱 EcoTrack – Smart Resource Tracker

 📌 Overview

EcoTrack is a web-based application developed to monitor and manage water and energy consumption. It provides users with a centralized dashboard to track usage, analyze trends, and make informed decisions. The system focuses on promoting efficient resource usage and raising awareness about sustainable consumption habits.

---

🏗️ System Architecture & Design

EcoTrack follows a **client-server architecture**:

* **Frontend (Client Side):**

  * Built using HTML, CSS, and Bootstrap
  * Handles user interface and data input
  * Displays charts and tables

* **Backend (Server Side):**

  * Developed using PHP
  * Processes user requests and handles business logic
  * Manages authentication and data operations

* **Database Layer:**

  * MySQL database
  * Stores user details and usage records
  * Connected using MySQLi

---

🧩 Modules of the System

1. Authentication Module

* User registration and login
* Session management
* Secure access to dashboard

2. Dashboard Module

* Displays usage data in graphical format
* Shows water and energy trends using charts

3. Data Management Module

* Add new usage data
* Edit existing records
* Delete records
* Store device-specific information

4. Device Energy Calculator

* Calculates energy usage using:

  Energy (kWh) = (Power × Time) / 1000

* Helps users estimate consumption of individual devices

5. Alert System

* Detects high energy usage
* Displays warning messages to users

---

🔗 APIs / Libraries Used

* **Chart.js** → For graphical data visualization
* **Bootstrap** → For responsive UI design

*(No external APIs used; calculations handled internally)*

---

⚙️ Challenges Faced

* Implementing user-specific data access securely
* Managing sessions across multiple pages
* Integrating charts with dynamic database data
* Designing a clean and responsive UI
* Handling CRUD operations efficiently
* Ensuring proper data flow between frontend and backend

---

🔐 Login Page

* User enters credentials to access the system

📊 Dashboard

* Displays usage graph and summary

➕ Add Data

* Form to input water, energy, and device usage

📋 Device History

* Table showing all records with edit/delete options

⚡ Energy Calculator

* Calculates energy based on device usage

🚨 Alerts

* Warning message for high energy consumption

---

🌍 Conclusion

EcoTrack is a practical solution for tracking and managing resource usage. It combines data visualization, user management, and real-time calculations to help users understand and optimize their consumption habits.

---
