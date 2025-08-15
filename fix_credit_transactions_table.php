<?php
/**
 * Fix Credit Transactions Table - Add Reset and Set Transaction Types
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Credit Transactions Table Fix</h1>";

try {
    // Mevcut tablo yapısını kontrol et
    echo "<h2>1. Current Table Structure</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM credit_transactions LIKE 'transaction_type'");
    $currentColumn = $stmt->fetch();
    
    if ($currentColumn) {
        echo "<p><strong>Current transaction_type column:</strong></p>";
        echo "<pre>";
        print_r($currentColumn);
        echo "</pre>";
        
        // Mevcut transaction_type değerleri
        echo "<h2>2. Current Transaction Types</h2>";
        $stmt = $pdo->query("SELECT DISTINCT transaction_type FROM credit_transactions ORDER BY transaction_type");
        $existingTypes = $stmt->fetchAll();
        
        echo "<p><strong>Existing values:</strong></p>";
        echo "<ul>";
        foreach ($existingTypes as $type) {
            echo "<li>" . htmlspecialchars($type['transaction_type']) . "</li>";
        }
        echo "</ul>";
        
        // ENUM ise değerleri çıkar
        $columnType = $currentColumn['Type'];
        if (strpos($columnType, 'enum') !== false) {
            echo "<h2>3. ENUM Values Detected</h2>";
            echo "<p>Column type: <code>$columnType</code></p>";
            
            // ENUM değerlerini parse et
            preg_match_all("/'([^']+)'/", $columnType, $matches);
            $enumValues = $matches[1];
            
            echo "<p><strong>Current ENUM values:</strong></p>";
            echo "<ul>";
            foreach ($enumValues as $value) {
                echo "<li>$value</li>";
            }
            echo "</ul>";
            
            // Yeni değerleri ekle
            $newValues = array_merge($enumValues, ['reset', 'set']);
            $newValues = array_unique($newValues);
            
            $newEnumValues = "'" . implode("','", $newValues) . "'";
            
            echo "<h2>4. Updating ENUM Values</h2>";
            echo "<p><strong>New ENUM values will be:</strong> $newEnumValues</p>";
            
            $sql = "ALTER TABLE credit_transactions MODIFY COLUMN transaction_type ENUM($newEnumValues) NOT NULL";
            
            echo "<p><strong>SQL Command:</strong></p>";
            echo "<code>$sql</code>";
            
            echo "<h2>5. Executing Update...</h2>";
            $pdo->exec($sql);
            
            echo "<div style='color: green; font-weight: bold; padding: 10px; background: #e8f5e8; border: 1px solid #4caf50;'>
                ✅ SUCCESS: transaction_type column updated successfully!
            </div>";
            
        } else {
            echo "<h2>3. Converting to VARCHAR</h2>";
            echo "<p>Column is not ENUM. Converting to VARCHAR to allow any values...</p>";
            
            $sql = "ALTER TABLE credit_transactions MODIFY COLUMN transaction_type VARCHAR(50) NOT NULL";
            
            echo "<p><strong>SQL Command:</strong></p>";
            echo "<code>$sql</code>";
            
            echo "<h2>4. Executing Update...</h2>";
            $pdo->exec($sql);
            
            echo "<div style='color: green; font-weight: bold; padding: 10px; background: #e8f5e8; border: 1px solid #4caf50;'>
                ✅ SUCCESS: transaction_type column converted to VARCHAR!
            </div>";
        }
        
        // Type sütununu da kontrol et ve gerekirse güncelle
        echo "<h2>6. Checking 'type' Column</h2>";
        $stmt = $pdo->query("SHOW COLUMNS FROM credit_transactions LIKE 'type'");
        $typeColumn = $stmt->fetch();
        
        if ($typeColumn) {
            $typeColumnType = $typeColumn['Type'];
            echo "<p><strong>Current type column:</strong> $typeColumnType</p>";
            
            if (strpos($typeColumnType, 'enum') !== false) {
                // Mevcut type değerleri
                $stmt = $pdo->query("SELECT DISTINCT type FROM credit_transactions ORDER BY type");
                $existingTypeValues = $stmt->fetchAll();
                
                echo "<p><strong>Existing type values:</strong></p>";
                echo "<ul>";
                foreach ($existingTypeValues as $typeValue) {
                    echo "<li>" . htmlspecialchars($typeValue['type']) . "</li>";
                }
                echo "</ul>";
                
                // Type ENUM değerlerini parse et
                preg_match_all("/'([^']+)'/", $typeColumnType, $typeMatches);
                $typeEnumValues = $typeMatches[1];
                
                // Yeni değerleri ekle (quota_reset, quota_set)
                $newTypeValues = array_merge($typeEnumValues, ['quota_reset', 'quota_set']);
                $newTypeValues = array_unique($newTypeValues);
                
                $newTypeEnumValues = "'" . implode("','", $newTypeValues) . "'";
                
                echo "<p><strong>Updating type ENUM values to:</strong> $newTypeEnumValues</p>";
                
                $typeSql = "ALTER TABLE credit_transactions MODIFY COLUMN type ENUM($newTypeEnumValues) NOT NULL";
                echo "<p><strong>SQL Command:</strong></p>";
                echo "<code>$typeSql</code>";
                
                $pdo->exec($typeSql);
                
                echo "<div style='color: green; font-weight: bold; padding: 10px; background: #e8f5e8; border: 1px solid #4caf50; margin-top: 10px;'>
                    ✅ SUCCESS: type column updated successfully!
                </div>";
            } else {
                echo "<p>Type column is not ENUM, no update needed.</p>";
            }
        }
        
        // Sonuç kontrolü
        echo "<h2>7. Final Verification</h2>";
        $stmt = $pdo->query("SHOW COLUMNS FROM credit_transactions WHERE Field IN ('transaction_type', 'type')");
        $finalColumns = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($finalColumns as $column) {
            echo "<tr>";
            echo "<td><strong>{$column['Field']}</strong></td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='color: blue; font-weight: bold; padding: 10px; background: #e3f2fd; border: 1px solid #2196f3; margin-top: 20px;'>
            🎉 ALL DONE! You can now use 'reset' and 'set' transaction types in your credits system.
        </div>";
        
    } else {
        echo "<p style='color: red;'>ERROR: transaction_type column not found!</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold; padding: 10px; background: #ffebee; border: 1px solid #f44336;'>
        ❌ ERROR: " . $e->getMessage() . "
    </div>";
    
    echo "<h3>Alternative Solution</h3>";
    echo "<p>If the above failed, you can manually run this SQL command in phpMyAdmin:</p>";
    echo "<code style='display: block; background: #f5f5f5; padding: 10px; margin: 10px 0;'>
        ALTER TABLE credit_transactions MODIFY COLUMN transaction_type VARCHAR(50) NOT NULL;
        <br>
        ALTER TABLE credit_transactions MODIFY COLUMN type VARCHAR(50) NOT NULL;
    </code>";
}

echo "<br><br>";
echo "<a href='admin/credits.php' style='display: inline-block; padding: 10px 20px; background: #4caf50; color: white; text-decoration: none; border-radius: 5px;'>← Back to Credits Management</a>";
?>
