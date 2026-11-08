<?php
/**
 * Shared Header Component
 * CSE 391: Programming for the Internet - Assignment 3
 */

if (!isset($pageTitle)) {
    $pageTitle = 'Car Workshop Online Appointment System';
}
if (!isset($activeNav)) {
    $activeNav = 'home';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Apex Auto Workshop</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/assets/css/style.css">
</head>
<body>

<!-- Navigation Bar -->
<header class="navbar">
    <div class="container">
        <div class="navbar-inner">
            <a href="<?= getBaseUrl() ?>/index.php" class="nav-brand">
                <div class="brand-icon">⚙️</div>
                <div>
                    Apex Auto Workshop
                    <span>Senior Mechanics & Precision Service</span>
                </div>
            </a>

            <nav>
                <ul class="nav-links">
                    <li class="nav-item">
                        <a href="<?= getBaseUrl() ?>/index.php" class="<?= $activeNav === 'home' ? 'active' : '' ?>">
                            📅 Book Appointment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= getBaseUrl() ?>/status.php" class="<?= $activeNav === 'status' ? 'active' : '' ?>">
                            🔍 Check My Status
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= getBaseUrl() ?>/index.php#help-section">
                            ❓ Help & Guidelines
                        </a>
                    </li>
                    <li class="nav-item nav-btn">
                        <a href="<?= getBaseUrl() ?>/admin/index.php" class="btn btn-secondary btn-sm <?= $activeNav === 'admin' ? 'active' : '' ?>">
                            🛡️ Admin Panel
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</header>

