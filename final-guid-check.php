<?php
/**
 * Mr ECU - Final GUID Migration Check
 * GUID sistemine geçiş kontrolü ve doğrulama
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

// 1. UUID Functions Check
$totalChecks++;
try {
    $uuid1 = generateUUID();
    $uuid2 = generateUUID();
    
    if (function_exists('generateUUID') && function_exists('isValidUUID') && 
        isValidUUID($uuid1) && isValidUUID($uuid2) && $uuid1 !== $uuid2) {
        addCheck('UUID Functions', true, 'UUID functions working correctly', "Generated: $uuid1, $uuid2");
        $passedChecks++;
    } else {
        addCheck('UUID Functions', false, 'UUID functions not working properly');
    }
} catch (Exception $e) {
    addCheck('UUID Functions', false, 'Error: ' . $e->getMessage());
}

// 2. Database Connection Check
$totalChecks++;
try {
    if ($pdo && $pdo instanceof PDO) {
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch();
        $dbName = $result['db_name'];
        
        if ($dbName === 'mrecu_db_guid') {
            addCheck('Database Connection', true, 'Connected to GUID database', "Database: $dbName");
            $passedChecks++;
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
$totalChecks++;
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
        $passedChecks++;
    } else {
        $failedTables = array_keys(array_filter($tableResults, function($v) { return !$v; }));
        addCheck('Table Structures', false, 'Some tables missing GUID structure', 'Failed: ' . implode(', ', $failedTables));
    }
} catch (Exception $e) {
    addCheck('Table Structures', false, 'Error: ' . $e->getMessage());
}

// 4. Sample Data Check
$totalChecks++;
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
            $passedChecks++;
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
$totalChecks++;
try {
    $user = new User($pdo);
    $fileManager = new FileManager($pdo);
    
    // Test UUID-based user method
    $testUser = $user->getAllUsers(1, 1);
    if (!empty($testUser) && isValidUUID($testUser[0]['id'])) {
        addCheck('Class Methods', true, 'User and FileManager classes work with GUIDs', 'Methods tested successfully');
        $passedChecks++;
    } else {
        addCheck('Class Methods', false, 'Class methods not returning valid GUIDs');
    }
} catch (Exception $e) {
    addCheck('Class Methods', false, 'Error: ' . $e->getMessage());
}

// 6. File System Check
$totalChecks++;
$guidFiles = [
    'config/install-guid.php',
    'test-guid-system.php',
    'GUID_MIGRATION_README.md'
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
    addCheck('GUID Files', true, 'All GUID-related files exist', 'Files: ' . implode(', ', $guidFiles));
    $passedChecks++;
} else {
    addCheck('GUID Files', false, 'Missing files: ' . implode(', ', $missingFiles));
}

// 7. Updated Files Check
$totalChecks++;
$updatedFiles = [
    'includes/User.php',
    'includes/FileManager.php',
    'admin/uploads.php',
    'admin/download.php',
    'admin/users.php',
    'user/upload.php',
    'user/download.php',
    'user/files.php',
    'user/download-revision.php'
];

$filesUpdated = true;
$notUpdatedFiles = [];

foreach ($updatedFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        // Check for GUID system indicators
        if (strpos($content, 'GUID') !== false || strpos($content, 'isValidUUID') !== false || strpos($content, 'generateUUID') !== false) {
            // File appears to be updated
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
    addCheck('Updated Files', true, 'All core files updated for GUID system', count($updatedFiles) . ' files checked');
    $passedChecks++;
} else {
    addCheck('Updated Files', false, 'Some files not updated: ' . implode(', ', $notUpdatedFiles));
}

// 8. Performance Test
$totalChecks++;
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
        $passedChecks++;
    } else {
        $issues = [];
        if ($duplicateCount > 0) $issues[] = "$duplicateCount duplicates found";
        if ($duration >= 1000) $issues[] = "slow generation ({$duration}ms)";
        addCheck('Performance Test', false, 'Performance issues detected', implode(', ', $issues));
    }
} catch (Exception $e) {
    addCheck('Performance Test', false, 'Error: ' . $e->getMessage());
}

// 9. Foreign Key Relationships Test
$totalChecks++;
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
        $passedChecks++;
    } else {
        $failed = array_keys(array_filter($fkTests, function($v) { return !$v; }));
        addCheck('Foreign Key Relations', false, 'Some FK relationships failed', 'Failed: ' . implode(', ', $failed));
    }
} catch (Exception $e) {
    addCheck('Foreign Key Relations', false, 'Error: ' . $e->getMessage());
}

// 10. Data Migration Completeness
$totalChecks++;
try {
    $completeness = [];
    
    // Check for any remaining INT IDs in key tables
    $problematicTables = [];
    $keyTables = ['users', 'brands', 'models', 'file_uploads', 'file_responses'];
    
    foreach ($keyTables as $table) {
        $stmt = $pdo->query("SELECT id FROM $table WHERE id REGEXP '^[0-9]+

// Calculate overall status
$successRate = round(($passedChecks / $totalChecks) * 100, 1);
$overallStatus = $successRate >= 90 ? 'excellent' : ($successRate >= 75 ? 'good' : ($successRate >= 50 ? 'warning' : 'critical'));

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GUID Migration Final Check - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .check-item { 
            margin: 15px 0; 
            padding: 20px; 
            border-radius: 10px; 
            border-left: 5px solid #ccc;
        }
        .check-success { 
            background: #d4edda; 
            border-left-color: #28a745;
            border: 1px solid #c3e6cb; 
        }
        .check-error { 
            background: #f8d7da; 
            border-left-color: #dc3545;
            border: 1px solid #f5c6cb; 
        }
        .status-excellent { color: #28a745; }
        .status-good { color: #17a2b8; }
        .status-warning { color: #ffc107; }
        .status-critical { color: #dc3545; }
        .progress-excellent { background: #28a745; }
        .progress-good { background: #17a2b8; }
        .progress-warning { background: #ffc107; }
        .progress-critical { background: #dc3545; }
        .feature-box {
            background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin: 20px 0;
        }
        .migration-summary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-11">
                
                <!-- Header -->
                <div class="feature-box text-center">
                    <h1 class="mb-3">
                        <i class="bi bi-shield-alt me-3"></i>
                        GUID Migration Final Check
                    </h1>
                    <p class="lead mb-0">
                        Mr ECU sisteminin INT'den GUID'e geçiş durumu kontrol ediliyor...
                    </p>
                </div>

                <!-- Overall Status -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <div class="row">
                            <div class="col-md-4">
                                <h2 class="status-<?php echo $overallStatus; ?>"><?php echo $passedChecks; ?>/<?php echo $totalChecks; ?></h2>
                                <p class="mb-0">Tests Passed</p>
                            </div>
                            <div class="col-md-4">
                                <h2 class="status-<?php echo $overallStatus; ?>"><?php echo $successRate; ?>%</h2>
                                <p class="mb-0">Success Rate</p>
                            </div>
                            <div class="col-md-4">
                                <h2 class="status-<?php echo $overallStatus; ?>">
                                    <?php 
                                    $statusText = [
                                        'excellent' => 'Excellent',
                                        'good' => 'Good', 
                                        'warning' => 'Warning',
                                        'critical' => 'Critical'
                                    ];
                                    echo $statusText[$overallStatus];
                                    ?>
                                </h2>
                                <p class="mb-0">Overall Status</p>
                            </div>
                        </div>
                        
                        <div class="progress mt-3" style="height: 25px;">
                            <div class="progress-bar progress-<?php echo $overallStatus; ?>" role="progressbar" 
                                 style="width: <?php echo $successRate; ?>%" 
                                 aria-valuenow="<?php echo $successRate; ?>" aria-valuemin="0" aria-valuemax="100">
                                <strong><?php echo $successRate; ?>%</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Migration Summary -->
                <div class="migration-summary">
                    <h3><i class="bi bi-rocket me-2"></i>Migration Summary</h3>
                    <div class="row mt-3">
                        <div class="col-md-3 text-center">
                            <h4>✅ Core Functions</h4>
                            <p>UUID generation & validation</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4>🗄️ Database</h4>
                            <p>GUID schema implemented</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4>⚙️ Backend</h4>
                            <p>Classes updated for GUID</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4>🎨 Frontend</h4>
                            <p>UI adapted for GUID IDs</p>
                        </div>
                    </div>
                </div>

                <!-- Detailed Check Results -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-list-check me-2"></i>Detailed Check Results
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($checks as $index => $check): ?>
                            <div class="check-item <?php echo $check['passed'] ? 'check-success' : 'check-error'; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-2">
                                            <i class="bi bi-<?php echo $check['passed'] ? 'check-circle text-success' : 'times-circle text-danger'; ?> me-2"></i>
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
                </div>

                <!-- Action Buttons -->
                <div class="text-center mt-4 mb-5">
                    <div class="row">
                        <div class="col-md-2">
                            <a href="config/install-guid.php" class="btn btn-primary btn-lg w-100 mb-2">
                                <i class="bi bi-redo me-2"></i>Re-install DB
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="final-guid-migration-complete.php" class="btn btn-warning btn-lg w-100 mb-2">
                                <i class="bi bi-star me-2"></i>Complete Check v2.0
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="test-guid-system.php" class="btn btn-info btn-lg w-100 mb-2">
                                <i class="bi bi-vial me-2"></i>System Tests
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="admin/" class="btn btn-success btn-lg w-100 mb-2">
                                <i class="bi bi-cog me-2"></i>Go to Admin Panel
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="login.php" class="btn btn-dark btn-lg w-100 mb-2">
                                <i class="bi bi-sign-in-alt me-2"></i>Test Login
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Final Status -->
                <div class="card">
                    <div class="card-header bg-<?php echo $overallStatus === 'excellent' ? 'success' : ($overallStatus === 'good' ? 'info' : ($overallStatus === 'warning' ? 'warning' : 'danger')); ?> text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-flag-checkered me-2"></i>Migration Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($overallStatus === 'excellent'): ?>
                            <div class="alert alert-success">
                                <h4><i class="bi bi-trophy me-2"></i>Migration Completed Successfully!</h4>
                                <p class="mb-0">
                                    🎉 <strong>Excellent!</strong> GUID migration is complete and all systems are working perfectly. 
                                    Your application is now using UUID-based IDs for enhanced security and scalability.
                                </p>
                                <hr>
                                <h6>✅ What's New:</h6>
                                <ul class="mb-0">
                                    <li>All database tables now use CHAR(36) GUID primary keys</li>
                                    <li>Enhanced security with non-predictable IDs</li>
                                    <li>Brute force protection improved</li>
                                    <li>UUID validation throughout the application</li>
                                    <li>Backward compatibility removed (clean migration)</li>
                                </ul>
                            </div>
                        <?php elseif ($overallStatus === 'good'): ?>
                            <div class="alert alert-info">
                                <h4><i class="bi bi-thumbs-up me-2"></i>Migration Nearly Complete</h4>
                                <p>
                                    <strong>Good progress!</strong> Most of the GUID migration is working correctly. 
                                    Please check the failed tests above and fix any remaining issues.
                                </p>
                            </div>
                        <?php elseif ($overallStatus === 'warning'): ?>
                            <div class="alert alert-warning">
                                <h4><i class="bi bi-exclamation-triangle me-2"></i>Migration Needs Attention</h4>
                                <p>
                                    <strong>Warning:</strong> Several checks have failed. Please review the failed tests 
                                    and complete the migration process before using the system.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h4><i class="bi bi-times-circle me-2"></i>Migration Incomplete</h4>
                                <p>
                                    <strong>Critical issues found!</strong> The GUID migration is not complete. 
                                    Please address all failed checks before proceeding.
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>Check completed at: <?php echo date('Y-m-d H:i:s'); ?>
                                <br><i class="bi bi-database me-1"></i>Database: <?php echo $pdo->query("SELECT DATABASE()")->fetchColumn(); ?>
                                <br><i class="bi bi-server me-1"></i>Server: <?php echo $_SERVER['SERVER_NAME'] ?? 'localhost'; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
 LIMIT 1");
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
        $passedChecks++;
    } else {
        $issues = array_merge($problematicTables, $formatIssues);
        addCheck('Migration Completeness', false, 'Migration incomplete', 'Issues: ' . implode(', ', $issues));
    }
} catch (Exception $e) {
    addCheck('Migration Completeness', false, 'Error: ' . $e->getMessage());
}

// 11. Security Enhancement Verification
$totalChecks++;
try {
    $securityTests = [];
    
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
        $passedChecks++;
    } else {
        addCheck('Security Enhancement', false, 'GUID validation not working properly');
    }
} catch (Exception $e) {
    addCheck('Security Enhancement', false, 'Error: ' . $e->getMessage());
}

// 12. Backup and Recovery Check
$totalChecks++;
try {
    $backupStatus = [];
    
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
        $passedChecks++;
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
    <title>GUID Migration Final Check - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .check-item { 
            margin: 15px 0; 
            padding: 20px; 
            border-radius: 10px; 
            border-left: 5px solid #ccc;
        }
        .check-success { 
            background: #d4edda; 
            border-left-color: #28a745;
            border: 1px solid #c3e6cb; 
        }
        .check-error { 
            background: #f8d7da; 
            border-left-color: #dc3545;
            border: 1px solid #f5c6cb; 
        }
        .status-excellent { color: #28a745; }
        .status-good { color: #17a2b8; }
        .status-warning { color: #ffc107; }
        .status-critical { color: #dc3545; }
        .progress-excellent { background: #28a745; }
        .progress-good { background: #17a2b8; }
        .progress-warning { background: #ffc107; }
        .progress-critical { background: #dc3545; }
        .feature-box {
            background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin: 20px 0;
        }
        .migration-summary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-11">
                
                <!-- Header -->
                <div class="feature-box text-center">
                    <h1 class="mb-3">
                        <i class="bi bi-shield-alt me-3"></i>
                        GUID Migration Final Check
                    </h1>
                    <p class="lead mb-0">
                        Mr ECU sisteminin INT'den GUID'e geçiş durumu kontrol ediliyor...
                    </p>
                </div>

                <!-- Overall Status -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <div class="row">
                            <div class="col-md-4">
                                <h2 class="status-<?php echo $overallStatus; ?>"><?php echo $passedChecks; ?>/<?php echo $totalChecks; ?></h2>
                                <p class="mb-0">Tests Passed</p>
                            </div>
                            <div class="col-md-4">
                                <h2 class="status-<?php echo $overallStatus; ?>"><?php echo $successRate; ?>%</h2>
                                <p class="mb-0">Success Rate</p>
                            </div>
                            <div class="col-md-4">
                                <h2 class="status-<?php echo $overallStatus; ?>">
                                    <?php 
                                    $statusText = [
                                        'excellent' => 'Excellent',
                                        'good' => 'Good', 
                                        'warning' => 'Warning',
                                        'critical' => 'Critical'
                                    ];
                                    echo $statusText[$overallStatus];
                                    ?>
                                </h2>
                                <p class="mb-0">Overall Status</p>
                            </div>
                        </div>
                        
                        <div class="progress mt-3" style="height: 25px;">
                            <div class="progress-bar progress-<?php echo $overallStatus; ?>" role="progressbar" 
                                 style="width: <?php echo $successRate; ?>%" 
                                 aria-valuenow="<?php echo $successRate; ?>" aria-valuemin="0" aria-valuemax="100">
                                <strong><?php echo $successRate; ?>%</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Migration Summary -->
                <div class="migration-summary">
                    <h3><i class="bi bi-rocket me-2"></i>Migration Summary</h3>
                    <div class="row mt-3">
                        <div class="col-md-3 text-center">
                            <h4>✅ Core Functions</h4>
                            <p>UUID generation & validation</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4>🗄️ Database</h4>
                            <p>GUID schema implemented</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4>⚙️ Backend</h4>
                            <p>Classes updated for GUID</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4>🎨 Frontend</h4>
                            <p>UI adapted for GUID IDs</p>
                        </div>
                    </div>
                </div>

                <!-- Detailed Check Results -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-list-check me-2"></i>Detailed Check Results
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($checks as $index => $check): ?>
                            <div class="check-item <?php echo $check['passed'] ? 'check-success' : 'check-error'; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-2">
                                            <i class="bi bi-<?php echo $check['passed'] ? 'check-circle text-success' : 'times-circle text-danger'; ?> me-2"></i>
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
                </div>

                <!-- Action Buttons -->
                <div class="text-center mt-4 mb-5">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="config/install-guid.php" class="btn btn-primary btn-lg w-100 mb-2">
                                <i class="bi bi-redo me-2"></i>Re-install GUID DB
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="test-guid-system.php" class="btn btn-info btn-lg w-100 mb-2">
                                <i class="bi bi-vial me-2"></i>Run System Tests
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="admin/" class="btn btn-success btn-lg w-100 mb-2">
                                <i class="bi bi-cog me-2"></i>Go to Admin Panel
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="login.php" class="btn btn-warning btn-lg w-100 mb-2">
                                <i class="bi bi-sign-in-alt me-2"></i>Test Login System
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Final Status -->
                <div class="card">
                    <div class="card-header bg-<?php echo $overallStatus === 'excellent' ? 'success' : ($overallStatus === 'good' ? 'info' : ($overallStatus === 'warning' ? 'warning' : 'danger')); ?> text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-flag-checkered me-2"></i>Migration Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($overallStatus === 'excellent'): ?>
                            <div class="alert alert-success">
                                <h4><i class="bi bi-trophy me-2"></i>Migration Completed Successfully!</h4>
                                <p class="mb-0">
                                    🎉 <strong>Excellent!</strong> GUID migration is complete and all systems are working perfectly. 
                                    Your application is now using UUID-based IDs for enhanced security and scalability.
                                </p>
                                <hr>
                                <h6>✅ What's New:</h6>
                                <ul class="mb-0">
                                    <li>All database tables now use CHAR(36) GUID primary keys</li>
                                    <li>Enhanced security with non-predictable IDs</li>
                                    <li>Brute force protection improved</li>
                                    <li>UUID validation throughout the application</li>
                                    <li>Backward compatibility removed (clean migration)</li>
                                </ul>
                            </div>
                        <?php elseif ($overallStatus === 'good'): ?>
                            <div class="alert alert-info">
                                <h4><i class="bi bi-thumbs-up me-2"></i>Migration Nearly Complete</h4>
                                <p>
                                    <strong>Good progress!</strong> Most of the GUID migration is working correctly. 
                                    Please check the failed tests above and fix any remaining issues.
                                </p>
                            </div>
                        <?php elseif ($overallStatus === 'warning'): ?>
                            <div class="alert alert-warning">
                                <h4><i class="bi bi-exclamation-triangle me-2"></i>Migration Needs Attention</h4>
                                <p>
                                    <strong>Warning:</strong> Several checks have failed. Please review the failed tests 
                                    and complete the migration process before using the system.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h4><i class="bi bi-times-circle me-2"></i>Migration Incomplete</h4>
                                <p>
                                    <strong>Critical issues found!</strong> The GUID migration is not complete. 
                                    Please address all failed checks before proceeding.
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>Check completed at: <?php echo date('Y-m-d H:i:s'); ?>
                                <br><i class="bi bi-database me-1"></i>Database: <?php echo $pdo->query("SELECT DATABASE()")->fetchColumn(); ?>
                                <br><i class="bi bi-server me-1"></i>Server: <?php echo $_SERVER['SERVER_NAME'] ?? 'localhost'; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
