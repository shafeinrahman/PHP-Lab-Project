<?php
/**
 * AJAX API: Pre-check if client has an appointment on the selected date
 * CSE 391: Assignment 3 - Car Workshop Appointment System
 *
 * Endpoint: GET api/check_appointment.php?date=YYYY-MM-DD&license=...&phone=...
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$date = trim($_GET['date'] ?? '');
$license = trim($_GET['license'] ?? '');
$phone = trim($_GET['phone'] ?? '');

if (empty($date) || (empty($license) && empty($phone))) {
    echo json_encode(['success' => false, 'has_appointment' => false]);
    exit;
}

try {
    $pdo = getDBConnection();
    $existing = checkClientHasAppointmentOnDate($pdo, $date, $license, $phone);

    if ($existing) {
        echo json_encode([
            'success' => true,
            'has_appointment' => true,
            'message' => "You already have an appointment on {$date} for vehicle {$existing['car_license_no']} with {$existing['mechanic_name']}.",
            'details' => [
                'id' => $existing['id'],
                'date' => $existing['appointment_date'],
                'mechanic' => $existing['mechanic_name'],
                'license' => $existing['car_license_no'],
                'status' => $existing['status']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'has_appointment' => false
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

