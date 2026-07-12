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

    if ($email && $password) {
        if (Auth::login($email, $password)) {
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid email or password. Please try again.";
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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #311042 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }
        
        /* Light mode overrides */
        body[data-theme="light"] {
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 50%, #f3e8ff 100%);
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 15px;
            z-index: 10;
        }
        
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        body[data-theme="light"] .glass-card {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        }
        
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
        }
        
        body[data-theme="light"] .glass-card:hover {
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.12);
        }
        
        .brand-logo {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(to right, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }
        
        body[data-theme="light"] .brand-logo {
            background: linear-gradient(to right, #0284c7, #4f46e5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #f8fafc;
            transition: all 0.3s ease;
        }
        
        body[data-theme="light"] .form-control {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #0f172a;
        }
        
        .form-control:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
            color: #ffffff;
        }
        
        body[data-theme="light"] .form-control:focus {
            background: #ffffff;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
            color: #0f172a;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(79, 70, 229, 0.5);
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
        }
        
        /* Floating background circles */
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 1;
        }
        
        .circle-1 {
            width: 300px;
            height: 300px;
            background: rgba(99, 102, 241, 0.15);
            top: 15%;
            left: 10%;
        }
        
        .circle-2 {
            width: 400px;
            height: 400px;
            background: rgba(14, 165, 233, 0.12);
            bottom: 10%;
            right: 10%;
        }
    </style>
</head>
<body>

<div class="bg-circle circle-1"></div>
<div class="bg-circle circle-2"></div>

<div class="login-container">
    <div class="glass-card p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="brand-logo mb-0"><i class="bi bi-speedometer2 me-2"></i>TransitOps</h1>
            
            <!-- Dark mode toggle -->
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch" id="themeToggle" checked>
                <label class="form-check-label text-muted" for="themeToggle"><i class="bi bi-moon-fill" id="themeIcon"></i></label>
            </div>
        </div>

        <h3 class="fw-bold mb-1 text-light-theme">Sign In</h3>
        <p class="text-muted mb-4 small">Enter details to access transport operations dashboard.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div>
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label text-muted small fw-semibold">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-muted" style="border-radius: 10px 0 0 10px;"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control border-start-0" id="email" name="email" required placeholder="name@company.com" style="border-radius: 0 10px 10px 0;">
                </div>
            </div>
            
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label text-muted small fw-semibold mb-0">Password</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-muted" style="border-radius: 10px 0 0 10px;"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control border-start-0" id="password" name="password" required placeholder="••••••••" style="border-radius: 0 10px 10px 0;">
                </div>
            </div>

            <button type="submit" class="btn btn-submit w-100 mb-3">
                Sign In <i class="bi bi-arrow-right-short ms-1"></i>
            </button>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const themeIcon = document.getElementById('themeIcon');
    const htmlElement = document.documentElement;

    const savedTheme = localStorage.getItem('theme') || 'dark';
    setTheme(savedTheme);
    themeToggle.checked = savedTheme === 'dark';

    themeToggle.addEventListener('change', () => {
        const theme = themeToggle.checked ? 'dark' : 'light';
        setTheme(theme);
    });

    function setTheme(theme) {
        body.setAttribute('data-theme', theme);
        htmlElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);
        
        if (theme === 'dark') {
            themeIcon.className = 'bi bi-moon-fill';
            document.querySelectorAll('.text-light-theme').forEach(el => {
                el.classList.remove('text-dark');
                el.classList.add('text-white');
            });
        } else {
            themeIcon.className = 'bi bi-sun-fill';
            document.querySelectorAll('.text-light-theme').forEach(el => {
                el.classList.remove('text-white');
                el.classList.add('text-dark');
            });
        }
    }
</script>
</body>
</html>
