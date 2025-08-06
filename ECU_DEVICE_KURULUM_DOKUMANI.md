# Mr ECU - ECU ve Device Yönetim Sistemi Kurulumu

Bu dokümantasyon, Mr ECU sistemine ECU ve device yönetimi özelliklerinin eklenmesi için yapılan tüm değişiklikleri içermektedir.

## 📋 Yapılan Değişiklikler

### 1. Database Tabloları

#### ECU Tablosu (`ecus`)
- **Konum:** `create_ecus_table.sql`
- **Yapı:**
  - `id` (CHAR(36), PRIMARY KEY, UUID)
  - `name` (VARCHAR(100), UNIQUE, NOT NULL)
  - `created_at` (TIMESTAMP)
  - `updated_at` (TIMESTAMP)
- **Veri:** 264 ECU tipi otomatik olarak eklendi

#### Device Tablosu (`devices`)
- **Konum:** `create_devices_table.sql`
- **Yapı:**
  - `id` (CHAR(36), PRIMARY KEY, UUID)
  - `name` (VARCHAR(100), UNIQUE, NOT NULL)
  - `created_at` (TIMESTAMP)
  - `updated_at` (TIMESTAMP)
- **Veri:** 22 tuning device'ı otomatik olarak eklendi

### 2. Model Sınıfları

#### EcuModel.php
- **Konum:** `includes/EcuModel.php`
- **Özellikler:**
  - ECU listesi getirme
  - ECU arama
  - ECU ekleme/düzenleme/silme
  - Sayfalı listeleme
  - GUID doğrulama

#### DeviceModel.php
- **Konum:** `includes/DeviceModel.php`
- **Özellikler:**
  - Device listesi getirme
  - Device arama
  - Device ekleme/düzenleme/silme
  - Sayfalı listeleme
  - GUID doğrulama

### 3. Admin Panel Sayfaları

#### ECU Yönetimi
- **Konum:** `admin/ecus.php`
- **Özellikler:**
  - ECU listesi görüntüleme
  - ECU ekleme/düzenleme/silme
  - Arama ve filtreleme
  - Sayfalama
  - Modal tabanlı işlemler

#### Device Yönetimi
- **Konum:** `admin/devices.php`
- **Özellikler:**
  - Device listesi görüntüleme
  - Device ekleme/düzenleme/silme
  - Arama ve filtreleme
  - Sayfalama
  - Modal tabanlı işlemler

### 4. AJAX API'ları

#### ECU API
- **Konum:** `admin/ajax/ecu-api.php`
- **Endpoints:**
  - `GET ?action=list` - ECU listesi
  - `GET ?action=get&id=X` - Tekil ECU
  - `POST action=add` - ECU ekleme
  - `POST action=update` - ECU güncelleme
  - `POST action=delete` - ECU silme
  - `GET ?action=search&term=X` - ECU arama
  - `GET ?action=stats` - İstatistikler

#### Device API
- **Konum:** `admin/ajax/device-api.php`
- **Endpoints:**
  - `GET ?action=list` - Device listesi
  - `GET ?action=get&id=X` - Tekil device
  - `POST action=add` - Device ekleme
  - `POST action=update` - Device güncelleme
  - `POST action=delete` - Device silme
  - `GET ?action=search&term=X` - Device arama
  - `GET ?action=stats` - İstatistikler

### 5. Kullanıcı Upload Sayfası Güncellemeleri

#### Dosya: `user/upload.php`
- **ECU Seçimi:** Dropdown menü eklendi
- **Device Seçimi:** Dropdown menü eklendi
- **Manuel ECU Girişi:** Yedek text alanı korundu
- **Form Özeti:** ECU ve device bilgileri eklendi
- **JavaScript:** Dinamik özet güncelleme

### 6. Admin Menü Güncellemeleri

#### Dosya: `includes/admin_sidebar.php`
- Sistem yönetimi bölümüne eklendi:
  - ECU Yönetimi linki
  - Device Yönetimi linki

### 7. Kurulum ve Test Dosyaları

#### Kurulum Scripti
- **Konum:** `install-ecu-device-tables.php`
- **Özellikler:**
  - Tabloları otomatik oluşturur
  - Verileri otomatik ekler
  - Web ve CLI desteği
  - Hata yönetimi

#### Test Sayfası
- **Konum:** `test-ecu-device-tables.php`
- **Özellikler:**
  - Tablo varlığı kontrolü
  - Veri sayısı gösterimi
  - Tablo yapısı analizi
  - API test linkleri
  - Admin panel linkleri

## 🚀 Kurulum Adımları

