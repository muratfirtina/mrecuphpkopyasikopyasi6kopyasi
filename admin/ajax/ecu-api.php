<?php
/**
 * Mr ECU - ECU AJAX API
 * ECU işlemleri için AJAX endpoint
 */

header('Content-Type: application/json');
require_once '../../config/config.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

require_once '../../includes/EcuModel.php';
$ecuModel = new EcuModel($pdo);

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
            $ecus = $ecuModel->searchEcus($search, 100);
            $result = [
                'success' => true,
                'data' => $ecus,
                'total' => count($ecus),
                'page' => 1,
                'perPage' => count($ecus),
                'totalPages' => 1
            ];
        } else {
            $result = $ecuModel->getEcusPaginated($page, $perPage);
            $result['success'] = true;
        }
        
        echo json_encode($result);
        break;
        
    case 'get':
        $id = sanitize($_GET['id'] ?? '');
        $ecu = $ecuModel->getEcuById($id);
        
        if ($ecu) {
            echo json_encode(['success' => true, 'data' => $ecu]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'ECU bulunamadı']);
        }
        break;
        
    case 'add':
        $name = sanitize($_POST['name'] ?? '');
        $result = $ecuModel->addEcu($name);
        
        http_response_code($result['success'] ? 201 : 400);
        echo json_encode($result);
        break;
        
    case 'update':
        $id = sanitize($_POST['id'] ?? '');
        $name = sanitize($_POST['name'] ?? '');
        $result = $ecuModel->updateEcu($id, $name);
        
        http_response_code($result['success'] ? 200 : 400);
        echo json_encode($result);
        break;
        
    case 'delete':
        $id = sanitize($_POST['id'] ?? '');
        $result = $ecuModel->deleteEcu($id);
        
        http_response_code($result['success'] ? 200 : 400);
        echo json_encode($result);
        break;
        
    case 'search':
        $term = sanitize($_GET['term'] ?? '');
        $limit = (int)($_GET['limit'] ?? 20);
        
        $ecus = $ecuModel->searchEcus($term, $limit);
        echo json_encode([
            'success' => true,
            'data' => $ecus,
            'count' => count($ecus)
        ]);
        break;
        
    case 'stats':
        $totalCount = $ecuModel->getTotalEcuCount();
        echo json_encode([
            'success' => true,
            'data' => [
                'total_ecus' => $totalCount
            ]
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
        break;
}
?>
