<?php
/**
 * Mr ECU - Database Debug
 * Veritabanı tablolarını ve yapısını kontrol etmek için
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$pageTitle = 'Database Debug';
$tables = [];
$errors = [];

try {
    // Tüm tabloları listele
    $stmt = $pdo->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Her tablo için detay bilgi al
    foreach ($allTables as $tableName) {
        $tableInfo = [];
        $tableInfo['name'] = $tableName;
        
        // Tablo yapısını al
        $stmt = $pdo->query("DESCRIBE `$tableName`");
        $tableInfo['columns'] = $stmt->fetchAll();
        
        // Satır sayısını al
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$tableName`");
        $tableInfo['row_count'] = $stmt->fetch()['count'];
        
        // Foreign key'leri al
        $stmt = $pdo->query("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '$tableName' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $tableInfo['foreign_keys'] = $stmt->fetchAll();
        
        $tables[] = $tableInfo;
    }
    
} catch(PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// file_responses tablosunu özel kontrol et
$fileResponsesExists = false;
$fileResponsesStructure = null;

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'file_responses'");
    $fileResponsesExists = $stmt->fetch() !== false;
    
    if ($fileResponsesExists) {
        $stmt = $pdo->query("DESCRIBE file_responses");
        $fileResponsesStructure = $stmt->fetchAll();
    }
} catch(PDOException $e) {
    $errors[] = "file_responses check error: " . $e->getMessage();
}

// Test insert yapabilir miyiz kontrol et (sadece kontrol, gerçek insert değil)
$testInsertResult = null;
if ($fileResponsesExists) {
    try {
        $testId = generateUUID();
        $testUploadId = generateUUID(); 
        $testAdminId = $_SESSION['user_id'];
        
        // Dry run - sadece prepare et, execute etme
        $stmt = $pdo->prepare("
            INSERT INTO file_responses 
            (id, upload_id, admin_id, filename, original_name, file_size, file_type, credits_charged, admin_notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $testInsertResult = "Prepare successful - INSERT query is valid";
        
    } catch(PDOException $e) {
        $testInsertResult = "Prepare failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-database me-2"></i>Database Debug
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="uploads.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Geri Dön
                        </a>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- file_responses Özel Kontrol -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-table me-2"></i>file_responses Tablo Kontrolü
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6>Tablo Durumu:</h6>
                                        <span class="badge bg-<?php echo $fileResponsesExists ? 'success' : 'danger'; ?> fs-6">
                                            <?php echo $fileResponsesExists ? 'Mevcut' : 'Bulunamadı'; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <h6>Insert Test:</h6>
                                        <span class="badge bg-<?php echo strpos($testInsertResult, 'successful') !== false ? 'success' : 'warning'; ?> fs-6">
                                            <?php echo $testInsertResult ? 'Test OK' : 'Test Fail'; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <h6>Kolon Sayısı:</h6>
                                        <span class="badge bg-info fs-6">
                                            <?php echo $fileResponsesStructure ? count($fileResponsesStructure) : '0'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if ($testInsertResult): ?>
                                    <div class="mt-3">
                                        <h6>Test Sonucu:</h6>
                                        <pre class="bg-light p-2 small"><?php echo htmlspecialchars($testInsertResult); ?></pre>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($fileResponsesStructure): ?>
                                    <div class="mt-3">
                                        <h6>Tablo Yapısı:</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Field</th>
                                                        <th>Type</th>
                                                        <th>Null</th>
                                                        <th>Key</th>
                                                        <th>Default</th>
                                                        <th>Extra</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($fileResponsesStructure as $column): ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars($column['Field']); ?></strong></td>
                                                            <td><?php echo htmlspecialchars($column['Type']); ?></td>
                                                            <td><?php echo htmlspecialchars($column['Null']); ?></td>
                                                            <td><?php echo htmlspecialchars($column['Key']); ?></td>
                                                            <td><?php echo htmlspecialchars($column['Default'] ?? 'NULL'); ?></td>
                                                            <td><?php echo htmlspecialchars($column['Extra']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tüm Tablolar -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Tüm Tablolar (<?php echo count($tables); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($tables as $table): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-table me-1"></i>
                                                <?php echo htmlspecialchars($table['name']); ?>
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-1">
                                                <strong>Kolonlar:</strong> <?php echo count($table['columns']); ?>
                                            </p>
                                            <p class="mb-1">
                                                <strong>Satırlar:</strong> <?php echo number_format($table['row_count']); ?>
                                            </p>
                                            <p class="mb-1">
                                                <strong>FK:</strong> <?php echo count($table['foreign_keys']); ?>
                                            </p>
                                            
                                            <button class="btn btn-sm btn-outline-info" type="button" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#table-<?php echo md5($table['name']); ?>">
                                                <i class="fas fa-eye me-1"></i>Detay
                                            </button>
                                        </div>
                                        
                                        <div class="collapse" id="table-<?php echo md5($table['name']); ?>">
                                            <div class="card-footer">
                                                <h6>Kolonlar:</h6>
                                                <ul class="list-unstyled small">
                                                    <?php foreach ($table['columns'] as $column): ?>
                                                        <li>
                                                            <strong><?php echo htmlspecialchars($column['Field']); ?></strong>
                                                            <br><span class="text-muted"><?php echo htmlspecialchars($column['Type']); ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                                
                                                <?php if (!empty($table['foreign_keys'])): ?>
                                                    <h6 class="mt-2">Foreign Keys:</h6>
                                                    <ul class="list-unstyled small">
                                                        <?php foreach ($table['foreign_keys'] as $fk): ?>
                                                            <li>
                                                                <?php echo htmlspecialchars($fk['COLUMN_NAME']); ?> → 
                                                                <?php echo htmlspecialchars($fk['REFERENCED_TABLE_NAME']); ?>.<?php echo htmlspecialchars($fk['REFERENCED_COLUMN_NAME']); ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
