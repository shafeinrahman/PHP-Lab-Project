<?php
/**
 * Admin Panel - Delete / Cancel Appointment Processor
 * CSE 391: Assignment 3 - Car Workshop Appointment System
 */

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $pdo = getDBConnection();
        $res = deleteAppointment($pdo, $id);

        if ($res['success']) {
            header('Location: dashboard.php?msg=' . urlencode("Appointment #{$id} deleted successfully."));
            exit;
        } else {
            header('Location: dashboard.php?err=' . urlencode($res['message']));
            exit;
        }
    } catch (Exception $e) {
        header('Location: dashboard.php?err=' . urlencode('Error deleting appointment: ' . $e->getMessage()));
        exit;
    }
} else {
    header('Location: dashboard.php');
    exit;
}
?>

