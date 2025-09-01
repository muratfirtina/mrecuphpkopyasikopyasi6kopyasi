<?php
/**
 * Mr ECU - AJAX Kredi Filtreleme (Basitleştirilmiş)
 */

// Error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();

try {
    require_once '../config/config.php';
    require_once '../config/database.php';
    
    // Clean any unexpected output
    ob_clean();
    header('Content-Type: application/json');
    
    // Giriş kontrolü
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    
    // Debug için POST verilerini logla
    error_log('AJAX POST data: ' . print_r($_POST, true));
    
    $userId = $_SESSION['user_id'];
    
    // Filtreleme parametreleri
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $dateFrom = isset($_POST['date_from']) ? trim($_POST['date_from']) : '';
    $dateTo = isset($_POST['date_to']) ? trim($_POST['date_to']) : '';
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $limit = 20;
    
    // WHERE clause oluştur
    $whereClause = 'WHERE ct.user_id = ?';
    $params = [$userId];
    
    // Filtreleri ekle
    if (!empty($type)) {
        $whereClause .= ' AND (ct.transaction_type = ? OR ct.type = ?)';
        $params[] = $type;
        $params[] = $type;
    }
    
    if (!empty($dateFrom)) {
        $whereClause .= ' AND DATE(ct.created_at) >= ?';
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $whereClause .= ' AND DATE(ct.created_at) <= ?';
        $params[] = $dateTo;
    }
    
    // Sayfalama
    $offset = ($page - 1) * $limit;
    
    // LIMIT ve OFFSET değerlerini integer'a çevir
    $limit = (int)$limit;
    $offset = (int)$offset;
    
    // Ana sorgu - LIMIT ve OFFSET'i direkt sorguya ekle
    $query = "
        SELECT ct.*, u.username as admin_username,
               CASE 
                   WHEN ct.transaction_type IS NOT NULL THEN ct.transaction_type
                   WHEN ct.type IS NOT NULL THEN ct.type
                   ELSE 'unknown'
               END as effective_type
        FROM credit_transactions ct
        LEFT JOIN users u ON ct.admin_id = u.id
        {$whereClause}
        ORDER BY ct.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    // Sadece WHERE clause parametrelerini kullan
    error_log('Query: ' . $query);
    error_log('Params: ' . print_r($params, true));
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Toplam sayı
    $countQuery = "
        SELECT COUNT(*) 
        FROM credit_transactions ct
        {$whereClause}
    ";
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $filteredTransactions = $countStmt->fetchColumn();
    $totalPages = ceil($filteredTransactions / $limit);
    
    // Basit pagination HTML
    $paginationHtml = '';
    if ($totalPages > 1) {
        $paginationHtml = '<div class="pagination-compact mt-3">';
        $paginationHtml .= '<div class="text-center">';
        $paginationHtml .= '<small class="text-muted">Sayfa ' . $page . ' / ' . $totalPages . ' (Toplam ' . $filteredTransactions . ' işlem)</small>';
        $paginationHtml .= '</div></div>';
    } elseif ($filteredTransactions > 0) {
        $paginationHtml = '<div class="pagination-compact mt-3"><div class="text-center"><small class="text-muted"><i class="bi bi-check-circle text-success me-1"></i>Tüm işlemler gösteriliyor (' . $filteredTransactions . ' işlem)</small></div></div>';
    }
    
    // Başarılı response
    $response = [
        'success' => true,
        'transactions' => $transactions,
        'pagination' => [
            'html' => $paginationHtml,
            'current_page' => $page,
            'total_pages' => $totalPages
        ],
        'info' => [
            'total' => $filteredTransactions,
            'showing' => count($transactions),
            'page' => $page
        ],
        'debug' => [
            'user_id' => $userId,
            'filters' => [
                'type' => $type,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'page' => $page
            ],
            'query_info' => [
                'where_clause' => $whereClause,
                'params_count' => count($params),
                'limit' => $limit,
                'offset' => $offset,
                'total_found' => $filteredTransactions
            ]
        ]
    ];
    
    echo json_encode($response);

} catch(PDOException $e) {
    error_log('Credits AJAX PDO error: ' . $e->getMessage());
    error_log('PDO error trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'debug' => [
            'post_data' => $_POST,
            'user_id' => $_SESSION['user_id'] ?? 'none',
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine()
        ]
    ]);
    
} catch(Exception $e) {
    error_log('Credits AJAX general error: ' . $e->getMessage());
    error_log('General error trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => 'General error: ' . $e->getMessage(),
        'debug' => [
            'post_data' => $_POST,
            'user_id' => $_SESSION['user_id'] ?? 'none',
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine()
        ]
    ]);
}
?>