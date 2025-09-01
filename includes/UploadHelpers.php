<?php
/**
 * Upload System Helper Functions
 * GUID tabanlı upload sistemi için yardımcı fonksiyonlar
 */

/**
 * Dosya detaylarını formatlar ve detaylı bilgi döndürür
 * @param array $fileData - Dosya verisi (FileManager->getUploadById'den gelen)
 * @return array - Formatlanmış dosya detayları
 */
function formatFileDetails($fileData) {
    if (!$fileData) {
        return null;
    }
    
    // Araç bilgilerini oluştur
    $vehicleInfo = [];
    
    // Marka/Model/Seri/Motor hiyerarşisi
    if (!empty($fileData['brand_name'])) {
        $vehicleInfo['brand'] = $fileData['brand_name'];
    }
    
    if (!empty($fileData['model_name'])) {
        $vehicleInfo['model'] = $fileData['model_name'];
    }
    
    if (!empty($fileData['series_name'])) {
        $vehicleInfo['series'] = $fileData['series_name'];
    }
    
    if (!empty($fileData['engine_name'])) {
        $vehicleInfo['engine'] = $fileData['engine_name'];
    }
    
    // Tam araç adı
    $vehicleFullName = implode(' ', array_filter([
        $vehicleInfo['brand'] ?? null,
        $vehicleInfo['model'] ?? null,
        $vehicleInfo['series'] ?? null,
        $vehicleInfo['engine'] ?? null
    ]));
    
    // ECU bilgisi - sadece ecu_name kullan
    $ecuInfo = !empty($fileData['ecu_name']) ? $fileData['ecu_name'] : 'Belirtilmedi';
    
    // Cihaz bilgisi
    $deviceInfo = !empty($fileData['device_name']) ? $fileData['device_name'] : 'Belirtilmedi';
    
    // Teknik detaylar
    $technicalDetails = [];
    
    // Motor kodu artık engines tablosundan gelecek
    
    if (!empty($fileData['kilometer'])) {
        $technicalDetails[] = 'Kilometre: ' . number_format($fileData['kilometer']) . ' km';
    }
    
    if (!empty($fileData['gearbox_type'])) {
        $technicalDetails[] = 'Şanzıman: ' . $fileData['gearbox_type'];
    }
    
    if (!empty($fileData['fuel_type'])) {
        $technicalDetails[] = 'Yakıt: ' . $fileData['fuel_type'];
    }
    
    if (!empty($fileData['hp_power'])) {
        $technicalDetails[] = 'Güç: ' . $fileData['hp_power'] . ' HP';
    }
    
    if (!empty($fileData['nm_torque'])) {
        $technicalDetails[] = 'Tork: ' . $fileData['nm_torque'] . ' Nm';
    }
    
    if (!empty($fileData['year'])) {
        $technicalDetails[] = 'Yıl: ' . $fileData['year'];
    }
    
    // Durum renkleri
    $statusColors = [
        'pending' => '#ffc107',
        'processing' => '#17a2b8',
        'completed' => '#28a745',
        'rejected' => '#dc3545'
    ];
    
    $statusTexts = [
        'pending' => 'Bekliyor',
        'processing' => 'İşleniyor',
        'completed' => 'Tamamlandı',
        'rejected' => 'Reddedildi'
    ];
    
    return [
        'original' => $fileData,
        'vehicle' => [
            'full_name' => $vehicleFullName,
            'brand' => $vehicleInfo['brand'] ?? 'Belirtilmedi',
            'model' => $vehicleInfo['model'] ?? 'Belirtilmedi',
            'series' => $vehicleInfo['series'] ?? 'Belirtilmedi',
            'engine' => $vehicleInfo['engine'] ?? 'Belirtilmedi',
            'plate' => !empty($fileData['plate']) ? strtoupper($fileData['plate']) : 'Belirtilmedi'
        ],
        'equipment' => [
            'ecu' => $ecuInfo,
            'device' => $deviceInfo
        ],
        'technical' => [
            'details_list' => $technicalDetails,
            'details_text' => implode(' • ', $technicalDetails),
            'engine_code' => 'Artık engines tablosunda',
            'kilometer' => !empty($fileData['kilometer']) ? number_format($fileData['kilometer']) . ' km' : 'Belirtilmedi',
            'gearbox' => $fileData['gearbox_type'] ?? 'Belirtilmedi',
            'fuel' => $fileData['fuel_type'] ?? 'Belirtilmedi',
            'power' => $fileData['hp_power'] ?? 'Belirtilmedi',
            'torque' => $fileData['nm_torque'] ?? 'Belirtilmedi',
            'year' => $fileData['year'] ?? 'Belirtilmedi'
        ],
        'file' => [
            'name' => $fileData['original_name'],
            'size' => formatFileSize($fileData['file_size']),
            'size_bytes' => $fileData['file_size'],
            'upload_date' => $fileData['upload_date'],
            'upload_date_formatted' => date('d.m.Y H:i', strtotime($fileData['upload_date']))
        ],
        'status' => [
            'code' => $fileData['status'],
            'text' => $statusTexts[$fileData['status']] ?? $fileData['status'],
            'color' => $statusColors[$fileData['status']] ?? '#6c757d',
            'is_completed' => $fileData['status'] === 'completed',
            'is_pending' => $fileData['status'] === 'pending',
            'is_processing' => $fileData['status'] === 'processing',
            'is_rejected' => $fileData['status'] === 'rejected'
        ],
        'notes' => [
            'upload_notes' => $fileData['upload_notes'] ?? '',
            'admin_notes' => $fileData['admin_notes'] ?? '',
            'has_upload_notes' => !empty($fileData['upload_notes']),
            'has_admin_notes' => !empty($fileData['admin_notes'])
        ],
        'credits' => [
            'charged' => $fileData['credits_charged'] ?? 0,
            'is_charged' => !empty($fileData['credits_charged']) && $fileData['credits_charged'] > 0
        ],
        'guid_info' => [
            'brand_id' => $fileData['brand_id'],
            'model_id' => $fileData['model_id'],
            'series_id' => $fileData['series_id'],
            'engine_id' => $fileData['engine_id'],
            'device_id' => $fileData['device_id'],
            'ecu_id' => $fileData['ecu_id']
        ]
    ];
}

