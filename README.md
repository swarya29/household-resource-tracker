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

