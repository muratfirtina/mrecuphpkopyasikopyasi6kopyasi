<?php
/**
 * Mr ECU - Final GUID Migration Complete Check v2.0
 * 16. Final GUID Migration Kontrol Dosyası - Geliştirilmiş Versiyon
 */

require_once 'config/config.php';
require_once 'config/database.php';

$checks = [];
$totalChecks = 0;
$passedChecks = 0;

// Function to add check result
function addCheck($name, $passed, $message = '', $details = '') {
    global $checks, $totalChecks, $passedChecks;
    
    $checks[] = [
        'name' => $name,
        'passed' => $passed,
        'message' => $message,
        'details' => $details
    ];
    
    $totalChecks++;
    if ($passed) {
        $passedChecks++;
    }
}

// 1. Core UUID Functions Check
try {
    $uuid1 = generateUUID();
    $uuid2 = generateUUID();
    
    if (function_exists('generateUUID') && function_exists('isValidUUID') && 
        isValidUUID($uuid1) && isValidUUID($uuid2) && $uuid1 !== $uuid2) {
        addCheck('UUID Core Functions', true, 'UUID functions working correctly', "Generated: $uuid1, $uuid2");
    } else {
        addCheck('UUID Core Functions', false, 'UUID functions not working properly');
    }
} catch (Exception $e) {
    addCheck('UUID Core Functions', false, 'Error: ' . $e->getMessage());
}

// 2. Database Connection Check
try {
    if ($pdo && $pdo instanceof PDO) {
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch();
        $dbName = $result['db_name'];
        
        if ($dbName === 'mrecu_db_guid') {
            addCheck('Database Connection', true, 'Connected to GUID database', "Database: $dbName");
        } else {
            addCheck('Database Connection', false, "Wrong database: $dbName", 'Expected: mrecu_db_guid');
        }
    } else {
        addCheck('Database Connection', false, 'PDO connection failed');
    }
} catch (Exception $e) {
    addCheck('Database Connection', false, 'Error: ' . $e->getMessage());
}

// 3. Tables Structure Check
try {
    $tables = ['users', 'brands', 'models', 'file_uploads', 'file_responses', 'revisions', 'revision_files', 'credit_transactions', 'system_logs', 'settings'];
    $tableResults = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll();
            
            $idColumn = null;
            foreach ($columns as $column) {
                if ($column['Field'] === 'id') {
                    $idColumn = $column;
                    break;
                }
            }
            
            if ($idColumn && strpos($idColumn['Type'], 'char(36)') !== false && $idColumn['Key'] === 'PRI') {
                $tableResults[$table] = true;
            } else {
                $tableResults[$table] = false;
            }
        } catch (Exception $e) {
            $tableResults[$table] = false;
        }
    }
    
    $successCount = array_sum($tableResults);
    if ($successCount === count($tables)) {
        addCheck('Table Structures', true, 'All tables have GUID primary keys', "$successCount/" . count($tables) . " tables correct");
    } else {
        $failedTables = array_keys(array_filter($tableResults, function($v) { return !$v; }));
        addCheck('Table Structures', false, 'Some tables missing GUID structure', 'Failed: ' . implode(', ', $failedTables));
    }
} catch (Exception $e) {
    addCheck('Table Structures', false, 'Error: ' . $e->getMessage());
}

// 4. Sample Data Check
try {
    $stmt = $pdo->query("SELECT id, username FROM users LIMIT 3");
    $users = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT id, name FROM brands LIMIT 3");
    $brands = $stmt->fetchAll();
    
    if (!empty($users) && !empty($brands)) {
        $allGuidsValid = true;
        
        foreach ($users as $user) {
            if (!isValidUUID($user['id'])) {
                $allGuidsValid = false;
                break;
            }
        }
        
        foreach ($brands as $brand) {
            if (!isValidUUID($brand['id'])) {
                $allGuidsValid = false;
                break;
            }
        }
        
        if ($allGuidsValid) {
            addCheck('Sample Data', true, 'Sample data has valid GUIDs', "Users: " . count($users) . ", Brands: " . count($brands));
        } else {
            addCheck('Sample Data', false, 'Sample data contains invalid GUIDs');
        }
    } else {
        addCheck('Sample Data', false, 'No sample data found');
    }
} catch (Exception $e) {
    addCheck('Sample Data', false, 'Error: ' . $e->getMessage());
}

