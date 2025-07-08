<?php
/**
 * MR.ECU Legacy Data Converter - SQL Server Database Debug & Query Generator
 * SQL Server verilerini MySQL formatına dönüştürme yardımcısı + Debug sistemi
 */

// SQL Server bağlantı test fonksiyonu
function testSqlServerConnection($server, $database = null, $username = null, $password = null) {
    try {
        $dsn = "sqlsrv:Server=$server";
        if ($database) {
            $dsn .= ";Database=$database";
        }
        
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        );
        
        if ($username && $password) {
            $pdo = new PDO($dsn, $username, $password, $options);
        } else {
            // Windows Authentication
            $pdo = new PDO($dsn, null, null, $options);
        }
        
        return ['success' => true, 'connection' => $pdo, 'message' => 'Bağlantı başarılı!'];
    } catch (Exception $e) {
        return ['success' => false, 'connection' => null, 'message' => $e->getMessage()];
    }
}

// Debug işlemleri
$debug_results = [];
$sql_queries = [];

if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'test_connection':
            $server = $_POST['server'] ?? '';
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $debug_results['connection'] = testSqlServerConnection($server, null, $username, $password);
            break;
            
        case 'list_databases':
            $server = $_POST['server'] ?? '';
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $conn_result = testSqlServerConnection($server, null, $username, $password);
            if ($conn_result['success']) {
                try {
                    $stmt = $conn_result['connection']->query("SELECT name FROM sys.databases WHERE name NOT IN ('master', 'model', 'msdb', 'tempdb') ORDER BY name");
                    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $debug_results['databases'] = ['success' => true, 'data' => $databases];
                } catch (Exception $e) {
                    $debug_results['databases'] = ['success' => false, 'message' => $e->getMessage()];
                }
            } else {
                $debug_results['databases'] = ['success' => false, 'message' => 'Bağlantı kurulamadı'];
            }
            break;
            
        case 'analyze_database':
            $server = $_POST['server'] ?? '';
            $database = $_POST['database'] ?? '';
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $conn_result = testSqlServerConnection($server, $database, $username, $password);
            if ($conn_result['success']) {
                try {
                    // Tabloları listele
                    $stmt = $conn_result['connection']->query("
                        SELECT 
                            TABLE_NAME,
                            (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = t.TABLE_NAME) as column_count
                        FROM INFORMATION_SCHEMA.TABLES t 
                        WHERE TABLE_TYPE = 'BASE TABLE'
                        ORDER BY TABLE_NAME
                    ");
                    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Her tablo için sütun bilgilerini al
                    $table_details = [];
                    foreach ($tables as $table) {
                        $table_name = $table['TABLE_NAME'];
                        
                        // Sütun bilgileri
                        $column_stmt = $conn_result['connection']->prepare("
                            SELECT 
                                COLUMN_NAME,
                                DATA_TYPE,
                                IS_NULLABLE,
                                CHARACTER_MAXIMUM_LENGTH,
                                COLUMN_DEFAULT
                            FROM INFORMATION_SCHEMA.COLUMNS 
                            WHERE TABLE_NAME = ?
                            ORDER BY ORDINAL_POSITION
                        ");
                        $column_stmt->execute([$table_name]);
                        $columns = $column_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Örnek veri (ilk 3 satır)
                        try {
                            $sample_stmt = $conn_result['connection']->query("SELECT TOP 3 * FROM [$table_name]");
                            $sample_data = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) {
                            $sample_data = [];
                        }
                        
                        // Toplam satır sayısı
                        try {
                            $count_stmt = $conn_result['connection']->query("SELECT COUNT(*) as total FROM [$table_name]");
                            $row_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                        } catch (Exception $e) {
                            $row_count = 0;
                        }
                        
                        $table_details[$table_name] = [
                            'columns' => $columns,
                            'sample_data' => $sample_data,
                            'row_count' => $row_count
                        ];
                    }
                    
                    $debug_results['analysis'] = [
                        'success' => true, 
                        'tables' => $tables,
                        'details' => $table_details
                    ];
                } catch (Exception $e) {
                    $debug_results['analysis'] = ['success' => false, 'message' => $e->getMessage()];
                }
            } else {
                $debug_results['analysis'] = ['success' => false, 'message' => 'Bağlantı kurulamadı'];
            }
            break;
            
        case 'generate_queries':
            $server = $_POST['server'] ?? '';
            $database = $_POST['database'] ?? '';
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            // Dinamik SQL sorguları oluştur
            $sql_queries = generateDynamicQueries($database, $server, $username, $password);
            break;
    }
}

