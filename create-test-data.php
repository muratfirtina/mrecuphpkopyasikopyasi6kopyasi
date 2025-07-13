<?php
/**
 * Test Data Generator
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Test Data Generator</h2>";

try {
    require_once 'config/config.php';
    require_once 'config/database.php';
    require_once 'includes/User.php';
    require_once 'includes/FileManager.php';
    
    if (!$pdo) {
        echo "❌ Database connection failed<br>";
        exit;
    }
    
    echo "✅ Database connected successfully<br><br>";
    
    // Check if we already have data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_uploads");
    $fileCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM revisions");
    $revisionCount = $stmt->fetch()['count'];
    
    echo "Current data: $fileCount files, $revisionCount revisions<br><br>";
    
    if ($fileCount > 0) {
        echo "⚠️ Data already exists. Do you want to create more test data?<br>";
        echo "<a href='?create=yes'>Yes, create test data</a> | ";
        echo "<a href='database-check.php'>Check database first</a><br><br>";
        
        if (!isset($_GET['create'])) {
            exit;
        }
    }
    
    // Get or create test users
    echo "<h3>Creating Test Data...</h3>";
    
    // Get users
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY created_at DESC LIMIT 3");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "❌ No users found. Please create users first.<br>";
        exit;
    }
    
    echo "Found " . count($users) . " users<br>";
    
    // Get or create brands
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM brands");
    $brandCount = $stmt->fetch()['count'];
    
    if ($brandCount == 0) {
        echo "Creating test brands...<br>";
        $brands = [
            ['id' => generateUUID(), 'name' => 'BMW'],
            ['id' => generateUUID(), 'name' => 'Mercedes'],
            ['id' => generateUUID(), 'name' => 'Audi'],
            ['id' => generateUUID(), 'name' => 'Volkswagen'],
            ['id' => generateUUID(), 'name' => 'Ford']
        ];
        
        foreach ($brands as $brand) {
            $stmt = $pdo->prepare("INSERT INTO brands (id, name, is_active, created_at) VALUES (?, ?, 1, NOW())");
            $stmt->execute([$brand['id'], $brand['name']]);
        }
        echo "✅ Created " . count($brands) . " brands<br>";
    }
    
    // Get brands
    $stmt = $pdo->query("SELECT id, name FROM brands WHERE is_active = 1 LIMIT 5");
    $brands = $stmt->fetchAll();
    
    // Get or create models
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM models");
    $modelCount = $stmt->fetch()['count'];
    
    if ($modelCount == 0 && !empty($brands)) {
        echo "Creating test models...<br>";
        $modelNames = ['320i', 'C200', 'A4', 'Golf', 'Focus'];
        $modelsCreated = 0;
        
        foreach ($brands as $brand) {
            foreach ($modelNames as $modelName) {
                $stmt = $pdo->prepare("INSERT INTO models (id, brand_id, name, year_start, year_end, is_active, created_at) VALUES (?, ?, ?, 2015, 2024, 1, NOW())");
                $stmt->execute([generateUUID(), $brand['id'], $modelName]);
                $modelsCreated++;
            }
        }
        echo "✅ Created $modelsCreated models<br>";
    }
    
    // Get models
    $stmt = $pdo->query("SELECT id, brand_id, name FROM models WHERE is_active = 1 LIMIT 10");
    $models = $stmt->fetchAll();
    
    // Create test file uploads
    echo "Creating test file uploads...<br>";
    $fileUploads = [];
    $statuses = ['pending', 'processing', 'completed', 'rejected'];
    
    for ($i = 1; $i <= 10; $i++) {
        $user = $users[array_rand($users)];
        $model = !empty($models) ? $models[array_rand($models)] : null;
        
        $fileId = generateUUID();
        $filename = 'test_file_' . $i . '_' . time() . '.bin';
        $originalName = 'ECU_File_' . $i . '.bin';
        $status = $statuses[array_rand($statuses)];
        $plate = sprintf('%02d%s%04d', rand(1, 81), chr(rand(65, 90)), rand(1000, 9999));
        
        $stmt = $pdo->prepare("
            INSERT INTO file_uploads 
            (id, user_id, brand_id, model_id, filename, original_name, file_size, status, 
             plate, ecu_type, engine_code, gearbox_type, fuel_type, hp_power, nm_torque, 
             upload_date, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $result = $stmt->execute([
            $fileId,
            $user['id'],
            $model ? $model['brand_id'] : null,
            $model ? $model['id'] : null,
            $filename,
            $originalName,
            rand(50000, 500000), // file size
            $status,
            $plate,
            'Bosch EDC17', // ecu_type
            'N47D20', // engine_code
            'Manual', // gearbox_type
            'Diesel', // fuel_type
            rand(150, 400), // hp_power
            rand(300, 600) // nm_torque
        ]);
        
        if ($result) {
            $fileUploads[] = ['id' => $fileId, 'user_id' => $user['id'], 'status' => $status];
            echo "✅ Created file upload: $originalName (Status: $status)<br>";
        }
    }
    
    // Create test revisions
    echo "<br>Creating test revisions...<br>";
    $revisionStatuses = ['pending', 'in_progress', 'completed', 'rejected'];
    
    // Only create revisions for completed files
    $completedFiles = array_filter($fileUploads, function($file) {
        return $file['status'] === 'completed';
    });
    
    foreach ($completedFiles as $file) {
        // 50% chance to have a revision
        if (rand(0, 1) == 1) {
            $revisionId = generateUUID();
            $status = $revisionStatuses[array_rand($revisionStatuses)];
            $requestNotes = 'Test revision request for file improvements. Please adjust power and torque settings.';
            
            $stmt = $pdo->prepare("
                INSERT INTO revisions 
                (id, upload_id, user_id, request_notes, status, requested_at, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([
                $revisionId,
                $file['id'],
                $file['user_id'],
                $requestNotes,
                $status
            ]);
            
            if ($result) {
                echo "✅ Created revision for file {$file['id']} (Status: $status)<br>";
            }
        }
    }
    
    echo "<br><h3>✅ Test Data Creation Completed!</h3>";
    
    // Show final counts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_uploads");
    $finalFileCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM revisions");
    $finalRevisionCount = $stmt->fetch()['count'];
    
    echo "Final data: $finalFileCount files, $finalRevisionCount revisions<br><br>";
    
    echo "<strong>Next Steps:</strong><br>";
    echo "1. <a href='database-check.php'>Check database structure and data</a><br>";
    echo "2. <a href='admin/index.php'>Test Admin Dashboard</a><br>";
    echo "3. <a href='user/index.php'>Test User Dashboard</a><br>";
    echo "4. <a href='admin/revisions.php'>Test Admin Revisions</a><br>";
    echo "5. <a href='user/revisions.php'>Test User Revisions</a><br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
