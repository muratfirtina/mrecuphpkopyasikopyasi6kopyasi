# 🛡️ Mr ECU Güvenlik Sistemi

Bu proje için kapsamlı güvenlik sistemi başarıyla kurulmuştur. Aşağıdaki güvenlik önlemleri aktif edilmiştir:

## 🚀 Kurulum Tamamlandı

### ✅ Aktif Güvenlik Önlemleri:

1. **SQL Injection Koruması**
   - Prepared statements ile güvenli database sorguları
   - SQL pattern tespiti ve engelleme
   - Input sanitization
   - Güvenli database wrapper (SecureDatabase.php)

2. **XSS (Cross-Site Scripting) Koruması**
   - HTML encoding ve sanitization
   - CSP (Content Security Policy) headers
   - JavaScript içerik filtreleme
   - DOM manipülasyon koruması

3. **CSRF (Cross-Site Request Forgery) Koruması**
   - Token tabanlı form koruması
   - Otomatik token üretimi ve doğrulama
   - Session tabanlı token yönetimi

4. **DOM Manipülasyon Koruması**
   - JavaScript ile real-time DOM izleme
   - innerHTML, outerHTML koruması
   - Kötü niyetli script injection tespiti

5. **Rate Limiting**
   - IP tabanlı istek sınırlandırması
   - Brute force saldırı koruması
   - Login deneme limitleri

6. **Dosya Yükleme Güvenliği**
   - MIME type kontrolü
   - Dosya içerik taraması
   - Güvenli dosya adı sanitization
   - Path traversal koruması

7. **Session Güvenliği**
   - Secure session ayarları
   - Session hijacking koruması
   - Timeout yönetimi

8. **Güvenlik Headers**
   - XSS Protection
   - Clickjacking koruması
   - Content-Type sniffing koruması
   - HSTS (HTTPS Strict Transport Security)

## 📁 Oluşturulan Dosyalar

```
mrecuphp/
├── security/
│   ├── SecurityManager.php          # Ana güvenlik sınıfı
│   ├── SecureDatabase.php           # Güvenli database wrapper
│   ├── SecurityHeaders.php          # HTTP güvenlik headers
│   ├── security-guard.js            # Frontend güvenlik koruması
│   ├── log-security-event.php       # Güvenlik olayları logger
│   ├── security_tables.sql          # Güvenlik veritabanı tabloları
│   └── install-security.php         # Güvenlik sistemi kurulumu
├── config/
│   └── config.php                   # Güvenlik entegrasyonu ile güncellenmiş
├── admin/
│   └── security-dashboard.php       # Güvenlik yönetim paneli
└── secure-login-example.php         # Güvenli login örneği
```

## 🗄️ Veritabanı Tabloları

Aşağıdaki güvenlik tabloları oluşturulmuştur:

- `security_logs` - Güvenlik olayları kayıtları
- `ip_security` - IP whitelist/blacklist
- `failed_logins` - Başarısız giriş denemeleri
- `csrf_tokens` - CSRF token'ları
- `rate_limits` - Rate limiting kayıtları
- `security_config` - Güvenlik konfigürasyonu
- `file_security_scans` - Dosya güvenlik taraması
- `waf_rules` - Web Application Firewall kuralları

## 🔧 Kullanım Örnekleri

### 1. Güvenli Form Oluşturma

```php
// CSRF token ekle
$csrfToken = generateCsrfToken();

// Form HTML'i
echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">';

// Form işleme
if ($_POST) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF token geçersiz!');
    }
    
    $input = sanitize($_POST['data']);
    // İşleme devam et...
}
```

### 2. Güvenli Database Sorguları

```php
global $secureDb;

// Güvenli SELECT
$users = $secureDb->secureSelect('users', ['id', 'username'], ['status' => 'active']);

// Güvenli INSERT
$userId = $secureDb->secureInsert('users', [
    'username' => $username,
    'email' => $email,
    'password' => password_hash($password, PASSWORD_DEFAULT)
]);
```

### 3. Dosya Yükleme Güvenliği

