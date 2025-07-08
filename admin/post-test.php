<?php
// POST Test Sayfası
echo "<!DOCTYPE html><html><head><title>POST Test</title></head><body>";
echo "<h2>POST Test Sayfası</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>✅ POST İşlemi Başarılı!</h3>";
    echo "<h4>POST Verisi:</h4>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h4>Tüm Request Bilgileri:</h4>";
    echo "<pre>";
    echo "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
    echo "Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n";
    echo "Content-Length: " . ($_SERVER['CONTENT_LENGTH'] ?? 'not set') . "\n";
    echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
    echo "</pre>";
    
} else {
    echo "<p>Henüz POST verisi gönderilmedi.</p>";
}

echo "<h3>Test Formu:</h3>";
echo '<form method="POST" action="post-test.php">
    <input type="hidden" name="hidden_field" value="hidden_value">
    <label for="text_field">Metin:</label>
    <input type="text" name="text_field" id="text_field" value="test123" required><br><br>
    
    <label for="number_field">Sayı:</label>
    <input type="number" name="number_field" id="number_field" value="100" step="0.01" required><br><br>
    
    <label for="textarea_field">Açıklama:</label><br>
    <textarea name="textarea_field" id="textarea_field" required>Test açıklaması</textarea><br><br>
    
    <button type="submit">Test Gönder</button>
</form>';

echo "<h3>Ajax Test:</h3>";
echo '<button onclick="ajaxTest()">Ajax POST Test</button>';
echo '<div id="ajaxResult"></div>';

echo '<script>
function ajaxTest() {
    const formData = new FormData();
    formData.append("ajax_field", "ajax_value");
    formData.append("test_number", "456");
    
    fetch("post-test.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById("ajaxResult").innerHTML = "<h4>Ajax Sonucu:</h4><pre>" + data + "</pre>";
    })
    .catch(error => {
        document.getElementById("ajaxResult").innerHTML = "<h4>Ajax Hatası:</h4>" + error;
    });
}
</script>';

echo "</body></html>";
?>
