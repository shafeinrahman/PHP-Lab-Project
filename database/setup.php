<?php
/**
 * Automated Database Setup & Seeder Script
 * Car Workshop Online Appointment System
 *
 * This file can be run directly from browser: http://localhost/A3/database/setup.php
 * or from CLI: php database/setup.php
 */

require_once __DIR__ . '/../config/db.php';

$message = '';
$status = 'idle';
$sampleDataAdded = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || php_sapi_name() === 'cli' || isset($_GET['auto'])) {
    try {
        // Step 1: Connect to MySQL server without selecting database
        $pdo = getDBConnection(false);

        // Step 2: Create Database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `" . DB_NAME . "`;");

        // Step 3: Create Tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `mechanics` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `name` VARCHAR(100) NOT NULL,
              `specialty` VARCHAR(150) NOT NULL,
              `experience_years` INT NOT NULL DEFAULT 10,
              `phone` VARCHAR(25) NOT NULL,
              `avatar_color` VARCHAR(20) NOT NULL DEFAULT '#2563eb',
              `max_daily_capacity` INT NOT NULL DEFAULT 4,
              `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `appointments` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `client_name` VARCHAR(100) NOT NULL,
              `client_address` TEXT NOT NULL,
              `client_phone` VARCHAR(30) NOT NULL,
              `car_license_no` VARCHAR(50) NOT NULL,
              `car_engine_no` VARCHAR(50) NOT NULL,
              `appointment_date` DATE NOT NULL,
              `mechanic_id` INT NOT NULL,
              `status` ENUM('confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'confirmed',
              `admin_notes` TEXT NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              CONSTRAINT `fk_appointment_mechanic` 
                FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics` (`id`) 
                ON DELETE RESTRICT ON UPDATE CASCADE,
              INDEX `idx_date_mechanic` (`appointment_date`, `mechanic_id`),
              INDEX `idx_license_date` (`car_license_no`, `appointment_date`),
              INDEX `idx_phone_date` (`client_phone`, `appointment_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `admins` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `username` VARCHAR(50) NOT NULL UNIQUE,
              `password_hash` VARCHAR(255) NOT NULL,
              `full_name` VARCHAR(100) NOT NULL,
              `email` VARCHAR(100) NOT NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Step 4: Seed Mechanics if empty
        $stmt = $pdo->query("SELECT COUNT(*) FROM `mechanics`");
        if ($stmt->fetchColumn() == 0) {
            $mechanics = [
                [1, 'Robert "Bob" Vance', 'Senior Engine Diagnostic & Powertrain Tuning', 15, '01711001122', '#1e40af', 4],
                [2, 'David Miller', 'Master Transmission & Gearbox Overhaul', 13, '01811223344', '#047857', 4],
                [3, 'Carlos Rodriguez', 'Auto Electrical, Hybrid Systems & ECU Programming', 14, '01911334455', '#b45309', 4],
                [4, 'Alex Chen', 'High-Performance Brakes, Suspension & Steering', 11, '01611445566', '#7c3aed', 4],
                [5, 'Marcus Williams', 'Complete Vehicle Inspection & AC Climate Systems', 16, '01511556677', '#c026d3', 4],
            ];

            $insertMech = $pdo->prepare("
                INSERT INTO `mechanics` (`id`, `name`, `specialty`, `experience_years`, `phone`, `avatar_color`, `max_daily_capacity`)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($mechanics as $m) {
                $insertMech->execute($m);
            }
        }

        // Step 5: Seed Admin if empty
        $stmt = $pdo->query("SELECT COUNT(*) FROM `admins`");
        if ($stmt->fetchColumn() == 0) {
            $hashed = password_hash('admin123', PASSWORD_DEFAULT);
            $insertAdmin = $pdo->prepare("
                INSERT INTO `admins` (`username`, `password_hash`, `full_name`, `email`)
                VALUES (?, ?, ?, ?)
            ");
            $insertAdmin->execute(['admin', $hashed, 'Workshop Manager', 'manager@workshop.com']);
        }

        // Step 6: Optionally Seed Sample Appointments for testing
        if (isset($_POST['seed_samples'])) {
            $today = date('Y-m-d');
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            
            $sampleAppointments = [
                // Mechanic 1 has 3 appointments today (1 free slot left)
                ['John Doe', '12 Lake View Road, Sector 4', '01710112233', 'DHK-11-2021', '849302194', $today, 1, 'confirmed'],
                ['Jane Smith', '45 Green Road, Dhanmondi', '01710223344', 'DHK-22-3032', '938201948', $today, 1, 'confirmed'],
                ['Michael Scott', 'Scranton Branch, Main Road', '01710334455', 'DHK-33-4043', '128402948', $today, 1, 'confirmed'],
                
                // Mechanic 2 is FULL today (4 appointments - 0 free slots)
                ['Bruce Wayne', '100 Manor Drive, Gotham', '01810112233', 'DHK-44-5054', '492019482', $today, 2, 'confirmed'],
                ['Clark Kent', 'Daily Planet Ave, Metropolis', '01810223344', 'DHK-55-6065', '839201948', $today, 2, 'confirmed'],
                ['Diana Prince', 'Themyscira Embassy', '01810334455', 'DHK-66-7076', '294810293', $today, 2, 'confirmed'],
                ['Barry Allen', 'Central City Expressway', '01810445566', 'DHK-77-8087', '948201948', $today, 2, 'confirmed'],

                // Mechanic 3 has 1 appointment today (3 free slots)
                ['Arthur Dent', 'Country Lane, Cottington', '01910112233', 'DHK-88-9098', '583920194', $today, 3, 'confirmed'],

                // Tomorrow bookings
                ['Tony Stark', '10880 Malibu Point', '01610112233', 'DHK-99-0109', '748291048', $tomorrow, 4, 'confirmed'],
                ['Peter Parker', '20 Ingram Street, Queens', '01510112233', 'DHK-12-3456', '384910294', $tomorrow, 5, 'confirmed'],
            ];

            $insertSample = $pdo->prepare("
                INSERT INTO `appointments` (`client_name`, `client_address`, `client_phone`, `car_license_no`, `car_engine_no`, `appointment_date`, `mechanic_id`, `status`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($sampleAppointments as $sa) {
                // Check if already seeded to avoid duplicates
                $check = $pdo->prepare("SELECT COUNT(*) FROM `appointments` WHERE `car_license_no` = ? AND `appointment_date` = ?");
                $check->execute([$sa[3], $sa[5]]);
                if ($check->fetchColumn() == 0) {
                    $insertSample->execute($sa);
                }
            }
            $sampleDataAdded = true;
        }

        $status = 'success';
        $message = "Database '<b>" . DB_NAME . "</b>' and required tables configured successfully!";

    } catch (Exception $e) {
        $status = 'error';
        $message = "Installation Error: " . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup & Installation - Car Workshop Appointment System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #16a34a;
            --danger: #dc2626;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg); color: var(--text); padding: 40px 20px; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 650px; width: 100%; background: var(--card); border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); border: 1px solid var(--border); overflow: hidden; }
        .header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; padding: 32px 30px; text-align: center; }
        .header h1 { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
        .header p { color: #94a3b8; font-size: 14px; }
        .body { padding: 30px; }
        .alert { padding: 16px 20px; border-radius: 10px; margin-bottom: 24px; font-size: 14px; line-height: 1.5; }
        .alert-success { background-color: #dcfce7; border: 1px solid #86efac; color: #166534; }
        .alert-danger { background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
        .alert-info { background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
        .info-box { background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 24px; }
        .info-box h3 { font-size: 16px; margin-bottom: 12px; color: #334155; }
        .info-list { list-style: none; font-size: 14px; }
        .info-list li { padding: 6px 0; border-bottom: 1px dashed var(--border); display: flex; justify-content: space-between; }
        .info-list li:last-child { border-bottom: none; }
        .info-list span.label { color: var(--muted); }
        .info-list span.val { font-weight: 600; color: #1e293b; }
        .btn-group { display: flex; flex-direction: column; gap: 12px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 14px 20px; border-radius: 10px; font-weight: 600; font-size: 15px; text-decoration: none; cursor: pointer; transition: all 0.2s; border: none; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-outline { background: transparent; border: 2px solid var(--border); color: #334155; }
        .btn-outline:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #15803d; }
        .checkbox-container { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; font-size: 14px; color: #475569; }
        .footer { padding: 16px 30px; background: #f1f5f9; text-align: center; font-size: 13px; color: var(--muted); border-top: 1px solid var(--border); }
        code { background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 13px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🔧 Car Workshop System Setup</h1>
        <p>CSE 391: Assignment 3 - Database Auto-Installer</p>
    </div>

    <div class="body">
        <?php if ($status === 'success'): ?>
            <div class="alert alert-success">
                <strong>✔ Success!</strong> <?= $message ?>
                <?php if ($sampleDataAdded): ?>
                    <br><small>+ Realistic sample appointments populated for testing capacity limits & mechanics.</small>
                <?php endif; ?>
            </div>

            <div class="info-box">
                <h3>System Setup Summary</h3>
                <ul class="info-list">
                    <li><span class="label">Database Name:</span> <span class="val"><?= DB_NAME ?></span></li>
                    <li><span class="label">Mechanics Loaded:</span> <span class="val">5 Senior Mechanics (Max 4 cars/day each)</span></li>
                    <li><span class="label">Admin Username:</span> <span class="val"><code>admin</code></span></li>
                    <li><span class="label">Admin Password:</span> <span class="val"><code>admin123</code></span></li>
                </ul>
            </div>

            <div class="btn-group">
                <a href="../index.php" class="btn btn-primary">🚗 Go to User Booking Page</a>
                <a href="../admin/index.php" class="btn btn-outline">🛡️ Go to Admin Panel</a>
            </div>

        <?php elseif ($status === 'error'): ?>
            <div class="alert alert-danger">
                <strong>❌ Error Occurred:</strong><br><?= $message ?>
            </div>
            <p style="font-size: 14px; color: var(--muted); margin-bottom: 20px;">
                Make sure your MySQL server is running (e.g. in XAMPP / WAMP) and database credentials in <code>config/db.php</code> are correct.
            </p>
            <form method="POST">
                <button type="submit" class="btn btn-primary" style="width: 100%;">🔄 Retry Setup</button>
            </form>
        <?php else: ?>
            <div class="alert alert-info">
                This installer will create the database <code><?= DB_NAME ?></code>, create the tables for mechanics, appointments, and admin, and seed the 5 senior mechanics.
            </div>

            <div class="info-box">
                <h3>Configured MySQL Connection</h3>
                <ul class="info-list">
                    <li><span class="label">Host:</span> <span class="val"><?= DB_HOST ?></span></li>
                    <li><span class="label">User:</span> <span class="val"><?= DB_USER ?></span></li>
                    <li><span class="label">Target Database:</span> <span class="val"><?= DB_NAME ?></span></li>
                </ul>
            </div>

            <form method="POST">
                <div class="checkbox-container">
                    <input type="checkbox" id="seed_samples" name="seed_samples" value="1" checked>
                    <label for="seed_samples"><strong>Seed realistic test appointments</strong> (Simulates 1 fully booked mechanic and 1 busy mechanic today for instant testing)</label>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">⚡ Run Database Setup & Seeder</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="footer">
        CSE 391: Programming for the Internet &bull; Assignment 3 (Part 1 & 2)
    </div>
</div>

</body>
</html>

