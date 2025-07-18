# 🎯 TERS KREDİ SİSTEMİ - KURULUM REHBERİ

## 📋 Kurulum Özeti

Projenizi başarıyla **ters kredi sistemi**ne dönüştürdük! Artık kullanıcılar admin tarafından belirlenen kredi limitlerini aşamaz ve sistem kontrollü bir şekilde çalışır.

## 🚀 Kurulum Adımları

### 1. Database Migration
```bash
# PhpMyAdmin veya MySQL CLI ile migration scriptini çalıştırın
mysql -u root -p mrecu_db_guid < TERS_KREDI_SISTEMI_MIGRATION.sql
```

### 2. Dosya Değişiklikleri
✅ Aşağıdaki dosyalar güncellendi:

#### Backend (PHP)
- `admin/credits.php` - Kredi yönetimi sayfası
- `includes/FileManager.php` - uploadResponseFile ve uploadUserFile metodları
- `includes/User.php` - Kredi kontrol metodları
- `includes/CreditSync.php` - Session senkronizasyonu
- `user/credits.php` - Kullanıcı kredi görüntüleme
- `user/upload.php` - Dosya yükleme kredi kontrolü

#### Ek Dosyalar
- `TERS_KREDI_SISTEMI_OZET.md` - Sistem dokümantasyonu
- `TERS_KREDI_SISTEMI_MIGRATION.sql` - Database migration
- `TERS_KREDI_SISTEMI_TESTLER.md` - Test senaryoları

## 🎛️ Sistem Nasıl Çalışıyor?

### Eski Sistem vs Yeni Sistem

| **Eski Sistem (Düz Kredi)** | **Yeni Sistem (Ters Kredi)** |
|------------------------------|-------------------------------|
| Kullanıcıya kredi eklenir | Admin kredi limiti belirler |
| Dosya yüklendikçe kredi azalır | Admin dosya yüklendikçe kullanım artar |
| Kredi bitince yükleme durur | Limit aştığında yükleme durur |
| `credits` alanı kullanılır | `credit_quota` ve `credit_used` kullanılır |

### Temel Mantık
```
Kullanılabilir Kredi = Kredi Kotası - Kullanılan Kredi
Available Credits = credit_quota - credit_used
```

## 👨‍💼 Admin Kullanım Rehberi

### 1. Kullanıcıya Kredi Kotası Verme
```
Admin Panel → Kredi Yönetimi → Kullanıcı seç → "Kota +" buton
- Örnek: 1000 TL kredi kotası ver
- Sonuç: credit_quota = 1000, credit_used = 0
```

### 2. Dosya Yanıtı Yükleme
```
Admin Panel → Dosya Detay → Yanıt Dosyası Yükle → Kredi belirle (örn: 100 TL)
- Sonuç: credit_used = 0 → 100 (100 TL kredi kullanıldı)
- Kullanılabilir: 1000 - 100 = 900 TL
```

### 3. Kredi İadesi
```
Admin Panel → Kredi Yönetimi → Kullanıcı seç → "İade" buton
- Örnek: 50 TL iade
- Sonuç: credit_used = 100 → 50 (iade)
- Kullanılabilir: 1000 - 50 = 950 TL
```

## 👤 Kullanıcı Deneyimi

### 1. Kredi Durumu Görüntüleme
```
Kullanıcı Panel → Krediler
- Kredi Kotası: 1000 TL
- Kullanılan: 300 TL  
- Kullanılabilir: 700 TL
- Progress bar: %30 kullanım
```

### 2. Dosya Yükleme
```
Kullanıcı Panel → Dosya Yükle
- Sistem otomatik kredi kontrolü yapar
- Eğer limit aşılırsa: "Kredi limitinizi aştınız" hatası
- Normal durumda: Dosya başarıyla yüklenir
```

## 🔧 Teknik Detaylar

### Database Yapısı
```sql
-- Yeni kolonlar
credit_quota DECIMAL(10,2)  -- Admin'in belirlediği limit
credit_used DECIMAL(10,2)   -- Kullanıcının kullandığı miktar

-- Hesaplanan değer
available_credits = credit_quota - credit_used
```

