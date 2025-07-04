# 🚀 MR.ECU Legacy Migration System

## 📋 Proje Özeti
Bu sistem, SQL Server'da çalışan aktif MR.ECU sistemindeki verileri yeni MySQL GUID tabanlı sisteme güvenli bir şekilde geçirmek için geliştirilmiştir.

## 🎯 Ana Özellikler

### ✅ Tamamlanan Özellikler
- **GUID Tabanlı MySQL Veritabanı**: UUID/GUID primary key'ler ile güvenli sistem
- **Kapsamlı Migration Sistemi**: SQL Server'dan MySQL'e veri aktarımı
- **Web Tabanlı Interface**: Kullanıcı dostu migration arayüzü
- **Sample Data Generator**: Test için örnek veriler
- **Real-time Progress Tracking**: Canlı migration takibi
- **Automatic Mapping**: Brand/Model/User otomatik eşleştirme
- **Error Handling**: Gelişmiş hata yönetimi
- **System Health Check**: Sistem durumu kontrolü

## 📁 Dosya Yapısı

```
mrecuphpkopyasikopyasi6/
├── migration-dashboard.php          # Ana migration kontrol paneli
├── legacy-migration-interface.php   # CSV import arayüzü
├── legacy-data-converter.php        # SQL Server query'leri
├── sample-data-generator.php        # Test verileri oluşturucu
├── ajax-migration-handler.php       # AJAX işleyici
├── config/
│   ├── database.php                 # Database bağlantısı
│   ├── legacy-data-migration.php    # Migration sınıfı
│   └── install-guid.php            # GUID veritabanı kurulumu
├── includes/
│   ├── functions.php               # Yardımcı fonksiyonlar
│   ├── User.php                    # GUID kullanıcı sınıfı
│   └── FileManager.php             # Dosya yönetimi
└── sample_data/                    # Oluşturulan test CSV'leri
```

## 🚀 Kurulum ve Kullanım

### 1. Sistem Hazırlığı
```bash
# MAMP/XAMPP'ı başlatın
# MySQL ve PHP'nin çalıştığından emin olun
# Proje klasörünü htdocs'a yerleştirin
```

### 2. Database Kurulumu
1. `http://localhost:8889/mrecuphpkopyasikopyasi6/config/install-guid.php` adresine gidin
2. GUID tabanlı MySQL veritabanını kurun
3. Admin kullanıcısı otomatik oluşturulur (admin/admin123)

### 3. Migration Süreci

#### Adım 1: Migration Dashboard
```
http://localhost:8889/mrecuphpkopyasikopyasi6/migration-dashboard.php
```
- Sistem durumunu kontrol edin
- İstatistikleri görüntüleyin
- Migration araçlarına erişin

#### Adım 2: Data Converter
```
http://localhost:8889/mrecuphpkopyasikopyasi6/legacy-data-converter.php
```
- SQL Server query'lerini kopyalayın
- SQL Server Management Studio'da çalıştırın
- CSV dosyalarını export edin

#### Adım 3: Sample Data (Test İçin)
```
http://localhost:8889/mrecuphpkopyasikopyasi6/sample-data-generator.php
```
- Test verileri oluşturun
- Migration'ı test edin

#### Adım 4: Migration Interface
```
http://localhost:8889/mrecuphpkopyasikopyasi6/legacy-migration-interface.php
```
- CSV dosyalarını yükleyin
- Migration işlemini başlatın
- Progress'i takip edin

### 4. Test ve Doğrulama
```
http://localhost:8889/mrecuphpkopyasikopyasi6/final-guid-migration-complete.php
```
- Sistem testlerini çalıştırın
- GUID işlevselliğini doğrulayın

## 📊 Desteklenen Veriler

### Users Tablosu
- **Kaynak**: SQL Server `mrecu_123.Users`
- **Hedef**: MySQL `mrecu_db_guid.users`
- **Mapping**: 
  - `UserType` → `role` (Admin/User → admin/user)
  - `Wallet` → `credits` + `wallet`
  - `IsConfirm` → `email_verified` + `is_confirm`

### Files Tablosu
- **Kaynak**: SQL Server `mrecu_123.Files`
- **Hedef**: MySQL `mrecu_db_guid.file_uploads`
- **Mapping**:
  - Brand/Model strings → GUID references
  - Status numeric → enum values
  - TransmissionType → gearbox_type enum

