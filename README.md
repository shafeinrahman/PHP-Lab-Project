# CSE 391: Programming for the Internet - Assignment 3
## Car Workshop Online Appointment System (PHP & MySQL)

A complete, production-grade online appointment management system for a car workshop, built with **HTML5, CSS3, JavaScript (ES6+ AJAX), PHP (PDO), and MySQL**.

---

## 📋 Project Specifications & Rubric Coverage

This project satisfies all requirements outlined in the course assignment:

### Problem Scenario
The workshop employs **5 senior mechanics**. Each mechanic is strictly restricted to a **maximum of 4 active cars (clients) per day**. The online system prevents workshop chaos by enabling clients to select their desired mechanic in advance, showing real-time slot availability, and enforcing daily quota constraints.

### 1. User Panel
- **Client Input Form**:
  - Client Full Name
  - Client Address
  - Phone Number (*validated: digits/numbers only*)
  - Car License / Registration Number (*validated: non-empty*)
  - Car Engine Number (*validated: digits/numbers only*)
  - Appointment Date (*validated: calendar date format, current/future dates only*)
  - Desired Senior Mechanic (*interactive radio cards with live free places counter*)
- **Real-Time Client-Server Communication (AJAX)**:
  - When the client selects or modifies the appointment date, an AJAX request immediately queries the database and dynamically updates each mechanic's card to display remaining free places (`4 - booked`).
  - Mechanics who reach 4 booked cars are visually flagged as **`⛔ FULL (0/4 free)`** and automatically disabled from selection.
- **Duplicate Booking Prevention**:
  - After submission (and pre-checked via AJAX), the server verifies whether the client (by Car License or Phone) already holds an appointment on that date across any mechanic. If so, submission is rejected with a clear notification.
- **Capacity & Occupancy Enforcement**:
  - If a selected mechanic reaches full capacity (4 cars), the system blocks overbooking and instructs the client to choose another senior mechanic or date.
- **(Basic) Help Facilities**:
  - Step-by-step booking guide, daily limit policies, FAQs, and workshop contact assistance.
- **Client Lookup Tool (`status.php`)**:
  - Clients can search by car registration or phone to view their confirmed appointments and assigned mechanic.

### 2. Admin Panel (`/admin`)
- **Appointment Management Table**:
  - As explicitly required, displays:
    1. **Client Name**
    2. **Phone**
    3. **Car Registration Number (License Plate)**
    4. **Appointment Date**
    5. **Mechanic Name**
    - Plus: Engine Number, Address, Booking Status, and Admin Actions.
- **Change Date & Assigned Mechanic (Requirement 2.b)**:
  - Admin can change the appointment date and re-assign the car to any other available mechanic for any client.
  - The edit modal dynamically verifies the target mechanic's daily load for the new date and prevents assigning if that mechanic already has 4 active cars.
- **Daily Workload Monitor**:
  - Visual capacity bars for each of the 5 senior mechanics showing their daily utilization.
- **Live Search & Filters**:
  - Instant client search, date filter, and mechanic filter.

---

## 🗂️ File Directory Structure

```
h:\A3\
├── config\
│   └── db.php                     # PDO connection handler and base URL resolver
├── database\
│   ├── schema.sql                 # Complete database schema and seed data
│   └── setup.php                  # 1-Click web & CLI database installer and test seeder
├── api\
│   ├── get_mechanic_slots.php     # AJAX endpoint: live mechanic free slots for date
│   └── check_appointment.php      # AJAX endpoint: pre-check client duplicate booking
├── includes\
│   ├── functions.php              # Business logic, validations, atomic queries
│   ├── header.php                 # Navigation bar and brand header
│   └── footer.php                 # Footer, workshop hours, and contact details
├── assets\
│   ├── css\
│   │   └── style.css              # Responsive modern styling, badges, cards, modals
│   └── js\
│       ├── booking.js             # Client-side AJAX slot checker & form validations
│       └── admin.js               # Admin live search, filters, and edit modal logic
├── index.php                      # User Panel: Appointment booking, live slots & help
├── status.php                     # User Panel: Client appointment tracking & lookup
├── admin\
│   ├── index.php                  # Admin secure login page
│   ├── dashboard.php              # Admin control center, appointment table, stats
│   ├── edit_appointment.php       # Processes date & mechanic re-assignment
│   ├── delete_appointment.php     # Deletes / cancels an appointment
│   ├── get_slots.php              # AJAX endpoint for admin edit modal
│   └── logout.php                 # Admin session logout
└── README.md                      # Complete documentation & test guide
```

---

## 🚀 Setup & Installation Instructions

