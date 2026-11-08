<?php
/**
 * AJAX API: Get Mechanic Free Slots by Date
 * CSE 391: Assignment 3 - Car Workshop Appointment System
 *
 * Endpoint: GET api/get_mechanic_slots.php?date=YYYY-MM-DD
 * Returns JSON containing each mechanic's current booking count and free places.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$date = $_GET['date'] ?? date('Y-m-d');

// Basic validation of date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid date format. Expected YYYY-MM-DD.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    $mechanics = getMechanicsAvailability($pdo, $date);

    echo json_encode([
        'success' => true,
        'date' => $date,
        'count' => count($mechanics),
        'mechanics' => $mechanics
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

