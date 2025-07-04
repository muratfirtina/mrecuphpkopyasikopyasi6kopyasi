# 🔧 Mr ECU Güvenlik Sistemi - Sorun Giderme

## ⚠️ Karşılaştığınız Sorunlar ve Çözümleri

### 1. Session Warning'leri
```
Warning: ini_set(): Session ini settings cannot be changed when a session is active
```

**Sebep:** Session ayarları session_start()'dan sonra yapılmaya çalışılıyor.

**✅ Çözüm:** Config.php ve SecurityManager.php düzeltildi. Artık session ayarları session başlamadan önce yapılıyor.

### 2. Güvenlik Tabloları Oluşturulamıyor
```
Base table or view not found: 1146 Table 'mrecu_db.security_logs' doesn't exist
```

**Sebep:** SQL dosyası düzgün çalışmıyor veya veritabanı bağlantı sorunu.

**✅ Çözüm:** Yeni kurulum dosyası oluşturuldu:
```
http://localhost:8888/mrecuphp/security/install-security-v2.php
```

### 3. JSON Sütun Hatası (MySQL < 5.7)
```
Unknown column type 'JSON'
```

**Sebep:** MySQL versiyonu 5.7'den eski.

**✅ Çözüm:** MySQL'i güncelleyin veya JSON sütunları TEXT olarak değiştirin:
```sql
ALTER TABLE security_logs MODIFY details TEXT;
ALTER TABLE file_security_scans MODIFY threats_found TEXT;
```

### 4. PDO MySQL Driver Eksik
```
could not find driver
```

**Sebep:** PHP'de PDO MySQL extension yüklü değil.

**✅ Çözüm:** MAMP/XAMPP PHP ayarlarından pdo_mysql extension'ını aktifleştirin.

### 5. CSP (Content Security Policy) Hataları
```
Refused to execute inline script because it violates CSP directive
```

**Sebep:** Strict CSP mode aktif, inline script'ler engelleniyor.

**✅ Çözüm:** Config.php'de CSP_STRICT_MODE'u false yapın:
```php
define('CSP_STRICT_MODE', false);
```

### 6. CSRF Token Hatası
```
CSRF token hatası
```

**Sebep:** Form'da CSRF token eksik veya geçersiz.

**✅ Çözüm:** Form'a hidden input ekleyin:
```html
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
```

### 7. Rate Limit Aşımı
```
Rate limit exceeded
```

**Sebep:** Çok fazla istek gönderildi.

**✅ Çözüm:** 5 dakika bekleyin veya session'ı temizleyin:
```php
session_destroy();
session_start();
```

### 8. Dosya Yükleme Engellendi
```
Dosya içeriği güvenlik kontrolünden geçemedi
```

**Sebep:** Dosya içeriğinde şüpheli kod tespit edildi.

**✅ Çözüm:** Dosyayı virüs taramasından geçirin veya validation ayarlarını gevşetin.

## 🛠️ Hızlı Çözümler

### Güvenlik Sistemini Geçici Olarak Devre Dışı Bırakma
```php
// config/config.php
define('SECURITY_ENABLED', false);
```

### Session Problemlerini Çözme
```php
// Sayfanın en başında
session_destroy();
session_start();
```

### Veritabanı Bağlantı Kontrolü
```php
// Test dosyası oluşturun
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=8889;dbname=mrecu_db', 'root', 'root');
    echo "✅ Veritabanı bağlantısı başarılı";
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage();
}
```

