<?php
/**
 * WhatsApp ve Scroll to Top Test Sayfası
 */

$pageTitle = 'Floating Buttons Test';
$pageDescription = 'WhatsApp ve Scroll to Top butonları test sayfası';
$pageKeywords = 'whatsapp, scroll to top, floating buttons, test';

include_once 'includes/header.php';
?>

<!-- Test Content -->
<div class="container mt-5 pt-5">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-test-tube me-2"></i>
                        Floating Buttons Test Sayfası
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="bi bi-info-circle me-2"></i>Test Edilecek Özellikler:</h5>
                        <ul class="mb-0">
                            <li><strong>WhatsApp Floating Button:</strong> Sayfanın sağ alt köşesinde yeşil WhatsApp butonu görünmeli</li>
                            <li><strong>Scroll to Top Button:</strong> Sayfa aşağı kaydırıldığında sol alt köşede kırmızı yukarı ok butonu görünmeli</li>
                            <li><strong>FontAwesome Icons:</strong> Tüm ikonlar düzgün görünmeli</li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h4><i class="bi bi-whatsapp text-success me-2"></i>WhatsApp Button</h4>
                            <p>Sağ alt köşede bulunan yeşil WhatsApp butonuna tıklayarak test edin.</p>
                            <ul>
                                <li>Button hover efekti çalışıyor mu?</li>
                                <li>WhatsApp'a doğru yönlendiriyor mu?</li>
                                <li>Tooltip gösterimi çalışıyor mu?</li>
                                <li>Mobile responsive çalışıyor mu?</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h4><i class="bi bi-arrow-up text-danger me-2"></i>Scroll to Top Button</h4>
                            <p>Sayfayı aşağı kaydırın ve sol alt köşede beliren butonu test edin.</p>
                            <ul>
                                <li>300px kaydırma sonrası görünüyor mu?</li>
                                <li>Smooth scroll çalışıyor mu?</li>
                                <li>Button hover efekti çalışıyor mu?</li>
                                <li>Keyboard kısayolu (Alt+T) çalışıyor mu?</li>
                            </ul>
                        </div>
                    </div>

                    <hr>

                    <!-- FontAwesome Test Section -->
                    <h4><i class="bi bi-icons me-2"></i>FontAwesome İkon Testi</h4>
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            <div class="p-3 border rounded">
                                <i class="bi bi-home fa-2x text-primary mb-2"></i>
                                <br><small>bi bi-home</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="p-3 border rounded">
                                <i class="bi bi-user fa-2x text-success mb-2"></i>
                                <br><small>bi bi-user</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="p-3 border rounded">
                                <i class="bi bi-whatsapp fa-2x text-success mb-2"></i>
                                <br><small>bi bi-whatsapp</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="p-3 border rounded">
                                <i class="bi bi-arrow-up fa-2x text-danger mb-2"></i>
                                <br><small>bi bi-arrow-up</small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Keyboard Shortcuts -->
                    <h4><i class="bi bi-keyboard me-2"></i>Klavye Kısayolları</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Kısayol</th>
                                    <th>Fonksiyon</th>
                                    <th>Test</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><kbd>Alt + W</kbd></td>
                                    <td>WhatsApp butonunu tıkla</td>
                                    <td><span class="badge bg-secondary" id="whatsappTest">Test Et</span></td>
                                </tr>
                                <tr>
                                    <td><kbd>Alt + T</kbd></td>
                                    <td>Sayfanın başına git</td>
                                    <td><span class="badge bg-secondary" id="scrollTest">Test Et</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Uzun içerik alanı (scroll test için) -->
    <?php for($i = 1; $i <= 10; $i++): ?>
    <div class="card mt-4">
        <div class="card-body">
            <h5>Test İçerik Alanı <?php echo $i; ?></h5>
            <p>Bu alan scroll to top butonunu test etmek için oluşturulmuştur. Sayfayı aşağı doğru kaydırın ve sol alt köşede beliren kırmızı butonu test edin.</p>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
        </div>
    </div>
    <?php endfor; ?>

    <!-- Son test alanı -->
    <div class="card mt-4 mb-5">
        <div class="card-body text-center">
            <h3><i class="bi bi-flag-checkered text-success me-2"></i>Test Tamamlandı!</h3>
            <p>Eğer bu alanı görüyorsanız ve butonlar çalışıyorsa, entegrasyon başarılıdır.</p>
            <button class="btn btn-success me-2" onclick="scrollToTop()">
                <i class="bi bi-arrow-up me-1"></i>Sayfa Başına Dön
            </button>
            <a href="https://wa.me/905551234567" target="_blank" class="btn btn-success">
                <i class="bi bi-whatsapp me-1"></i>WhatsApp Test
            </a>
        </div>
    </div>
</div>

<!-- Test Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Keyboard shortcut test indicators
    document.getElementById('whatsappTest').addEventListener('click', function() {
        this.textContent = 'Alt + W tuşlarına basın';
        this.className = 'badge bg-warning';
    });

    document.getElementById('scrollTest').addEventListener('click', function() {
        this.textContent = 'Alt + T tuşlarına basın';
        this.className = 'badge bg-warning';
    });

    // Test keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.altKey && e.key === 'w') {
            document.getElementById('whatsappTest').textContent = 'Çalışıyor!';
            document.getElementById('whatsappTest').className = 'badge bg-success';
        }
        
        if (e.altKey && e.key === 't') {
            document.getElementById('scrollTest').textContent = 'Çalışıyor!';
            document.getElementById('scrollTest').className = 'badge bg-success';
        }
    });

    // Auto scroll test
    setTimeout(function() {
        if (confirm('Otomatik scroll testi yapmak ister misiniz?')) {
            window.scrollTo({
                top: 1000,
                behavior: 'smooth'
            });
            
            setTimeout(function() {
                alert('Scroll to top butonu görünüyor mu? Sol alt köşeyi kontrol edin.');
            }, 2000);
        }
    }, 3000);
});
</script>

<?php include_once 'includes/footer.php'; ?>