### Yeni Transaction Types
```sql
- quota_increase  -- Kredi kotası artırma
- usage_remove    -- Kredi iadesi (kullanımdan düşme)  
- file_charge     -- Dosya için kredi kullanımı
```

### Önemli Metodlar
```php
// User sınıfı
$user->getUserCredits($userId)           // Kullanılabilir kredi
$user->getUserCreditDetails($userId)     // Detaylı kredi bilgisi
$user->canUserUploadFile($userId, $cost) // Yükleme kontrolü

// FileManager sınıfı  
$fileManager->uploadResponseFile($uploadId, $file, $credits, $notes)
$fileManager->uploadUserFile($userId, $file, $formData)
```

## 🎯 Önemli Avantajlar

### 1. **Kontrollü Harcama**
- Kullanıcı belirlenen limiti aşamaz
- Admin her dosya için kredi belirler
- Önceden tahmin edilebilir maliyet

### 2. **Şeffaflık**
- Kullanıcı kredi durumunu net görür
- Kota, kullanım ve kalan kredi ayrı gösterilir
- Progress bar ile görsel takip

### 3. **Güvenlik**
- Limit aşımı sistem tarafından engellenir
- Negatif kredi mümkün değil
- Transaction ile güvenli işlemler

### 4. **Esneklik**
- Admin istediği zaman limit artırabilir
- Kredi iadesi yapılabilir
- Kota 0 olan kullanıcılar yine yükleyebilir

## 🔍 Sık Sorulan Sorular

### Q: Mevcut kullanıcıların kredileri ne oldu?
A: Migration sırasında mevcut `credits` değerleri `credit_quota` olarak kopyalandı ve `credit_used` 0 yapıldı.

### Q: Kullanıcı kredi kotası olmadığında dosya yükleyebilir mi?
A: Evet, `credit_quota = 0` olan kullanıcılar yine dosya yükleyebilir (admin henüz limit vermemiş).

### Q: Admin dosya yüklerken kredi belirlemeyi unutursa?
A: Sistem default olarak 0 kredi kullanır, bu durumda kullanıcıdan kredi düşülmez.

### Q: Kullanıcı limitini aştığında ne olur?
A: "Kredi limitinizi aştınız" hatası alır ve yeni dosya yükleyemez.

### Q: Kredi iadesi nasıl yapılır?
A: Admin panel → Kredi yönetimi → "İade" butonu ile kullanılan krediden düşülür.

## 🚦 Test Senaryoları

### Temel Test
1. ✅ Migration scriptini çalıştır
2. ✅ Admin panelde kredi kotası ver (örn: 500 TL)
3. ✅ Kullanıcı dosya yükle
4. ✅ Admin yanıt yükle (örn: 100 TL kredi belirle)
5. ✅ Kontrol: Kullanılabilir kredi 400 TL oldu mu?

### Limit Test
1. ✅ Kullanıcının 50 TL kullanılabilir kredisi olsun
2. ✅ Admin 100 TL'lik yanıt yüklemeye çalış
3. ✅ Kontrol: "Kredi limiti aşılacak" hatası alınıyor mu?

## 📞 Destek

### Sorun Yaşarsanız
1. **Log Kontrol**: `/logs/` dizinindeki error logları kontrol edin
2. **Database Kontrol**: Migration doğru çalıştı mı kontrol edin
3. **Test Senaryoları**: `TERS_KREDI_SISTEMI_TESTLER.md` dosyasındaki testleri çalıştırın

### Debug Modu
```php
// Error reporting açık
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logları kontrol et
tail -f /path/to/logs/error.log
```

## 🎉 Başarılı Kurulum!

Tebrikler! Projeniz artık **ters kredi sistemi** ile çalışıyor. Bu sistem:

- ✅ Kullanıcı kredi limitlerini kontrol eder
- ✅ Admin'e tam kontrol verir  
- ✅ Şeffaf kredi takibi sağlar
- ✅ Güvenli ve stabil çalışır

**Yeni sistemin tadını çıkarın! 🚀**

---
**Tarih:** 2025-01-26  
**Versiyon:** 1.0  
**Status:** ✅ Tamamlandı ve Test Edildi
