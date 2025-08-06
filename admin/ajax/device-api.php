<?php
/**
 * Mr ECU - Device AJAX API
 * Device işlemleri için AJAX endpoint
 */

header('Content-Type: application/json');
require_once '../../config/config.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

require_once '../../includes/DeviceModel.php';
$deviceModel = new DeviceModel($pdo);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF token kontrolü (POST istekleri için)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Güvenlik hatası']);
        exit;
    }
}

switch ($action) {
    case 'list':
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        $search = sanitize($_GET['search'] ?? '');
        
        if (!empty($search)) {
            $devices = $deviceModel->searchDevices($search, 100);
            $result = [
                'success' => true,
                'data' => $devices,
                'total' => count($devices),
                'page' => 1,
                'perPage' => count($devices),
                'totalPages' => 1
            ];
        } else {
            $result = $deviceModel->getDevicesPaginated($page, $perPage);
            $result['success'] = true;
        }
        
        echo json_encode($result);
        break;
        
    case 'get':
        $id = sanitize($_GET['id'] ?? '');
        $device = $deviceModel->getDeviceById($id);
        
        if ($device) {
            echo json_encode(['success' => true, 'data' => $device]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Device bulunamadı']);
        }
        break;
        
    case 'add':
        $name = sanitize($_POST['name'] ?? '');
        $result = $deviceModel->addDevice($name);
        
        http_response_code($result['success'] ? 201 : 400);
        echo json_encode($result);
        break;
        
    case 'update':
        $id = sanitize($_POST['id'] ?? '');
        $name = sanitize($_POST['name'] ?? '');
        $result = $deviceModel->updateDevice($id, $name);
        
        http_response_code($result['success'] ? 200 : 400);
        echo json_encode($result);
        break;
        
    case 'delete':
        $id = sanitize($_POST['id'] ?? '');
        $result = $deviceModel->deleteDevice($id);
        
        http_response_code($result['success'] ? 200 : 400);
        echo json_encode($result);
        break;
        
    case 'search':
        $term = sanitize($_GET['term'] ?? '');
        $limit = (int)($_GET['limit'] ?? 20);
        
        $devices = $deviceModel->searchDevices($term, $limit);
        echo json_encode([
            'success' => true,
            'data' => $devices,
            'count' => count($devices)
        ]);
        break;
        
    case 'stats':
        $totalCount = $deviceModel->getTotalDeviceCount();
        echo json_encode([
            'success' => true,
            'data' => [
                'total_devices' => $totalCount
            ]
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
        break;
}
?>
