# 🔄 TERS KREDİ SİSTEMİ - KURULUM VE KULLANIM REHBERİ

## 📊 Sistem Özeti

**Eski Sistem (Düz Kredi):**
- Kullanıcıya kredi eklenir
- Dosya yüklendikçe kredi azalır
- Kredi bitince yükleme durur

**Yeni Sistem (Ters Kredi Sayacı):**
- Admin kullanıcıya kredi limiti/kotası belirler
- Kullanıcı başlangıçta 0 kredi kullanmış olur
- Admin dosya yükledikçe kullanılan kredi artar
- Kullanılan kredi = Kredi limiti olduğunda artık yükleme yapılamaz

## 🗄️ Database Alanları

| Alan | Açıklama | Örnek |
|------|----------|-------|
| `credit_quota` | Admin'in belirlediği kredi limiti | 1000.00 TL |
| `credit_used` | Kullanıcının şu ana kadar kullandığı kredi | 300.00 TL |
| Kullanılabilir Kredi | `credit_quota - credit_used` | 700.00 TL |

## 🔄 Yapılan Değişiklikler

### 1. Admin Kredi Yönetimi (`admin/credits.php`)

**✅ Kredi Ekleme İşlemi:**
- Eski: `credits` alanına ekleme yapılıyordu
- Yeni: `credit_quota` alanına ekleme yapılıyor
- Transaction type: `quota_increase`

**✅ Kredi Düşürme İşlemi:**
- Eski: `credits` alanından çıkarma yapılıyordu  
- Yeni: `credit_used` alanından çıkarma yapılıyor (iade)
- Transaction type: `usage_remove`

**✅ İstatistik Kartları:**
- Toplam Kredi Kotası
- Kullanılan Krediler
- Kullanılabilir Krediler
- Aktif Kullanıcılar

### 2. FileManager Sınıfı (`includes/FileManager.php`)

**✅ uploadResponseFile Metodu:**
```php
// Kredi limit kontrolü
if ($newCreditUsed > $userCredit['credit_quota']) {
    return ['success' => false, 'message' => 'Kredi limiti aşılacak!'];
}

// Kullanılan krediyi artır
$stmt = $pdo->prepare("UPDATE users SET credit_used = ? WHERE id = ?");
$stmt->execute([$newCreditUsed, $upload['user_id']]);
```

### 3. User Sınıfı (`includes/User.php`)

**✅ Yeni Metodlar:**
- `getUserCredits($userId)` - Kullanılabilir kredi hesaplar
- `getUserCreditDetails($userId)` - Detaylı kredi bilgileri
- `canUserUploadFile($userId, $estimatedCredits)` - Yükleme kontrol

### 4. CreditSync Sınıfı (`includes/CreditSync.php`)

**✅ Session Güncelleme:**
```php
// Kullanılabilir kredi hesapla
$availableCredits = $result['credit_quota'] - $result['credit_used'];
$_SESSION['credits'] = $availableCredits;
$_SESSION['credit_quota'] = $result['credit_quota'];
$_SESSION['credit_used'] = $result['credit_used'];
```

### 5. Kullanıcı Kredi Sayfası (`user/credits.php`)

**✅ Kredi Durumu Görüntüleme:**
```php
// Ters kredi sistemi bilgileri
$creditQuota = $userCreditInfo['credit_quota'] ?? 0;
$creditUsed = $userCreditInfo['credit_used'] ?? 0;
$availableCredits = $creditQuota - $creditUsed;
```

## 🎯 Kullanım Senaryosu

### Admin Tarafı:
1. **Kredi Kotası Belirleme:**
   - Kullanıcıya 1000 TL kredi kotası verir
   - `credit_quota = 1000, credit_used = 0`

2. **Dosya Yükleme:**
   - Admin dosya yüklerken 100 TL kredi belirler
   - `credit_used = 0 → 100`
   - Kullanılabilir kredi: `1000 - 100 = 900 TL`

3. **Limit Kontrolü:**
   - Kullanıcı 1000 TL limitine ulaştığında
   - `credit_used = 1000` olur
   - Artık yeni dosya yüklenemez

### Kullanıcı Tarafı:
1. **Kredi Durumu:**
   - Kredi Kotası: 1000 TL
   - Kullanılan: 300 TL  
   - Kullanılabilir: 700 TL

2. **Yükleme Kontrolü:**
   - Sistem otomatik kontrol eder
   - Limit aşılırsa yükleme reddedilir

## 🔧 Teknik Notlar

### Transaction Types:
- `quota_increase` - Admin kredi kotası artırması
- `usage_remove` - Admin kredi iadesi  
- `file_charge` - Dosya için kredi kullanımı

### Uyumluluk:
- Eski sistem ile uyumluluk için `$_SESSION['credits']` kullanılabilir kredi olarak güncellenir
- Mevcut frontend kodları çalışmaya devam eder

### Güvenlik:
- Kredi kontrolü FileManager sınıfında yapılır
- Transaction ile güvenli güncelleme
- Error handling ve rollback mekanizması

## 🚀 Test Edilmesi Gerekenler

1. **Admin Panel:**
   - [ ] Kredi kotası artırma
   - [ ] Kredi iadesi (kullanımdan düşme)
   - [ ] İstatistik kartları görüntüleme
   - [ ] Kullanıcı listesi kredi durumları

2. **Dosya Yükleme:**
   - [ ] Admin dosya yükleme + kredi düşme
   - [ ] Limit aşımı kontrolü
   - [ ] Hata mesajları

3. **Kullanıcı Panel:**
   - [ ] Kredi durumu görüntüleme
   - [ ] Session güncellemesi
   - [ ] Dosya yükleme kısıtlaması

## 💡 Avantajları

1. **Kontrollü Kullanım:** Admin her dosya için kredi belirler
2. **Ön Ödemeli Model:** Kullanıcı limitini aşamaz  
3. **Şeffaflık:** Kullanıcı kredi durumunu net görür
4. **Güvenlik:** Limit aşımı sistem tarafından engellenir
5. **Esneklik:** Admin istediği zaman limit artırabilir

## 🔄 Geçiş Süreci

Mevcut sistemden yeni sisteme geçiş için:

1. Mevcut `credits` değerlerini `credit_quota` olarak kopyala
2. `credit_used` değerlerini 0 olarak ayarla
3. Sistem testlerini yap
4. Kullanıcıları bilgilendir

---
**Tarih:** 2025-01-26  
**Versiyon:** 1.0  
**Status:** ✅ Tamamlandı
