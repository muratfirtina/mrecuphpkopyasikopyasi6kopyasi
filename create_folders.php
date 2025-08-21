<?php
/**
 * Eksik Klasörleri Oluştur
 */

header('Content-Type: application/json');

$result = ['success' => true, 'created' => [], 'errors' => []];

$requiredFolders = [
    'design/' => 0755,
    'assets/' => 0755,
    'assets/images/' => 0755,
    'assets/css/' => 0755,
    'assets/js/' => 0755,
    'uploads/' => 0777,
    'uploads/temp/' => 0777,
    'logs/' => 0755
];

foreach ($requiredFolders as $folder => $permissions) {
    if (!is_dir($folder)) {
        try {
            if (mkdir($folder, $permissions, true)) {
                $result['created'][] = $folder;
            } else {
                $result['errors'][] = "Klasör oluşturulamadı: $folder";
            }
        } catch (Exception $e) {
            $result['errors'][] = "Hata ($folder): " . $e->getMessage();
        }
    }
}

if (!empty($result['errors'])) {
    $result['success'] = false;
}

echo json_encode($result);
?>
