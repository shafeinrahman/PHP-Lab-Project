<?php
/**
 * Admin Panel - Edit Appointment Processor
 * CSE 391: Assignment 3 - Car Workshop Appointment System
 *
 * Requirement 2.b: "If admin wants he can change the date of appointment and 
 * also the assigned mechanic name with any other available mechanic for any specific client."
 */

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = (int)($_POST['appointment_id'] ?? 0);
    $newDate = trim($_POST['appointment_date'] ?? '');
    $newMechanicId = (int)($_POST['mechanic_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? 'confirmed');
    $adminNotes = trim($_POST['admin_notes'] ?? '');

    if ($appointmentId <= 0 || empty($newDate) || $newMechanicId <= 0) {
        header('Location: dashboard.php?err=' . urlencode('Invalid inputs provided for appointment update.'));
        exit;
    }

    try {
        $pdo = getDBConnection();
        $result = updateAppointmentByAdmin($pdo, $appointmentId, $newDate, $newMechanicId, $adminNotes, $newStatus);

        if ($result['success']) {
            header('Location: dashboard.php?msg=' . urlencode($result['message']));
            exit;
        } else {
            header('Location: dashboard.php?err=' . urlencode($result['message']));
            exit;
        }
    } catch (Exception $e) {
        header('Location: dashboard.php?err=' . urlencode('Error updating appointment: ' . $e->getMessage()));
        exit;
    }
} else {
    header('Location: dashboard.php');
    exit;
}
?>