### Tickets Sistemi
- **Kaynak**: `Ticket`, `TicketAdmin`, `TicketUser`
- **Hedef**: `legacy_tickets`, `legacy_ticket_admin`, `legacy_ticket_user`

### Wallet Log
- **Kaynak**: SQL Server `WalletLog`
- **Hedef**: MySQL `credit_transactions`

## 🔧 Teknik Detaylar

### GUID/UUID Sistem
- Primary key olarak 36 karakter UUID kullanır
- `generateUUID()` fonksiyonu ile oluşturulur
- SQL injection'a karşı daha güvenli
- Brute force saldırılarına karşı koruma

### Database Schema
```sql
-- Örnek GUID tablosu
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,           -- UUID format
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE,
    role ENUM('user', 'admin'),
    credits DECIMAL(10,2),
    -- ...
);
```

### Migration Sınıfı
```php
class LegacyDataMigration {
    public function addMissingColumns()      // Eksik alanları ekle
    public function createLegacyTables()     // Legacy tabloları oluştur
    public function createMappingTables()    // Mapping tabloları
    public function importUsersFromCSV()     // Users import
    public function importFilesFromCSV()     // Files import
    // ...
}
```

## ⚙️ Konfigürasyon

### Database Ayarları
```php
// config/database.php
$host = '127.0.0.1';
$port = '8889';                    // MAMP default
$db_name = 'mrecu_db_guid';       // GUID database
$username = 'root';
$password = 'root';               // MAMP default
```

### Sistem Sabitleri
```php
// includes/functions.php
define('DEFAULT_CREDITS', 0);
define('SITE_NAME', 'MR.ECU');
define('MAX_FILE_SIZE', 52428800); // 50MB
```

## 🛡️ Güvenlik Özellikleri

- **CSRF Protection**: Token tabanlı koruma
- **XSS Prevention**: Input sanitization
- **SQL Injection**: Prepared statements
- **File Upload Security**: Type ve size kontrolü
- **Rate Limiting**: API endpoint koruması
- **IP Whitelist/Blacklist**: Erişim kontrolü

## 📈 Performans

- **Batch Processing**: Büyük veri setleri için batch import
- **Memory Management**: Memory limit optimizasyonu
- **Index Optimization**: Database indeksleri
- **AJAX Progress**: Non-blocking UI

## 🚨 Önemli Notlar

### ⚠️ Dikkat Edilmesi Gerekenler
1. **Backup**: Migration öncesi mutlaka backup alın
2. **Test Environment**: Önce test ortamında deneyin
3. **Brand/Model Mapping**: Eksik brand/model'leri önceden ekleyin
4. **Memory Limit**: Büyük dosyalar için PHP memory limit'i artırın
5. **Execution Time**: max_execution_time'ı artırın

### 🔍 Troubleshooting

#### CSV Import Hataları
```bash
# Memory hatası
ini_set('memory_limit', '512M');

# Execution time hatası
ini_set('max_execution_time', 300);

# File upload hatası
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
```

#### Database Bağlantı Hataları
```bash
# MAMP port kontrolü
netstat -an | grep 8889

# MySQL servis kontrolü
brew services list | grep mysql
```

## 📞 Destek

### Log Dosyaları
- Migration logları: `logs/migration_YYYY-MM-DD_HH-mm-ss.log`
- System logları: Database `system_logs` tablosu
- Error logları: PHP error logs

### Test Dosyaları
```bash
# GUID sistem testi
final-guid-migration-complete.php

# Basic test
test-guid-system.php

# Database test
config/test-connection.php
```

## 🎉 Başarı Kriterleri

Migration başarılı sayılır:
- ✅ Tüm kullanıcılar MySQL'e aktarıldı
- ✅ Brand/Model mapping doğru çalışıyor
- ✅ Dosya yükleme sistemi çalışıyor
- ✅ Credit sistemi çalışıyor
- ✅ Admin panel erişilebilir
- ✅ Kullanıcı girişi yapılabiliyor

---

**Geliştirici**: MR.ECU Migration Team  
**Versiyon**: 2.0  
**Tarih**: Haziran 2025  
**Lisans**: Proprietary

🚀 **Migration başarılı olursa, SQL Server bağımlılığından kurtulmuş, modern, güvenli ve ölçeklenebilir bir sistem elde edeceksiniz!**