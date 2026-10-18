<?php
// login.php - Authentication screen
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: pos.php");
    }
    exit;
}

$error = '';
$settings = get_settings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            log_activity($pdo, $user['id'], 'User Login', 'Auth', "User {$user['username']} logged in successfully");

            if ($user['role'] === 'admin') {
                header("Location: dashboard.php");
            } else {
                header("Location: pos.php");
            }
            exit;
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?= htmlspecialchars($settings['store_name'] ?? 'Metro POS') ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Security: Developer Tools Blocker -->
    <script src="assets/js/anti-devtools.js?v=3.0"></script>
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 10% 20%, #1e1b4b 0%, #0f172a 90%);
            padding: 20px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: var(--radius-xl);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 440px;
            padding: 40px;
            position: relative;
            backdrop-filter: blur(8px);
        }
        .brand-badge {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #4f46e5, #0ea5e9);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: white;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.35);
            margin: 0 auto 20px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .login-header h2 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .login-header p {
            font-size: 13px;
            color: #64748b;
        }
        .demo-roles-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: var(--radius-md);
            padding: 12px 16px;
            margin-top: 24px;
        }
        .demo-roles-box p {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .quick-fill-btn {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            margin-right: 6px;
            transition: all 0.15s;
        }
        .quick-fill-btn:hover {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-badge">
        <i class="bi bi-book-half"></i>
    </div>
    
    <div class="login-header">
        <h2><?= htmlspecialchars($settings['store_name'] ?? 'Metro POS') ?></h2>
        <p>Communication & Bookshop Management System</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label class="form-label">Username</label>
            <div style="position: relative;">
                <input type="text" name="username" id="username" class="form-control" placeholder="Enter username" required autofocus autocomplete="username">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 10px;">
            <i class="bi bi-box-arrow-in-right"></i> Sign In to Terminal
        </button>
    </form>

    <!-- <div class="demo-roles-box">
        <p><i class="bi bi-lightning-charge-fill" style="color: #f59e0b;"></i> Quick Fill Demo Accounts:</p>
        <button type="button" class="quick-fill-btn" onclick="fillCreds('admin', 'admin123')">
            <i class="bi bi-shield-check"></i> Admin (admin / admin123)
        </button>
        <button type="button" class="quick-fill-btn" onclick="fillCreds('cashier', 'cashier123')">
            <i class="bi bi-cash-stack"></i> Cashier (cashier / cashier123)
        </button>
    </div> -->
</div>

<script>
function fillCreds(u, p) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = p;
}
</script>

</body>
</html>