### Güvenlik Loglarını Temizleme
```sql
-- 30 günden eski logları sil
DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### IP'yi Whitelist'e Ekleme
```sql
INSERT INTO ip_security (ip_address, type, reason) 
VALUES ('127.0.0.1', 'whitelist', 'Local development');
```

## 📞 Destek Kontrol Listesi

### 1. ✅ Sistem Gereksinimleri Kontrolü
- [ ] PHP 7.4+ aktif
- [ ] MySQL 5.7+ çalışıyor
- [ ] PDO MySQL extension yüklü
- [ ] JSON desteği aktif
- [ ] Session support aktif

### 2. ✅ Dosya İzinleri Kontrolü
- [ ] uploads/ dizini yazılabilir (755)
- [ ] logs/ dizini yazılabilir (755)
- [ ] config/ dosyaları okunabilir (644)

### 3. ✅ Veritabanı Kontrolü
- [ ] Veritabanı bağlantısı çalışıyor
- [ ] Güvenlik tabloları mevcut
- [ ] INSERT/UPDATE yetkileri var

### 4. ✅ Güvenlik Ayarları Kontrolü
- [ ] SECURITY_ENABLED = true
- [ ] Session ayarları doğru
- [ ] CSRF token'lar çalışıyor

## 🔍 Debug Araçları

### Güvenlik Dashboard'ı Kontrol
```
http://localhost:8888/mrecuphp/admin/security-dashboard.php
```

### Test Güvenlik Olayı Oluşturma
```php
logSecurityEvent('test_event', 'Test mesajı');
```

### Session Bilgilerini Görüntüleme
```php
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
```

### Veritabanı Tablo Kontrolü
```sql
SHOW TABLES LIKE 'security_%';
DESCRIBE security_logs;
```

## 🚨 Acil Durum Prosedürleri

### Tüm Güvenlik Sistemini Sıfırlama
```sql
DROP TABLE security_logs;
DROP TABLE ip_security;
DROP TABLE failed_logins;
DROP TABLE csrf_tokens;
DROP TABLE rate_limits;
DROP TABLE security_config;
DROP TABLE file_security_scans;
DROP TABLE waf_rules;
```

Sonra yeniden kurulum:
```
http://localhost:8888/mrecuphp/security/install-security-v2.php
```

### Backup Güvenlik Ayarları (Güvenlik Devre Dışı)
```php
// config/config-backup.php (güvenlik olmadan)
define('SECURITY_ENABLED', false);
define('CSP_STRICT_MODE', false);

// Basit helper fonksiyonlar
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateCsrfToken($token) {
    return true; // Geçici olarak hep true döndür
}
```

## 📋 Kurulum Sonrası Kontrol

### 1. Güvenlik Tabloları Test
```bash
# Yeni kurulum dosyasını çalıştırın
http://localhost:8888/mrecuphp/security/install-security-v2.php
```

### 2. Session Warning'leri Test
```bash
# Ana sayfayı yenileyin, warning olmamalı
http://localhost:8888/mrecuphp/
```

### 3. Dashboard Erişim Test
```bash
# Admin olarak giriş yapın ve dashboard'a gidin
http://localhost:8888/mrecuphp/admin/security-dashboard.php
```

### 4. CSRF Token Test
```bash
# Güvenli login örneğini test edin
http://localhost:8888/mrecuphp/secure-login-example.php
```

## 🎯 Başarı Kriterleri

✅ **Session warning'leri yok**  
✅ **8 güvenlik tablosu oluşturuldu**  
✅ **Güvenlik dashboard açılıyor**  
✅ **Test güvenlik olayı kaydedildi**  
✅ **CSRF token'lar çalışıyor**  
✅ **Rate limiting aktif**  
✅ **Security headers aktif**  
✅ **Log dosyaları yazılıyor**  

## 📞 Hâlâ Sorun Yaşıyorsanız

### Debug Bilgileri Toplayın:
1. **PHP Version**: `<?php echo phpversion(); ?>`
2. **MySQL Version**: `SELECT VERSION();`
3. **PDO Drivers**: `<?php print_r(PDO::getAvailableDrivers()); ?>`
4. **Error Logs**: `/Applications/MAMP/logs/php_error.log`
5. **Session Status**: `<?php echo session_status(); ?>`

### Common Issues Kontrol Listesi:
- [ ] MAMP/XAMPP çalışıyor mu?
- [ ] Apache ve MySQL servisleri aktif mi?
- [ ] PHP.ini'de session desteği var mı?
- [ ] Veritabanı kullanıcısının yetkileri yeterli mi?
- [ ] Dosya izinleri doğru mu?

---

**🔧 Bu sorun giderme dosyası ile tüm yaygın problemleri çözebilirsiniz!**

**Güncellenme**: 18 Haziran 2025  
**Versiyon**: 1.0.1