### 1. Tabloları Oluştur
```bash
# Web tarayıcıdan:
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/install-ecu-device-tables.php

# Veya MySQL'de manuel olarak:
mysql -u root -p mrecu_db_guid < create_ecus_table.sql
mysql -u root -p mrecu_db_guid < create_devices_table.sql
```

### 2. Test Et
```bash
# Test sayfasını ziyaret et:
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/test-ecu-device-tables.php
```

### 3. Admin Panelden Kontrol Et
- Admin panele giriş yap
- "Sistem > ECU Yönetimi" menüsüne git
- "Sistem > Device Yönetimi" menüsüne git

## 📊 Eklenen Veriler

### ECU Tipleri (264 adet)
- CRD11, CRD2X, CRD3X
- DCM serisi (DCM1.2, DCM3.4, vb.)
- EDC serisi (EDC15, EDC16, EDC17, vb.)
- MED serisi (MED17.2.3, MED17.5.20, vb.)
- SID serisi (SID202, SID206, vb.)
- SIMOS serisi (SIMOS10, SIMOS18, vb.)
- Ve daha fazlası...

### Tuning Device'ları (22 adet)
- Auto tuner Obd
- Autotuner Bench/Boot
- Flex serisi
- Galletto serisi
- Kess v2/v3
- KT200 serisi
- Ktag serisi
- Ve daha fazlası...

## 🔧 Kullanım

### Admin Tarafı
1. **ECU Yönetimi:** Admin > Sistem > ECU Yönetimi
2. **Device Yönetimi:** Admin > Sistem > Device Yönetimi
3. Her iki sayfada da ekleme, düzenleme, silme ve arama işlemleri yapılabilir

### Kullanıcı Tarafı
1. **Dosya Yükleme:** User Panel > Dosya Yükle
2. Form kısmında ECU ve device dropdown'ları kullanılabilir
3. Manuel ECU girişi hala mevcut (yedek olarak)

## 🛡️ Güvenlik Özellikleri

- **GUID Tabanlı ID'ler:** Tüm tablolar UUID kullanır
- **CSRF Koruması:** Tüm formlar CSRF token ile korunur
- **Input Sanitization:** Tüm veriler temizlenir
- **SQL İnjection Koruması:** PDO prepared statements kullanılır
- **Admin Yetki Kontrolü:** Admin sayfaları yetki kontrolü ile korunur

## 📁 Dosya Yapısı

```
/
├── admin/
│   ├── ecus.php              # ECU yönetim sayfası
│   ├── devices.php           # Device yönetim sayfası
│   └── ajax/
│       ├── ecu-api.php       # ECU AJAX API
│       └── device-api.php    # Device AJAX API
├── includes/
│   ├── EcuModel.php          # ECU model sınıfı
│   ├── DeviceModel.php       # Device model sınıfı
│   └── admin_sidebar.php     # Güncellenmiş admin menü
├── user/
│   └── upload.php            # Güncellenmiş upload sayfası
├── create_ecus_table.sql     # ECU tablosu SQL
├── create_devices_table.sql  # Device tablosu SQL
├── install-ecu-device-tables.php  # Kurulum scripti
└── test-ecu-device-tables.php     # Test sayfası
```

## ✅ Başarılı Kurulum Kontrolü

Kurulum başarılı ise:
- ✅ Admin panelde ECU ve Device menüleri görünür
- ✅ Test sayfasında yeşil onay işaretleri görünür
- ✅ Upload sayfasında ECU ve device dropdown'ları çalışır
- ✅ API endpoint'leri JSON döner

## 🔄 Yapılan İsimlendirme Değişiklikleri

### Eski → Yeni
- `cihazlar` → `devices` (tablo adı)
- `cihazlar.php` → `devices.php` (admin sayfası)
- `CihazModel.php` → `DeviceModel.php` (model sınıfı)
- `cihaz-api.php` → `device-api.php` (API dosyası)
- `create_cihazlar_table.sql` → `create_devices_table.sql`
- `install-ecu-cihaz-tables.php` → `install-ecu-device-tables.php`
- `test-ecu-cihaz-tables.php` → `test-ecu-device-tables.php`

### Form Alanları
- `cihaz_id` → `device_id` (HTML form field)
- `summary-cihaz` → `summary-device` (JavaScript element ID)

## 🔄 Gelecek Geliştirmeler

- [ ] ECU ve device arasında ilişki tablosu
- [ ] ECU özelliklerine göre filtreleme
- [ ] Device uyumluluğu kontrolü
- [ ] Bulk import/export özelliği
- [ ] API rate limiting
- [ ] Gelişmiş raporlama

---

**Not:** Bu sistem GUID tabanlı güvenli bir yapı kullanır ve mevcut Mr ECU sistemine tam entegre edilmiştir. Tüm isimlendirmeler İngilizce standardına uygun hale getirilmiştir.
