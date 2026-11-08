<?php
/**
 * Core Application Functions & Business Logic
 * CSE 391: Assignment 3 - Car Workshop Appointment System
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Fetch all active senior mechanics
 */
function getAllMechanics($pdo) {
    $stmt = $pdo->query("SELECT * FROM `mechanics` WHERE `status` = 'active' ORDER BY `id` ASC");
    return $stmt->fetchAll();
}

/**
 * Fetch a single mechanic by ID
 */
function getMechanicById($pdo, $mechanicId) {
    $stmt = $pdo->prepare("SELECT * FROM `mechanics` WHERE `id` = ?");
    $stmt->execute([(int)$mechanicId]);
    return $stmt->fetch();
}

/**
 * Get the real-time availability and free places for each mechanic on a specific date.
 * Requirement: "The list of the mechanic should show how many free places are available for each mechanic."
 *
 * @param PDO $pdo
 * @param string $date (YYYY-MM-DD)
 * @return array List of mechanics with booked_count, free_slots, is_full
 */
function getMechanicsAvailability($pdo, $date) {
    $sql = "
        SELECT 
            m.id,
            m.name,
            m.specialty,
            m.experience_years,
            m.phone,
            m.avatar_color,
            m.max_daily_capacity,
            COALESCE(app.booked_count, 0) AS booked_count,
            (m.max_daily_capacity - COALESCE(app.booked_count, 0)) AS free_slots
        FROM `mechanics` m
        LEFT JOIN (
            SELECT `mechanic_id`, COUNT(*) AS booked_count
            FROM `appointments`
            WHERE `appointment_date` = ? AND `status` != 'cancelled'
            GROUP BY `mechanic_id`
        ) app ON m.id = app.mechanic_id
        WHERE m.status = 'active'
        ORDER BY m.id ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    $results = $stmt->fetchAll();

    foreach ($results as &$mech) {
        $mech['free_slots'] = max(0, (int)$mech['free_slots']);
        $mech['is_full'] = ($mech['free_slots'] <= 0);
    }
    
    return $results;
}

/**
 * Check if a client has already taken any appointment on a specific date.
 * Requirement: "After a client makes data submission, it should check whether the client has 
 * already taken any appointment from any mechanic on certain date or not. If not, then the 
 * client is allowed to take an appointment otherwise s/he will be notifying that he has 
 * already taken an appointment on that specific date."
 *
 * Checked by Car License (Registration) No OR Client Phone Number.
 *
 * @param PDO $pdo
 * @param string $date
 * @param string $licenseNo
 * @param string $phone
 * @param int|null $excludeId When updating existing appointment
 * @return array|false Returns appointment row if duplicate exists, false otherwise
 */
