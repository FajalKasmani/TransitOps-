<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';

use Api\Classes\Auth;

Auth::startSession();

// Enforce authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Enforce session timeout (30 minutes)
if (!Auth::enforceTimeout(1800)) {
    header("Location: login.php?timeout=1");
    exit;
}

$config = require __DIR__ . '/../../config.php';
$baseUrl = $config['base_url'] ?? 'http://localhost/TransitOps/public';

$currentRole = $_SESSION['role_name'] ?? '';
$fullName = $_SESSION['full_name'] ?? 'User';
$email = $_SESSION['email'] ?? '';

// Determine active page
$activePage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TransitOps Dashboard</title>
    <link rel="icon" type="image/png" href="<?php echo $baseUrl; ?>/favicon.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        /* Custom Utilities */
        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }
        body[data-theme="dark"] .hover-lift:hover {
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3) !important;
        }
        
        /* 3D Card Effect */
        .card-3d {
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            background-color: var(--bs-card-bg);
            background-image: linear-gradient(145deg, rgba(255, 255, 255, 0.05) 0%, rgba(0, 0, 0, 0.02) 100%);
            z-index: 1;
        }
        body[data-theme="dark"] .card-3d {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.05);
            background-image: linear-gradient(145deg, rgba(255, 255, 255, 0.02) 0%, rgba(0, 0, 0, 0.1) 100%);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .card-3d::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(180deg, rgba(255,255,255,0.1) 0%, transparent 100%);
            opacity: 0.5;
            pointer-events: none;
            z-index: -1;
        }

        /* Glassmorphism Progress Bar */
        .progress-glass {
            height: 6px;
            background-color: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        body[data-theme="dark"] .progress-glass {
            background-color: rgba(255, 255, 255, 0.1);
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.3);
        }


        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background-color: var(--bs-body-bg);
            transition: background-color 0.3s ease;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            background-color: #0f172a;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        body[data-theme="light"] .sidebar {
            background-color: #f8fafc;
            border-right: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        body[data-theme="light"] .sidebar-brand {
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sidebar-brand .brand-logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(to right, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        body[data-theme="light"] .sidebar-brand .brand-logo {
            background: linear-gradient(to right, #0284c7, #4f46e5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-menu {
            list-style: none;
            padding: 1rem 0;
            margin: 0;
        }

        .sidebar-menu-item {
            padding: 0.25rem 1rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            color: #94a3b8;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        body[data-theme="light"] .sidebar-link {
            color: #475569;
        }

        .sidebar-link:hover, .sidebar-link.active {
            color: #ffffff;
            background-color: rgba(99, 102, 241, 0.15);
        }

        body[data-theme="light"] .sidebar-link:hover, body[data-theme="light"] .sidebar-link.active {
            color: #4f46e5;
            background-color: rgba(79, 70, 229, 0.08);
        }

        .sidebar-link i {
            font-size: 1.25rem;
            margin-right: 0.75rem;
            transition: all 0.3s ease;
        }

        .sidebar-link:hover i, .sidebar-link.active i {
            color: #6366f1;
        }

        /* Main Content Panel */
        .main-wrapper {
            margin-left: 260px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        .top-navbar {
            height: 70px;
            background-color: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 99;
        }

        body[data-theme="light"] .top-navbar {
            background-color: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .content-body {
            padding: 2rem;
            flex-grow: 1;
        }

        /* User Profile Dropdown / Badge */
        .role-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
        }

        /* Responsive Breakpoints */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-wrapper {
                margin-left: 0;
            }
            .top-navbar {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar Navigation -->
<div class="sidebar" id="sidebarMenu">
    <div class="sidebar-brand d-flex justify-content-between align-items-center">
        <a href="<?php echo $baseUrl; ?>/index.php" class="brand-logo"><i class="bi bi-speedometer2 me-2"></i>TransitOps</a>
        <button class="btn btn-sm d-lg-none text-muted" onclick="toggleSidebar()"><i class="bi bi-x-lg"></i></button>
    </div>
    
    <ul class="sidebar-menu">
        <!-- Dashboard Link: Accessible to all roles -->
        <li class="sidebar-menu-item">
            <a href="<?php echo $baseUrl; ?>/index.php" class="sidebar-link <?php echo $activePage === 'index.php' ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
        </li>

        <?php if (in_array($currentRole, ['admin', 'fleet_manager'], true)): ?>
            <!-- Vehicles: Admin and Fleet Manager -->
            <li class="sidebar-menu-item">
                <a href="<?php echo $baseUrl; ?>/vehicles/list.php" class="sidebar-link <?php echo str_starts_with($activePage, 'vehicles') || str_contains($_SERVER['PHP_SELF'], '/vehicles/') ? 'active' : ''; ?>">
                    <i class="bi bi-truck"></i> Vehicles Registry
                </a>
            </li>
        <?php endif; ?>

        <?php if (in_array($currentRole, ['admin', 'fleet_manager', 'safety_officer'], true)): ?>
            <!-- Drivers: Admin, Fleet Manager, Safety Officer -->
            <li class="sidebar-menu-item">
                <a href="<?php echo $baseUrl; ?>/drivers/list.php" class="sidebar-link <?php echo str_starts_with($activePage, 'drivers') || str_contains($_SERVER['PHP_SELF'], '/drivers/') ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill"></i> Drivers Registry
                </a>
            </li>
        <?php endif; ?>

        <?php if (in_array($currentRole, ['admin', 'fleet_manager', 'driver'], true)): ?>
            <!-- Trips: Admin, Fleet Manager, Driver -->
            <li class="sidebar-menu-item">
                <a href="<?php echo $baseUrl; ?>/trips/list.php" class="sidebar-link <?php echo str_starts_with($activePage, 'trips') || str_contains($_SERVER['PHP_SELF'], '/trips/') ? 'active' : ''; ?>">
                    <i class="bi bi-map-fill"></i> Trip Logs
                </a>
            </li>
        <?php endif; ?>

        <?php if (in_array($currentRole, ['admin', 'fleet_manager', 'safety_officer'], true)): ?>
            <!-- Maintenance: Admin, Fleet Manager, Safety Officer -->
            <li class="sidebar-menu-item">
                <a href="<?php echo $baseUrl; ?>/maintenance/list.php" class="sidebar-link <?php echo str_starts_with($activePage, 'maintenance') || str_contains($_SERVER['PHP_SELF'], '/maintenance/') ? 'active' : ''; ?>">
                    <i class="bi bi-wrench-adjustable"></i> Maintenance
                </a>
            </li>
        <?php endif; ?>

        <?php if (in_array($currentRole, ['admin', 'fleet_manager', 'financial_analyst'], true)): ?>
            <!-- Fuel & Expenses: Admin, Fleet Manager, Financial Analyst -->
            <li class="sidebar-menu-item">
                <a href="<?php echo $baseUrl; ?>/expenses/list.php" class="sidebar-link <?php echo str_starts_with($activePage, 'expenses') || str_contains($_SERVER['PHP_SELF'], '/expenses/') ? 'active' : ''; ?>">
                    <i class="bi bi-wallet2"></i> Fuel & Expenses
                </a>
            </li>
        <?php endif; ?>

        <?php if (in_array($currentRole, ['admin', 'fleet_manager', 'financial_analyst'], true)): ?>
            <!-- Reports: Admin, Fleet Manager, Financial Analyst -->
            <li class="sidebar-menu-item">
                <a href="<?php echo $baseUrl; ?>/reports/list.php" class="sidebar-link <?php echo str_starts_with($activePage, 'reports') || str_contains($_SERVER['PHP_SELF'], '/reports/') ? 'active' : ''; ?>">
                    <i class="bi bi-bar-chart-line-fill"></i> Analytics & Reports
                </a>
            </li>
        <?php endif; ?>

        <?php if ($currentRole === 'admin'): ?>
            <!-- Admin Settings & Management -->
            <li class="sidebar-menu-item-header text-uppercase text-muted px-4 mt-3 mb-1" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05rem; list-style-type: none;">Administration</li>
            <li class="sidebar-menu-item">
                <a href="<?php echo $baseUrl; ?>/admin/users.php" class="sidebar-link <?php echo str_contains($_SERVER['PHP_SELF'], '/admin/users.php') ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i> User Management
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="<?php echo $baseUrl; ?>/admin/trash.php" class="sidebar-link <?php echo str_contains($_SERVER['PHP_SELF'], '/admin/trash.php') ? 'active' : ''; ?>">
                    <i class="bi bi-trash"></i> Trash Recovery
                </a>
            </li>
        <?php endif; ?>
    </ul>
</div>

<!-- Main Body Wrapper -->
<div class="main-wrapper">
    <!-- Top Navbar -->
    <header class="top-navbar">
        <div class="d-flex align-items-center">
            <button class="btn text-muted me-2 d-lg-none" onclick="toggleSidebar()"><i class="bi bi-list fs-3"></i></button>
            <h5 class="mb-0 fw-semibold text-light-theme" style="text-transform: capitalize;">
                <?php 
                    $pageTitle = str_replace(['.php', '_'], ['', ' '], $activePage);
                    echo htmlspecialchars($pageTitle === 'index' ? 'Dashboard Summary' : ($pageTitle === 'list' ? 'List Registry' : $pageTitle));
                ?>
            </h5>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <!-- Theme Toggle Switch -->
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch" id="headerThemeToggle" checked>
                <label class="form-check-label text-muted" for="headerThemeToggle"><i class="bi bi-moon-fill" id="headerThemeIcon"></i></label>
            </div>

            <!-- Vertical Divider -->
            <div class="vr bg-secondary opacity-25" style="height: 30px;"></div>

            <!-- User Info Dropdown -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-light-theme" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="avatar bg-indigo-subtle text-indigo rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 38px; height: 38px; font-weight: 700; background-color: #6366f120; color: #6366f1;">
                        <?php echo strtoupper(substr($fullName, 0, 2)); ?>
                    </div>
                    <div class="d-none d-md-block text-start">
                        <div class="fw-semibold small lh-1"><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></div>
                        <span class="role-badge bg-secondary-subtle text-secondary lh-1 mt-1 d-inline-block">
                            <?php echo htmlspecialchars(str_replace('_', ' ', $currentRole), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDropdown">
                    <li class="px-3 py-2 border-bottom border-light">
                        <div class="small text-muted">Signed in as</div>
                        <div class="fw-bold text-truncate" style="max-width: 180px;"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></div>
                    </li>
                    <li><a class="dropdown-item py-2 text-light-theme" href="<?php echo $baseUrl; ?>/change_password.php"><i class="bi bi-key me-2"></i> Change Password</a></li>
                    <li><a class="dropdown-item py-2 text-danger" href="<?php echo $baseUrl; ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Content Body -->
    <main class="content-body">
