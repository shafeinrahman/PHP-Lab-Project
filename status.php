<?php
/**
 * User Panel - Appointment Status & Receipt Lookup
 * CSE 391: Programming for the Internet - Assignment 3
 */

session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Look Up Appointment Status';
$activeNav = 'status';

$queryTerm = trim($_GET['q'] ?? '');
$appointments = [];
$searched = false;

if (!empty($queryTerm)) {
    try {
        $pdo = getDBConnection();
        $searched = true;

        $sql = "
            SELECT a.*, m.name AS mechanic_name, m.specialty AS mechanic_specialty, m.phone AS mechanic_phone
            FROM `appointments` a
            JOIN `mechanics` m ON a.mechanic_id = m.id
            WHERE a.car_license_no LIKE ? OR a.client_phone LIKE ?
            ORDER BY a.appointment_date DESC
        ";

        $stmt = $pdo->prepare($sql);
        $likeTerm = "%{$queryTerm}%";
        $stmt->execute([$likeTerm, $likeTerm]);
        $appointments = $stmt->fetchAll();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero" style="padding: 40px 0;">
    <div class="container">
        <div class="hero-content">
            <h1 style="font-size: 2.2rem;">Track Your Vehicle Appointment</h1>
            <p>
                Enter your Car Registration / License number or Contact Phone number to view your appointment details, scheduled date, and assigned senior mechanic.
            </p>
        </div>
    </div>
</section>

<main class="main-wrapper">
    <div class="container" style="max-width: 900px;">
        
        <div class="card" style="margin-bottom: 30px;">
            <div class="card-body">
                <form method="GET" action="status.php" style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <input 
                        type="text" 
                        name="q" 
                        class="form-control" 
                        style="flex: 1; min-width: 250px;" 
                        placeholder="Enter Car License (e.g. DHK-11-2021) or Phone Number"
                        value="<?= h($queryTerm) ?>"
                        required
                    >
                    <button type="submit" class="btn btn-primary">
                        🔍 Search Appointment
                    </button>
                    <?php if ($searched): ?>
                        <a href="status.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if ($searched): ?>
            <?php if (empty($appointments)): ?>
                <div class="alert alert-info">
                    <span class="alert-icon">ℹ️</span>
                    <div class="alert-body">
                        <strong>No Active Appointments Found</strong>
                        No records match "<?= h($queryTerm) ?>". Please double-check your registration number or contact phone.
                    </div>
                </div>
            <?php else: ?>
                <h3 style="font-size: 1.25rem; margin-bottom: 16px;">
                    Found <?= count($appointments) ?> Appointment(s)
                </h3>

                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php foreach ($appointments as $app): ?>
                        <div class="card" style="border-left: 5px solid var(--primary);">
                            <div class="card-header" style="background: #f8fafc;">
                                <div>
                                    <h3 style="font-size: 1.15rem;">
                                        Vehicle: <?= h($app['car_license_no']) ?>
                                    </h3>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);">
                                        Booking ID #<?= (int)$app['id'] ?> &bull; Booked on <?= date('M j, Y', strtotime($app['created_at'])) ?>
                                    </span>
                                </div>
                                <div>
                                    <?php if ($app['status'] === 'confirmed'): ?>
                                        <span class="badge badge-success">Approved & Confirmed</span>
                                    <?php elseif ($app['status'] === 'completed'): ?>
                                        <span class="badge badge-primary">Completed</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Cancelled</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="receipt-details">
                                    <div class="receipt-row">
                                        <span class="label">Client Name:</span>
                                        <span class="val"><?= h($app['client_name']) ?></span>
                                    </div>
                                    <div class="receipt-row">
                                        <span class="label">Contact Phone:</span>
                                        <span class="val"><?= h($app['client_phone']) ?></span>
                                    </div>
                                    <div class="receipt-row">
                                        <span class="label">Engine Number:</span>
                                        <span class="val"><?= h($app['car_engine_no']) ?></span>
                                    </div>
                                    <div class="receipt-row">
                                        <span class="label">Appointment Date:</span>
                                        <span class="val" style="color: #2563eb; font-size: 1.05rem;">
                                            📅 <?= date('l, F j, Y', strtotime($app['appointment_date'])) ?>
                                        </span>
                                    </div>
                                    <div class="receipt-row">
                                        <span class="label">Assigned Senior Mechanic:</span>
                                        <span class="val" style="color: #047857; font-size: 1.05rem;">
                                            👨‍🔧 <?= h($app['mechanic_name']) ?>
                                        </span>
                                        <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">
                                            <?= h($app['mechanic_specialty']) ?>
                                        </span>
                                    </div>
                                    <div class="receipt-row">
                                        <span class="label">Address:</span>
                                        <span class="val"><?= h($app['client_address']) ?></span>
                                    </div>
                                </div>

                                <?php if (!empty($app['admin_notes'])): ?>
                                    <div style="margin-top: 16px; padding: 12px; background: #eff6ff; border-radius: 8px; font-size: 0.85rem; color: #1e40af;">
                                        <strong>Workshop Management Note:</strong> <?= h($app['admin_notes']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