```php
$fileValidation = validateFileUpload($_FILES['file']);
if (!$fileValidation['valid']) {
    foreach ($fileValidation['errors'] as $error) {
        echo "Hata: $error\n";
    }
} else {
    // Güvenli dosya yükleme işlemi
    $safeName = $fileValidation['safe_name'];
    move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $safeName);
}
```

### 4. Rate Limiting Kontrolü

```php
if (!checkRateLimit('login_attempt', $_SERVER['REMOTE_ADDR'], 5, 300)) {
    die('Çok fazla deneme! 5 dakika bekleyin.');
}

// Brute force kontrolü
if (!checkBruteForce($email . '_' . $_SERVER['REMOTE_ADDR'])) {
    die('Hesap geçici olarak bloklandı.');
}
```

## 🎛️ Yönetim Paneli

Güvenlik olaylarını izlemek için admin paneline yeni özellikler eklendi:

- **Güvenlik Dashboard:** `/admin/security-dashboard.php`
- **Gerçek zamanlı tehdit izleme**
- **IP tabanlı risk analizi**
- **Güvenlik olayları export**

## ⚙️ Konfigürasyon

`config/config.php` dosyasında güvenlik ayarları:

```php
// Güvenlik sistemini aktif/pasif et
define('SECURITY_ENABLED', true);

// CSP sıkı mod (production için true)
define('CSP_STRICT_MODE', false);

// Rate limiting limitleri
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_BLOCK_DURATION', 900);
```

## 🧪 Test Etme

1. **Güvenlik Kurulumunu Test Et:**
   ```
   http://localhost:8888/mrecuphp/security/install-security.php
   ```

2. **Güvenli Login'i Test Et:**
   ```
   http://localhost:8888/mrecuphp/secure-login-example.php
   ```

3. **Admin Güvenlik Dashboard:**
   ```
   http://localhost:8888/mrecuphp/admin/security-dashboard.php
   ```

## 🚨 Güvenlik Olayları

Sistem otomatik olarak aşağıdaki güvenlik olaylarını tespit eder ve loglar:

- SQL Injection denemeleri
- XSS saldırı denemeleri
- CSRF token ihlalleri
- Brute force saldırıları
- Kötü niyetli dosya yükleme
- DOM manipülasyon denemeleri
- Rate limit aşımları
- Güvenli olmayan redirect denemeleri

## 📧 Bildirimler

Kritik güvenlik olayları için otomatik email bildirimleri gönderilir.

## 🔄 Güncelleme ve Bakım

1. **Log Dosyalarını Temizleme:**
   ```sql
   DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
   ```

2. **IP Blacklist Güncelleme:**
   ```sql
   INSERT INTO ip_security (ip_address, type, reason) VALUES ('192.168.1.100', 'blacklist', 'Repeated attacks');
   ```

3. **WAF Kuralları Güncelleme:**
   ```sql
   UPDATE waf_rules SET is_active = 0 WHERE rule_name = 'specific_rule';
   ```

## 🆘 Sorun Giderme

### Güvenlik Sistemi Çalışmıyor
1. `SECURITY_ENABLED` konstanti `true` olduğundan emin olun
2. Database bağlantısını kontrol edin
3. `security_logs` tablosunun var olduğunu kontrol edin

### CSRF Token Hataları
1. Session'ın başlatıldığından emin olun
2. Form'da `csrf_token` hidden input'u olduğunu kontrol edin
3. Token validation'ı form işlemeden önce yapın

### Rate Limiting Çok Sıkı
1. `config.php`'de rate limit değerlerini artırın
2. Test IP'leri için whitelist ekleyin

## 🎯 Production Ortamı İçin

Production'a geçerken şunları yapın:

1. `CSP_STRICT_MODE` değerini `true` yapın
2. `display_errors` değerini `0` yapın
3. HTTPS kullanın
4. Güvenlik loglarını düzenli olarak inceleyin
5. WAF kurallarını fine-tune edin

---

**🔒 Güvenlik sistemi başarıyla kurulmuştur ve aktiftir!**

Herhangi bir sorun için güvenlik loglarını kontrol edin ve admin panelinden güvenlik olaylarını izleyin.
