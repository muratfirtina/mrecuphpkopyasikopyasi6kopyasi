# 🚗 Email Notifications Enhancement - Araç Plakası Entegrasyonu

Bu güncelleme ile **tüm email bildirimlerinde araç plakası bilgisi** otomatik olarak gösterilir.

## 📋 Yapılan Değişiklikler

### ✅ 1. Email Template'leri Güncellendi

Tüm email template'lerinde araç plakası bilgisi eklendi:

- **admin_file_upload.html** - Yeni dosya yüklendi bildirimi (Admin'e)
- **user_file_ready.html** - Dosya hazır bildirimi (Kullanıcıya)  
- **admin_additional_file.html** - Ek dosya bildirimi (Admin'e)
- **user_additional_file.html** - Ek dosya bildirimi (Kullanıcıya)
- **admin_revision_request.html** - Revizyon talebi bildirimi (Admin'e)
- **chat_message.html** - Chat mesaj bildirimi (YENİ)

### ✅ 2. Yeni EmailTemplateEngine Oluşturuldu

**`includes/EmailTemplateEngine.php`**
- Otomatik araç plakası ekleme
- Template işleme sistemi
- Email subject'lerine plaka bilgisi ekleme
- Conditional blocks işleme ({{#if}} {{/if}})

### ✅ 3. Gelişmiş Email Entegrasyon Sistemi

**`includes/enhanced-email-integration.php`**
- `sendEnhancedFileUploadNotificationToAdmin()` - Plaka bilgili admin bildirimi
- `sendEnhancedFileReadyNotificationToUser()` - Plaka bilgili kullanıcı bildirimi  
- `sendEnhancedChatMessageNotification()` - Chat mesaj bildirimi
- `sendEnhancedRevisionRequestNotificationToAdmin()` - Revizyon talebi bildirimi
- `sendEnhancedAdditionalFileNotification()` - Ek dosya bildirimi

### ✅ 4. Otomatik Güncelleme Script'i

**`update-email-notifications.php`** - Sistemdeki mevcut email çağrılarını güncelleyen script

## 🎯 Email Bildirimlerinde Değişiklikler

### ÖNCE:
```
📧 Subject: "Yeni Dosya Yüklendi - dosya.zip"
📋 Content: "Dosya Adı: dosya.zip"
```

### SONRA:
```
📧 Subject: "Yeni Dosya Yüklendi - dosya.zip (Plaka: 34ABC123)"
📋 Content: "Dosya Adı: dosya.zip (34ABC123)"
           "Araç Plakası: 34ABC123"
```

## 🚀 Kullanım

### 1. Sistemi Aktifleştir
```bash
php update-email-notifications.php
```

### 2. Test Et
```php
// test-enhanced-emails.php dosyasını düzenleyip çalıştır
$testUploadId = "gerçek-upload-id";
$testEmail = "test@email.com";
```

### 3. Yeni Email Fonksiyonlarını Kullan

#### Dosya Upload Bildirimi:
```php
require_once 'includes/enhanced-email-integration.php';

// Admin'e bildirim gönder
sendEnhancedFileUploadNotificationToAdmin($uploadId);
```

#### Yanıt Dosyası Hazır Bildirimi:
```php
// Kullanıcıya bildirim gönder
sendEnhancedFileReadyNotificationToUser($uploadId, $responseFileName, $adminNotes);
```

#### Chat Mesajı Bildirimi:
```php
// Chat mesajı bildirimi
sendEnhancedChatMessageNotification($uploadId, $message, $senderName, $receiverEmail, $receiverName);
```

## 📧 Email Template Örnekleri

### Admin Dosya Upload Bildirimi:
```html
<h3>📋 Dosya Detayları</h3>
<p><strong>Dosya Adı:</strong> motor_yazilimi.zip (34ABC123)</p>
<p><strong>Araç Plakası:</strong> <strong>34ABC123</strong></p>
<p><strong>Marka:</strong> BMW</p>
<p><strong>Model:</strong> 3 Series</p>
```

### Chat Mesaj Bildirimi:
```html
<h3>📋 İlgili Dosya</h3>
<p><strong>Dosya Adı:</strong> ecu_dosyasi.bin (06DEF456)</p>
<p><strong>Araç Plakası:</strong> <strong>06DEF456</strong></p>
<p><strong>Araç:</strong> Mercedes C180</p>
```

## 🔧 Chat Sistemi Entegrasyonu

Chat sisteminizde mesaj gönderildiğinde email bildirimi için:

```php
// examples/chat-email-integration.php dosyasını kullanın
notifyChatMessage($uploadId, $message, $senderName, $senderType);
```

## 📁 Oluşturulan Dosyalar

```
/includes/
├── EmailTemplateEngine.php          # Yeni template engine
├── enhanced-email-integration.php   # Gelişmiş email fonksiyonları
/email_templates/
├── chat_message.html               # Yeni chat mesaj template'i
/examples/
├── chat-email-integration.php      # Chat entegrasyon örneği
/
├── update-email-notifications.php  # Güncelleme script'i
├── test-enhanced-emails.php       # Test script'i
└── EMAIL_ENHANCEMENT_README.md    # Bu dosya
```

## ⚠️ Önemli Notlar

1. **Database `plate` Alanı**: Email'lerde araç plakası görünmesi için `file_uploads` tablosunda `plate` alanının dolu olması gerekir.

2. **Mevcut Email Fonksiyonları**: Eski email fonksiyonları hala çalışır, ancak yeni fonksiyonlar araç plakası bilgisini otomatik ekler.

3. **Template Değişkenleri**: Tüm template'lerde `{{plate}}` değişkeni kullanılabilir.

4. **Hata Durumları**: Araç plakası bilgisi bulunamazsa email yine gönderilir, sadece plaka bilgisi boş görünür.

## 🧪 Test Checklist

- [ ] Admin'e dosya upload bildirimi - plaka bilgisi var mı?
- [ ] Kullanıcıya yanıt dosyası bildirimi - plaka bilgisi var mı? 
- [ ] Chat mesajı bildirimi - plaka bilgisi var mı?
- [ ] Email subject'lerinde plaka bilgisi var mı?
- [ ] Revizyon talep bildirimlerinde plaka bilgisi var mı?
- [ ] Ek dosya bildirimlerinde plaka bilgisi var mı?

## 🎉 Sonuç

✅ **Tüm dosya bildirimlerinde araç plakası bilgisi artık görünüyor!**

- Email subject'lerinde: `dosya.zip (Plaka: 34ABC123)`
- Email içeriklerinde: `Dosya Adı: dosya.zip (34ABC123)` 
- Ayrı plaka alanında: `Araç Plakası: 34ABC123`
- Chat mesajlarında: Plaka bilgisi ile birlikte dosya adı

Bu güncelleme ile kullanıcılar ve adminler, email bildirimlerinde hangi araç için işlem yapıldığını kolayca görebilecekler.