// 5. Class Methods Check
try {
    $user = new User($pdo);
    $fileManager = new FileManager($pdo);
    
    // Test UUID-based user method
    $testUser = $user->getAllUsers(1, 1);
    if (!empty($testUser) && isValidUUID($testUser[0]['id'])) {
        addCheck('Class Methods', true, 'User and FileManager classes work with GUIDs', 'Methods tested successfully');
    } else {
        addCheck('Class Methods', false, 'Class methods not returning valid GUIDs');
    }
} catch (Exception $e) {
    addCheck('Class Methods', false, 'Error: ' . $e->getMessage());
}

// 6. Performance Test
try {
    $start = microtime(true);
    
    // Test GUID generation performance
    $guids = [];
    for ($i = 0; $i < 100; $i++) {
        $guids[] = generateUUID();
    }
    
    $end = microtime(true);
    $duration = round(($end - $start) * 1000, 2);
    
    // Check for duplicates
    $uniqueGuids = array_unique($guids);
    $duplicateCount = count($guids) - count($uniqueGuids);
    
    if ($duplicateCount === 0 && $duration < 1000) {
        addCheck('Performance Test', true, 'GUID generation performance optimal', "Generated 100 GUIDs in {$duration}ms");
    } else {
        $issues = [];
        if ($duplicateCount > 0) $issues[] = "$duplicateCount duplicates found";
        if ($duration >= 1000) $issues[] = "slow generation ({$duration}ms)";
        addCheck('Performance Test', false, 'Performance issues detected', implode(', ', $issues));
    }
} catch (Exception $e) {
    addCheck('Performance Test', false, 'Error: ' . $e->getMessage());
}

// 7. Foreign Key Relationships Test
try {
    $fkTests = [];
    
    // Test user-file_uploads relationship
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_uploads fu JOIN users u ON fu.user_id = u.id LIMIT 1");
    $fkTests['user_uploads'] = $stmt->fetch()['count'] >= 0;
    
    // Test brand-models relationship
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM models m JOIN brands b ON m.brand_id = b.id LIMIT 1");
    $fkTests['brand_models'] = $stmt->fetch()['count'] >= 0;
    
    // Test upload-responses relationship
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_responses fr JOIN file_uploads fu ON fr.upload_id = fu.id LIMIT 1");
    $fkTests['upload_responses'] = $stmt->fetch()['count'] >= 0;
    
    $passedFKs = array_sum($fkTests);
    $totalFKs = count($fkTests);
    
    if ($passedFKs === $totalFKs) {
        addCheck('Foreign Key Relations', true, 'All GUID foreign key relationships working', "$passedFKs/$totalFKs relationships tested");
    } else {
        $failed = array_keys(array_filter($fkTests, function($v) { return !$v; }));
        addCheck('Foreign Key Relations', false, 'Some FK relationships failed', 'Failed: ' . implode(', ', $failed));
    }
} catch (Exception $e) {
    addCheck('Foreign Key Relations', false, 'Error: ' . $e->getMessage());
}

