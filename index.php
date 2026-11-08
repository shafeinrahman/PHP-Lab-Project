<?php
/**
 * User Panel - Main Appointment Booking Page
 * CSE 391: Programming for the Internet - Assignment 3
 * Car Workshop Online Appointment System
 */

session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Book Online Appointment';
$activeNav = 'home';

$errors = [];
$bookingResult = null;
$selectedDate = date('Y-m-d');
$formData = [
    'client_name' => '',
    'client_address' => '',
    'client_phone' => '',
    'car_license_no' => '',
    'car_engine_no' => '',
    'appointment_date' => $selectedDate,
    'mechanic_id' => ''
];

try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    // If database connection fails, redirect to auto installer
    header('Location: database/setup.php');
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['client_name'] = trim($_POST['client_name'] ?? '');
    $formData['client_address'] = trim($_POST['client_address'] ?? '');
    $formData['client_phone'] = trim($_POST['client_phone'] ?? '');
    $formData['car_license_no'] = strtoupper(trim($_POST['car_license_no'] ?? ''));
    $formData['car_engine_no'] = trim($_POST['car_engine_no'] ?? '');
    $formData['appointment_date'] = trim($_POST['appointment_date'] ?? $selectedDate);
    $formData['mechanic_id'] = (int)($_POST['mechanic_id'] ?? 0);

    $selectedDate = $formData['appointment_date'];

    // 1. Validate inputs (Part 2 requirement)
    $errors = validateAppointmentInputs($formData);

    // 2. If valid, proceed to atomic booking process
    if (empty($errors)) {
        $bookingResult = bookAppointment($pdo, $formData);

        if ($bookingResult['success']) {
            // Store confirmation details for receipt
            $_SESSION['last_booking'] = [
                'id' => $bookingResult['appointment_id'],
                'client_name' => $formData['client_name'],
                'client_address' => $formData['client_address'],
                'client_phone' => $formData['client_phone'],
                'car_license_no' => $formData['car_license_no'],
                'car_engine_no' => $formData['car_engine_no'],
                'appointment_date' => $formData['appointment_date'],
                'mechanic_name' => $bookingResult['mechanic_name'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            // Redirect to prevent duplicate submission on page refresh
            header('Location: index.php?booked=success');
            exit;
        }
    }
}

// Check if redirected after successful booking
$confirmedBooking = null;
if (isset($_GET['booked']) && $_GET['booked'] === 'success' && isset($_SESSION['last_booking'])) {
    $confirmedBooking = $_SESSION['last_booking'];
}

// Fetch live mechanics availability for the selected date
$mechanics = getMechanicsAvailability($pdo, $selectedDate);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-pill">
                <span>⚡ Real-Time Booking & Availability System</span>
            </div>
            <h1>Reserve Your Desired Senior Mechanic</h1>
            <p>
                Choose from our 5 senior master mechanics and secure your appointment without physical queues. 
                Each mechanic is strictly assigned up to <strong>4 active cars per day</strong> to ensure 
                uncompromised diagnostic quality.
            </p>
            <div class="hero-stats">
                <div class="stat-item">
                    <h4>5</h4>
                    <span>Senior Mechanics</span>
                </div>
                <div class="stat-item">
                    <h4>4 Cars / Day</h4>
                    <span>Daily Limit Per Mechanic</span>
                </div>
                <div class="stat-item">
                    <h4>100%</h4>
                    <span>Online Slot Transparency</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Booking Section -->
<main class="main-wrapper">
    <div class="container">

        <!-- Booking Result Notification -->
        <?php if ($confirmedBooking): ?>
            <div class="receipt-card">
                <div class="receipt-header">
                    <div class="receipt-title">
                        <h3>🎉 Appointment Approved & Confirmed!</h3>
                        <p style="font-size: 0.85rem; color: #15803d;">
                            Your appointment has been registered in the workshop database. Please save your reference ID.
                        </p>
                    </div>
                    <div>
                        <span class="badge badge-success" style="font-size: 0.9rem; padding: 6px 14px;">
                            Booking Ref #<?= h($confirmedBooking['id']) ?>
                        </span>
                    </div>
                </div>

                <div class="receipt-details">
                    <div class="receipt-row">
                        <span class="label">Client Name:</span>
                        <span class="val"><?= h($confirmedBooking['client_name']) ?></span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">Phone Number:</span>
                        <span class="val"><?= h($confirmedBooking['client_phone']) ?></span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">Car Registration:</span>
                        <span class="val"><?= h($confirmedBooking['car_license_no']) ?></span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">Car Engine Number:</span>
                        <span class="val"><?= h($confirmedBooking['car_engine_no']) ?></span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">Appointment Date:</span>
                        <span class="val" style="color: #2563eb; font-weight: 800;">
                            <?= date('l, F j, Y', strtotime($confirmedBooking['appointment_date'])) ?>
                        </span>
                    </div>
                    <div class="receipt-row">
                        <span class="label">Assigned Senior Mechanic:</span>
                        <span class="val" style="color: #047857; font-weight: 800;">
                            <?= h($confirmedBooking['mechanic_name']) ?>
                        </span>
                    </div>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 12px; align-items: center; justify-content: flex-end;">
                    <button onclick="window.print()" class="btn btn-secondary btn-sm">🖨️ Print Receipt</button>
                    <a href="index.php" class="btn btn-primary btn-sm">➕ Book Another Vehicle</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Server-Side Error Alert (e.g. Duplicate booking or Mechanic occupied) -->
        <?php if ($bookingResult && !$bookingResult['success']): ?>
            <div class="alert alert-danger" role="alert">
                <span class="alert-icon">⚠️</span>
                <div class="alert-body">
                    <strong>
                        <?php if ($bookingResult['error_type'] === 'duplicate_booking'): ?>
                            Duplicate Appointment Restriction
                        <?php elseif ($bookingResult['error_type'] === 'mechanic_full'): ?>
                            Mechanic Capacity Exceeded
                        <?php else: ?>
                            Appointment Submission Notice
                        <?php endif; ?>
                    </strong>
                    <?= h($bookingResult['message']) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Client-Side Duplicate Pre-check Warning -->
        <div id="duplicateAlert" class="alert alert-warning" style="display: none;">
            <span class="alert-icon">⚠️</span>
            <div class="alert-body">
                <strong>Existing Appointment Detected:</strong>
                <span id="duplicateMessage"></span>
            </div>
        </div>

        <div class="grid-2">
            <!-- Left Column: Appointment Booking Form -->
            <div class="card">
                <div class="card-header">
                    <h2>📝 Vehicle Service Appointment Form</h2>
                    <span class="badge badge-primary">Step 1 of 2</span>
                </div>

                <div class="card-body">
                    <form id="bookingForm" method="POST" action="index.php" novalidate>
                        
                        <!-- Client Contact Details -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span>👤</span> Client Information
                            </h3>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label" for="client_name">
                                        Client Full Name <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="client_name" 
                                        name="client_name" 
                                        class="form-control <?= isset($errors['client_name']) ? 'is-invalid' : '' ?>" 
                                        placeholder="e.g. Md. Tanvir Ahmed" 
                                        value="<?= h($formData['client_name']) ?>"
                                        required
                                    >
                                    <div class="invalid-feedback"><?= $errors['client_name'] ?? '' ?></div>
                                    <div class="form-hint">Enter your official name for workshop registration.</div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="client_phone">
                                        Phone Number <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="tel" 
                                        id="client_phone" 
                                        name="client_phone" 
                                        class="form-control <?= isset($errors['client_phone']) ? 'is-invalid' : '' ?>" 
                                        placeholder="e.g. 01711223344 (digits only)" 
                                        value="<?= h($formData['client_phone']) ?>"
                                        pattern="[0-9]+"
                                        required
                                    >
                                    <div class="invalid-feedback"><?= $errors['client_phone'] ?? '' ?></div>
                                    <div class="form-hint">Must contain numbers only (digits 0-9).</div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="client_address">
                                    Client Address <span class="required">*</span>
                                </label>
                                <textarea 
                                    id="client_address" 
                                    name="client_address" 
                                    class="form-control <?= isset($errors['client_address']) ? 'is-invalid' : '' ?>" 
                                    rows="2" 
                                    placeholder="e.g. House #14, Road #5, Dhanmondi, Dhaka"
                                    required
                                ><?= h($formData['client_address']) ?></textarea>
                                <div class="invalid-feedback"><?= $errors['client_address'] ?? '' ?></div>
                            </div>
                        </div>

                        <!-- Vehicle Information -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span>🚘</span> Vehicle Identification
                            </h3>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label" for="car_license_no">
                                        Car License / Registration No. <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="car_license_no" 
                                        name="car_license_no" 
                                        class="form-control <?= isset($errors['car_license_no']) ? 'is-invalid' : '' ?>" 
                                        placeholder="e.g. DHK-METRO-GA-11-2233" 
                                        value="<?= h($formData['car_license_no']) ?>"
                                        required
                                    >
                                    <div class="invalid-feedback"><?= $errors['car_license_no'] ?? '' ?></div>
                                    <div class="form-hint">Your vehicle plate number.</div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="car_engine_no">
                                        Car Engine Number <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="car_engine_no" 
                                        name="car_engine_no" 
                                        class="form-control <?= isset($errors['car_engine_no']) ? 'is-invalid' : '' ?>" 
                                        placeholder="e.g. 192840294 (numbers only)" 
                                        value="<?= h($formData['car_engine_no']) ?>"
                                        pattern="[0-9]+"
                                        required
                                    >
                                    <div class="invalid-feedback"><?= $errors['car_engine_no'] ?? '' ?></div>
                                    <div class="form-hint">Must contain numbers only (digits 0-9).</div>
                                </div>
                            </div>
                        </div>

                        <!-- Appointment Date Selection -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <span>📅</span> Schedule Date & Live Availability
                            </h3>

                            <div class="form-group" style="max-width: 320px;">
                                <label class="form-label" for="appointment_date">
                                    Select Appointment Date <span class="required">*</span>
                                </label>
                                <input 
                                    type="date" 
                                    id="appointment_date" 
                                    name="appointment_date" 
                                    class="form-control <?= isset($errors['appointment_date']) ? 'is-invalid' : '' ?>" 
                                    value="<?= h($selectedDate) ?>"
                                    min="<?= date('Y-m-d') ?>"
                                    required
                                >
                                <div class="invalid-feedback"><?= $errors['appointment_date'] ?? '' ?></div>
                                <div class="form-hint">
                                    Changing the date immediately refreshes mechanic availability below.
                                </div>
                            </div>
                        </div>

                        <!-- Mechanic Selection (Requirement: Show free places for each mechanic) -->
                        <div class="form-section">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <h3 class="section-title" style="border: none; margin-bottom: 4px;">
                                    <span>👨‍🔧</span> Select Your Desired Senior Mechanic <span class="required">*</span>
                                </h3>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">
                                    Showing live slots for: <strong id="selectedDateDisplay"><?= h($selectedDate) ?></strong>
                                </span>
                            </div>

                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px;">
                                Click on any available mechanic card to assign them to your vehicle investigation.
                            </p>

                            <div class="invalid-feedback" id="mechanicError" style="margin-bottom: 10px; <?= isset($errors['mechanic_id']) ? 'display: block;' : '' ?>">
                                <?= $errors['mechanic_id'] ?? '' ?>
                            </div>

                            <div class="mechanic-selection-grid" id="mechanicGrid">
                                <?php foreach ($mechanics as $mech): ?>
                                    <?php 
                                        $free = (int)$mech['free_slots'];
                                        $isFull = ($free <= 0);
                                        $isSelected = ($formData['mechanic_id'] == $mech['id']);
                                    ?>
                                    <div 
                                        class="mechanic-card <?= $isFull ? 'disabled' : '' ?> <?= $isSelected ? 'selected' : '' ?>" 
                                        data-mechanic-id="<?= $mech['id'] ?>"
                                    >
                                        <input 
                                            type="radio" 
                                            name="mechanic_id" 
                                            id="mech_<?= $mech['id'] ?>" 
                                            value="<?= $mech['id'] ?>" 
                                            class="mechanic-radio"
                                            <?= $isFull ? 'disabled' : '' ?>
                                            <?= $isSelected ? 'checked' : '' ?>
                                        >

                                        <div>
                                            <div class="mech-header">
                                                <div class="mech-avatar" style="background-color: <?= h($mech['avatar_color']) ?>;">
                                                    <?= strtoupper(substr($mech['name'], 0, 1)) ?>
                                                </div>
                                                <div class="mech-title">
                                                    <h4><?= h($mech['name']) ?></h4>
                                                    <span><?= (int)$mech['experience_years'] ?> Years Master Experience</span>
                                                </div>
                                            </div>

                                            <div class="mech-specialty">
                                                <strong>Specialty:</strong> <?= h($mech['specialty']) ?>
                                            </div>
                                        </div>

                                        <div class="mech-footer">
                                            <?php if ($free <= 0): ?>
                                                <span class="slot-indicator full">
                                                    ⛔ FULL (0/4 free)
                                                </span>
                                            <?php elseif ($free === 1): ?>
                                                <span class="slot-indicator almost-full">
                                                    ⚠️ 1 slot left (1/4 free)
                                                </span>
                                            <?php else: ?>
                                                <span class="slot-indicator available">
                                                    🟢 <?= $free ?>/4 free places
                                                </span>
                                            <?php endif; ?>

                                            <div class="check-indicator">✔</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Submission Button -->
                        <div style="margin-top: 30px;">
                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                🚀 Confirm & Submit Appointment Request
                            </button>
                            <p class="text-center text-muted" style="font-size: 0.8rem; margin-top: 10px;">
                                By submitting, the server verifies mechanic capacity and checks against duplicate bookings on this date.
                            </p>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Right Column: Basic Help Facilities & Workshop Policies (Assignment Part 1 Requirement) -->
            <aside>
                <!-- Help Facility Card -->
                <div class="help-card" id="help-section">
                    <div class="help-card-header">
                        <span style="font-size: 1.3rem;">ℹ️</span>
                        <h3>Appointment Help & System Rules</h3>
                    </div>

                    <div class="help-card-body">
                        <div class="rule-box">
                            <strong>Daily Mechanic Limit Policy:</strong><br>
                            Each senior mechanic is allocated a maximum of <strong>4 cars per day</strong> to guarantee deep diagnostic attention and prevent chaotic overbooking.
                        </div>

                        <h4 style="font-size: 0.95rem; margin-bottom: 12px; color: var(--secondary);">
                            How the Booking Process Works:
                        </h4>

                        <ol class="help-steps">
                            <li>
                                <div class="step-number">1</div>
                                <div>
                                    <strong>Pick Appointment Date:</strong> Choose your planned service day. The calendar instantly updates the live availability of all 5 senior mechanics.
                                </div>
                            </li>
                            <li>
                                <div class="step-number">2</div>
                                <div>
                                    <strong>Choose Desired Mechanic:</strong> Mechanics with free slots (1 to 4) will be active. Occupied mechanics (0 free places) are marked FULL and cannot be selected.
                                </div>
                            </li>
                            <li>
                                <div class="step-number">3</div>
                                <div>
                                    <strong>Provide Vehicle & Contact Info:</strong> Enter your full name, address, phone (numeric only), license registration, and engine number (numeric only).
                                </div>
                            </li>
                            <li>
                                <div class="step-number">4</div>
                                <div>
                                    <strong>Instant Verification:</strong> The server checks for duplicate appointments on that date and confirms your reservation immediately.
                                </div>
                            </li>
                        </ol>

                        <div class="rule-box" style="border-left-color: #f59e0b; background: #fffbeb;">
                            <strong>Single Daily Booking Rule:</strong><br>
                            A client (or vehicle) cannot book more than one appointment across any mechanic on the same calendar day.
                        </div>

                        <div class="contact-card">
                            <h4>Need Assistance or Rescheduling?</h4>
                            <p>📞 <strong>Workshop Desk:</strong> 01711-001122</p>
                            <p>📍 <strong>Location:</strong> Apex Workshop, Auto Avenue</p>
                            <p>⏱️ <strong>Operating Hours:</strong> 8:00 AM - 6:00 PM</p>
                            <a href="status.php" class="btn btn-secondary btn-sm btn-block" style="margin-top: 10px;">
                                🔍 Look Up Existing Appointment
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Senior Mechanics Roster Overview -->
                <div class="card" style="padding: 22px;">
                    <h3 style="font-size: 1.05rem; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                        <span>⭐</span> Meet Our Senior Mechanics
                    </h3>
                    <ul style="list-style: none; font-size: 0.85rem; display: flex; flex-direction: column; gap: 12px;">
                        <li>
                            <strong>1. Robert "Bob" Vance</strong> &bull; 15 yrs exp.<br>
                            <span class="text-muted">Powertrain, Engine Tuning & Computer Diagnostics</span>
                        </li>
                        <li>
                            <strong>2. David Miller</strong> &bull; 13 yrs exp.<br>
                            <span class="text-muted">Master Transmission & Automatic Gearbox Rebuild</span>
                        </li>
                        <li>
                            <strong>3. Carlos Rodriguez</strong> &bull; 14 yrs exp.<br>
                            <span class="text-muted">Auto Electrical, ECU Mapping & Hybrid Systems</span>
                        </li>
                        <li>
                            <strong>4. Alex Chen</strong> &bull; 11 yrs exp.<br>
                            <span class="text-muted">ABS Brakes, Suspension & Electronic Steering</span>
                        </li>
                        <li>
                            <strong>5. Marcus Williams</strong> &bull; 16 yrs exp.<br>
                            <span class="text-muted">Comprehensive Overhaul & Dual-Zone AC Systems</span>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>

    </div>
</main>

<!-- Client Booking JavaScript -->
<script src="<?= getBaseUrl() ?>/assets/js/booking.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