/**
 * Dosya boyutunu human readable formatta döndürür
 */
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if ($bytes == 0) return '0 B';
        $k = 1024;
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}

/**
 * Dosya listesi için kompakt bilgi oluşturur
 * @param array $files - Dosya listesi
 * @return array - Formatlanmış dosya listesi
 */
function formatFileList($files) {
    $formattedFiles = [];
    
    foreach ($files as $file) {
        $details = formatFileDetails($file);
        if ($details) {
            // Kompakt görünüm için sadece önemli bilgileri al
            $formattedFiles[] = [
                'id' => $file['id'],
                'original_name' => $file['original_name'],
                'upload_date' => $details['file']['upload_date_formatted'],
                'vehicle_summary' => $details['vehicle']['full_name'],
                'plate' => $details['vehicle']['plate'],
                'status' => $details['status'],
                'file_size' => $details['file']['size'],
                'ecu' => $details['equipment']['ecu'],
                'device' => $details['equipment']['device'],
                'has_notes' => $details['notes']['has_upload_notes'] || $details['notes']['has_admin_notes'],
                'is_charged' => $details['credits']['is_charged']
            ];
        }
    }
    
    return $formattedFiles;
}

/**
 * Admin için detaylı dosya bilgilerini HTML olarak döndürür
 * @param array $fileData - Dosya verisi
 * @return string - HTML formatında detay bilgiler
 */
