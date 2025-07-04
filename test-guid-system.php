<?php
/**
 * Mr ECU - GUID System Test Script
 * GUID sistemini test eder
 */

require_once 'config/config.php';
require_once 'config/database.php';

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GUID System Test - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-item { margin: 10px 0; padding: 15px; border-radius: 8px; }
        .test-success { background: #d4edda; border: 1px solid #c3e6cb; }
        .test-error { background: #f8d7da; border: 1px solid #f5c6cb; }
        .guid-display { font-family: monospace; background: #f8f9fa; padding: 5px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-vial me-2"></i>GUID System Test
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <!-- Test 1: UUID Generation -->
                        <h5><i class="fas fa-plus-circle me-2"></i>Test 1: UUID Generation</h5>
                        <?php
                        $testsPassed = 0;
                        $totalTests = 0;
                        
                        $totalTests++;
                        try {
                            $uuid1 = generateUUID();
                            $uuid2 = generateUUID();
                            $uuid3 = generateUUID();
                            
                            if (!empty($uuid1) && !empty($uuid2) && !empty($uuid3) && 
                                $uuid1 !== $uuid2 && $uuid2 !== $uuid3) {
                                echo "<div class='test-item test-success'>";
                                echo "<i class='fas fa-check-circle text-success me-2'></i>";
                                echo "<strong>✅ UUID Generation Test Passed</strong><br>";
                                echo "Generated UUIDs:<br>";
                                echo "<span class='guid-display'>$uuid1</span><br>";
                                echo "<span class='guid-display'>$uuid2</span><br>";
                                echo "<span class='guid-display'>$uuid3</span>";
                                echo "</div>";
                                $testsPassed++;
                            } else {
                                throw new Exception("UUID generation failed or duplicates found");
                            }
                        } catch (Exception $e) {
                            echo "<div class='test-item test-error'>";
                            echo "<i class='fas fa-times-circle text-danger me-2'></i>";
                            echo "<strong>❌ UUID Generation Test Failed:</strong> " . $e->getMessage();
                            echo "</div>";
                        }
                        ?>

                        <!-- Test 2: UUID Validation -->
                        <h5><i class="fas fa-shield-alt me-2"></i>Test 2: UUID Validation</h5>
                        <?php
                        $totalTests++;
                        try {
                            $validUuid = generateUUID();
                            $invalidUuids = [
                                '123',
                                'not-a-uuid',
                                '12345678-1234-1234-1234-123456789012', // wrong format
                                'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
                                ''
                            ];
                            
                            $validationPassed = true;
                            
                            // Test valid UUID
                            if (!isValidUUID($validUuid)) {
                                $validationPassed = false;
                                throw new Exception("Valid UUID rejected: $validUuid");
                            }
                            
                            // Test invalid UUIDs
                            foreach ($invalidUuids as $invalid) {
                                if (isValidUUID($invalid)) {
                                    $validationPassed = false;
                                    throw new Exception("Invalid UUID accepted: $invalid");
                                }
                            }
                            
                            if ($validationPassed) {
                                echo "<div class='test-item test-success'>";
                                echo "<i class='fas fa-check-circle text-success me-2'></i>";
                                echo "<strong>✅ UUID Validation Test Passed</strong><br>";
                                echo "Valid UUID: <span class='guid-display'>$validUuid</span> ✓<br>";
                                echo "Invalid UUIDs rejected: " . count($invalidUuids) . " ✓";
                                echo "</div>";
                                $testsPassed++;
                            }
                            
                        } catch (Exception $e) {
                            echo "<div class='test-item test-error'>";
                            echo "<i class='fas fa-times-circle text-danger me-2'></i>";
                            echo "<strong>❌ UUID Validation Test Failed:</strong> " . $e->getMessage();
                            echo "</div>";
                        }
                        ?>

                        <!-- Test 3: Database Connection -->
                        <h5><i class="fas fa-database me-2"></i>Test 3: Database Connection</h5>
                        <?php
                        $totalTests++;
                        try {
                            if ($pdo && $pdo instanceof PDO) {
                                // Test database name
                                $stmt = $pdo->query("SELECT DATABASE() as db_name");
                                $result = $stmt->fetch();
                                $dbName = $result['db_name'];
                                
                                if ($dbName === 'mrecu_db_guid') {
                                    echo "<div class='test-item test-success'>";
                                    echo "<i class='fas fa-check-circle text-success me-2'></i>";
                                    echo "<strong>✅ Database Connection Test Passed</strong><br>";
                                    echo "Connected to: <strong>$dbName</strong>";
                                    echo "</div>";
                                    $testsPassed++;
                                } else {
                                    throw new Exception("Wrong database: $dbName (expected: mrecu_db_guid)");
                                }
                            } else {
                                throw new Exception("PDO connection failed");
                            }
                        } catch (Exception $e) {
                            echo "<div class='test-item test-error'>";
                            echo "<i class='fas fa-times-circle text-danger me-2'></i>";
                            echo "<strong>❌ Database Connection Test Failed:</strong> " . $e->getMessage();
                            echo "</div>";
                        }
                        ?>

                        <!-- Test 4: Table Structure -->
                        <h5><i class="fas fa-table me-2"></i>Test 4: GUID Table Structure</h5>
                        <?php
                        $totalTests++;
                        try {
                            $tables = ['users', 'brands', 'models', 'file_uploads', 'file_responses', 
                                      'revisions', 'revision_files', 'credit_transactions', 'system_logs'];
                            
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
                                    
                                    if ($idColumn && 
                                        strpos($idColumn['Type'], 'char(36)') !== false && 
                                        $idColumn['Key'] === 'PRI') {
                                        $tableResults[$table] = true;
                                    } else {
                                        $tableResults[$table] = false;
                                    }
                                } catch (Exception $e) {
                                    $tableResults[$table] = false;
                                }
                            }
                            
                            $successCount = array_sum($tableResults);
                            $totalTableCount = count($tables);
                            
                            if ($successCount === $totalTableCount) {
                                echo "<div class='test-item test-success'>";
                                echo "<i class='fas fa-check-circle text-success me-2'></i>";
                                echo "<strong>✅ Table Structure Test Passed</strong><br>";
                                echo "All $totalTableCount tables have CHAR(36) GUID primary keys";
                                echo "</div>";
                                $testsPassed++;
                            } else {
                                echo "<div class='test-item test-error'>";
                                echo "<i class='fas fa-times-circle text-danger me-2'></i>";
                                echo "<strong>❌ Table Structure Test Failed</strong><br>";
                                echo "Only $successCount/$totalTableCount tables have correct GUID structure<br>";
                                echo "<small>";
                                foreach ($tableResults as $table => $success) {
                                    $icon = $success ? '✅' : '❌';
                                    echo "$icon $table<br>";
                                }
                                echo "</small>";
                                echo "</div>";
                            }
                            
                        } catch (Exception $e) {
                            echo "<div class='test-item test-error'>";
                            echo "<i class='fas fa-times-circle text-danger me-2'></i>";
                            echo "<strong>❌ Table Structure Test Failed:</strong> " . $e->getMessage();
                            echo "</div>";
                        }
                        ?>

                        <!-- Test 5: Sample Data -->
                        <h5><i class="fas fa-data me-2"></i>Test 5: Sample GUID Data</h5>
                        <?php
                        $totalTests++;
                        try {
                            // Check for sample data
                            $stmt = $pdo->query("SELECT id, username FROM users LIMIT 3");
                            $users = $stmt->fetchAll();
                            
                            $stmt = $pdo->query("SELECT id, name FROM brands LIMIT 5");
                            $brands = $stmt->fetchAll();
                            
                            if (!empty($users) && !empty($brands)) {
                                $allGuidsValid = true;
                                
                                // Validate user GUIDs
                                foreach ($users as $user) {
                                    if (!isValidUUID($user['id'])) {
                                        $allGuidsValid = false;
                                        break;
                                    }
                                }
                                
                                // Validate brand GUIDs
                                foreach ($brands as $brand) {
                                    if (!isValidUUID($brand['id'])) {
                                        $allGuidsValid = false;
                                        break;
                                    }
                                }
                                
                                if ($allGuidsValid) {
                                    echo "<div class='test-item test-success'>";
                                    echo "<i class='fas fa-check-circle text-success me-2'></i>";
                                    echo "<strong>✅ Sample Data Test Passed</strong><br>";
                                    echo "Found " . count($users) . " users and " . count($brands) . " brands with valid GUIDs<br>";
                                    echo "<small>";
                                    echo "Sample User: <span class='guid-display'>{$users[0]['id']}</span> - {$users[0]['username']}<br>";
                                    echo "Sample Brand: <span class='guid-display'>{$brands[0]['id']}</span> - {$brands[0]['name']}";
                                    echo "</small>";
                                    echo "</div>";
                                    $testsPassed++;
                                } else {
                                    throw new Exception("Some data contains invalid GUIDs");
                                }
                            } else {
                                throw new Exception("No sample data found");
                            }
                        } catch (Exception $e) {
                            echo "<div class='test-item test-error'>";
                            echo "<i class='fas fa-times-circle text-danger me-2'></i>";
                            echo "<strong>❌ Sample Data Test Failed:</strong> " . $e->getMessage();
                            echo "</div>";
                        }
                        ?>

                        <!-- Test Results Summary -->
                        <hr class="my-4">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-chart-pie me-2"></i>Test Results Summary</h5>
                                <?php
                                $successRate = round(($testsPassed / $totalTests) * 100, 1);
                                $badgeClass = $successRate >= 80 ? 'success' : ($successRate >= 60 ? 'warning' : 'danger');
                                ?>
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h2 class="text-<?php echo $badgeClass; ?>"><?php echo $testsPassed; ?>/<?php echo $totalTests; ?></h2>
                                        <p class="mb-0">Tests Passed</p>
                                        <div class="progress mt-2">
                                            <div class="progress-bar bg-<?php echo $badgeClass; ?>" role="progressbar" 
                                                 style="width: <?php echo $successRate; ?>%" 
                                                 aria-valuenow="<?php echo $successRate; ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $successRate; ?>%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-info-circle me-2"></i>Next Steps</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <?php if ($testsPassed === $totalTests): ?>
                                            <div class="alert alert-success mb-0">
                                                <i class="fas fa-check-circle me-2"></i>
                                                <strong>Perfect!</strong> GUID system is ready for use.
                                                <hr>
                                                <small>
                                                    ✅ All tests passed<br>
                                                    ✅ Database schema updated<br>
                                                    ✅ GUID functions working<br>
                                                    ✅ Sample data valid
                                                </small>
                                            </div>
                                        <?php elseif ($testsPassed >= $totalTests * 0.8): ?>
                                            <div class="alert alert-warning mb-0">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>Almost there!</strong> Minor issues detected.
                                                <hr>
                                                <small>
                                                    Check failed tests above and fix them.
                                                </small>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-danger mb-0">
                                                <i class="fas fa-times-circle me-2"></i>
                                                <strong>Issues found!</strong> GUID system needs attention.
                                                <hr>
                                                <small>
                                                    Please fix the failed tests before proceeding.
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="text-center mt-4">
                            <a href="config/install-guid.php" class="btn btn-primary me-2">
                                <i class="fas fa-redo me-1"></i>Re-run Installation
                            </a>
                            <a href="final-guid-migration-complete.php" class="btn btn-warning me-2">
                                <i class="fas fa-clipboard-check me-1"></i>Complete Check
                            </a>
                            <a href="admin/" class="btn btn-success me-2">
                                <i class="fas fa-cog me-1"></i>Go to Admin Panel
                            </a>
                            <a href="login.php" class="btn btn-info">
                                <i class="fas fa-sign-in-alt me-1"></i>Test Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