### Prerequisites
- Any standard PHP & MySQL stack (e.g., **XAMPP**, **WAMP**, **Laragon**, or **PHP 7.4+ with MySQL/MariaDB**).

### Option A: Using XAMPP / WAMP (Recommended)
1. Copy or move the `A3` folder into your web server's document root:
   - For XAMPP: `C:\xampp\htdocs\A3`
   - For WAMP: `C:\wamp64\www\A3`
2. Start **Apache** and **MySQL** from your XAMPP/WAMP Control Panel.
3. Open your browser and navigate to the automated setup script:
   ```
   http://localhost/A3/database/setup.php
   ```
4. Click **"⚡ Run Database Setup & Seeder"**.
   - This automatically creates the `car_workshop_db` database, creates all tables, seeds the 5 senior mechanics, and configures the default admin account.
   - *Tip:* Leave "Seed realistic test appointments" checked to immediately populate sample data (e.g. 1 mechanic fully booked, 1 busy mechanic) for instant evaluation!
5. Access the application:
   - **Client Booking Page:** `http://localhost/A3/index.php`
   - **Admin Control Panel:** `http://localhost/A3/admin/index.php`

### Option B: Using PHP Built-In Server
If running from the command line:
1. Ensure MySQL is running on `localhost:3306` with user `root` (password blank, or update `config/db.php`).
2. Run database setup:
   ```bash
   cd h:\A3
   php database/setup.php
   ```
3. Start the PHP server:
   ```bash
   php -S localhost:8000
   ```
4. Access: `http://localhost:8000/index.php` and `http://localhost:8000/admin/index.php`.

---

## 🔑 Default Credentials

| Role | Username | Password | Access URL |
|---|---|---|---|
| **Workshop Admin / Manager** | `admin` | `admin123` | `http://localhost/A3/admin/` |

---

## 🧪 Testing Checklist (How to Verify All Features)

### 1. Test Real-Time Slot Display (User Panel)
1. Navigate to `index.php`.
2. Notice the 5 senior mechanics displayed with their live free places (e.g. `🟢 4/4 free places`).
3. Change the **Appointment Date** to today or another date. Notice how the mechanic cards automatically refresh via AJAX to reflect booking counts for that specific day.
4. If a mechanic has 4 cars on that day, their card is disabled and marked `⛔ FULL (0/4 free)`.

### 2. Test Input Validations (Part 2)
1. Leave required fields empty and click submit &rarr; red feedback appears.
2. Enter letters in the **Phone Number** field &rarr; the form blocks non-numeric keystrokes and alerts that only numbers are allowed.
3. Enter letters in the **Car Engine Number** field &rarr; alerts that only numbers are allowed.
4. Try to submit without selecting a mechanic &rarr; error prompt requires choosing an available mechanic.

### 3. Test Duplicate Booking Restriction
1. Book an appointment for client `Tanvir`, Car License `DHK-11-2233`, for date `2026-11-10`.
2. Try booking another appointment for the **same vehicle registration or phone on the same date** (even with a different mechanic).
3. The system rejects the submission with:
   > *"You already have an appointment on 2026-11-10 with mechanic '...' for vehicle DHK-11-2233."*

### 4. Test Maximum Capacity (4 Cars Quota)
1. If a mechanic already has 4 appointments on a date, selecting that mechanic is disallowed.
2. If two clients simultaneously submit the last spot, the server-side database transaction checks capacity and approves only the first, rejecting the second with a capacity notification.

### 5. Test Admin Panel (Requirement 2.a & 2.b)
1. Go to `admin/index.php` and log in (`admin` / `admin123`).
2. Verify the list table displays:
   - **Client Name**
   - **Phone**
   - **Car Registration Number**
   - **Appointment Date**
   - **Assigned Mechanic**
3. Click **"✏️ Change Date/Mechanic"** on an appointment.
4. Change the date or pick any other available senior mechanic.
5. Notice that mechanics who are already full on that target date are disabled in the dropdown.
6. Save changes and observe the updated schedule in the table.
7. Test deleting an appointment &rarr; observe that the freed slot immediately opens up for clients.

---

## 🛡️ Security & Quality Standards
- **SQL Injection Prevention:** 100% prepared statements via PHP Data Objects (`PDO`).
- **XSS Protection:** All dynamic user inputs are safely escaped using `htmlspecialchars()`.
- **Concurrency Control:** Strict database transactions (`beginTransaction` / `commit` / `rollBack`) to prevent race conditions during slot reservation.
- **Responsive Design:** Pure CSS3 flexbox and grid, tested for mobile, tablet, and desktop viewports.
- **Clean Architecture:** Separation of concerns across configuration, API endpoints, reusable functions, views, and assets.

