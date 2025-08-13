<?php
/**
 * Additional Files System Test Script
 * Ek dosya sistemi test scripti
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Additional Files System Test</h1>";
echo "<hr>";

// 1. Tablo kontrolü
echo "<h2>1. Veritabanı Tablosu Kontrolü</h2>";
try {
    $stmt = $pdo->query("DESCRIBE additional_files");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p style='color:green'>✓ additional_files tablosu mevcut</p>";
    echo "<details><summary>Tablo yapısı</summary><pre>";
    print_r($columns);
    echo "</pre></details>";
} catch(Exception $e) {
    echo "<p style='color:red'>✗ Tablo bulunamadı: " . $e->getMessage() . "</p>";
}

// 2. Upload dizini kontrolü
echo "<h2>2. Upload Dizini Kontrolü</h2>";
$uploadDir = __DIR__ . '/uploads/additional_files/';
if (is_dir($uploadDir)) {
    echo "<p style='color:green'>✓ Upload dizini mevcut: " . $uploadDir . "</p>";
    if (is_writable($uploadDir)) {
        echo "<p style='color:green'>✓ Upload dizini yazılabilir</p>";
    } else {
        echo "<p style='color:red'>✗ Upload dizini yazılamaz! chmod 755 yapın</p>";
    }
} else {
    echo "<p style='color:orange'>! Upload dizini yok, oluşturuluyor...</p>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "<p style='color:green'>✓ Upload dizini oluşturuldu</p>";
    } else {
        echo "<p style='color:red'>✗ Upload dizini oluşturulamadı</p>";
    }
}

// 3. FileManager.php fonksiyon kontrolü
echo "<h2>3. FileManager.php Fonksiyon Kontrolü</h2>";
require_once 'includes/FileManager.php';
$fileManager = new FileManager($pdo);

$requiredMethods = [
    'uploadAdditionalFile',
    'getAdditionalFiles',
    'downloadAdditionalFile',
    'getUnreadAdditionalFilesCount'
];

foreach ($requiredMethods as $method) {
    if (method_exists($fileManager, $method)) {
        echo "<p style='color:green'>✓ $method() fonksiyonu mevcut</p>";
    } else {
        echo "<p style='color:red'>✗ $method() fonksiyonu eksik!</p>";
    }
}

// 4. AJAX handler kontrolü
echo "<h2>4. AJAX Handler Kontrolü</h2>";
$ajaxFile = __DIR__ . '/ajax/additional_files.php';
if (file_exists($ajaxFile)) {
    echo "<p style='color:green'>✓ ajax/additional_files.php mevcut</p>";
    $content = file_get_contents($ajaxFile);
    if (strpos($content, 'upload_additional_file') !== false) {
        echo "<p style='color:green'>✓ upload_additional_file action mevcut</p>";
    } else {
        echo "<p style='color:red'>✗ upload_additional_file action eksik!</p>";
    }
} else {
    echo "<p style='color:red'>✗ ajax/additional_files.php dosyası yok!</p>";
}

// 5. Test dosya yükleme (sadece admin olarak)
echo "<h2>5. Test Dosya İşlemleri</h2>";

// Admin kullanıcısını bul
try {
    $stmt = $pdo->query("SELECT id, username FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $stmt->fetch();
    
    $stmt = $pdo->query("SELECT id, username FROM users WHERE role = 'user' LIMIT 1");
    $user = $stmt->fetch();
    
    $stmt = $pdo->query("SELECT id, original_name FROM file_uploads LIMIT 1");
    $testFile = $stmt->fetch();
    
    if ($admin && $user && $testFile) {
        echo "<p style='color:green'>✓ Test için admin, user ve dosya bulundu</p>";
        echo "<ul>";
        echo "<li>Admin: " . $admin['username'] . " (ID: " . $admin['id'] . ")</li>";
        echo "<li>User: " . $user['username'] . " (ID: " . $user['id'] . ")</li>";
        echo "<li>Test Dosya: " . $testFile['original_name'] . " (ID: " . $testFile['id'] . ")</li>";
        echo "</ul>";
        
        // Bu dosya için ek dosyaları kontrol et
        $additionalFiles = $fileManager->getAdditionalFiles($testFile['id'], $admin['id'], 'admin');
        echo "<p>Bu dosya için " . count($additionalFiles) . " ek dosya bulundu.</p>";
        
        if (count($additionalFiles) > 0) {
            echo "<details><summary>Ek dosyalar</summary><pre>";
            foreach ($additionalFiles as $file) {
                echo "- " . $file['original_name'] . " (Gönderen: " . $file['sender_type'] . ", Alıcı: " . $file['receiver_type'] . ")\n";
            }
            echo "</pre></details>";
        }
        
    } else {
        echo "<p style='color:orange'>! Test için gerekli veriler eksik</p>";
    }
} catch(Exception $e) {
    echo "<p style='color:red'>✗ Test hatası: " . $e->getMessage() . "</p>";
}

// 6. Form test
echo "<h2>6. Manuel Test Formu</h2>";
if (isset($admin) && isset($user) && isset($testFile)) {
    ?>
    <div style="border: 1px solid #ccc; padding: 20px; margin: 20px 0;">
        <h3>Admin'den User'a Ek Dosya Gönder</h3>
        <form id="testForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_additional_file">
            <input type="hidden" name="related_file_id" value="<?php echo $testFile['id']; ?>">
            <input type="hidden" name="related_file_type" value="upload">
            <input type="hidden" name="sender_id" value="<?php echo $admin['id']; ?>">
            <input type="hidden" name="sender_type" value="admin">
            <input type="hidden" name="receiver_id" value="<?php echo $user['id']; ?>">
            <input type="hidden" name="receiver_type" value="user">
            
            <p>
                <label>Dosya: <input type="file" name="additional_file" required></label>
            </p>
            <p>
                <label>Notlar: <br>
                <textarea name="notes" rows="3" cols="50">Test ek dosya</textarea>
                </label>
            </p>
            <p>
                <label>Ücret (Kredi): <input type="number" name="credits" value="5" min="0" step="0.01"></label>
            </p>
            <p>
                <button type="submit">Test Gönder</button>
            </p>
        </form>
        <div id="result"></div>
    </div>
    
    <script>
    document.getElementById('testForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const resultDiv = document.getElementById('result');
        
        // Debug: Form data'yı göster
        console.log('Form Data:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
        
        resultDiv.innerHTML = '<p>Gönderiliyor...</p>';
        
        fetch('ajax/additional_files.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    resultDiv.innerHTML = '<p style="color:green">✓ ' + data.message + '</p>';
                } else {
                    resultDiv.innerHTML = '<p style="color:red">✗ ' + data.message + '</p>';
                }
            } catch(e) {
                resultDiv.innerHTML = '<p style="color:red">✗ JSON parse hatası: ' + text + '</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultDiv.innerHTML = '<p style="color:red">✗ İstek hatası: ' + error + '</p>';
        });
    });
    </script>
    <?php
}

// 7. Session kontrolü
echo "<h2>7. Session Kontrolü</h2>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "<p style='color:green'>✓ Session aktif - User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Role: " . ($_SESSION['role'] ?? 'Belirtilmemiş') . "</p>";
} else {
    echo "<p style='color:orange'>! Session yok - Test için giriş yapmanız gerekebilir</p>";
}

echo "<hr>";
echo "<p><strong>Test tamamlandı.</strong> Yukarıdaki sonuçları kontrol edin.</p>";
?>