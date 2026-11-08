<?php
/**
 * Admin Panel - Management Dashboard & Appointment List
 * CSE 391: Assignment 3 - Car Workshop Appointment System
 */

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

try {
    $pdo = getDBConnection();

    // Flash messages
    $successMsg = $_GET['msg'] ?? '';
    $errorMsg = $_GET['err'] ?? '';

    // Fetch all senior mechanics
    $mechanics = getAllMechanics($pdo);

    // Fetch all appointments joined with mechanic details
    $sql = "
        SELECT 
            a.*,
            m.name AS mechanic_name,
            m.specialty AS mechanic_specialty,
            m.avatar_color
        FROM `appointments` a
        JOIN `mechanics` m ON a.mechanic_id = m.id
        ORDER BY a.appointment_date DESC, a.id DESC
    ";
    $stmt = $pdo->query($sql);
    $appointments = $stmt->fetchAll();

    // Stats calculations
    $totalAppointments = count($appointments);
    $todayDate = date('Y-m-d');
    $todayAppointments = 0;
    $upcomingAppointments = 0;

    foreach ($appointments as $app) {
        if ($app['appointment_date'] === $todayDate && $app['status'] !== 'cancelled') {
            $todayAppointments++;
        }
        if ($app['appointment_date'] > $todayDate && $app['status'] !== 'cancelled') {
            $upcomingAppointments++;
        }
    }

    // Workload today for each mechanic
    $todayWorkload = getMechanicsAvailability($pdo, $todayDate);

} catch (Exception $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard & Appointments | Apex Auto Workshop</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Main CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .table-action-btns {
            display: flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body style="background-color: #f1f5f9;">

<!-- Admin Header Bar -->
<header class="admin-header">
    <div class="container">
        <div class="admin-header-inner">
            <div>
                <h1>🛡️ Workshop Admin Control Center</h1>
                <p style="color: #94a3b8; font-size: 0.9rem; margin-top: 4px;">
                    Logged in as: <strong><?= h($_SESSION['admin_name'] ?? 'Workshop Manager') ?></strong> &bull; Assignment 3 Evaluation Portal
                </p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="../index.php" target="_blank" class="btn btn-secondary btn-sm" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2);">
                    🌐 Open Client Booking Page &nearr;
                </a>
                <a href="logout.php" class="btn btn-danger btn-sm">
                    🚪 Logout
                </a>
            </div>
        </div>
    </div>
</header>

<main class="container" style="padding-bottom: 60px;">

    <!-- Flash Notifications -->
    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success">
            <span class="alert-icon">✔</span>
            <div class="alert-body">
                <strong>Success:</strong> <?= h($successMsg) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger">
            <span class="alert-icon">⚠️</span>
            <div class="alert-body">
                <strong>Notice / Error:</strong> <?= h($errorMsg) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Overview Statistics -->
    <section class="admin-stats-grid">
        <div class="admin-stat-card">
            <div>
                <div class="val"><?= $totalAppointments ?></div>
                <div class="desc">Total Bookings Recorded</div>
            </div>
            <div class="admin-stat-icon">📋</div>
        </div>

        <div class="admin-stat-card">
            <div>
                <div class="val" style="color: #2563eb;"><?= $todayAppointments ?></div>
                <div class="desc">Appointments Scheduled Today</div>
            </div>
            <div class="admin-stat-icon" style="background: #eff6ff; color: #2563eb;">📅</div>
        </div>

        <div class="admin-stat-card">
            <div>
                <div class="val" style="color: #059669;"><?= $upcomingAppointments ?></div>
                <div class="desc">Upcoming Future Bookings</div>
            </div>
            <div class="admin-stat-icon" style="background: #ecfdf5; color: #059669;">⏳</div>
        </div>

        <div class="admin-stat-card">
            <div>
                <div class="val" style="color: #7c3aed;">5 / 5</div>
                <div class="desc">Senior Mechanics Active</div>
            </div>
            <div class="admin-stat-icon" style="background: #f5f3ff; color: #7c3aed;">👨‍🔧</div>
        </div>
    </section>

    <!-- Daily Mechanic Capacity Monitor (Today) -->
    <section class="workload-card">
        <div class="workload-header">
            <div>
                <h3>📊 Today's Senior Mechanics Workload (Max 4 Cars / Day)</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted);">
                    Live status for <?= date('l, F j, Y') ?>. Mechanics reach capacity when 4 appointments are assigned.
                </p>
            </div>
            <span class="badge badge-secondary"><?= date('Y-m-d') ?></span>
        </div>

        <div class="workload-grid">
            <?php foreach ($todayWorkload as $mech): ?>
                <?php 
                    $booked = (int)$mech['booked_count'];
                    $free = (int)$mech['free_slots'];
                    $cap = (int)$mech['max_daily_capacity'];
                    $percent = min(100, round(($booked / $cap) * 100));

                    $fillClass = 'safe';
                    if ($percent >= 100) {
                        $fillClass = 'danger';
                    } elseif ($percent >= 75) {
                        $fillClass = 'warning';
                    }
                ?>
                <div class="workload-item">
                    <div class="workload-name" title="<?= h($mech['name']) ?>">
                        <?= h($mech['name']) ?>
                    </div>
                    <div style="font-size: 0.775rem; color: var(--text-muted);">
                        <?= $booked ?> / <?= $cap ?> cars booked
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill <?= $fillClass ?>" style="width: <?= $percent ?>%;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                        <span style="font-size: 0.75rem; font-weight: 700; color: <?= $free <= 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                            <?= $free <= 0 ? '⛔ FULL' : "🟢 {$free} free slots" ?>
                        </span>
                        <span style="font-size: 0.75rem; color: #94a3b8;"><?= $percent ?>%</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Appointments Management Table -->
    <section class="card">
        <div class="card-header">
            <div>
                <h2>📋 Client Appointments Roster</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 2px;">
                    Requirement 2.a: Client name, phone, car registration number, appointment date, and assigned mechanic.
                </p>
            </div>
            <div>
                <button type="button" id="resetFilters" class="btn btn-secondary btn-sm">
                    🔄 Reset Filters
                </button>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="table-filter-bar">
            <div style="flex: 1; min-width: 250px;">
                <input 
                    type="text" 
                    id="searchAppointments" 
                    class="form-control" 
                    placeholder="🔍 Search client, phone, registration, mechanic..."
                >
            </div>

            <div class="filter-group">
                <div>
                    <input type="date" id="filterDate" class="form-control" title="Filter by date">
                </div>

                <div>
                    <select id="filterMechanic" class="form-control">
                        <option value="">All Mechanics</option>
                        <?php foreach ($mechanics as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= h($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Display -->
        <div class="table-responsive">
            <table class="custom-table" id="appointmentsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client Name</th>
                        <th>Phone</th>
                        <th>Car Registration No.</th>
                        <th>Appointment Date</th>
                        <th>Assigned Mechanic</th>
                        <th>Engine No. / Details</th>
                        <th>Status</th>
                        <th style="text-align: right;">Admin Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                No appointments found in database. You can book an appointment on the client page or seed samples in <a href="../database/setup.php">setup.php</a>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $app): ?>
                            <tr 
                                data-date="<?= h($app['appointment_date']) ?>" 
                                data-mechanic-id="<?= (int)$app['mechanic_id'] ?>"
                            >
                                <td style="font-weight: 700; color: #94a3b8;">
                                    #<?= (int)$app['id'] ?>
                                </td>
                                <td>
                                    <strong style="color: #0f172a; font-size: 0.95rem; display: block;">
                                        <?= h($app['client_name']) ?>
                                    </strong>
                                    <small style="color: var(--text-muted); font-size: 0.775rem;">
                                        <?= h($app['client_address']) ?>
                                    </small>
                                </td>
                                <td>
                                    <code style="font-size: 0.85rem; font-weight: 600; color: #1e293b;">
                                        <?= h($app['client_phone']) ?>
                                    </code>
                                </td>
                                <td>
                                    <span class="badge badge-secondary" style="font-size: 0.825rem; letter-spacing: 0.05em;">
                                        <?= h($app['car_license_no']) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: #2563eb; font-size: 0.95rem; display: block;">
                                        <?= date('M j, Y', strtotime($app['appointment_date'])) ?>
                                    </strong>
                                    <small style="color: var(--text-muted);">
                                        <?= date('l', strtotime($app['appointment_date'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 10px; height: 10px; border-radius: 50%; background: <?= h($app['avatar_color']) ?>;"></div>
                                        <div>
                                            <strong style="color: #1e293b;"><?= h($app['mechanic_name']) ?></strong>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= h($app['mechanic_specialty']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.8rem;">
                                        <span class="text-muted">Eng:</span> <code><?= h($app['car_engine_no']) ?></code>
                                    </div>
                                    <?php if (!empty($app['admin_notes'])): ?>
                                        <div style="font-size: 0.75rem; color: #2563eb; margin-top: 2px;">
                                            📝 <?= h($app['admin_notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($app['status'] === 'confirmed'): ?>
                                        <span class="badge badge-success">Confirmed</span>
                                    <?php elseif ($app['status'] === 'completed'): ?>
                                        <span class="badge badge-primary">Completed</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div class="table-action-btns" style="justify-content: flex-end;">
                                        <!-- Edit Date & Mechanic Button (Requirement 2.b) -->
                                        <button 
                                            type="button" 
                                            class="btn btn-secondary btn-sm btn-edit-appointment" 
                                            data-id="<?= (int)$app['id'] ?>"
                                            data-client="<?= h($app['client_name']) ?>"
                                            data-date="<?= h($app['appointment_date']) ?>"
                                            data-mechanic-id="<?= (int)$app['mechanic_id'] ?>"
                                            data-status="<?= h($app['status']) ?>"
                                            data-notes="<?= h($app['admin_notes'] ?? '') ?>"
                                            title="Change Appointment Date & Mechanic"
                                        >
                                            ✏️ Change Date/Mechanic
                                        </button>

                                        <!-- Delete Button -->
                                        <a 
                                            href="delete_appointment.php?id=<?= (int)$app['id'] ?>" 
                                            class="btn btn-secondary btn-sm btn-delete-appointment" 
                                            data-client="<?= h($app['client_name']) ?>"
                                            title="Cancel & Delete Appointment"
                                            style="color: var(--danger);"
                                        >
                                            🗑️
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Dynamic Row when no search results match -->
                    <tr id="noResultsRow" style="display: none;">
                        <td colspan="9" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            No appointments match your search/filter criteria.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Admin Operational Help & Guidelines Facility (Requirement: Part 1 basic help facilities) -->
    <section class="help-card" style="margin-top: 30px;">
        <div class="help-card-header">
            <span style="font-size: 1.25rem;">ℹ️</span>
            <h3>Admin Panel Help & Quota Enforcement Facility</h3>
        </div>
        <div class="help-card-body">
            <div class="rule-box">
                <strong>Requirement 2.b Compliance:</strong><br>
                Admin can change the appointment date and assigned mechanic for any specific client. 
                When editing, the system automatically checks the target mechanic's schedule for that date. 
                If a mechanic already has 4 clients on the chosen date, the system marks them occupied and prohibits overbooking.
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; font-size: 0.875rem;">
                <div>
                    <h4 style="margin-bottom: 6px; color: var(--secondary);">Changing Date & Mechanic:</h4>
                    <p class="text-muted">
                        Click the <strong>"✏️ Change Date/Mechanic"</strong> button on any row. Select the new appointment date. 
                        The dropdown instantly displays each senior mechanic's remaining places for that specific day.
                    </p>
                </div>
                <div>
                    <h4 style="margin-bottom: 6px; color: var(--secondary);">Status Transitions:</h4>
                    <p class="text-muted">
                        Mark appointments as <strong>Confirmed</strong>, <strong>Completed</strong> (service finished), or <strong>Cancelled</strong>. 
                        Cancelled appointments free up that mechanic's daily slot immediately.
                    </p>
                </div>
                <div>
                    <h4 style="margin-bottom: 6px; color: var(--secondary);">Concurrency & Quotas:</h4>
                    <p class="text-muted">
                        All mechanic assignments are locked at the database level using atomic transactions. Even simultaneous manager and client updates cannot exceed the 4 active cars threshold.
                    </p>
                </div>
            </div>
        </div>
    </section>

</main>

<!-- Edit Appointment Modal (Requirement 2.b) -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>✏️ Change Appointment Date & Mechanic</h3>
            <button type="button" class="modal-close" id="modalCloseBtn">&times;</button>
        </div>

        <form id="editAppointmentForm" method="POST" action="edit_appointment.php">
            <input type="hidden" name="appointment_id" id="edit_appointment_id" value="">

            <div class="modal-body">
                <div style="margin-bottom: 16px; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid var(--border);">
                    <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Client:</span>
                    <strong id="edit_client_name" style="display: block; font-size: 1.05rem; color: #0f172a;"></strong>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_appointment_date">
                        New Appointment Date <span class="required">*</span>
                    </label>
                    <input 
                        type="date" 
                        name="appointment_date" 
                        id="edit_appointment_date" 
                        class="form-control" 
                        required
                    >
                    <div class="form-hint">Changing date checks mechanic slots for that new day.</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_mechanic_id">
                        Select Available Senior Mechanic <span class="required">*</span>
                    </label>
                    <select name="mechanic_id" id="edit_mechanic_id" class="form-control" required>
                        <option value="">Loading availability...</option>
                    </select>
                    <div class="form-hint" id="modalSlotNotice" style="color: var(--primary); font-weight: 500;">
                        Checking availability...
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_status">Appointment Status</label>
                    <select name="status" id="edit_status" class="form-control">
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_notes">Manager Reason / Rescheduling Notes</label>
                    <textarea name="admin_notes" id="edit_notes" class="form-control" rows="2" placeholder="e.g. Rescheduled per client request; assigned Bob for engine diagnostic"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelModalBtn">Cancel</button>
                <button type="submit" class="btn btn-primary">💾 Save Appointment Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Admin JavaScript -->
<script src="../assets/js/admin.js"></script>

</body>
</html>

