<?php
/**
 * Icon Picture Column Updater
 * Services tablosuna icon_picture kolonu ekler
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Icon Picture Column Update - " . SITE_NAME . "</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body style='background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);'>
<div class='container my-5'>
    <div class='row justify-content-center'>
        <div class='col-lg-8'>
            <div class='card shadow'>
                <div class='card-header bg-info text-white'>
                    <h3 class='mb-0'><i class='fas fa-image me-2'></i>Icon Picture Column Update</h3>
                </div>
                <div class='card-body'>
";

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM services LIKE 'icon_picture'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "<div class='alert alert-info'>
            <i class='fas fa-info-circle me-2'></i>icon_picture kolonu zaten mevcut.
        </div>";
    } else {
        // Add the column
        $sql = "ALTER TABLE `services` ADD COLUMN `icon_picture` VARCHAR(255) DEFAULT NULL AFTER `image`";
        $pdo->exec($sql);
        
        echo "<div class='alert alert-success'>
            <i class='fas fa-check-circle me-2'></i>icon_picture kolonu başarıyla eklendi.
        </div>";
    }
    
    // Show current table structure
    $stmt = $pdo->query("DESCRIBE services");
    $columns = $stmt->fetchAll();
    
    echo "<div class='mt-4'>
        <h5>Services Tablo Yapısı:</h5>
        <div class='table-responsive'>
            <table class='table table-sm table-striped'>
                <thead>
                    <tr>
                        <th>Kolon</th>
                        <th>Tip</th>
                        <th>Null</th>
                        <th>Key</th>
                        <th>Default</th>
                    </tr>
                </thead>
                <tbody>";
    
    foreach ($columns as $column) {
        $highlight = ($column['Field'] === 'icon_picture') ? 'table-success' : '';
        echo "<tr class='$highlight'>
            <td><strong>{$column['Field']}</strong></td>
            <td>{$column['Type']}</td>
            <td>{$column['Null']}</td>
            <td>{$column['Key']}</td>
            <td>{$column['Default']}</td>
        </tr>";
    }
    
    echo "</tbody></table></div></div>";
    
    echo "<div class='d-grid gap-2 d-md-flex justify-content-md-center mt-4'>
        <a href='services.php' class='btn btn-primary'>
            <i class='fas fa-cog me-2'></i>Hizmet Yönetimine Git
        </a>
        <a href='../index.php' class='btn btn-success'>
            <i class='fas fa-home me-2'></i>Ana Sayfaya Git
        </a>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
        <i class='fas fa-exclamation-triangle me-2'></i>Hata: " . $e->getMessage() . "
    </div>";
}

echo "</div></div></div></div></body></html>";
?>
