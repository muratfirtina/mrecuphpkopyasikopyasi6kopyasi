<?php
/**
 * Add remember_token column to users table
 * This migration adds the remember_token column needed for "Remember Me" functionality
 */

require_once '../config/config.php';
require_once '../config/database.php';

try {
    // Check if remember_token column already exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'remember_token'");
    
    if ($checkColumn->rowCount() == 0) {
        // Column doesn't exist, add it
        $pdo->exec("ALTER TABLE users ADD COLUMN remember_token VARCHAR(255) NULL AFTER reset_token_expires");
        echo "✅ remember_token column added successfully to users table.\n";
    } else {
        echo "ℹ️  remember_token column already exists in users table.\n";
    }
    
    // Also check if we need to add an index for performance
    $checkIndex = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'idx_remember_token'");
    
    if ($checkIndex->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD INDEX idx_remember_token (remember_token)");
        echo "✅ Index added for remember_token column.\n";
    } else {
        echo "ℹ️  Index for remember_token already exists.\n";
    }
    
    echo "🎉 Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    // If the error is about the column already existing, that's fine
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "ℹ️  Column already exists, migration skipped.\n";
    } else {
        die("Migration failed!\n");
    }
}
?>