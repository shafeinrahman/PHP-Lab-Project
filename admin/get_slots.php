<?php
/**
 * AJAX API: Get Mechanic Free Slots for Admin Editing
 * CSE 391: Assignment 3 - Car Workshop Appointment System
 *
 * Endpoint: GET admin/get_slots.php?date=YYYY-MM-DD&exclude_id=123
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized admin access']);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$date = $_GET['date'] ?? date('Y-m-d');
$excludeId = isset($_GET['exclude_id']) && is_numeric($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'error' => 'Invalid date format']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Query each mechanic and calculate active appointments excluding this appointment ID
    $sql = "
        SELECT 
            m.id,
            m.name,
            m.specialty,
            m.experience_years,
            m.phone,
            m.max_daily_capacity,
            COALESCE(app.booked_count, 0) AS booked_count,
            (m.max_daily_capacity - COALESCE(app.booked_count, 0)) AS free_slots
        FROM `mechanics` m
        LEFT JOIN (
            SELECT `mechanic_id`, COUNT(*) AS booked_count
            FROM `appointments`
            WHERE `appointment_date` = ? 
              AND `status` != 'cancelled'
              " . ($excludeId ? " AND `id` != " . (int)$excludeId : "") . "
            GROUP BY `mechanic_id`
        ) app ON m.id = app.mechanic_id
        WHERE m.status = 'active'
        ORDER BY m.id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    $mechanics = $stmt->fetchAll();

    foreach ($mechanics as &$mech) {
        $mech['free_slots'] = max(0, (int)$mech['free_slots']);
        $mech['is_full'] = ($mech['free_slots'] <= 0);
    }

    echo json_encode([
        'success' => true,
        'date' => $date,
        'mechanics' => $mechanics
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

