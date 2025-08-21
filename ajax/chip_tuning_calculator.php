<?php
/**
 * Chip Tuning Calculator AJAX Endpoint
 * Mevcut database yapısı kullanılarak dinamik form verileri
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once '../config/config.php';
require_once '../config/database.php';

try {
    $action = $_GET['action'] ?? '';
    $response = ['success' => false, 'data' => [], 'message' => ''];

    switch ($action) {
        case 'get_brands':
            $stmt = $pdo->query("
                SELECT id, name 
                FROM brands 
                ORDER BY name ASC
            ");
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            break;

        case 'get_models':
            $brandId = $_GET['brand_id'] ?? '';
            if (empty($brandId)) {
                $response['message'] = 'Geçersiz marka ID';
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT id, name 
                FROM models 
                WHERE brand_id = ? 
                ORDER BY name ASC
            ");
            $stmt->execute([$brandId]);
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            break;

        case 'get_series':
            $modelId = $_GET['model_id'] ?? '';
            if (empty($modelId)) {
                $response['message'] = 'Geçersiz model ID';
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT id, name, year_range 
                FROM series 
                WHERE model_id = ? 
                ORDER BY name ASC
            ");
            $stmt->execute([$modelId]);
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            break;

        case 'get_engines':
            $seriesId = $_GET['series_id'] ?? '';
            if (empty($seriesId)) {
                $response['message'] = 'Geçersiz seri ID';
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT DISTINCT e.id, e.name, e.fuel_type,
                       s.original_power, s.tuning_power, s.difference_power,
                       s.original_torque, s.tuning_torque, s.difference_torque
                FROM engines e
                INNER JOIN stages s ON e.id = s.engine_id
                WHERE e.series_id = ? AND s.is_active = 1
                ORDER BY e.name ASC
            ");
            $stmt->execute([$seriesId]);
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            break;

        case 'get_tuning_data':
            $engineId = $_GET['engine_id'] ?? '';
            if (empty($engineId)) {
                $response['message'] = 'Geçersiz motor ID';
                break;
            }
            
            // Motor ve marka bilgilerini al
            $stmt = $pdo->prepare("
                SELECT 
                    e.*,
                    s.name as series_name,
                    s.year_range,
                    m.name as model_name,
                    b.name as brand_name
                FROM engines e
                JOIN series s ON e.series_id = s.id
                JOIN models m ON s.model_id = m.id
                JOIN brands b ON m.brand_id = b.id
                WHERE e.id = ?
            ");
            $stmt->execute([$engineId]);
            $engineData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$engineData) {
                $response['message'] = 'Motor bulunamadı';
                break;
            }
            
            // Stage bilgilerini al
            $stmt = $pdo->prepare("
                SELECT stage_name, fullname, original_power, tuning_power, difference_power,
                       original_torque, tuning_torque, difference_torque, ecu, notes, price,
                       ROUND((difference_power / original_power) * 100, 2) as hp_percentage,
                       ROUND((difference_torque / original_torque) * 100, 2) as torque_percentage
                FROM stages 
                WHERE engine_id = ? AND is_active = 1 
                ORDER BY 
                    CASE stage_name 
                        WHEN 'Stage1' THEN 1 
                        WHEN 'Stage2' THEN 2 
                        WHEN 'Stage3' THEN 3 
                        ELSE 4 
                    END
            ");
            $stmt->execute([$engineId]);
            $stageData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['data'] = [
                'engine' => $engineData,
                'stages' => $stageData
            ];
            $response['success'] = true;
            break;

        default:
            $response['message'] = 'Geçersiz işlem';
            break;
    }

} catch (PDOException $e) {
    error_log('Chip Tuning Calculator AJAX Error: ' . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'Veritabanı hatası oluştu',
        'data' => []
    ];
} catch (Exception $e) {
    error_log('Chip Tuning Calculator AJAX Error: ' . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'Beklenmeyen bir hata oluştu',
        'data' => []
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>
