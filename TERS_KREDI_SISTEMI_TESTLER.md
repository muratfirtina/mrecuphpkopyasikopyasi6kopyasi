# 🧪 TERS KREDİ SİSTEMİ - TEST SENARYOLARI

## 📋 Test Checklist

### 1. 🗄️ Database Migration Tests

- [ ] **Migration Script Çalıştırma**
  ```sql
  -- Migration scriptini çalıştır
  SOURCE /Applications/MAMP/htdocs/mrecuphpkopyasikopyasi6kopyasi/TERS_KREDI_SISTEMI_MIGRATION.sql
  ```

- [ ] **Kolon Kontrolü**
  ```sql
  DESCRIBE users;
  -- credit_quota ve credit_used kolonları eklenmiş mi?
  ```

- [ ] **Veri Transferi Kontrolü**
  ```sql
  SELECT username, credits, credit_quota, credit_used, (credit_quota - credit_used) as available 
  FROM users WHERE role = 'user';
  ```

### 2. 👨‍💼 Admin Panel Tests

#### Kredi Yönetimi Sayfası (`admin/credits.php`)

- [ ] **İstatistik Kartları**
  - [ ] Toplam Kredi Kotası gösteriliyor
  - [ ] Kullanılan Krediler gösteriliyor  
  - [ ] Kullanılabilir Krediler gösteriliyor
  - [ ] Aktif Kullanıcı sayısı gösteriliyor

- [ ] **Kullanıcı Tablosu**
  - [ ] Kota, Kullanılan, Kullanılabilir kredi kolonları görüntüleniyor
  - [ ] Progress bar kullanım yüzdesini gösteriyor
  - [ ] "Kota +" butonu kredi kotası artırıyor
  - [ ] "İade" butonu kullanılan krediyi azaltıyor

- [ ] **Kredi Kotası Artırma**
  ```
  Test Adımları:
  1. Bir kullanıcıya 500 TL kota artır
  2. Kontrol: credit_quota arttı mı?
  3. Kontrol: Transaction kaydı oluştu mu?
  4. Kontrol: Başarı mesajı gösteriliyor mu?
  ```

- [ ] **Kredi İadesi**
  ```
  Test Adımları:
  1. Kullanılan kredisi olan kullanıcıdan 100 TL iade al
  2. Kontrol: credit_used azaldı mı?
  3. Kontrol: Kullanılabilir kredi arttı mı?
  4. Kontrol: Hata: yetersiz kullanılan kredi durumu
  ```

#### Dosya Detay Sayfası (`admin/file-detail.php`)

- [ ] **Yanıt Dosyası Yükleme**
  ```
  Test Adımları:
  1. Pending durumundaki bir dosyaya yanıt yükle
  2. 150 TL kredi belirle
  3. Kontrol: Kullanıcının credit_used değeri arttı mı?
  4. Kontrol: Kredi limiti aşım kontrolü çalışıyor mu?
  ```

- [ ] **Kredi Limit Kontrolü**
  ```
  Test Adımları:
  1. Kullanıcının kalan kredisinden fazla kredi belirle
  2. Kontrol: "Kredi limiti aşılacak" hatası alınıyor mu?
  3. Kontrol: Dosya yüklenmedi mi?
  ```

### 3. 👤 Kullanıcı Panel Tests

#### Kredi Sayfası (`user/credits.php`)

- [ ] **Kredi Durumu Görüntüleme**
  - [ ] Kredi Kotası gösteriliyor
  - [ ] Kullanılan Kredi gösteriliyor
  - [ ] Kullanılabilir Kredi gösteriliyor
  - [ ] Progress bar doğru çalışıyor

#### Dosya Yükleme (`user/upload.php`)

- [ ] **Kredi Kontrollü Yükleme**
  ```
  Test Adımları:
  1. Kredi kotası olan kullanıcı ile dosya yükle
  2. Kontrol: Yükleme başarılı mı?
  3. Kredi kotası 0 olan kullanıcı ile test et
  4. Kontrol: Yine de yükleme yapabiliyor mu?
  ```

- [ ] **Kredi Limit Aşımı**
  ```
  Test Adımları:
  1. Kredi kotası dolmuş kullanıcı ile yükleme dene
  2. Kontrol: "Kredi limitinizi aştınız" hatası alınıyor mu?
  3. Kontrol: Dosya yüklenmedi mi?
  ```

### 4. 🔄 Sistem Entegrasyon Tests

#### Session Yönetimi

- [ ] **CreditSync Sınıfı**
  ```php
  // Test kodu
  $creditSync = new CreditSync($pdo);
  $result = $creditSync->refreshUserCredits($userId);
  // Kontrol: $_SESSION['credits'] kullanılabilir kredi mi?
  ```

#### FileManager Sınıfı

- [ ] **uploadResponseFile Metodu**
  ```php
  // Test parametreleri
  $uploadId = 'test-upload-id';
  $creditsCharged = 200;
  $result = $fileManager->uploadResponseFile($uploadId, $file, $creditsCharged, 'Test');
  // Kontrol: Kredi limit kontrolü çalışıyor mu?
  ```

