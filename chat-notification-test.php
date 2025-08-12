<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Bildirim Test</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>
                            <i class="fas fa-comments me-2"></i>
                            Chat Bildirim Test 
                            <span class="chat-notification-badge badge bg-danger" style="display: none;">0</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <p>Bu sayfa chat bildirim sistemini test etmek için oluşturulmuştur.</p>
                        
                        <div class="alert alert-info">
                            <strong>Test Durumu:</strong>
                            <ul class="mb-0">
                                <li>✅ ChatManager.php - Bildirim sistemi entegrasyonu tamamlandı</li>
                                <li>✅ NotificationManager.php - Chat mesajları için bildirim tipi eklendi</li>
                                <li>✅ get-chat-notifications.php - AJAX endpoint oluşturuldu</li>
                                <li>✅ admin_header.php - Chat bildirim JavaScript'i eklendi</li>
                                <li>✅ user_header.php - Chat bildirim JavaScript'i eklendi</li>
                                <li>✅ admin_sidebar.php - Toplam bildirim sayısı (chat + diğer) eklendi</li>
                                <li>🔄 HTML badge'leri - Test ediliyor</li>
                            </ul>
                        </div>
                        
                        <div class="mt-4">
                            <button id="testChatNotification" class="btn btn-primary">
                                <i class="fas fa-bell me-2"></i>
                                Chat Bildirimi Test Et
                            </button>
                            
                            <button id="checkChatCount" class="btn btn-info ms-2">
                                <i class="fas fa-refresh me-2"></i>
                                Bildirim Sayısını Kontrol Et
                            </button>
                        </div>
                        
                        <div id="testResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6>Sistem Bilgileri</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Proje:</strong> Mr ECU</p>
                        <p><strong>Özellik:</strong> Chat Bildirim Sistemi</p>
                        <p><strong>Tarih:</strong> <?php echo date('d.m.Y H:i'); ?></p>
                        <p><strong>Versiyon:</strong> 1.0.0</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Chat bildirim test fonksiyonları
        function showResult(message, isSuccess = true) {
            const resultDiv = document.getElementById('testResult');
            resultDiv.innerHTML = `
                <div class="alert alert-${isSuccess ? 'success' : 'danger'}">
                    <i class="fas fa-${isSuccess ? 'check' : 'times'} me-2"></i>
                    ${message}
                </div>
            `;
        }
        
        // Chat bildirim sayısını kontrol et
        function checkChatNotifications() {
            fetch('ajax/get-chat-notifications.php?action=count')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const count = data.count || 0;
                    const badge = document.querySelector('.chat-notification-badge');
                    
                    if (count > 0) {
                        badge.textContent = count;
                        badge.style.display = 'inline';
                        showResult(`Chat bildirim sayısı: ${count}`, true);
                    } else {
                        badge.style.display = 'none';
                        showResult('Chat bildirimi yok.', true);
                    }
                } else {
                    showResult('Chat bildirim kontrol hatası: ' + data.message, false);
                }
            })
            .catch(error => {
                console.error('Chat bildirim kontrol hatası:', error);
                showResult('Chat bildirim sistemi henüz aktif değil veya bir hata oluştu.', false);
            });
        }
        
        // Test butonları
        document.getElementById('testChatNotification').addEventListener('click', function() {
            showResult('Chat bildirim testi başlatıldı...', true);
            setTimeout(checkChatNotifications, 1000);
        });
        
        document.getElementById('checkChatCount').addEventListener('click', checkChatNotifications);
        
        // Sayfa yüklendiğinde otomatik kontrol
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Chat bildirim test sayfası yüklendi');
            setTimeout(checkChatNotifications, 2000);
        });
        
        // Otomatik güncelleme (her 10 saniyede bir)
        setInterval(checkChatNotifications, 10000);
    </script>
</body>
</html>
