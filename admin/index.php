<?php
/**
 * Admin Panel - Authentication & Login
 * CSE 391: Assignment 3 - Car Workshop Appointment System
 */

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT * FROM `admins` WHERE `username` = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'];

                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid admin username or password.';
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workshop Admin Login | Apex Auto Workshop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }
        .login-header {
            background: #1e293b;
            color: white;
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid #334155;
        }
        .login-header h2 { color: white; font-size: 1.5rem; margin-bottom: 6px; }
        .login-header p { color: #94a3b8; font-size: 0.85rem; }
        .login-body { padding: 32px; }
        .demo-box {
            background: #f1f5f9;
            border-left: 4px solid var(--primary);
            padding: 12px 14px;
            border-radius: 4px;
            font-size: 0.825rem;
            color: #334155;
            margin-bottom: 20px;
        }
        .demo-box code { font-weight: 700; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="brand-icon" style="margin: 0 auto 14px; width: 48px; height: 48px; font-size: 1.5rem;">🛡️</div>
        <h2>Workshop Manager Login</h2>
        <p>Assignment 3 Admin Portal</p>
    </div>

    <div class="login-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="padding: 12px 16px; margin-bottom: 20px;">
                <span class="alert-icon">⚠️</span>
                <div class="alert-body"><?= h($error) ?></div>
            </div>
        <?php endif; ?>

        <div class="demo-box">
            <strong>Default Assessment Credentials:</strong><br>
            Username: <code>admin</code> &bull; Password: <code>admin123</code>
        </div>

        <form method="POST" action="index.php">
            <div class="form-group">
                <label class="form-label" for="username">Admin Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-control" 
                    placeholder="e.g. admin" 
                    value="<?= h($username) ?>"
                    required 
                    autofocus
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="••••••••" 
                    required
                >
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    🔐 Sign In to Dashboard
                </button>
            </div>
        </form>

        <div style="margin-top: 20px; text-align: center;">
            <a href="../index.php" style="font-size: 0.875rem; color: var(--text-muted);">
                &larr; Back to Client Booking Page
            </a>
        </div>
    </div>
</div>

</body>
</html>

