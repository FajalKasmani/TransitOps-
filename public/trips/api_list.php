<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';

use Api\Classes\Auth;
use Api\Classes\Database;

header('Content-Type: application/json; charset=utf-8');

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager', 'driver'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Access Denied.']);
    exit;
}

$role = $_SESSION['role_name'] ?? '';
$email = $_SESSION['email'] ?? '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$offset = ($page - 1) * $limit;

try {
    $pdo = Database::getInstance();
    
    $whereClauses = [];
    $params = [];
    
    if ($role === 'driver') {
        $stmt = $pdo->prepare("SELECT id FROM drivers WHERE email = :email AND is_deleted = 0 LIMIT 1");
        $stmt->execute(['email' => $email]);
        $driverId = (int)$stmt->fetchColumn();
        
        $whereClauses[] = "t.driver_id = :driver_id";
        $params['driver_id'] = $driverId;
    }
    
    if (!empty($search)) {
        $whereClauses[] = "(t.source LIKE :search OR t.destination LIKE :search OR v.vehicle_name LIKE :search OR v.registration_number LIKE :search OR d.name LIKE :search OR t.status LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }
    
    $whereSQL = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";
    
    // Count Query
    $countSql = "
        SELECT COUNT(*) 
        FROM trips t
        JOIN vehicles v ON t.vehicle_id = v.id
        JOIN drivers d ON t.driver_id = d.id
        $whereSQL
    ";
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($params);
    $totalRecords = (int)$stmtCount->fetchColumn();
    
    // Data Query
    $dataSql = "
        SELECT t.*, v.vehicle_name, v.registration_number, d.name as driver_name 
        FROM trips t
        JOIN vehicles v ON t.vehicle_id = v.id
        JOIN drivers d ON t.driver_id = d.id
        $whereSQL
        ORDER BY t.id DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmtData = $pdo->prepare($dataSql);
    $stmtData->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    foreach ($params as $key => $val) {
        $stmtData->bindValue(':' . $key, $val);
    }
    
    $stmtData->execute();
    $trips = $stmtData->fetchAll(PDO::FETCH_ASSOC);
    
    $totalPages = (int)ceil($totalRecords / $limit);
    
    echo json_encode([
        'trips' => $trips,
        'total' => $totalRecords,
        'page' => $page,
        'limit' => $limit,
        'totalPages' => $totalPages,
        'role' => $role
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
}
