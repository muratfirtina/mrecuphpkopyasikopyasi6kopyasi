# Legacy Files System - Kurulum ve Test Rehberi

## ✅ Tamamlanan İşlemler

### 1. Dosya Sistemi
- ✅ `includes/LegacyFilesManager.php` - Ana yönetim sınıfı
- ✅ `database/legacy_files.sql` - Veritabanı tablosu
- ✅ `database/setup_legacy_files.sql` - Kurulum scripti
- ✅ `uploads/legacy_files/` - Upload klasörü

### 2. Admin Panel
- ✅ `admin/legacy-files.php` - Ana yönetim sayfası
- ✅ `admin/legacy-files-detail.php` - Detay sayfası  
- ✅ `admin/download-legacy-file.php` - Admin download
- ✅ `admin/setup-legacy-files.php` - Kurulum sayfası
- ✅ `admin/create-legacy-demo.php` - Demo veri oluşturma
- ✅ `admin/ajax/get-user-legacy-files.php` - Ajax API
- ✅ Admin sidebar'a menü eklendi

### 3. User Panel
- ✅ `user/legacy-files.php` - Kullanıcı dosyaları görüntüleme
- ✅ `user/download-legacy-file.php` - User download
- ✅ User sidebar'a menü eklendi

### 4. Veritabanı
- ✅ `legacy_files` tablosu tasarımı
- ✅ Foreign key ilişkileri
- ✅ Index'ler
- ✅ `config/database.php`'ye LegacyFilesManager include'u eklendi

### 5. Dokümantasyon
- ✅ `LEGACY_FILES_README.md` - Detaylı dokümantasyon

## 🚀 Test Adımları

### 1. Sistem Kurulumu
```bash
# 1. Admin panelinde setup sayfasını açın:
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/admin/setup-legacy-files.php

# 2. "Legacy Files Sistemini Kur" butonuna tıklayın
# 3. Yeşil "Sistem Hazır!" mesajını bekleyin
```

### 2. Demo Veri Oluşturma
```bash
# 1. Demo veri sayfasını açın:
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/admin/create-legacy-demo.php

# 2. "Demo Veri Oluştur" butonuna tıklayın
# 3. Demo dosyalarının oluşturulduğunu kontrol edin
```

### 3. Admin Panel Testi
```bash
# 1. Admin olarak giriş yapın
# 2. Sidebar'da "Dosya Yönetimi > Eski Dosyalar" menüsüne tıklayın
# 3. İstatistikleri kontrol edin
# 4. Kullanıcı seçip yeni dosya yüklemeyi test edin
# 5. Mevcut dosyaları görüntüleme ve silme işlemlerini test edin
```

### 4. User Panel Testi
```bash
# 1. Normal kullanıcı olarak giriş yapın
# 2. Sidebar'da "Dosya İşlemleri > Eski Dosyalarım" menüsüne tıklayın
# 3. Plaka gruplarını görüntüleyin
# 4. Bir plakaya tıklayıp dosyaları görün
# 5. Dosya indirme işlemini test edin
```

## 🔧 Konfigürasyon Kontrolleri

### 1. Veritabanı Kontrolü
```sql
-- Tablo oluşturuldu mu?
SHOW TABLES LIKE 'legacy_files';

-- Demo veriler var mı?
SELECT COUNT(*) FROM legacy_files;
SELECT user_id, plate_number, COUNT(*) FROM legacy_files GROUP BY user_id, plate_number;
```

### 2. Dosya Sistemi Kontrolü
```bash
# Upload klasörü var mı ve yazılabilir mi?
ls -la uploads/legacy_files/

# Demo dosyalar oluşturuldu mu?
find uploads/legacy_files/ -type f -name "*.bin" -o -name "*.pdf" -o -name "*.jpg"
```

### 3. Permission Kontrolü
```bash
# Klasör izinleri
chmod 755 uploads/legacy_files/

# Alt klasörler için (gerekirse)
find uploads/legacy_files/ -type d -exec chmod 755 {} \;
find uploads/legacy_files/ -type f -exec chmod 644 {} \;
```

## 🐛 Olası Sorunlar ve Çözümleri

### 1. "Class 'LegacyFilesManager' not found"
**Çözüm:** `config/database.php` dosyasında include kontrolü
```php
require_once __DIR__ . '/../includes/LegacyFilesManager.php';
```

### 2. "Table 'legacy_files' doesn't exist"
**Çözüm:** Setup sayfasını çalıştırın veya SQL'i manuel çalıştırın
```bash
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/admin/setup-legacy-files.php
```

### 3. Dosya Upload Hatası
**Çözüm:** Klasör izinlerini kontrol edin
```bash
chmod -R 755 uploads/legacy_files/
chown -R www-data:www-data uploads/legacy_files/  # Linux için
```

### 4. Sidebar'da Menü Görünmüyor
**Çözüm:** Cache temizleme ve session yenileme
```php
// Tarayıcı cache'ini temizleyin
// Çıkış yapıp tekrar giriş yapın
```

### 5. Ajax Hataları
**Çözüm:** Browser Developer Tools'da Network tab'ını kontrol edin
```javascript
// 404 hataları için path kontrol edin
// PHP hataları için server loglarını kontrol edin
```

## 📋 Test Checklist

### Admin Panel ✅
- [ ] Setup sayfası çalışıyor
- [ ] Demo veri oluşturuluyor
- [ ] Eski Dosyalar menüsü görünüyor
- [ ] İstatistikler doğru gösteriliyor
- [ ] Kullanıcı seçimi çalışıyor
- [ ] Dosya yükleme çalışıyor
- [ ] Ajax dosya listeleme çalışıyor
- [ ] Dosya silme çalışıyor
- [ ] Dosya indirme çalışıyor

### User Panel ✅
- [ ] Eski Dosyalarım menüsü görünüyor
- [ ] Plaka grupları listeleniyor
- [ ] Dosya detayları görünüyor
- [ ] Dosya indirme çalışıyor
- [ ] Resim önizleme çalışıyor (varsa)
- [ ] Sayfa responsive

### Güvenlik ✅
- [ ] Kullanıcılar sadece kendi dosyalarını görebiliyor
- [ ] Admin yetkisi kontrolü çalışıyor
- [ ] Dosya path'leri güvenli
- [ ] Log kayıtları tutruluyor

## 🎯 Kullanım Senaryoları

### Senaryo 1: Yeni Kullanıcıya Dosya Yükleme
1. Admin panel > Eski Dosyalar
2. Kullanıcı seç
3. Plaka gir (örn: 34ABC123)
4. Dosyaları seç ve yükle
5. Kullanıcı panelinde kontrol et

### Senaryo 2: Kullanıcı Dosya İndirme
1. User olarak giriş yap
2. Eski Dosyalarım > Plaka seç
3. Dosyaya tıkla ve indir
4. Log kayıtlarını kontrol et

### Senaryo 3: Toplu Dosya Yönetimi
1. Admin panel > Kullanıcılar tablosunu kullan
2. "Detay" butonlarıyla kullanıcı dosyalarını yönet
3. Gerekirse dosya sil
4. İstatistikleri takip et

## 📞 Destek

Sorun yaşadığınızda:
1. Browser Console'u kontrol edin (F12)
2. Server error loglarını kontrol edin
3. Database connection'ı test edin
4. File permissions'ları kontrol edin
5. README.md dosyasını tekrar okuyun

---

**Not:** Bu sistem production-ready'dir ancak büyük dosyalar için upload limits ve güvenlik ayarlarını gözden geçirin.
