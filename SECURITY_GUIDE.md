# 🛡️ Mr ECU Güvenlik Sistemi Kurulum Rehberi

Bu rehber, **mrecuphp** projesine kapsamlı güvenlik önlemlerinin nasıl entegre edileceğini açıklar.

## 📋 Güvenlik Özellikleri

### ✅ SQL Injection Koruması
- **Prepared Statements**: Tüm database sorguları için güvenli parametre binding
- **Input Sanitization**: Kullanıcı girdilerinin otomatik temizlenmesi
- **SecureDatabase Class**: SQL injection pattern tespiti ve engelleme
- **Query Validation**: Tehlikeli SQL komutlarının tespiti

### ✅ XSS (Cross-Site Scripting) Koruması  
- **HTML Encoding**: Tüm kullanıcı çıktılarının güvenli encode edilmesi
- **CSP (Content Security Policy)**: Zararlı script'lerin çalıştırılmasını engelleme
- **Input Filtering**: XSS pattern'lerinin tespiti ve temizlenmesi
- **DOM Manipulation Protection**: Client-side DOM değişikliklerinin kontrolü

### ✅ CSRF (Cross-Site Request Forgery) Koruması
- **CSRF Token**: Her form için benzersiz token oluşturma
- **Token Validation**: Form gönderimlerinde token doğrulama
- **Same-Origin Policy**: Cross-origin isteklerin kontrolü

### ✅ DOM Manipülasyon Koruması
- **JavaScript Security Guard**: Client-side güvenlik koruması
- **innerHTML Protection**: Zararlı HTML enjeksiyonunu engelleme
- **Event Handler Security**: Güvenli event listener yönetimi
- **Console Protection**: Hassas bilgilerin console'a yazdırılmasını engelleme

### ✅ Dosya Yükleme Güvenliği
- **File Type Validation**: Sadece izin verilen dosya formatları
- **Content Scanning**: Dosya içeriğinde zararlı kod tespiti
- **Size Limitation**: Dosya boyutu kontrolü
- **Safe File Naming**: Güvenli dosya adı oluşturma

### ✅ Rate Limiting & Brute Force Koruması
- **Login Attempts**: Başarısız giriş denemelerini sınırlama
- **IP-based Blocking**: Şüpheli IP adreslerini geçici engelleme
- **Request Rate Limiting**: Aşırı istek gönderimini engelleme

### ✅ Session Güvenliği
- **Secure Session Settings**: HTTPOnly, Secure, SameSite cookie ayarları
- **Session Hijacking Protection**: User-Agent ve IP kontrolü
- **Session Timeout**: Otomatik oturum sonlandırma
- **Session Regeneration**: Düzenli session ID yenileme

### ✅ Security Headers
- **X-XSS-Protection**: Tarayıcı XSS koruması
- **X-Frame-Options**: Clickjacking koruması
- **X-Content-Type-Options**: MIME type sniffing koruması
- **HSTS**: HTTPS zorunluluğu
- **CSP**: İçerik güvenlik politikası

## 🚀 Kurulum Adımları

### 1. Güvenlik Tablolarını Oluştur
```bash
http://localhost:8888/mrecuphp/security/install-security.php
```

Bu adım şunları yapar:
- Güvenlik log tabloları oluşturur
- WAF kurallarını yükler
- Güvenlik konfigürasyonlarını ayarlar
- Log dizinlerini oluşturur

### 2. Config.php Güncellemesi
Config dosyası otomatik olarak güvenlik entegrasyonu ile güncellenmiştir:
- SecurityManager entegrasyonu
- Güvenli helper fonksiyonları
- CSRF token yönetimi
- Rate limiting kontrolü

### 3. HTML Sayfalarına Meta Tag Ekle
Tüm HTML sayfalarına şu kodları ekleyin:

```php
<?php renderSecurityMeta(); ?>
```

### 4. JavaScript Security Guard Dahil Et
Sayfa sonunda şu kodu ekleyin:

```php
<?php includeSecurityScript(); ?>
```

### 5. Form'lara CSRF Token Ekle
Tüm form'lara hidden input ekleyin:

```html
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
```

### 6. Form İşlemlerinde CSRF Kontrol
Form işleme sayfalarında:

```php
if (!validateCsrfToken($_POST['csrf_token'])) {
    die('CSRF token hatası');
}
```

## 📁 Dosya Yapısı

```
mrecuphp/
├── security/
│   ├── SecurityManager.php          # Ana güvenlik yöneticisi
│   ├── SecureDatabase.php          # Güvenli database wrapper
│   ├── SecurityHeaders.php         # HTTP güvenlik başlıkları
│   ├── security-guard.js           # Client-side güvenlik koruması
│   ├── log-security-event.php      # Güvenlik olayları logger
│   ├── install-security.php        # Güvenlik sistemi kurulumu
│   └── security_tables.sql         # Güvenlik tabloları SQL
├── admin/
│   └── security-dashboard.php      # Güvenlik olayları dashboard
├── config/
│   └── config.php                  # Güvenlik entegrasyonlu config
└── logs/                           # Güvenlik log dosyaları
    └── security.log
```

