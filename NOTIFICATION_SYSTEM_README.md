# Mr ECU Bildirim Sistemi Kurulum Rehberi

Bu rehber, Mr ECU projesi için gelişmiş bildirim ve email sistemi kurulumunu adım adım açıklar.

## 🎯 Sistem Özellikleri

### ✅ Admin Bildirimleri
- Kullanıcı dosya yüklediğinde email + navbar bildirimi
- Revize talebi geldiğinde bildirim
- Real-time bildirim güncellemeleri

### ✅ Kullanıcı Bildirimleri  
- Dosya durumu güncellendiğinde email + navbar bildirimi
- Admin yanıtı geldiğinde bildirim
- İşlem tamamlandığında bildirim

### ✅ Email Sistemi
- Outlook SMTP entegrasyonu (mrecu@outlook.com)
- Email kuyruk sistemi
- HTML email şablonları
- Otomatik email gönderimi

## 📋 Kurulum Adımları

### 1. Mevcut Bildirim Tablolarını Temizle (Eğer Hata Alıyorsanız)

```bash
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/config/clean-notifications.php
```

### 2. Bildirim Tablolarını Oluştur

```bash
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/config/install-notifications.php
```

### 3. Notified Kolonunu Ekle

```bash
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/config/add-notified-column.php
```

### 4. Email Ayarlarını Yap

```bash
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/admin/email-settings.php
```

#### a) mrecu@outlook.com için App Password oluştur:
1. Outlook.com hesabına giriş yapın
2. Güvenlik ayarlarına gidin
3. "App passwords" bölümünden yeni şifre oluşturun
4. Bu şifreyi not alın

#### b) Admin panelinden email ayarlarını yapın:
```bash
# Admin paneline giriş yapın ve şu sayfaya gidin:
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/admin/email-settings.php
```

- SMTP şifre alanına app password'u girin
- Test email adresi girin
- "Ayarları Kaydet" butonuna tıklayın
- "Test Email Gönder" ile test edin

### 🔧 MAMP Email Test Modu

MAMP ortamında PHP'nin `mail()` fonksiyonu genellikle çalışmaz. Bu nedenle sistem **Email Test Modu** ile gelir:

```php
// config/config.php dosyasında:
define('EMAIL_TEST_MODE', true);  // Test modu (emailler log'a yazılır)
// define('EMAIL_TEST_MODE', false); // Gerçek email gönderimi
```

#### Test Modunda:
- ✅ Emailler `/logs/email_test.log` dosyasına yazılır
- ✅ Admin paneli > Email Logları'ndan görüntülenebilir
- ✅ Bildirim sistemi normal çalışır
- ✅ Test emaili gönderebilirsiniz

#### Gerçek Email İçin:
1. `EMAIL_TEST_MODE` = `false` yapın
2. Outlook SMTP şifresini girin
3. Veya PHPMailer kurun: `composer require phpmailer/phpmailer`

### 4. Cron Job Kurulumu (İsteğe Bağlı)

Email kuyruğunu otomatik işlemek için:

```bash
# Crontab'ı düzenle
crontab -e

# Her dakika email kuyruğunu işle
* * * * * /usr/bin/php /Applications/MAMP/htdocs/mrecuphpkopyasikopyasi6kopyasi/process_email_queue.php

# Veya her 5 dakikada bir
*/5 * * * * /usr/bin/php /Applications/MAMP/htdocs/mrecuphpkopyasikopyasi6kopyasi/process_email_queue.php
```

### 5. Test İşlemleri

#### a) Dosya Yükleme Testi:
1. Kullanıcı hesabı ile giriş yapın
2. Dosya yükleyin
3. Admin hesabında bildirim gelip gelmediğini kontrol edin
4. Email gelip gelmediğini kontrol edin

#### b) Admin Yanıt Testi:
1. Admin olarak dosya durumunu güncelleyin
2. Kullanıcı hesabında bildirim kontrolü yapın

#### c) Revize Talebi Testi:
1. Kullanıcı olarak revize talebi oluşturun
2. Admin hesabında bildirim kontrolü yapın

