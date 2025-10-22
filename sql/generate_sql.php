<?php
/**
 * SQL Generator from development-database.txt
 * Bu script development-database.txt dosyasından SQL oluşturur
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$inputFile = __DIR__ . '/../development-database.txt';
$outputFile = __DIR__ . '/full_database_structure.sql';

if (!file_exists($inputFile)) {
    die("Error: development-database.txt bulunamadı!\n");
}

echo "SQL Generator başlatılıyor...\n";
echo "Input: $inputFile\n";
echo "Output: $outputFile\n\n";

// Dosyayı oku
$content = file_get_contents($inputFile);

// UTF-8 BOM temizle
$content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

// Satırlara ayır
$lines = explode("\n", $content);

$sql = "-- MR.ECU Tuning Database Structure\n";
$sql .= "-- Generated from development-database.txt\n";
$sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- Total Tables: 76\n\n";

$sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
$sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$sql .= "SET time_zone = \"+00:00\";\n\n";

$currentTable = null;
$columns = [];
$inTableSection = false;

foreach ($lines as $line) {
    $line = trim($line);
    
    // Tablo başlığını yakala
    if (preg_match('/^\s*Tablo:\s*(.+)$/i', $line, $matches)) {
        // Önceki tabloyu SQL'e ekle
        if ($currentTable && count($columns) > 0) {
            $sql .= generateTableSQL($currentTable, $columns);
        }
        
        $currentTable = trim($matches[1]);
        $columns = [];
        $inTableSection = true;
        continue;
    }
    
    // Kolon başlığı satırını atla
    if (preg_match('/^Kolon Adı\s+Veri Tipi/i', $line)) {
        continue;
    }
    
    // Kolon verisini yakala (tab ile ayrılmış)
    if ($inTableSection && !empty($line) && strpos($line, "\t") !== false) {
        $parts = preg_split('/\t+/', $line);
        
        if (count($parts) >= 4) {
            $columnName = trim($parts[0]);
            $dataType = trim($parts[1]);
            $null = trim($parts[2]);
            $key = trim($parts[3]);
            $default = isset($parts[4]) ? trim($parts[4]) : '';
            $extra = isset($parts[5]) ? trim($parts[5]) : '';
            
            // Eğer geçerli kolon adı değilse atla
            if (empty($columnName) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $columnName)) {
                continue;
            }
            
            $columns[] = [
                'name' => $columnName,
                'type' => $dataType,
                'null' => $null,
                'key' => $key,
                'default' => $default,
                'extra' => $extra
            ];
        }
    }
}

// Son tabloyu ekle
if ($currentTable && count($columns) > 0) {
    $sql .= generateTableSQL($currentTable, $columns);
}

$sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

// SQL dosyasını yaz
file_put_contents($outputFile, $sql);

echo "\n✓ SQL dosyası başarıyla oluşturuldu!\n";
echo "Toplam Boyut: " . number_format(strlen($sql)) . " bytes\n";
echo "Dosya: $outputFile\n";

/**
 * Tablo SQL'i oluştur
 */
function generateTableSQL($tableName, $columns) {
    $sql = "-- Table: $tableName\n";
    $sql .= "CREATE TABLE IF NOT EXISTS `$tableName` (\n";
    
    $columnDefs = [];
    $primaryKeys = [];
    $indexes = [];
    
    foreach ($columns as $col) {
        $colDef = "  `{$col['name']}` {$col['type']}";
        
        // NULL kontrolü
        if (strtoupper($col['null']) === 'NO') {
            $colDef .= ' NOT NULL';
        } else {
            $colDef .= ' NULL';
        }
        
        // Default değer
        if (!empty($col['default']) && $col['default'] !== 'NULL') {
            $default = $col['default'];
            
            // CURRENT_TIMESTAMP gibi özel değerler
            if (stripos($default, 'CURRENT_TIMESTAMP') !== false || 
                stripos($default, 'uuid()') !== false ||
                is_numeric($default)) {
                $colDef .= " DEFAULT $default";
            } else {
                $colDef .= " DEFAULT '$default'";
            }
        } elseif ($col['default'] === 'NULL' && strtoupper($col['null']) === 'YES') {
            $colDef .= ' DEFAULT NULL';
        }
        
        // Extra (auto_increment, on update, etc.)
        if (!empty($col['extra'])) {
            $extra = str_replace('DEFAULT_GENERATED', '', $col['extra']);
            $extra = trim($extra);
            if (!empty($extra)) {
                $colDef .= ' ' . strtoupper($extra);
            }
        }
        
        $columnDefs[] = $colDef;
        
        // Primary Key
        if (stripos($col['key'], 'PRIMARY') !== false) {
            $primaryKeys[] = $col['name'];
        }
        
        // Index
        if (stripos($col['key'], 'UNIQUE') !== false) {
            $indexes[] = "UNIQUE KEY `idx_{$tableName}_{$col['name']}` (`{$col['name']}`)";
        } elseif (stripos($col['key'], 'FOREIGN') !== false || stripos($col['key'], 'MUL') !== false) {
            $indexes[] = "KEY `idx_{$tableName}_{$col['name']}` (`{$col['name']}`)";
        }
    }
    
    $sql .= implode(",\n", $columnDefs);
    
    // Primary Key ekle
    if (!empty($primaryKeys)) {
        $sql .= ",\n  PRIMARY KEY (`" . implode('`, `', $primaryKeys) . "`)";
    }
    
    // Index'leri ekle
    if (!empty($indexes)) {
        $sql .= ",\n  " . implode(",\n  ", $indexes);
    }
    
    $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";
    
    return $sql;
}
?>