function generateDynamicQueries($database, $server, $username, $password) {
    $queries = [];
    
    $conn_result = testSqlServerConnection($server, $database, $username, $password);
    if (!$conn_result['success']) {
        return ['error' => 'Bağlantı kurulamadı: ' . $conn_result['message']];
    }
    
    $pdo = $conn_result['connection'];
    
    try {
        // Users tablosu kontrolü
        $stmt = $pdo->query("
            SELECT TABLE_NAME 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_TYPE = 'BASE TABLE' 
            AND (TABLE_NAME LIKE '%User%' OR TABLE_NAME LIKE '%users%')
        ");
        $user_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($user_tables)) {
            $user_table = $user_tables[0];
            
            // Users tablosu sütunlarını kontrol et
            $column_stmt = $pdo->prepare("
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = ?
            ");
            $column_stmt->execute([$user_table]);
            $user_columns = $column_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Users query oluştur
            $queries['users'] = "-- SQL Server Users Export Query
SELECT 
    LOWER(NEWID()) as new_id,
    CAST(" . (in_array('Id', $user_columns) ? 'Id' : (in_array('ID', $user_columns) ? 'ID' : 'UserID')) . " as VARCHAR(50)) as legacy_id,
    " . (in_array('UserName', $user_columns) ? 'UserName' : (in_array('Username', $user_columns) ? 'Username' : 'Email')) . " as username,
    Email as email,
    Password as password,
    " . (in_array('Name', $user_columns) ? 'ISNULL(Name, \'\')' : (in_array('FirstName', $user_columns) ? 'ISNULL(FirstName, \'\')' : '\'\'')) . " as first_name,
    " . (in_array('LastName', $user_columns) ? 'ISNULL(LastName, \'\')' : (in_array('Surname', $user_columns) ? 'ISNULL(Surname, \'\')' : '\'\'')) . " as last_name,
    " . (in_array('Phone', $user_columns) ? 'ISNULL(Phone, \'\')' : '\'\'') . " as phone,
    " . (in_array('Wallet', $user_columns) ? 'ISNULL(Wallet, 0)' : '0') . " as wallet,
    CASE 
        WHEN UPPER(" . (in_array('UserType', $user_columns) ? 'UserType' : (in_array('Role', $user_columns) ? 'Role' : '\'user\'')) . ") = 'ADMIN' THEN 'admin'
        ELSE 'user' 
    END as user_type,
    " . (in_array('IsConfirm', $user_columns) ? 'CASE WHEN IsConfirm = 1 THEN 1 ELSE 0 END' : (in_array('IsConfirmed', $user_columns) ? 'CASE WHEN IsConfirmed = 1 THEN 1 ELSE 0 END' : '1')) . " as is_confirm,
    " . (in_array('CreatedDate', $user_columns) ? 'CreatedDate' : (in_array('CreateDate', $user_columns) ? 'CreateDate' : 'GETDATE()')) . " as created_date,
    " . (in_array('UpdatedDate', $user_columns) ? 'UpdatedDate' : (in_array('UpdateDate', $user_columns) ? 'UpdateDate' : 'GETDATE()')) . " as updated_date,
    " . (in_array('DeletedDate', $user_columns) ? 'CASE WHEN DeletedDate IS NOT NULL AND DeletedDate != \'1900-01-01\' THEN DeletedDate ELSE NULL END' : 'NULL') . " as deleted_date
FROM [$database].[$user_table]
" . (in_array('DeletedDate', $user_columns) ? "WHERE (DeletedDate IS NULL OR DeletedDate = '1900-01-01')" : '') . "
ORDER BY " . (in_array('CreatedDate', $user_columns) ? 'CreatedDate' : (in_array('CreateDate', $user_columns) ? 'CreateDate' : (in_array('Id', $user_columns) ? 'Id' : 'ID'))) . ";";
        }
        
        // Files tablosu kontrolü
        $stmt = $pdo->query("
            SELECT TABLE_NAME 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_TYPE = 'BASE TABLE' 
            AND (TABLE_NAME LIKE '%File%' OR TABLE_NAME LIKE '%files%')
        ");
        $file_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($file_tables)) {
            $file_table = $file_tables[0];
            
            // Files tablosu sütunlarını kontrol et
            $column_stmt = $pdo->prepare("
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = ?
            ");
            $column_stmt->execute([$file_table]);
            $file_columns = $column_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Files query oluştur
            $queries['files'] = "-- SQL Server Files Export Query
SELECT 
    LOWER(NEWID()) as new_id,
    CAST(" . (in_array('Id', $file_columns) ? 'f.Id' : (in_array('ID', $file_columns) ? 'f.ID' : 'f.FileID')) . " as VARCHAR(50)) as legacy_file_id,
    CAST(" . (in_array('UserId', $file_columns) ? 'f.UserId' : (in_array('UserID', $file_columns) ? 'f.UserID' : 'f.User_Id')) . " as VARCHAR(50)) as user_id,
    " . (in_array('Brand', $file_columns) ? 'TRIM(f.Brand)' : (in_array('Marka', $file_columns) ? 'TRIM(f.Marka)' : '\'\'')) . " as brand,
    " . (in_array('Model', $file_columns) ? 'TRIM(f.Model)' : '\'\'') . " as model,
    " . (in_array('Year', $file_columns) ? 'COALESCE(f.Year, 2020)' : (in_array('ModelYear', $file_columns) ? 'COALESCE(f.ModelYear, 2020)' : '2020')) . " as year,
    " . (in_array('Ecu', $file_columns) ? 'ISNULL(f.Ecu, \'\')' : (in_array('ECU', $file_columns) ? 'ISNULL(f.ECU, \'\')' : '\'\'')) . " as ecu,
    " . (in_array('Motor', $file_columns) ? 'ISNULL(f.Motor, \'\')' : (in_array('Engine', $file_columns) ? 'ISNULL(f.Engine, \'\')' : '\'\'')) . " as motor,
    " . (in_array('DeviceType', $file_columns) ? 'ISNULL(f.DeviceType, \'\')' : '\'\'') . " as device_type,
    CASE 
        WHEN UPPER(" . (in_array('TransmissionType', $file_columns) ? 'f.TransmissionType' : (in_array('Transmission', $file_columns) ? 'f.Transmission' : '\'Manual\'')) . ") LIKE '%AUTO%' THEN 'Automatic'
        WHEN UPPER(" . (in_array('TransmissionType', $file_columns) ? 'f.TransmissionType' : (in_array('Transmission', $file_columns) ? 'f.Transmission' : '\'Manual\'')) . ") LIKE '%MANUAL%' THEN 'Manual'
        ELSE 'Manual'
    END as gearbox_type,
    CASE 
        WHEN UPPER(" . (in_array('Type', $file_columns) ? 'f.Type' : (in_array('FuelType', $file_columns) ? 'f.FuelType' : '\'Benzin\'')) . ") LIKE '%BENZIN%' THEN 'Benzin'
        WHEN UPPER(" . (in_array('Type', $file_columns) ? 'f.Type' : (in_array('FuelType', $file_columns) ? 'f.FuelType' : '\'Benzin\'')) . ") LIKE '%DIZEL%' THEN 'Dizel'
        ELSE 'Benzin'
    END as fuel_type,
    " . (in_array('Kilometer', $file_columns) ? 'ISNULL(f.Kilometer, \'\')' : (in_array('KM', $file_columns) ? 'ISNULL(f.KM, \'\')' : '\'\'')) . " as kilometer,
    " . (in_array('Plate', $file_columns) ? 'ISNULL(f.Plate, \'\')' : (in_array('PlateNumber', $file_columns) ? 'ISNULL(f.PlateNumber, \'\')' : '\'\'')) . " as plate,
    " . (in_array('Type', $file_columns) ? 'ISNULL(f.Type, \'\')' : '\'\'') . " as type,
    " . (in_array('FileLink', $file_columns) ? 'f.FileLink' : (in_array('FilePath', $file_columns) ? 'f.FilePath' : '\'\'')) . " as file_link,
    " . (in_array('Comment', $file_columns) ? 'ISNULL(f.Comment, \'\')' : (in_array('Note', $file_columns) ? 'ISNULL(f.Note, \'\')' : '\'\'')) . " as comment,
    " . (in_array('Code', $file_columns) ? 'ISNULL(f.Code, \'\')' : '\'\'') . " as code,
    " . (in_array('Status', $file_columns) ? 'f.Status' : '0') . " as status,
    " . (in_array('StatusText', $file_columns) ? 'ISNULL(f.StatusText, \'\')' : '\'\'') . " as status_text,
    " . (in_array('AdminNote', $file_columns) ? 'ISNULL(f.AdminNote, \'\')' : '\'\'') . " as admin_note,
    " . (in_array('Price', $file_columns) ? 'ISNULL(f.Price, 0)' : '0') . " as price,
    " . (in_array('UpdatedFileLink', $file_columns) ? 'f.UpdatedFileLink' : (in_array('UpdatedFilePath', $file_columns) ? 'f.UpdatedFilePath' : 'NULL')) . " as updated_file_link,
    " . (in_array('CreatedDate', $file_columns) ? 'f.CreatedDate' : (in_array('CreateDate', $file_columns) ? 'f.CreateDate' : 'GETDATE()')) . " as created_date
FROM [$database].[$file_table] f";
            
            // Join with users table if exists
            if (!empty($user_tables)) {
                $user_table = $user_tables[0];
                $user_id_field = in_array('UserId', $file_columns) ? 'UserId' : (in_array('UserID', $file_columns) ? 'UserID' : 'User_Id');
                $user_id_pk = in_array('Id', $user_columns) ? 'Id' : 'ID';
                
                $queries['files'] .= "
INNER JOIN [$database].[$user_table] u ON f.$user_id_field = u.$user_id_pk";
                
                if (in_array('DeletedDate', $user_columns)) {
                    $queries['files'] .= "
WHERE u.DeletedDate IS NULL OR u.DeletedDate = '1900-01-01'";
                }
            }
            
            $queries['files'] .= "
ORDER BY " . (in_array('CreatedDate', $file_columns) ? 'f.CreatedDate' : (in_array('CreateDate', $file_columns) ? 'f.CreateDate' : (in_array('Id', $file_columns) ? 'f.Id' : 'f.ID'))) . ";";
        }
        
        // Ticket tabloları kontrolü
        $stmt = $pdo->query("
            SELECT TABLE_NAME 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_TYPE = 'BASE TABLE' 
            AND TABLE_NAME LIKE '%Ticket%'
        ");
        $ticket_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($ticket_tables as $ticket_table) {
            if (stripos($ticket_table, 'admin') !== false) continue;
            if (stripos($ticket_table, 'user') !== false) continue;
            
            // Ana ticket tablosu
            $column_stmt = $pdo->prepare("
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = ?
            ");
            $column_stmt->execute([$ticket_table]);
            $ticket_columns = $column_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $queries['tickets'] = "-- SQL Server Tickets Export Query
SELECT 
    LOWER(NEWID()) as new_id,
    CAST(" . (in_array('Id', $ticket_columns) ? 't.Id' : 't.ID') . " as VARCHAR(50)) as legacy_ticket_id,
    " . (in_array('Title', $ticket_columns) ? 't.Title' : (in_array('Subject', $ticket_columns) ? 't.Subject' : '\'Ticket\'')) . " as title,
    CAST(" . (in_array('UserId', $ticket_columns) ? 't.UserId' : 't.UserID') . " as VARCHAR(50)) as user_id,
    CAST(" . (in_array('FileId', $ticket_columns) ? 't.FileId' : (in_array('FileID', $ticket_columns) ? 't.FileID' : 'NULL')) . " as VARCHAR(50)) as file_id,
    " . (in_array('Status', $ticket_columns) ? 't.Status' : '0') . " as status,
    " . (in_array('StatusText', $ticket_columns) ? 'ISNULL(t.StatusText, \'\')' : '\'\'') . " as status_text,
    " . (in_array('TicketCode', $ticket_columns) ? 't.TicketCode' : (in_array('Code', $ticket_columns) ? 't.Code' : '\'TKT-\' + CAST(t.Id as VARCHAR)')) . " as ticket_code,
    " . (in_array('CreatedDate', $ticket_columns) ? 't.CreatedDate' : 'GETDATE()') . " as created_date,
    " . (in_array('UpdatedDate', $ticket_columns) ? 't.UpdatedDate' : 't.CreatedDate') . " as updated_date
FROM [$database].[$ticket_table] t";
            
            // Join with users if needed
            if (!empty($user_tables)) {
                $user_table = $user_tables[0];
                $user_id_field = in_array('UserId', $ticket_columns) ? 'UserId' : 'UserID';
                $user_id_pk = in_array('Id', $user_columns) ? 'Id' : 'ID';
                
                $queries['tickets'] .= "
INNER JOIN [$database].[$user_table] u ON t.$user_id_field = u.$user_id_pk";
                
                if (in_array('DeletedDate', $user_columns)) {
                    $queries['tickets'] .= "
WHERE u.DeletedDate IS NULL OR u.DeletedDate = '1900-01-01'";
                }
            }
            
            $queries['tickets'] .= "
ORDER BY " . (in_array('CreatedDate', $ticket_columns) ? 't.CreatedDate' : (in_array('Id', $ticket_columns) ? 't.Id' : 't.ID')) . ";";
            
            break; // Sadece ilk ticket tablosunu al
        }
        
        // Wallet Log kontrolü
        $stmt = $pdo->query("
            SELECT TABLE_NAME 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_TYPE = 'BASE TABLE' 
            AND (TABLE_NAME LIKE '%Wallet%' OR TABLE_NAME LIKE '%wallet%')
        ");
        $wallet_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($wallet_tables)) {
            $wallet_table = $wallet_tables[0];
            
            $column_stmt = $pdo->prepare("
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = ?
            ");
            $column_stmt->execute([$wallet_table]);
            $wallet_columns = $column_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $queries['wallet'] = "-- SQL Server Wallet Log Export Query
SELECT 
    LOWER(NEWID()) as new_id,
    CAST(" . (in_array('UserId', $wallet_columns) ? 'wl.UserId' : 'wl.UserID') . " as VARCHAR(50)) as user_id,
    " . (in_array('Amount', $wallet_columns) ? 'ABS(wl.Amount)' : (in_array('NewWallet', $wallet_columns) && in_array('OldWallet', $wallet_columns) ? 'ABS(wl.NewWallet - wl.OldWallet)' : '0')) . " as amount,
    CASE 
        " . (in_array('Type', $wallet_columns) ? "WHEN UPPER(wl.Type) LIKE '%DEPOSIT%' THEN 'deposit' ELSE 'withdraw'" : (in_array('NewWallet', $wallet_columns) && in_array('OldWallet', $wallet_columns) ? "WHEN wl.NewWallet > wl.OldWallet THEN 'deposit' ELSE 'withdraw'" : "'deposit'")) . "
    END as type,
    " . (in_array('Comment', $wallet_columns) ? 'ISNULL(wl.Comment, \'Legacy wallet transaction\')' : (in_array('Description', $wallet_columns) ? 'ISNULL(wl.Description, \'Legacy wallet transaction\')' : '\'Legacy wallet transaction\'')) . " as description,
    " . (in_array('CreatedDate', $wallet_columns) ? 'wl.CreatedDate' : (in_array('CreateDate', $wallet_columns) ? 'wl.CreateDate' : 'GETDATE()')) . " as created_at
FROM [$database].[$wallet_table] wl";
            
            // Join with users if needed
            if (!empty($user_tables)) {
                $user_table = $user_tables[0];
                $user_id_field = in_array('UserId', $wallet_columns) ? 'UserId' : 'UserID';
                $user_id_pk = in_array('Id', $user_columns) ? 'Id' : 'ID';
                
                $queries['wallet'] .= "
INNER JOIN [$database].[$user_table] u ON wl.$user_id_field = u.$user_id_pk";
                
                if (in_array('DeletedDate', $user_columns)) {
                    $queries['wallet'] .= "
WHERE u.DeletedDate IS NULL OR u.DeletedDate = '1900-01-01'";
                }
            }
            
            $queries['wallet'] .= "
ORDER BY " . (in_array('Id', $wallet_columns) ? 'wl.Id' : (in_array('CreatedDate', $wallet_columns) ? 'wl.CreatedDate' : 'wl.UserID')) . ";";
        }
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
    
    return $queries;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MR.ECU Legacy Data Converter - Debug & Query Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            padding: 30px 0;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
            margin-bottom: 30px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #071e3d, #d32835);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 25px;
        }
        
        .sql-container {
            background: #2d3748;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            overflow-x: auto;
            position: relative;
        }
        
        .sql-container pre {
            margin: 0;
            color: #e2e8f0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.5;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #4a5568;
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            z-index: 10;
        }
        
        .copy-btn:hover {
            background: #2d3748;
        }
        
        .debug-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
        }
        
        .alert {
            border-radius: 10px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
        }
        
        .btn {
            border-radius: 8px;
        }
        
        .table-info {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .step-number {
            background: #007bff;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <!-- Ana Başlık -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0">
                            <i class="fas fa-bug"></i> 
                            MR.ECU Legacy Data Converter - Debug Sistemi
                        </h2>
                        <p class="mb-0 mt-2">SQL Server veritabanınızı analiz edin ve dinamik query'ler oluşturun</p>
                    </div>
                    
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> Önemli!</h5>
                            <p class="mb-0">Bu sistem, SQL Server veritabanınızdaki gerçek tablo ve sütun isimlerini tespit ederek
                            doğru SQL sorguları oluşturacaktır. Veritabanı bağlantı bilgilerinizi girin ve analiz başlatın.</p>
                        </div>
                        
                        <!-- SQL Server Bağlantı Testi -->
                        <div class="debug-section">
                            <h4><span class="step-number">1</span>SQL Server Bağlantı Bilgileri</h4>
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="action" value="test_connection">
                                
                                <div class="col-md-6">
                                    <label for="server" class="form-label">Server Adresi</label>
                                    <input type="text" class="form-control" id="server" name="server" 
                                           value="<?= $_POST['server'] ?? 'localhost' ?>" 
                                           placeholder="localhost, .\SQLEXPRESS, vb.">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="username" class="form-label">Kullanıcı Adı</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?= $_POST['username'] ?? '' ?>" 
                                           placeholder="Boş bırakın (Windows Auth)">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="password" class="form-label">Şifre</label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           value="<?= $_POST['password'] ?? '' ?>" 
                                           placeholder="Boş bırakın (Windows Auth)">
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plug"></i> Bağlantıyı Test Et
                                    </button>
                                </div>
                            </form>
                            
                            <?php if (isset($debug_results['connection'])): ?>
                                <?php if ($debug_results['connection']['success']): ?>
                                    <div class="alert alert-success mt-3">
                                        <i class="fas fa-check-circle"></i> <?= $debug_results['connection']['message'] ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-danger mt-3">
                                        <i class="fas fa-times-circle"></i> Bağlantı Hatası: <?= $debug_results['connection']['message'] ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Veritabanları Listesi -->
                        <?php if (isset($debug_results['connection']) && $debug_results['connection']['success']): ?>
                        <div class="debug-section">
                            <h4><span class="step-number">2</span>Veritabanları Listesi</h4>
                            <form method="POST">
                                <input type="hidden" name="action" value="list_databases">
                                <input type="hidden" name="server" value="<?= $_POST['server'] ?? '' ?>">
                                <input type="hidden" name="username" value="<?= $_POST['username'] ?? '' ?>">
                                <input type="hidden" name="password" value="<?= $_POST['password'] ?? '' ?>">
                                
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-database"></i> Veritabanlarını Listele
                                </button>
                            </form>
                            
                            <?php if (isset($debug_results['databases'])): ?>
                                <?php if ($debug_results['databases']['success']): ?>
                                    <div class="mt-3">
                                        <h5>Bulunan Veritabanları:</h5>
                                        <div class="row">
                                            <?php foreach ($debug_results['databases']['data'] as $db): ?>
                                                <div class="col-md-4 mb-2">
                                                    <div class="alert alert-info p-2">
                                                        <i class="fas fa-database"></i> <?= $db ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-danger mt-3">
                                        <i class="fas fa-times-circle"></i> <?= $debug_results['databases']['message'] ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Veritabanı Analizi -->
                        <?php if (isset($debug_results['databases']) && $debug_results['databases']['success']): ?>
                        <div class="debug-section">
                            <h4><span class="step-number">3</span>Veritabanı Analizi</h4>
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="action" value="analyze_database">
                                <input type="hidden" name="server" value="<?= $_POST['server'] ?? '' ?>">
                                <input type="hidden" name="username" value="<?= $_POST['username'] ?? '' ?>">
                                <input type="hidden" name="password" value="<?= $_POST['password'] ?? '' ?>">
                                
                                <div class="col-md-8">
                                    <label for="database" class="form-label">Analiz Edilecek Veritabanı</label>
                                    <select class="form-select" id="database" name="database" required>
                                        <option value="">Veritabanı seçin...</option>
                                        <?php foreach ($debug_results['databases']['data'] as $db): ?>
                                            <option value="<?= $db ?>" <?= ($_POST['database'] ?? '') == $db ? 'selected' : '' ?>>
                                                <?= $db ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-success d-block w-100">
                                        <i class="fas fa-search"></i> Veritabanını Analiz Et
                                    </button>
                                </div>
                            </form>
                            
                            <?php if (isset($debug_results['analysis'])): ?>
                                <?php if ($debug_results['analysis']['success']): ?>
                                    <div class="mt-4">
                                        <h5>Tablo Analizi Sonuçları:</h5>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Tablo Adı</th>
                                                        <th>Sütun Sayısı</th>
                                                        <th>Satır Sayısı</th>
                                                        <th>Örnek Veriler</th>
                                                        <th>Sütunlar</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($debug_results['analysis']['tables'] as $table): ?>
                                                        <tr>
                                                            <td><strong><?= $table['TABLE_NAME'] ?></strong></td>
                                                            <td><span class="badge bg-primary"><?= $table['column_count'] ?></span></td>
                                                            <td><span class="badge bg-info"><?= $debug_results['analysis']['details'][$table['TABLE_NAME']]['row_count'] ?></span></td>
                                                            <td>
                                                                <?php $sample = $debug_results['analysis']['details'][$table['TABLE_NAME']]['sample_data']; ?>
                                                                <?php if (!empty($sample)): ?>
                                                                    <button class="btn btn-sm btn-outline-info" type="button" 
                                                                            data-bs-toggle="collapse" 
                                                                            data-bs-target="#sample-<?= $table['TABLE_NAME'] ?>" 
                                                                            aria-expanded="false">
                                                                        <i class="fas fa-eye"></i> Görüntüle
                                                                    </button>
                                                                    <div class="collapse mt-2" id="sample-<?= $table['TABLE_NAME'] ?>">
                                                                        <div class="card card-body">
                                                                            <pre style="font-size: 11px; max-height: 200px; overflow-y: auto;"><?= htmlspecialchars(json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                                                        </div>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <span class="text-muted">Veri yok</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-secondary" type="button" 
                                                                        data-bs-toggle="collapse" 
                                                                        data-bs-target="#columns-<?= $table['TABLE_NAME'] ?>" 
                                                                        aria-expanded="false">
                                                                    <i class="fas fa-list"></i> Sütunlar
                                                                </button>
                                                                <div class="collapse mt-2" id="columns-<?= $table['TABLE_NAME'] ?>">
                                                                    <div class="card card-body">
                                                                        <?php foreach ($debug_results['analysis']['details'][$table['TABLE_NAME']]['columns'] as $col): ?>
                                                                            <small>
                                                                                <strong><?= $col['COLUMN_NAME'] ?></strong> - 
                                                                                <?= $col['DATA_TYPE'] ?>
                                                                                <?= $col['CHARACTER_MAXIMUM_LENGTH'] ? '(' . $col['CHARACTER_MAXIMUM_LENGTH'] . ')' : '' ?>
                                                                                <?= $col['IS_NULLABLE'] == 'YES' ? ' (NULL)' : ' (NOT NULL)' ?>
                                                                            </small><br>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-danger mt-3">
                                        <i class="fas fa-times-circle"></i> <?= $debug_results['analysis']['message'] ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Dinamik Query Oluşturma -->
                        <?php if (isset($debug_results['analysis']) && $debug_results['analysis']['success']): ?>
                        <div class="debug-section">
                            <h4><span class="step-number">4</span>Dinamik SQL Sorguları Oluştur</h4>
                            <form method="POST">
                                <input type="hidden" name="action" value="generate_queries">
                                <input type="hidden" name="server" value="<?= $_POST['server'] ?? '' ?>">
                                <input type="hidden" name="database" value="<?= $_POST['database'] ?? '' ?>">
                                <input type="hidden" name="username" value="<?= $_POST['username'] ?? '' ?>">
                                <input type="hidden" name="password" value="<?= $_POST['password'] ?? '' ?>">
                                
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-code"></i> SQL Sorguları Oluştur
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Oluşturulan SQL Sorguları -->
                        <?php if (!empty($sql_queries)): ?>
                            <?php if (isset($sql_queries['error'])): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-times-circle"></i> Hata: <?= $sql_queries['error'] ?>
                                </div>
                            <?php else: ?>
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h5><i class="fas fa-check-circle"></i> Dinamik SQL Sorguları Oluşturuldu!</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-info-circle"></i> Kullanım Talimatları:</h6>
                                            <ol class="mb-0">
                                                <li>Aşağıdaki SQL sorgularını SQL Server Management Studio'da çalıştırın</li>
                                                <li>Her sorgunun sonucunu CSV formatında export edin</li>
                                                <li>CSV dosyalarını <a href="legacy-migration-interface.php">Migration Interface</a> ile yükleyin</li>
                                            </ol>
                                        </div>
                                        
                                        <?php foreach ($sql_queries as $type => $query): ?>
                                            <div class="card mb-3">
                                                <div class="card-header bg-primary text-white">
                                                    <h6 class="mb-0">
                                                        <?php
                                                        $icons = [
                                                            'users' => 'fa-users',
                                                            'files' => 'fa-file',
                                                            'tickets' => 'fa-ticket-alt',
                                                            'wallet' => 'fa-wallet'
                                                        ];
                                                        $titles = [
                                                            'users' => 'Users (Kullanıcılar)',
                                                            'files' => 'Files (Dosyalar)',
                                                            'tickets' => 'Tickets (Destek Talepleri)',
                                                            'wallet' => 'Wallet Log (Cüzdan İşlemleri)'
                                                        ];
                                                        ?>
                                                        <i class="fas <?= $icons[$type] ?? 'fa-code' ?>"></i>
                                                        <?= $titles[$type] ?? ucfirst($type) ?>
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="sql-container">
                                                        <button class="copy-btn" onclick="copyToClipboard('query-<?= $type ?>')">
                                                            <i class="fas fa-copy"></i> Kopyala
                                                        </button>
                                                        <pre id="query-<?= $type ?>"><code><?= htmlspecialchars($query) ?></code></pre>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <div class="mt-4">
                                            <a href="legacy-migration-interface.php" class="btn btn-primary me-2">
                                                <i class="fas fa-upload"></i> Migration Interface'e Git
                                            </a>
                                            <a href="migration-dashboard.php" class="btn btn-success">
                                                <i class="fas fa-tachometer-alt"></i> Migration Dashboard
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Sonraki Adımlar -->
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5><i class="fas fa-arrow-right"></i> Sonraki Adımlar</h5>
                            </div>
                            <div class="card-body">
                                <ol>
                                    <li><strong>SQL Server Analizi:</strong> Yukarıdaki adımları sırayla tamamlayın</li>
                                    <li><strong>Query Çalıştırma:</strong> Oluşturulan SQL sorgularını SQL Server'da çalıştırın</li>
                                    <li><strong>CSV Export:</strong> Sonuçları CSV formatında export edin</li>
                                    <li><strong>Migration:</strong> CSV dosyalarını Migration Interface ile yükleyin</li>
                                    <li><strong>Test:</strong> Migration sonrası sistemi test edin</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(function() {
                const button = element.parentElement.querySelector('.copy-btn');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Kopyalandı!';
                button.style.background = '#28a745';
                
                setTimeout(function() {
                    button.innerHTML = originalText;
                    button.style.background = '#4a5568';
                }, 2000);
            }).catch(function(err) {
                console.error('Kopyalama hatası: ', err);
                alert('Kopyalama başarısız! Metni manuel olarak kopyalayın.');
            });
        }
        
        // Auto-scroll to results
        <?php if (!empty($debug_results) || !empty($sql_queries)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const lastResult = document.querySelector('.debug-section:last-of-type, .card:last-of-type');
            if (lastResult) {
                lastResult.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>