## 🔧 Kullanım Örnekleri

### Güvenli Database Sorgusu
```php
// Eski yöntem (güvenli değil)
$sql = "SELECT * FROM users WHERE email = '" . $_POST['email'] . "'";

// Yeni yöntem (güvenli)
$users = $secureDb->secureSelect('users', '*', ['email' => $_POST['email']]);
```

### Güvenli Input Sanitization
```php
// Genel sanitization
$clean_input = sanitize($_POST['data']);

// Özel tip sanitization
$email = sanitize($_POST['email'], 'email');
$phone = sanitize($_POST['phone'], 'phone');
$filename = sanitize($_FILES['file']['name'], 'filename');
```

### Güvenli Dosya Yükleme
```php
$validation = validateFileUpload($_FILES['file'], ['pdf', 'doc', 'docx'], 10*1024*1024);

if (!$validation['valid']) {
    foreach ($validation['errors'] as $error) {
        echo "Hata: " . htmlspecialchars($error) . "<br>";
    }
}
```

### Rate Limiting Kontrolü
```php
if (!checkRateLimit('login_attempt', $_SERVER['REMOTE_ADDR'], 5, 300)) {
    die('Çok fazla deneme. 5 dakika bekleyin.');
}
```

## 📊 Güvenlik Dashboard

Admin panelinde güvenlik dashboard'ına erişim:
```
http://localhost:8888/mrecuphp/admin/security-dashboard.php
```

Dashboard özellikleri:
- ✅ Gerçek zamanlı güvenlik istatistikleri
- ✅ Tehdit türleri analizi
- ✅ Şüpheli IP adresleri listesi
- ✅ Güvenlik olayları geçmişi
- ✅ Tehlikeli IP engelleme

## ⚙️ Konfigürasyon

### Güvenlik Ayarları (config.php)
```php
define('SECURITY_ENABLED', true);       // Güvenlik sistemini aktif et
define('CSP_STRICT_MODE', false);       // Geliştirme: false, Production: true
define('MAX_LOGIN_ATTEMPTS', 5);        // Maksimum giriş deneme sayısı
define('LOGIN_BLOCK_DURATION', 900);    // Giriş bloklama süresi (saniye)
```

### Rate Limiting Ayarları
```php
define('MAX_REQUESTS_PER_MINUTE', 60);      // Dakikada maksimum istek
define('MAX_FILE_UPLOADS_PER_HOUR', 10);   // Saatte maksimum dosya yükleme
```

## 🔍 Güvenlik Olayları

Sistem şu olayları otomatik loglar:
- `sql_injection_attempt` - SQL injection denemesi
- `xss_attempt` - XSS saldırısı denemesi
- `brute_force_detected` - Brute force saldırısı
- `csrf_token_invalid` - Geçersiz CSRF token
- `malicious_file_upload` - Zararlı dosya yükleme
- `dom_manipulation_blocked` - DOM manipülasyon engellendi
- `rate_limit_exceeded` - Rate limit aşıldı
- `unsafe_redirect_attempt` - Güvenli olmayan yönlendirme

## 🚨 Acil Durum Prosedürleri

### Güvenlik Sistemini Devre Dışı Bırakma
Config.php'de:
```php
define('SECURITY_ENABLED', false);
```

### Şüpheli IP'yi Manuel Engelleme
```sql
INSERT INTO ip_security (ip_address, type, reason) 
VALUES ('192.168.1.100', 'blacklist', 'Manuel engelleme');
```

### Güvenlik Loglarını Temizleme
```sql
DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

## 📈 Performans Notları

- Güvenlik kontrolleri minimum 2-5ms overhead ekler
- Rate limiting için session kullanılır (memory efficient)
- Güvenlik logları async olarak yazılır
- CSP strict mode geliştirme sürecini yavaşlatabilir

## 🔧 Sorun Giderme

### Yaygın Hatalar

1. **CSRF token hatası**
   - Çözüm: Sayfayı yenileyin, form'da hidden input kontrol edin

2. **Rate limit aşımı**
   - Çözüm: 5 dakika bekleyin veya session'ı temizleyin

3. **Dosya yükleme engellendi**
   - Çözüm: Dosya formatını ve boyutunu kontrol edin

4. **XSS koruması false positive**
   - Çözüm: Input'u kontrol edin, güvenli içerik için whitelist kullanın

## 📞 Destek

Güvenlik sistemi ile ilgili sorunlar için:
- Log dosyalarını kontrol edin: `logs/security.log`
- Admin dashboard'ında güvenlik olaylarını inceleyin
- Error log'ları kontrol edin: PHP error log

---

**🛡️ Mr ECU Security System - Her türlü siber tehdide karşı kapsamlı koruma**
