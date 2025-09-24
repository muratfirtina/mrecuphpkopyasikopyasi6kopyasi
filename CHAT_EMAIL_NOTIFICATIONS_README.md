# Chat Email Bildirimleri - Kurulum ve Kullanım Kılavuzu

## 🔧 Yapılan Değişiklikler

### 1. Veritabanı Güncellemeleri
- `user_email_preferences` tablosuna `chat_message_notifications` alanı eklendi
- Chat mesajları için email template'leri eklendi
- Mevcut kullanıcılar için varsayılan chat bildirim tercihi etkinleştirildi

### 2. Kod Güncellemeleri
- `ChatManager.php`: Email bildirim sistemi entegre edildi
- `user/email-preferences.php`: Chat mesaj bildirimleri seçeneği eklendi
- Hem admin hem kullanıcı chat mesajları için email gönderimi aktif edildi

## 🚀 Kurulum Adımları

### Adım 1: Veritabanı Kurulumu
```bash
# Admin panelinden setup script'ini çalıştır
http://localhost/admin/setup_chat_email_notifications.php
```

### Adım 2: Email Ayarları
`.env` dosyasında SMTP ayarlarının doğru olduğundan emin olun:
```env
SMTP_HOST=smtp-mail.outlook.com
SMTP_PORT=587
SMTP_USERNAME=mr.ecu@outlook.com
SMTP_PASSWORD=your_password
SMTP_ENCRYPTION=tls
SMTP_FROM_EMAIL=mr.ecu@outlook.com
SMTP_FROM_NAME=Mr ECU
```

### Adım 3: Test
```bash
# Test sayfasını açın
http://localhost/admin/test_chat_email_notifications.php
```

## 💬 Nasıl Çalışır?

### Kullanıcı Mesaj Gönderdiğinde:
1. Kullanıcı bir dosyaya chat mesajı yazar
2. Sistem, tüm aktif adminlere bildirim gönderir:
   - ✅ Sistem içi bildirim
   - ✅ Email bildirimi (admin email tercihleri aktifse)

### Admin Mesaj Gönderdiğinde:
1. Admin bir kullanıcıya chat mesajı yazar  
2. Sistem, dosya sahibi kullanıcıya bildirim gönderir:
   - ✅ Sistem içi bildirim
   - ✅ Email bildirimi (kullanıcı email tercihleri aktifse)

## ⚙️ Email Tercihleri

### Kullanıcılar İçin:
- `user/email-preferences.php` sayfasından chat bildirimlerini açıp kapatabilir
- Varsayılan olarak **AÇIK** 

### Adminler İçin:
- Admin panelinden kendi email tercihlerini yönetebilir
- Chat mesajları için ayrı bir tercih seçeneği var

## 🎨 Email Template'leri

### Admin için Template (`chat_message_admin`):
- Kullanıcı mesajlarını alırken kullanılır
- Mavi renk teması (#3498db)
- "Mesajı Yanıtla" linki

### Kullanıcı için Template (`chat_message_user`):
- Admin mesajlarını alırken kullanılır  
- Yeşil renk teması (#27ae60)
- "Mesajı Görüntüle" linki

## 🔍 Log ve Debug

### Email Logları:
- `logs/email_test.log` - Detaylı email gönderim logları
- PHP error log - Genel sistem hataları

### Debug Modunu Açma:
`.env` dosyasında:
```env
DEBUG=true
```

## 🧪 Test Senaryoları

### 1. Kullanıcı → Admin Mesajı:
1. Kullanıcı hesabı ile giriş yap
2. Bir dosya detay sayfasına git  
3. Chat bölümünden admin'e mesaj gönder
4. Admin email adresini kontrol et

### 2. Admin → Kullanıcı Mesajı:
1. Admin hesabı ile giriş yap
2. Bir dosya detay sayfasına git
3. Chat bölümünden kullanıcıya mesaj gönder
4. Kullanıcının email adresini kontrol et

## ⚠️ Önemli Notlar

### Email Tercihleri Kontrolleri:
- Email gönderilmesi için alıcının `email_verified = 1` olması gerekir
- `chat_message_notifications = 1` olması gerekir  
- SMTP ayarları doğru yapılandırılmış olmalıdır

### Performance:
- Email gönderimi asenkron olarak çalışır
- Sistem içi bildirimler anında oluşturulur
- Hatalı email adresleri loglanır

### Güvenlik:
- Chat mesajları HTML olarak güvenli hale getirilir
- Email içeriği XSS korumalıdır
- Sadece dosya sahipleri ve adminler mesajlaşabilir

## 🆘 Sorun Giderme

### Email Gönderilmiyor:
1. SMTP ayarlarını kontrol edin
2. `logs/email_test.log` dosyasını inceleyin
3. Alıcının email doğrulama durumunu kontrol edin
4. Email tercihlerini kontrol edin

### Bildirimler Çalışmıyor:
1. `setup_chat_email_notifications.php` çalıştırıldı mı?
2. Veritabanı güncellemeleri tamamlandı mı?
3. ChatManager sınıfı doğru yükleniyor mu?

### Debug için:
```php
error_log("Chat email debug: recipient_id={$recipientId}, notifications={$chatNotifications}");
```

## 📞 Destek

Herhangi bir sorun durumunda:
1. Log dosyalarını inceleyin
2. Test script'ini çalıştırın  
3. Email ayarlarını doğrulayın
4. Veritabanı bağlantılarını kontrol edin

---

**🎉 Chat Email Bildirimleri Başarıyla Kuruldu!**

Artık kullanıcılar ve adminler chat mesajlaşırken hem sistem içi hem email bildirimleri alacaklar. 
Kullanıcılar email tercihlerinden bu bildirimleri istediği zaman açıp kapatabilir.