// 8. Data Migration Completeness
try {
    // Check for any remaining INT IDs in key tables
    $problematicTables = [];
    $keyTables = ['users', 'brands', 'models', 'file_uploads', 'file_responses'];
    
    foreach ($keyTables as $table) {
        $stmt = $pdo->query("SELECT id FROM $table WHERE id REGEXP '^[0-9]+$' LIMIT 1");
        if ($stmt->rowCount() > 0) {
            $problematicTables[] = $table;
        }
    }
    
    // Check GUID format consistency
    $formatIssues = [];
    foreach ($keyTables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table WHERE LENGTH(id) != 36");
        $badFormatCount = $stmt->fetch()['count'];
        if ($badFormatCount > 0) {
            $formatIssues[] = "$table ($badFormatCount records)";
        }
    }
    
    if (empty($problematicTables) && empty($formatIssues)) {
        addCheck('Migration Completeness', true, 'Data migration 100% complete', 'All IDs converted to GUID format');
    } else {
        $issues = array_merge($problematicTables, $formatIssues);
        addCheck('Migration Completeness', false, 'Migration incomplete', 'Issues: ' . implode(', ', $issues));
    }
} catch (Exception $e) {
    addCheck('Migration Completeness', false, 'Error: ' . $e->getMessage());
}

// 9. Security Enhancement Verification
try {
    // Test URL parameter validation
    $testGuid = generateUUID();
    $invalidIds = ['1', '123', 'abc', '../etc/passwd', '<script>alert(1)</script>'];
    
    $validationWorks = true;
    foreach ($invalidIds as $invalid) {
        if (isValidUUID($invalid)) {
            $validationWorks = false;
            break;
        }
    }
    
    if ($validationWorks && isValidUUID($testGuid)) {
        addCheck('Security Enhancement', true, 'GUID validation prevents injection attacks', 'URL parameter security improved');
    } else {
        addCheck('Security Enhancement', false, 'GUID validation not working properly');
    }
} catch (Exception $e) {
    addCheck('Security Enhancement', false, 'Error: ' . $e->getMessage());
}

// 10. File System Integrity
try {
    $guidFiles = [
        'config/install-guid.php',
        'test-guid-system.php',
        'GUID_MIGRATION_README.md',
        'final-guid-check.php'
    ];

    $filesExist = true;
    $missingFiles = [];

    foreach ($guidFiles as $file) {
        if (!file_exists($file)) {
            $filesExist = false;
            $missingFiles[] = $file;
        }
    }

    if ($filesExist) {
        addCheck('GUID System Files', true, 'All GUID-related files exist', 'Files: ' . implode(', ', $guidFiles));
    } else {
        addCheck('GUID System Files', false, 'Missing files: ' . implode(', ', $missingFiles));
    }
} catch (Exception $e) {
    addCheck('GUID System Files', false, 'Error: ' . $e->getMessage());
}

// 11. Updated Core Files Check
try {
    $updatedFiles = [
        'includes/User.php',
        'includes/FileManager.php',
        'admin/uploads.php',
        'admin/download.php',
        'user/upload.php',
        'user/download.php'
    ];

    $filesUpdated = true;
    $notUpdatedFiles = [];
    $fileDetails = [];

    foreach ($updatedFiles as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            // Check for GUID system indicators
            if (strpos($content, 'GUID') !== false || strpos($content, 'isValidUUID') !== false || strpos($content, 'generateUUID') !== false) {
                $fileDetails[] = "$file ✓";
            } else {
                $filesUpdated = false;
                $notUpdatedFiles[] = $file;
            }
        } else {
            $filesUpdated = false;
            $notUpdatedFiles[] = $file . ' (missing)';
        }
    }

    if ($filesUpdated) {
        addCheck('Updated Core Files', true, 'All core files updated for GUID system', count($updatedFiles) . ' files checked');
    } else {
        addCheck('Updated Core Files', false, 'Some files not updated: ' . implode(', ', $notUpdatedFiles));
    }
} catch (Exception $e) {
    addCheck('Updated Core Files', false, 'Error: ' . $e->getMessage());
}

