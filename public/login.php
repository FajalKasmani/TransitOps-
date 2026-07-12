<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/classes/Database.php';
require_once __DIR__ . '/../api/classes/Auth.php';

use Api\Classes\Auth;

Auth::startSession();

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if ($email && $password) {
        try {
            if (!Auth::verifyCsrfToken($csrfToken)) {
                throw new RuntimeException("Invalid CSRF token. Request verification failed.");
            }
            if (Auth::login($email, $password)) {
                header("Location: index.php");
                exit;
            } else {
                $error = "Invalid email or password. Please try again.";
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TransitOps - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --bg-gradient-dark: radial-gradient(at 0% 0%, #0f172a 0px, transparent 50%), 
                                radial-gradient(at 50% 0%, #1e1b4b 0px, transparent 50%), 
                                radial-gradient(at 100% 0%, #0f172a 0px, transparent 50%),
                                radial-gradient(at 0% 100%, #111827 0px, transparent 50%),
                                #070a12;
            --bg-gradient-light: radial-gradient(at 0% 0%, #e0e7ff 0px, transparent 50%), 
                                 radial-gradient(at 50% 0%, #f1f5f9 0px, transparent 50%), 
                                 radial-gradient(at 100% 0%, #fae8ff 0px, transparent 50%),
                                 #f8fafc;
            --glass-bg-dark: rgba(15, 23, 42, 0.45);
            --glass-bg-light: rgba(255, 255, 255, 0.55);
            --glass-border-dark: rgba(255, 255, 255, 0.07);
            --glass-border-light: rgba(15, 23, 42, 0.06);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-gradient-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
            transition: background 0.5s ease;
        }
        
        [data-bs-theme="light"] body {
            background: var(--bg-gradient-light);
        }
        
        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 20px;
            z-index: 10;
        }
        
        .glass-card {
            background: var(--glass-bg-dark);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--glass-border-dark);
            border-radius: 28px;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.4);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s ease, border-color 0.3s ease;
        }
        
        [data-bs-theme="light"] .glass-card {
            background: var(--glass-bg-light);
            border: 1px solid var(--glass-border-light);
            box-shadow: 0 30px 60px -15px rgba(15, 23, 42, 0.08);
        }
        
        .glass-card:hover {
            transform: translateY(-5px) scale(1.005);
            border-color: rgba(99, 102, 241, 0.35);
        }
        
        .brand-logo {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #38bdf8 0%, #6366f1 50%, #a855f7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.7px;
        }
        
        [data-bs-theme="light"] .brand-logo {
            background: linear-gradient(135deg, #0284c7 0%, #4f46e5 50%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .input-group-custom {
            position: relative;
        }

        .input-group-custom .form-control {
            border-radius: 14px;
            padding: 14px 44px 14px 48px;
            background: rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #f8fafc;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-bs-theme="light"] .input-group-custom .form-control {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid #cbd5e1;
            color: #0f172a;
        }
        
        .input-group-custom .form-control:focus {
            background: rgba(15, 23, 42, 0.6);
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.18);
            color: #ffffff;
        }

        [data-bs-theme="light"] .input-group-custom .form-control:focus {
            background: #ffffff;
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.14);
            color: #0f172a;
        }

        .input-group-custom .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 5;
            transition: color 0.25s ease;
            font-size: 1.1rem;
        }

        .input-group-custom .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 5;
            cursor: pointer;
            transition: color 0.25s ease;
            font-size: 1.1rem;
        }

        .input-group-custom .form-control:focus ~ .input-icon {
            color: #6366f1 !important;
        }
        [data-bs-theme="light"] .input-group-custom .form-control:focus ~ .input-icon {
            color: #4f46e5 !important;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            padding: 14px;
            border-radius: 14px;
            font-weight: 600;
            color: #ffffff;
            box-shadow: 0 4px 18px rgba(79, 70, 229, 0.4);
            transition: all 0.25s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.55);
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            color: #ffffff;
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        /* Sleek Theme Toggle Element */
        .theme-switch-btn {
            cursor: pointer;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.06);
            background: rgba(255,255,255,0.03);
            transition: all 0.2s ease;
        }

        [data-bs-theme="light"] .theme-switch-btn {
            border: 1px solid rgba(0,0,0,0.06);
            background: rgba(0,0,0,0.03);
        }

        .theme-switch-btn:hover {
            background: rgba(255,255,255,0.08);
            transform: scale(1.05);
        }
        [data-bs-theme="light"] .theme-switch-btn:hover {
            background: rgba(0,0,0,0.06);
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="glass-card p-4 p-sm-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2">
            <h1 class="brand-logo mb-0 d-flex align-items-center">
                <i class="bi bi-speedometer2 me-2"></i>TransitOps
            </h1>
            
            <div class="theme-switch-btn" id="themeToggle" role="button" aria-label="Toggle Theme">
                <i class="bi bi-moon-stars-fill text-info" id="themeIcon" style="font-size: 1.1rem;"></i>
            </div>
        </div>

        <h3 class="fw-bold mb-1 text-emphasis">Welcome</h3>
        <p class="text-secondary mb-4 small">Enter details to access transport operations dashboard.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center alert-dismissible fade show border-0 bg-danger-subtle text-danger-emphasis rounded-3 p-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div class="small fw-medium">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>


        <form method="POST" action="login.php" id="loginForm" class="needs-validation" novalidate>
    
        <form method="POST" action="login.php" class="needs-validation" novalidate>
            <?php echo Api\Classes\Auth::getCsrfInput(); ?>

            <div class="mb-3">
                <label for="email" class="form-label text-secondary small fw-semibold mb-2">Email Address</label>
                <div class="input-group-custom">
                    <input type="email" class="form-control" id="email" name="email" required placeholder="name@company.com" autocomplete="email">
                    <i class="bi bi-envelope text-muted input-icon"></i>
                </div>
            </div>
            
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label for="password" class="form-label text-secondary small fw-semibold mb-0">Password</label>
                </div>
                <div class="input-group-custom">
                    <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••" autocomplete="current-password">
                    <i class="bi bi-lock text-muted input-icon"></i>
                    <i class="bi bi-eye text-muted password-toggle" id="passwordToggle"></i>
                </div>
            </div>

            <button type="submit" class="btn btn-submit w-100 d-flex align-items-center justify-content-center" id="submitBtn">
                <span id="btnText">Sign In</span>
                <i class="bi bi-arrow-right-short ms-1 fs-5 align-middle" id="btnIcon"></i>
                <div class="spinner-border spinner-border-sm text-light d-none" role="status" id="btnSpinner"></div>
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const htmlElement = document.documentElement;
    const passwordToggle = document.getElementById('passwordToggle');
    const passwordInput = document.getElementById('password');
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnIcon = document.getElementById('btnIcon');
    const btnSpinner = document.getElementById('btnSpinner');

    // 1. Storage Theme Sync Logic
    const savedTheme = localStorage.getItem('theme') || 'dark';
    setTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
        const currentTheme = htmlElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
    });

    function setTheme(theme) {
        htmlElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);
        
        if (theme === 'dark') {
            themeIcon.className = 'bi bi-moon-stars-fill text-info';
        } else {
            themeIcon.className = 'bi bi-sun-fill text-warning';
        }
    }

    // 2. Dynamic Mask Toggle Script
    passwordToggle.addEventListener('click', () => {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        passwordToggle.classList.toggle('bi-eye');
        passwordToggle.classList.toggle('bi-eye-slash');
    });

    // 3. Prevent Double Submit & Provide Micro-loading State
    loginForm.addEventListener('submit', function (e) {
        if (this.checkValidity()) {
            submitBtn.setAttribute('disabled', 'true');
            btnText.textContent = "Verifying...";
            btnIcon.classList.add('d-none');
            btnSpinner.classList.remove('d-none');
        }
    });
</script>
</body>
</html>