## 🔧 Manuel Email Kuyruk İşleme

Web arayüzünden email kuyruğunu manuel olarak işlemek için:

```bash
# Admin panelinden:
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/admin/email-settings.php
# "Email Kuyruğunu İşle" butonuna tıklayın

# Veya doğrudan:
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/process_email_queue.php
```

## 📊 Dosya Yapısı

```
/includes/
├── NotificationManager.php     # Bildirim yönetimi
├── EmailManager.php           # Email yönetimi

/admin/
├── email-settings.php         # Email ayarları sayfası
├── ajax/                      # Admin AJAX dosyları
│   ├── mark_notification_read.php
│   ├── mark_all_notifications_read.php
│   ├── get_notification_count.php
│   └── send_test_email.php

/user/
├── ajax/                      # User AJAX dosyları
│   ├── mark_notification_read.php
│   ├── mark_all_notifications_read.php
│   └── get_notification_count.php

/assets/js/
├── notifications.js           # JavaScript fonksiyonları

/config/
├── install-notifications.php  # Kurulum scripti

process_email_queue.php        # Email kuyruk işleyici
```

## 🐛 Sorun Giderme

### Email Gönderilmiyor
1. SMTP ayarlarını kontrol edin
2. App password doğru girilmiş mi?
3. Internet bağlantısı var mı?
4. Email kuyruk tablosunu kontrol edin:
   ```sql
   SELECT * FROM email_queue WHERE status = 'failed';
   ```

### Bildirimler Görünmüyor
1. JavaScript konsol hatalarını kontrol edin
2. AJAX dosyalarının erişilebilir olduğunu kontrol edin
3. Notifications tablosunu kontrol edin:
   ```sql
   SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10;
   ```

### Veritabanı Hataları
1. Tablolar oluşturulmuş mu kontrol edin:
   ```sql
   SHOW TABLES LIKE '%notification%';
   SHOW TABLES LIKE '%email%';
   ```

2. Eksik tablolar varsa install-notifications.php'yi tekrar çalıştırın

## 🔒 Güvenlik Notları

1. **SMTP Şifresi**: App password kullanın, asıl şifrenizi kullanmayın
2. **Email Adresleri**: Tüm email adresleri sanitize edilir
3. **AJAX Güvenlik**: Tüm AJAX istekleri yetki kontrolü yapar
4. **SQL Injection**: Prepared statements kullanılır

## 📈 Performans İpuçları

1. **Email Kuyruk**: Büyük email hacmi için kuyruk sistemini kullanın
2. **Cron Job**: Email gönderimini arkaplanda çalıştırın  
3. **Log Temizleme**: Eski logları düzenli temizleyin
4. **Database Index**: Bildirim tablolarında index kullanın

## 🚀 İleri Seviye Özellikler

### Real-time Bildirimler (Gelecek)
- WebSocket entegrasyonu
- Push notifications
- Browser notifications

### Email Şablonları (Gelecek)  
- Görsel email editörü
- Değişken yönetimi
- A/B testing

### Analitik (Gelecek)
- Email açılma oranları
- Bildirim etkileşimleri  
- Kullanıcı davranış analizi

## ⚡ Hızlı Test

Sistemi hızlı test etmek için:

```bash
# 1. Tabloları oluştur
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/config/install-notifications.php

# 2. Email ayarlarını yap
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/admin/email-settings.php

# 3. Test email gönder
# Admin panelinden "Test Email Gönder" butonunu kullan

# 4. Dosya yükle
# User panelinden dosya yükle ve admin bildirimini kontrol et
```

## 📞 Destek

Sorun yaşarsanız:
1. Log dosyalarını kontrol edin: `/logs/email_queue.log`
2. Browser konsolunu kontrol edin
3. PHP error loglarını kontrol edin
4. Email kuyruk durumunu kontrol edin

---

**📌 Önemli**: Bu sistem mrecu@outlook.com email hesabı için optimize edilmiştir. Farklı email sağlayıcısı kullanacaksanız config.php dosyasındaki SMTP ayarlarını değiştirin.