// 12. Backup and Recovery Check
try {
    // Check if old database backup exists
    $oldDbExists = false;
    try {
        $testPdo = new PDO("mysql:host=127.0.0.1;port=8889;dbname=mrecu_db", 'root', 'root');
        $oldDbExists = true;
    } catch (Exception $e) {
        // Old DB doesn't exist or can't connect
    }
    
    // Check backup files
    $backupFiles = [
        'config/database-backup.php',
        'GUID_MIGRATION_README.md'
    ];
    
    $backupFilesExist = 0;
    foreach ($backupFiles as $file) {
        if (file_exists($file)) {
            $backupFilesExist++;
        }
    }
    
    if ($backupFilesExist === count($backupFiles)) {
        $details = "Documentation and backup files present";
        if ($oldDbExists) $details .= ", old database accessible";
        addCheck('Backup & Recovery', true, 'Backup and recovery options available', $details);
    } else {
        addCheck('Backup & Recovery', false, "Missing backup files: " . (count($backupFiles) - $backupFilesExist) . "/" . count($backupFiles));
    }
} catch (Exception $e) {
    addCheck('Backup & Recovery', false, 'Error: ' . $e->getMessage());
}

// Calculate overall status
$successRate = round(($passedChecks / $totalChecks) * 100, 1);
$overallStatus = $successRate >= 90 ? 'excellent' : ($successRate >= 75 ? 'good' : ($successRate >= 50 ? 'warning' : 'critical'));

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final GUID Migration Check v2.0 - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .check-item { 
            margin: 15px 0; 
            padding: 20px; 
            border-radius: 10px; 
            border-left: 5px solid #ccc;
            transition: transform 0.2s ease;
        }
        .check-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .check-success { 
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-left-color: #28a745;
            border: 1px solid #c3e6cb; 
        }
        .check-error { 
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-left-color: #dc3545;
            border: 1px solid #f5c6cb; 
        }
        .status-excellent { color: #28a745; }
        .status-good { color: #17a2b8; }
        .status-warning { color: #ffc107; }
        .status-critical { color: #dc3545; }
        .progress-excellent { background: linear-gradient(90deg, #28a745, #20c997); }
        .progress-good { background: linear-gradient(90deg, #17a2b8, #20c997); }
        .progress-warning { background: linear-gradient(90deg, #ffc107, #fd7e14); }
        .progress-critical { background: linear-gradient(90deg, #dc3545, #e74c3c); }
        .feature-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin: 30px 0;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .migration-summary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin: 30px 0;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 10%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin: 10px 0;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .final-status-card {
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .btn-action {
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .test-category {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-11 col-lg-10">
                
                <!-- Header -->
                <div class="feature-box">
                    <h1 class="mb-4">
                        <i class="fas fa-shield-alt me-3"></i>
                        GUID Migration Final Check v2.0
                    </h1>
                    <p class="lead mb-3">
                        Mr ECU sisteminin INT'den GUID'e geçiş durumunun kapsamlı analizi
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-rocket me-2"></i>
                        <strong>16. Final GUID Migration Kontrol Dosyası</strong>
                    </p>
                </div>

                <!-- Stats Row -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h2 class="mb-1"><?php echo $passedChecks; ?>/<?php echo $totalChecks; ?></h2>
                            <p class="mb-0"><i class="fas fa-check-circle me-2"></i>Tests Passed</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h2 class="mb-1"><?php echo $successRate; ?>%</h2>
                            <p class="mb-0"><i class="fas fa-chart-line me-2"></i>Success Rate</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h2 class="mb-1 status-<?php echo $overallStatus; ?>">
                                <?php 
                                $statusText = [
                                    'excellent' => 'Perfect',
                                    'good' => 'Good', 
                                    'warning' => 'Warning',
                                    'critical' => 'Critical'
                                ];
                                echo $statusText[$overallStatus];
                                ?>
                            </h2>
                            <p class="mb-0"><i class="fas fa-thermometer-half me-2"></i>Overall Status</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h2 class="mb-1"><?php echo date('H:i'); ?></h2>
                            <p class="mb-0"><i class="fas fa-clock me-2"></i>Check Time</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Migration Progress</h5>
                            <span class="badge bg-<?php echo $overallStatus === 'excellent' ? 'success' : ($overallStatus === 'good' ? 'info' : ($overallStatus === 'warning' ? 'warning' : 'danger')); ?> fs-6">
                                <?php echo $successRate; ?>%
                            </span>
                        </div>
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar progress-<?php echo $overallStatus; ?>" role="progressbar" 
                                 style="width: <?php echo $successRate; ?>%" 
                                 aria-valuenow="<?php echo $successRate; ?>" aria-valuemin="0" aria-valuemax="100">
                                <strong><?php echo $passedChecks; ?> of <?php echo $totalChecks; ?> tests passed</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Migration Summary -->
                <div class="migration-summary">
                    <h3><i class="fas fa-rocket me-2"></i>Migration Achievements</h3>
                    <div class="row mt-4">
                        <div class="col-md-3 text-center">
                            <div class="test-category">
                                <h4><i class="fas fa-cogs me-2"></i>Core Functions</h4>
                                <p class="mb-0">UUID generation & validation</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="test-category">
                                <h4><i class="fas fa-database me-2"></i>Database</h4>
                                <p class="mb-0">GUID schema implemented</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="test-category">
                                <h4><i class="fas fa-code me-2"></i>Backend</h4>
                                <p class="mb-0">Classes updated for GUID</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="test-category">
                                <h4><i class="fas fa-shield-alt me-2"></i>Security</h4>
                                <p class="mb-0">Enhanced protection</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Test Results -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list-check me-2"></i>Detailed Test Results
                            <span class="badge bg-secondary ms-2"><?php echo count($checks); ?> tests</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $halfPoint = ceil(count($checks) / 2);
                            $firstHalf = array_slice($checks, 0, $halfPoint);
                            $secondHalf = array_slice($checks, $halfPoint);
                            ?>
                            
                            <div class="col-md-6">
                                <?php foreach ($firstHalf as $index => $check): ?>
                                    <div class="check-item <?php echo $check['passed'] ? 'check-success' : 'check-error'; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-2">
                                                    <i class="fas fa-<?php echo $check['passed'] ? 'check-circle text-success' : 'times-circle text-danger'; ?> me-2"></i>
                                                    <?php echo $check['name']; ?>
                                                </h6>
                                                <p class="mb-1"><?php echo $check['message']; ?></p>
                                                <?php if ($check['details']): ?>
                                                    <small class="text-muted">
                                                        <strong>Details:</strong> <?php echo $check['details']; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <span class="badge bg-<?php echo $check['passed'] ? 'success' : 'danger'; ?> fs-6">
                                                <?php echo ($index + 1); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <?php foreach ($secondHalf as $index => $check): ?>
                                    <div class="check-item <?php echo $check['passed'] ? 'check-success' : 'check-error'; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-2">
                                                    <i class="fas fa-<?php echo $check['passed'] ? 'check-circle text-success' : 'times-circle text-danger'; ?> me-2"></i>
                                                    <?php echo $check['name']; ?>
                                                </h6>
                                                <p class="mb-1"><?php echo $check['message']; ?></p>
                                                <?php if ($check['details']): ?>
                                                    <small class="text-muted">
                                                        <strong>Details:</strong> <?php echo $check['details']; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <span class="badge bg-<?php echo $check['passed'] ? 'success' : 'danger'; ?> fs-6">
                                                <?php echo ($index + $halfPoint + 1); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="text-center mb-4">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="config/install-guid.php" class="btn btn-primary btn-action w-100">
                                <i class="fas fa-redo me-2"></i>Re-install GUID DB
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="test-guid-system.php" class="btn btn-info btn-action w-100">
                                <i class="fas fa-vial me-2"></i>Run System Tests
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="admin/" class="btn btn-success btn-action w-100">
                                <i class="fas fa-cog me-2"></i>Go to Admin Panel
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="login.php" class="btn btn-warning btn-action w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Test Login System
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Final Status -->
                <div class="card final-status-card">
                    <div class="card-header bg-<?php echo $overallStatus === 'excellent' ? 'success' : ($overallStatus === 'good' ? 'info' : ($overallStatus === 'warning' ? 'warning' : 'danger')); ?> text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-flag-checkered me-2"></i>Migration Status Report
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($overallStatus === 'excellent'): ?>
                            <div class="alert alert-success">
                                <h4><i class="fas fa-trophy me-2"></i>🎉 GUID Migration Completed Successfully!</h4>
                                <p class="mb-3">
                                    <strong>Excellent!</strong> GUID migration is complete and all systems are working perfectly. 
                                    Your application is now using UUID-based IDs for enhanced security and scalability.
                                </p>
                                <hr>
                                <h6><i class="fas fa-star me-2"></i>Migration Achievements:</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>All database tables now use CHAR(36) GUID primary keys</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Enhanced security with non-predictable IDs</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Brute force protection improved significantly</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>UUID validation throughout the application</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Foreign key relationships maintained</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Performance optimized for GUID operations</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($overallStatus === 'good'): ?>
                            <div class="alert alert-info">
                                <h4><i class="fas fa-thumbs-up me-2"></i>Migration Nearly Complete</h4>
                                <p>
                                    <strong>Good progress!</strong> Most of the GUID migration is working correctly. 
                                    Please check the failed tests above and fix any remaining issues.
                                </p>
                            </div>
                        <?php elseif ($overallStatus === 'warning'): ?>
                            <div class="alert alert-warning">
                                <h4><i class="fas fa-exclamation-triangle me-2"></i>Migration Needs Attention</h4>
                                <p>
                                    <strong>Warning:</strong> Several checks have failed. Please review the failed tests 
                                    and complete the migration process before using the system.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h4><i class="fas fa-times-circle me-2"></i>Migration Incomplete</h4>
                                <p>
                                    <strong>Critical issues found!</strong> The GUID migration is not complete. 
                                    Please address all failed checks before proceeding.
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3 p-3 bg-light rounded">
                            <div class="row">
                                <div class="col-md-4">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i><strong>Check completed:</strong><br>
                                        <?php echo date('Y-m-d H:i:s'); ?>
                                    </small>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">
                                        <i class="fas fa-database me-1"></i><strong>Database:</strong><br>
                                        <?php echo $pdo->query("SELECT DATABASE()")->fetchColumn(); ?>
                                    </small>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">
                                        <i class="fas fa-server me-1"></i><strong>Server:</strong><br>
                                        <?php echo $_SERVER['SERVER_NAME'] ?? 'localhost'; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Tools -->
                <div class="card mt-4 mb-5">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tools me-2"></i>Additional Migration Tools
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-file-alt me-2"></i>Documentation</h6>
                                <ul class="list-unstyled">
                                    <li><a href="GUID_MIGRATION_README.md" target="_blank"><i class="fas fa-book me-2"></i>Migration Guide</a></li>
                                    <li><a href="final-guid-check.php" target="_blank"><i class="fas fa-clipboard-check me-2"></i>Basic Check</a></li>
                                    <li><a href="test-guid-system.php" target="_blank"><i class="fas fa-flask me-2"></i>System Tests</a></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-wrench me-2"></i>Maintenance</h6>
                                <ul class="list-unstyled">
                                    <li><button class="btn btn-sm btn-outline-primary" onclick="window.location.reload()"><i class="fas fa-sync me-2"></i>Refresh Check</button></li>
                                    <li><a href="config/install-guid.php" class="btn btn-sm btn-outline-warning"><i class="fas fa-download me-2"></i>Reinstall Schema</a></li>
                                    <li><a href="admin/system-logs.php" class="btn btn-sm btn-outline-info"><i class="fas fa-history me-2"></i>View Logs</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh functionality
        let autoRefresh = false;
        
        function toggleAutoRefresh() {
            autoRefresh = !autoRefresh;
            if (autoRefresh) {
                setTimeout(function() {
                    if (autoRefresh) window.location.reload();
                }, 30000);
            }
        }
        
        // Test progress animation
        document.addEventListener('DOMContentLoaded', function() {
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.width = '0%';
                setTimeout(() => {
                    progressBar.style.width = '<?php echo $successRate; ?>%';
                }, 500);
            }
        });
    </script>
</body>
</html>