- [ ] **uploadUserFile Metodu**
  ```php
  // Test parametreleri
  $result = $fileManager->uploadUserFile($userId, $_FILES['test'], $_POST);
  // Kontrol: Kredi kontrolü yapılıyor mu?
  ```

#### User Sınıfı

- [ ] **canUserUploadFile Metodu**
  ```php
  $result = $user->canUserUploadFile($userId, 300);
  // Kontrol: Limit kontrolü doğru çalışıyor mu?
  ```

### 5. 📊 Performans Tests

- [ ] **Database Sorgu Performansı**
  ```sql
  EXPLAIN SELECT credit_quota, credit_used, (credit_quota - credit_used) as available 
  FROM users WHERE id = 'test-user-id';
  ```

- [ ] **Index Kullanımı**
  ```sql
  SHOW INDEX FROM users WHERE Column_name IN ('credit_quota', 'credit_used');
  ```

### 6. 🔒 Güvenlik Tests

- [ ] **Negatif Kredi Kontrolü**
  - [ ] credit_used negatif olamaz
  - [ ] credit_quota negatif olamaz
  - [ ] Kredi limitini aşma engelleniyor

- [ ] **SQL Injection Koruması**
  - [ ] Tüm kullanıcı inputları sanitize ediliyor
  - [ ] Prepared statements kullanılıyor

- [ ] **Yetki Kontrolleri**
  - [ ] Sadece admin kredi kotası artırabilir
  - [ ] Sadece admin kredi iadesi yapabilir
  - [ ] Kullanıcı kendi kredi durumunu görebilir

### 7. 🎯 Business Logic Tests

#### Senaryo 1: Normal Kullanım
```
1. Admin kullanıcıya 1000 TL kota verir
2. Kullanıcı dosya yükler
3. Admin 100 TL'lik yanıt dosyası yükler
4. Kontrol: Kullanılabilir kredi 900 TL oldu mu?
```

#### Senaryo 2: Limit Aşımı
```
1. Kullanıcının 50 TL kullanılabilir kredisi var
2. Admin 100 TL'lik yanıt yüklemeye çalışır
3. Kontrol: "Kredi limiti aşılacak" hatası alınıyor mu?
```

#### Senaryo 3: Kredi İadesi
```
1. Kullanıcının 300 TL kullanılan kredisi var
2. Admin 100 TL kredi iadesi yapar
3. Kontrol: Kullanılan kredi 200 TL oldu mu?
4. Kontrol: Kullanılabilir kredi arttı mı?
```

### 8. 🚨 Edge Case Tests

- [ ] **Sıfır Kredi Kotası**
  - [ ] Kota 0 olan kullanıcı dosya yükleyebilir mi?
  - [ ] Sistem çökmüyor mu?

- [ ] **Büyük Kredi Miktarları**
  - [ ] 999999.99 TL kredi işlemi yapılabiliyor mu?
  - [ ] Decimal hassasiyeti korunuyor mu?

- [ ] **Eşzamanlı İşlemler**
  - [ ] Aynı anda iki admin aynı kullanıcıya kredi verirse?
  - [ ] Database transaction kontrolü çalışıyor mu?

### 9. 📱 UI/UX Tests

- [ ] **Responsive Tasarım**
  - [ ] Mobilde kredi tablosu düzgün gösteriliyor
  - [ ] Progress barlar responsive

- [ ] **Kullanıcı Deneyimi**
  - [ ] Hata mesajları anlaşılır
  - [ ] Başarı mesajları bilgilendirici
  - [ ] Loading durumları gösteriliyor

### 10. 🔄 Regression Tests

- [ ] **Eski Özellikler**
  - [ ] Dosya indirme çalışıyor
  - [ ] Revizyon sistemi çalışıyor
  - [ ] Notification sistemi çalışıyor

- [ ] **Mevcut Datalar**
  - [ ] Eski kullanıcı verileri korundu
  - [ ] Eski dosyalar erişilebilir
  - [ ] Transaction geçmişi korundu

## 🎯 Test Sonuçları

### Başarı Kriterleri
- [ ] Tüm testler başarılı
- [ ] Performans degradasyonu yok
- [ ] Güvenlik açığı yok
- [ ] Kullanıcı deneyimi pozitif

### Test Raporu Şablonu
```
Test Tarihi: [TARIH]
Test Eden: [İSİM]
Test Ortamı: [LOCAL/STAGING/PRODUCTION]

✅ Başarılı Testler: [SAYI]
❌ Başarısız Testler: [SAYI]
⚠️ Kısmi Başarılı: [SAYI]

Kritik Sorunlar:
- [SORUN 1]
- [SORUN 2]

Öneriler:
- [ÖNERİ 1]
- [ÖNERİ 2]
```

## 🚀 Canlıya Alma Checklist

- [ ] Tüm testler geçti
- [ ] Database backup alındı
- [ ] Migration script test edildi
- [ ] Dokümantasyon tamamlandı
- [ ] Kullanıcılar bilgilendirildi
- [ ] Rollback planı hazır

---
**Not:** Bu test listesi kapsamlı olarak ters kredi sisteminin tüm bileşenlerini test eder. Her test öncesi database backup almayı unutmayın.