function checkClientHasAppointmentOnDate($pdo, $date, $licenseNo, $phone, $excludeId = null) {
    $licenseNo = strtoupper(trim($licenseNo));
    $phone = trim($phone);

    $sql = "
        SELECT a.*, m.name AS mechanic_name 
        FROM `appointments` a
        JOIN `mechanics` m ON a.mechanic_id = m.id
        WHERE a.appointment_date = ? 
          AND a.status != 'cancelled'
          AND (UPPER(a.car_license_no) = ? OR a.client_phone = ?)
    ";

    $params = [$date, $licenseNo, $phone];

    if ($excludeId !== null) {
        $sql .= " AND a.id != ?";
        $params[] = (int)$excludeId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Check if a mechanic has free slot on a specific date.
 * Max 4 active cars per mechanic per day.
 *
 * @param PDO $pdo
 * @param int $mechanicId
 * @param string $date
 * @param int|null $excludeId When updating existing appointment
 * @return array ['available' => bool, 'booked' => int, 'free' => int, 'max' => int]
 */
function checkMechanicSlotAvailable($pdo, $mechanicId, $date, $excludeId = null) {
    $sql = "
        SELECT COUNT(*) AS booked 
        FROM `appointments` 
        WHERE `mechanic_id` = ? 
          AND `appointment_date` = ? 
          AND `status` != 'cancelled'
    ";
    $params = [(int)$mechanicId, $date];

    if ($excludeId !== null) {
        $sql .= " AND `id` != ?";
        $params[] = (int)$excludeId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $booked = (int)$stmt->fetchColumn();

    $mechStmt = $pdo->prepare("SELECT `max_daily_capacity`, `name` FROM `mechanics` WHERE `id` = ?");
    $mechStmt->execute([(int)$mechanicId]);
    $mech = $mechStmt->fetch();

    $maxCapacity = $mech ? (int)$mech['max_daily_capacity'] : 4;
    $free = max(0, $maxCapacity - $booked);

    return [
        'available' => ($booked < $maxCapacity),
        'booked' => $booked,
        'free' => $free,
        'max' => $maxCapacity,
        'mechanic_name' => $mech ? $mech['name'] : 'Unknown'
    ];
}

/**
 * Validate appointment form inputs (Part 2 requirement)
 * Validates:
 * - Name: not empty, valid string
 * - Address: not empty
 * - Phone: contains only numbers
 * - Car License No: not empty
 * - Car Engine No: contains only numbers
 * - Appointment Date: valid format, not empty, not past date
 * - Mechanic ID: valid integer
 */
function validateAppointmentInputs($data) {
    $errors = [];

    // Client Name
    $name = trim($data['client_name'] ?? '');
    if (empty($name)) {
        $errors['client_name'] = 'Client name is required.';
    } elseif (strlen($name) < 2) {
        $errors['client_name'] = 'Name must be at least 2 characters long.';
    } elseif (!preg_match("/^[a-zA-Z\s\.\'-]+$/", $name)) {
        $errors['client_name'] = 'Name can only contain letters, spaces, dots, and hyphens.';
    }

    // Address
    $address = trim($data['client_address'] ?? '');
    if (empty($address)) {
        $errors['client_address'] = 'Client address is required.';
    } elseif (strlen($address) < 4) {
        $errors['client_address'] = 'Address must be at least 4 characters long.';
    }

    // Phone - requirement: "phone (that it has only number in it)"
    $phone = trim($data['client_phone'] ?? '');
    if (empty($phone)) {
        $errors['client_phone'] = 'Phone number is required.';
    } elseif (!preg_match('/^[0-9]+$/', $phone)) {
        $errors['client_phone'] = 'Phone number must contain only numbers (digits 0-9).';
    } elseif (strlen($phone) < 7 || strlen($phone) > 15) {
        $errors['client_phone'] = 'Phone number must be between 7 and 15 digits.';
    }

    // Car License / Registration Number
    $license = trim($data['car_license_no'] ?? '');
    if (empty($license)) {
        $errors['car_license_no'] = 'Car license / registration number is required.';
    } elseif (strlen($license) < 3) {
        $errors['car_license_no'] = 'Please enter a valid car license number.';
    }

    // Car Engine Number - requirement: "car engine no. (that it has only number in it)"
    $engine = trim($data['car_engine_no'] ?? '');
    if (empty($engine)) {
        $errors['car_engine_no'] = 'Car engine number is required.';
    } elseif (!preg_match('/^[0-9]+$/', $engine)) {
        $errors['car_engine_no'] = 'Car engine number must contain only numbers (digits 0-9).';
    } elseif (strlen($engine) < 3 || strlen($engine) > 20) {
        $errors['car_engine_no'] = 'Car engine number must be between 3 and 20 digits.';
    }

    // Appointment Date - requirement: "Appointment date (that it has only date in it)"
    $date = trim($data['appointment_date'] ?? '');
    if (empty($date)) {
        $errors['appointment_date'] = 'Appointment date is required.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $errors['appointment_date'] = 'Appointment date must be a valid date format (YYYY-MM-DD).';
    } else {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dt || $dt->format('Y-m-d') !== $date) {
            $errors['appointment_date'] = 'Please enter a valid calendar date.';
        } elseif ($date < date('Y-m-d')) {
            $errors['appointment_date'] = 'Appointment date cannot be in the past.';
        }
    }

    // Desired Mechanic
    $mechanicId = (int)($data['mechanic_id'] ?? 0);
    if ($mechanicId <= 0) {
        $errors['mechanic_id'] = 'You must select a desired mechanic from the available list.';
    }

    return $errors;
}

/**
 * Process client appointment booking with strict database transactions
 * and atomic concurrency checks.
 *
 * @param PDO $pdo
 * @param array $data Validated input data
 * @return array ['success' => bool, 'message' => string, 'appointment_id' => int|null, 'data' => array]
 */
function bookAppointment($pdo, $data) {
    try {
        $pdo->beginTransaction();

        $date = trim($data['appointment_date']);
        $license = strtoupper(trim($data['car_license_no']));
        $phone = trim($data['client_phone']);
        $mechanicId = (int)$data['mechanic_id'];

        // 1. Check duplicate booking on the same date for this client (license or phone)
        $existing = checkClientHasAppointmentOnDate($pdo, $date, $license, $phone);
        if ($existing) {
            $pdo->rollBack();
            return [
                'success' => false,
                'error_type' => 'duplicate_booking',
                'message' => "You already have an appointment on {$date} with mechanic '{$existing['mechanic_name']}' for vehicle {$existing['car_license_no']}."
            ];
        }

        // 2. Check if selected mechanic is occupied (max 4 cars)
        $slotInfo = checkMechanicSlotAvailable($pdo, $mechanicId, $date);
        if (!$slotInfo['available']) {
            $pdo->rollBack();
            return [
                'success' => false,
                'error_type' => 'mechanic_full',
                'message' => "Mechanic {$slotInfo['mechanic_name']} is currently fully occupied (4/4 clients booked) on {$date}. Please select another available senior mechanic or choose another date."
            ];
        }

        // 3. Insert appointment
        $stmt = $pdo->prepare("
            INSERT INTO `appointments` (
                `client_name`, `client_address`, `client_phone`, 
                `car_license_no`, `car_engine_no`, `appointment_date`, 
                `mechanic_id`, `status`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')
        ");

        $stmt->execute([
            trim($data['client_name']),
            trim($data['client_address']),
            $phone,
            $license,
            trim($data['car_engine_no']),
            $date,
            $mechanicId
        ]);

        $newId = (int)$pdo->lastInsertId();
        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Appointment approved successfully!',
            'appointment_id' => $newId,
            'mechanic_name' => $slotInfo['mechanic_name'],
            'date' => $date
        ];

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [
            'success' => false,
            'error_type' => 'system_error',
            'message' => 'Database error occurred: ' . $e->getMessage()
        ];
    }
}

/**
 * Admin: Change appointment date and assigned mechanic
 * Requirement: "If admin wants he can change the date of appointment and 
 * also the assigned mechanic name with any other available mechanic for any specific client."
 *
 * @param PDO $pdo
 * @param int $appointmentId
 * @param string $newDate
 * @param int $newMechanicId
 * @param string|null $adminNotes
 * @param string|null $newStatus
 * @return array ['success' => bool, 'message' => string]
 */
function updateAppointmentByAdmin($pdo, $appointmentId, $newDate, $newMechanicId, $adminNotes = null, $newStatus = null) {
    try {
        $pdo->beginTransaction();

        // Fetch current appointment
        $stmt = $pdo->prepare("SELECT * FROM `appointments` WHERE `id` = ?");
        $stmt->execute([(int)$appointmentId]);
        $current = $stmt->fetch();

        if (!$current) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Appointment not found.'];
        }

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate)) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Invalid date format provided.'];
        }

        // Check if target mechanic has free slots on target date (excluding this appointment)
        $slotInfo = checkMechanicSlotAvailable($pdo, $newMechanicId, $newDate, $appointmentId);
        if (!$slotInfo['available']) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => "Target mechanic {$slotInfo['mechanic_name']} is already fully occupied (4/4 cars) on {$newDate}. Please choose another mechanic or date."
            ];
        }

        // Check if client has another appointment on newDate (excluding this one)
        $duplicate = checkClientHasAppointmentOnDate($pdo, $newDate, $current['car_license_no'], $current['client_phone'], $appointmentId);
        if ($duplicate) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => "Client already has another appointment on {$newDate} with {$duplicate['mechanic_name']}."
            ];
        }

        // Update appointment
        $sql = "
            UPDATE `appointments`
            SET `appointment_date` = ?,
                `mechanic_id` = ?,
                `admin_notes` = COALESCE(?, `admin_notes`),
                `status` = COALESCE(?, `status`),
                `updated_at` = CURRENT_TIMESTAMP
            WHERE `id` = ?
        ";

        $updateStmt = $pdo->prepare($sql);
        $updateStmt->execute([
            $newDate,
            (int)$newMechanicId,
            $adminNotes,
            $newStatus,
            (int)$appointmentId
        ]);

        $pdo->commit();
        return [
            'success' => true,
            'message' => "Appointment #{$appointmentId} updated successfully. New date: {$newDate}, Assigned mechanic: {$slotInfo['mechanic_name']}."
        ];

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
    }
}

/**
 * Admin: Delete appointment
 */
function deleteAppointment($pdo, $appointmentId) {
    try {
        $stmt = $pdo->prepare("DELETE FROM `appointments` WHERE `id` = ?");
        $stmt->execute([(int)$appointmentId]);
        return ['success' => true, 'message' => 'Appointment deleted successfully.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to delete appointment: ' . $e->getMessage()];
    }
}

/**
 * Sanitize output string for safe HTML rendering
 */
function h($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}
?>

