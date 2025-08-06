<?php
/**
 * Mr ECU - ECU ve Device Tabloları Kurulum Scripti
 * Database migration for ECU and Device tables
 */

require_once 'config/config.php';

function createEcuAndDeviceTables($pdo) {
    try {
        // ECU tablosu oluştur
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS ecus (
            id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
            name VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_ecu_name (name),
            INDEX idx_ecu_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        echo "ECU tablosu oluşturuldu.\n";

        // Device tablosu oluştur
        $pdo->exec("
        CREATE TABLE IF NOT EXISTS devices (
            id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
            name VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_device_name (name),
            INDEX idx_device_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        echo "Device tablosu oluşturuldu.\n";

        // ECU'ları ekle
        $ecuList = [
            'CRD11', 'CRD2X', 'CRD3X', 'DCM1.2', 'DCM3.4', 'DCM3.5', 'DCM3.7', 'DCM6.2', 'DCM6.2A',
            'DCM7.1', 'DCM7.1A', 'DCM7.1B', 'DCU102', 'DCU17CP43', 'DDCR', 'DECM1.2', 'DELCO E78',
            'DELCO E83', 'DELCO E98', 'DELCOX', 'DELPHI', 'DELPHİ-CDID', 'DELPHİ-CRD2X', 'DELPHİ-CRD3X',
            'DELPHİ-DCM1.2', 'DELPHİ-DCM3.1', 'DELPHİ-DCM3.2', 'DELPHİ-DCM3.3', 'DELPHİ-DCM3.4',
            'DELPHİ-DCM3.5', 'DELPHİ-DCM3.7', 'DELPHİ-DCM6.1', 'DELPHİ-DCM6.2', 'DELPHİ-DCM7.1',
            'DELPHİ-DDCR', 'DELPHİ-DTI17', 'DELPHİ-MTXX', 'DENSO', 'DQ200', 'DQ250', 'EDC15', 'EDC15C0',
            'EDC15C11', 'EDC15C12', 'EDC15C2', 'EDC15C3', 'EDC15C4', 'EDC15C5', 'EDC15C6', 'EDC15C7',
            'EDC15C8', 'EDC15C9', 'EDC15M', 'EDC15P', 'EDC15P+', 'EDC15V', 'EDC15VM', 'EDC15VP', 'EDC15VP+',
            'EDC16', 'EDC16+', 'EDC161', 'EDC16C1', 'EDC16C10', 'EDC16C2', 'EDC16C3', 'EDC16C31', 'EDC16C32',
            'EDC16C33', 'EDC16C34', 'EDC16C35', 'EDC16C36', 'EDC16C37', 'EDC16C39', 'EDC16C4', 'EDC16C41',
            'EDC16C42', 'EDC16C7', 'EDC16C9', 'EDC16CP3', 'EDC16CP31', 'EDC16CP32', 'EDC16CP33', 'EDC16CP34',
            'EDC16CP35', 'EDC16CP36', 'EDC16CP39', 'EDC16CP42', 'EDC16U', 'EDC16U1', 'EDC16U31', 'EDC16U34',
            'EDC16UC40', 'EDC17', 'EDC1701', 'EDC1706', 'EDC1708', 'EDC1709', 'EDC1710', 'EDC1711',
            'EDC17C01', 'EDC17C011', 'EDC17C08', 'EDC17C10', 'EDC17C18', 'EDC17C19', 'EDC17C41', 'EDC17C42',
            'EDC17C43', 'EDC17C45', 'EDC17C46', 'EDC17C47', 'EDC17C49', 'EDC17C50', 'EDC17C53', 'EDC17C54',
            'EDC17C55', 'EDC17C56', 'EDC17C57', 'EDC17C58', 'EDC17C59', 'EDC17C60', 'EDC17C63', 'EDC17C64',
            'EDC17C66', 'EDC17C69', 'EDC17C70', 'EDC17C73', 'EDC17C74', 'EDC17C76', 'EDC17C79', 'EDC17C83',
            'EDC17C84', 'EDC17CP01', 'EDC17CP02', 'EDC17CP04', 'EDC17CP05', 'EDC17CP06', 'EDC17CP07',
            'EDC17CP09', 'EDC17CP10', 'EDC17CP11', 'EDC17CP14', 'EDC17CP15', 'EDC17CP16', 'EDC17CP17',
            'EDC17CP18', 'EDC17CP19', 'EDC17CP20', 'EDC17CP21', 'EDC17CP22', 'EDC17CP24', 'EDC17CP27',
            'EDC17CP37', 'EDC17CP42', 'EDC17CP44', 'EDC17CP45', 'EDC17CP46', 'EDC17CP47', 'EDC17CP48',
            'EDC17CP49', 'EDC17CP52', 'EDC17CP54', 'EDC17CP56', 'EDC17CP58', 'EDC17CP65', 'EDC17CP66',
            'EDC17CP68', 'EDC17CP74', 'EDC17CV41', 'EDC17CV42', 'EDC17CV44', 'EDC17CV52', 'EDC17CV54',
            'EDC17CV56', 'EDC17U1', 'EDC17UC31', 'EDC7', 'EDC7C1', 'EDC7C2', 'EDC7C3', 'EDC7C32', 'EDC7C4',
            'EDC7U1', 'EDC7U31', 'EDC7UC31', 'EMES2', 'EMS', 'EMS2.1', 'EMS2.2', 'EMS2.3', 'EMS2204',
            'EMS22XX', 'EMS3141', 'EMS3151', 'EMS3161', 'KEFİCO', 'M(E)1-5', 'MD1CE101', 'MD1CP001',
            'MD1CP004', 'MD1CS001', 'MD1CS003', 'MD1CS004', 'MD1CS005', 'MD1CS006', 'MD1CS012', 'MD1CS016',
            'MD1CS069', 'MD1P001', 'ME(D)7', 'ME17.9.20', 'ME7.5', 'ME7.X', 'ME9', 'ME9.1', 'ME9.2',
            'ME9.5', 'ME9.5.10', 'ME9.6', 'MED', 'MED17.2.3', 'MED17.2.9', 'MED17.5.20', 'MED17.5.21',
            'MED17.5.25', 'MED17.5.5', 'MED17.5.X', 'MED17.7.2', 'MED17.9.1', 'MED17.9.2', 'MED17.9.8',
            'MED9.1.5', 'MED9.5.10', 'MEV', 'MEVD17.5.X', 'MEVD17.9.2', 'MEVD19.2', 'MG1CS011', 'MG1CS016',
            'MJ8DF', 'MJ8GF', 'MJ9DF', 'MJ9GF', 'MJD6', 'MS41', 'MS43', 'MS6.3', 'MS6.4', 'MSD80', 'MSD81',
            'MSE6', 'MSE6.3', 'MSE8.0', 'PCR2.1', 'PHOENIX', 'PPD1.2', 'PPD1.5', 'SİD202', 'SİD206',
            'SİD208', 'SİD208PSA', 'SİD209', 'SİD211', 'SİD301', 'SİD305', 'SİD306', 'SİD307', 'SİD309',
            'SİD310', 'SİD321', 'SİD803A', 'SİD804', 'SİD807EVO', 'SİM271', 'SİM271DE', 'SİM271KE',
            'SİM28', 'SİM29', 'SİM2K-140', 'SİM2K-240', 'SİM2K-341', 'SİM32', 'SİM42', 'SİM4LE',
            'SİM4LKE', 'SİMOS10', 'SİMOS11', 'SİMOS12', 'SİMOS15', 'SİMOS18', 'SİMOS19', 'SİMOS6',
            'SİMOS7', 'SİMOS8', 'SİMOS9', 'SİMTECH', 'SİMTECH75', 'SİMTECH76', 'TEMİC', 'TRANSTRON',
            'TRW', 'V40', 'V46', 'V56', 'VALEO'
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO ecus (id, name) VALUES (UUID(), ?)");
        foreach ($ecuList as $ecu) {
            $stmt->execute([$ecu]);
        }
        echo "ECU verileri eklendi (" . count($ecuList) . " adet).\n";

        // Device'ları ekle
        $deviceList = [
            'Auto tuner Obd', 'Autotuner Bench', 'Autotuner Boot', 'Cmd Flash', 'Flex Bench',
            'Flex Boot', 'Flex Obd', 'Galletto Bench', 'Galletto Boot', 'Galletto Obd',
            'IO Terminal', 'Kessv2', 'Kessv3', 'KT200 BENCH', 'KT200-OBD', 'Ktag Bench',
            'Ktag Boot', 'Newgenius', 'Trastada Bench', 'Trastada Boot', 'X17 Boot', 'X17 Obd'
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO devices (id, name) VALUES (UUID(), ?)");
        foreach ($deviceList as $device) {
            $stmt->execute([$device]);
        }
        echo "Device verileri eklendi (" . count($deviceList) . " adet).\n";

        return true;

    } catch (Exception $e) {
        echo "Hata: " . $e->getMessage() . "\n";
        return false;
    }
}

// Web tarayıcıdan çalıştırılıyorsa
if (isset($_SERVER['HTTP_HOST'])) {
    echo "<h2>Mr ECU - ECU ve Device Tabloları Kurulumu</h2>";
    echo "<pre>";
    
    if (createEcuAndDeviceTables($pdo)) {
        echo "✅ Kurulum başarıyla tamamlandı!";
    } else {
        echo "❌ Kurulum sırasında hata oluştu!";
    }
    
    echo "</pre>";
    echo "<p><a href='index.php'>Ana Sayfaya Dön</a></p>";
} else {
    // Komut satırından çalıştırılıyorsa
    if (createEcuAndDeviceTables($pdo)) {
        echo "✅ Kurulum başarıyla tamamlandı!\n";
    } else {
        echo "❌ Kurulum sırasında hata oluştu!\n";
        exit(1);
    }
}
?>