function renderFileDetailsHTML($fileData) {
    $details = formatFileDetails($fileData);
    if (!$details) {
        return '<p class="text-danger">Dosya bilgileri alınamadı.</p>';
    }
    
    $html = '<div class="file-details-container">';
    
    // Araç Bilgileri
    $html .= '<div class="row mb-3">';
    $html .= '<div class="col-md-6">';
    $html .= '<h5><i class="bi bi-car me-2"></i>Araç Bilgileri</h5>';
    $html .= '<table class="table table-sm table-bordered">';
    $html .= '<tr><td><strong>Marka:</strong></td><td>' . $details['vehicle']['brand'] . '</td></tr>';
    $html .= '<tr><td><strong>Model:</strong></td><td>' . $details['vehicle']['model'] . '</td></tr>';
    $html .= '<tr><td><strong>Seri:</strong></td><td>' . $details['vehicle']['series'] . '</td></tr>';
    $html .= '<tr><td><strong>Motor:</strong></td><td>' . $details['vehicle']['engine'] . '</td></tr>';
    $html .= '<tr><td><strong>Plaka:</strong></td><td>' . $details['vehicle']['plate'] . '</td></tr>';
    $html .= '<tr><td><strong>Kilometre:</strong></td><td>' . $details['technical']['kilometer'] . '</td></tr>';
    $html .= '</table>';
    $html .= '</div>';
    
    // Ekipman Bilgileri
    $html .= '<div class="col-md-6">';
    $html .= '<h5><i class="bi bi-microchip me-2"></i>Ekipman Bilgileri</h5>';
    $html .= '<table class="table table-sm table-bordered">';
    $html .= '<tr><td><strong>ECU Tipi:</strong></td><td>' . $details['equipment']['ecu'] . '</td></tr>';
    $html .= '<tr><td><strong>Kullanılan Cihaz:</strong></td><td>' . $details['equipment']['device'] . '</td></tr>';
    $html .= '</table>';
    
    if (!empty($details['technical']['details_list'])) {
        $html .= '<h6><i class="bi bi-cog me-2"></i>Teknik Detaylar</h6>';
        $html .= '<ul class="list-unstyled">';
        foreach ($details['technical']['details_list'] as $detail) {
            $html .= '<li><small>' . $detail . '</small></li>';
        }
        $html .= '</ul>';
    }
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * GUID ID'lerini debug amacıyla gösterir
 * @param array $fileData - Dosya verisi
 * @return string - Debug HTML
 */
function renderGuidDebugInfo($fileData) {
    if (!defined('DEBUG') || !DEBUG) {
        return '';
    }
    
    $details = formatFileDetails($fileData);
    if (!$details) {
        return '';
    }
    
    $html = '<div class="alert alert-secondary mt-3">';
    $html .= '<h6>Debug: GUID Bilgileri</h6>';
    $html .= '<table class="table table-sm">';
    $html .= '<tr><td>Brand ID:</td><td><code>' . ($details['guid_info']['brand_id'] ?? 'NULL') . '</code></td></tr>';
    $html .= '<tr><td>Model ID:</td><td><code>' . ($details['guid_info']['model_id'] ?? 'NULL') . '</code></td></tr>';
    $html .= '<tr><td>Series ID:</td><td><code>' . ($details['guid_info']['series_id'] ?? 'NULL') . '</code></td></tr>';
    $html .= '<tr><td>Engine ID:</td><td><code>' . ($details['guid_info']['engine_id'] ?? 'NULL') . '</code></td></tr>';
    $html .= '<tr><td>Device ID:</td><td><code>' . ($details['guid_info']['device_id'] ?? 'NULL') . '</code></td></tr>';
    $html .= '<tr><td>ECU ID:</td><td><code>' . ($details['guid_info']['ecu_id'] ?? 'NULL') . '</code></td></tr>';
    $html .= '</table>';
    $html .= '</div>';
    
    return $html;
}
?>
