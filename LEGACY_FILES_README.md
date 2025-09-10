# Legacy Files System - Eski Dosyalar Sistemi

Bu özellik, eski projelerden gelen kullanıcıların dosyalarını yeni sisteme aktarmak için geliştirilmiştir.

## Özellikler

- **Admin Panel**: Admin kullanıcıları, user kullanıcılarına plaka bazında dosya yükleyebilir
- **User Panel**: Kullanıcılar kendi eski dosyalarını plaka grupları halinde görüntüleyebilir  
- **Dosya Yönetimi**: Tüm dosya tiplerini destekler (resim, PDF, Word, Excel vb.)
- **Güvenli İndirme**: Kullanıcılar sadece kendi dosyalarını görebilir ve indirebilir
- **Plaka Bazında Organizasyon**: Dosyalar araç plakalarına göre düzenli şekilde saklanır

## Kurulum

### 1. Veritabanı Kurulumu
```sql
-- database/setup_legacy_files.sql dosyasını çalıştırın
mysql -u username -p database_name < database/setup_legacy_files.sql
```

### 2. Dosya Sistemini Kontrol Edin
- `uploads/legacy_files/` klasörünün yazma izninin olduğunu kontrol edin
- Gerekirse klasör izinlerini ayarlayın: `chmod 755 uploads/legacy_files/`

### 3. Sınıfları Include Edin
LegacyFilesManager sınıfı otomatik olarak yüklenecektir.

## Kullanım

### Admin Paneli

1. **Admin Panel > Eski Dosyalar** menüsüne gidin
2. Kullanıcı seçin veya tabloda kullanıcı arayın
3. Araç plakasını girin (örn: 34ABC123)
4. Dosyaları seçip yükleyin
5. Kullanıcı panelinde otomatik olarak görünecektir

### User Paneli

1. **User Panel > Eski Dosyalarım** menüsüne gidin
2. Plaka gruplarını görüntüleyin
3. İstediğiniz plakaya tıklayarak dosyaları görün
4. Dosyaları görüntüleyin veya indirin

## Dosya Organizasyonu

```
uploads/legacy_files/
├── {user_id}/
│   ├── {plate_number_1}/
│   │   ├── dosya1.pdf
│   │   ├── dosya2.jpg
│   │   └── ...
│   ├── {plate_number_2}/
│   │   ├── dosya3.docx
│   │   └── ...
│   └── ...
└── ...
```

## Güvenlik

- Kullanıcılar sadece kendi dosyalarını görebilir
- Dosya indirme yetkisi kontrol edilir
- Tüm dosya işlemleri loglanır
- Admin yetkisi olmayan kullanıcılar dosya yükleyemez

## API / Sınıf Kullanımı

```php
// LegacyFilesManager sınıfını kullanma
$legacyManager = new LegacyFilesManager($pdo);

// Kullanıcının dosyalarını alma
$userFiles = $legacyManager->getUserLegacyFiles($userId);

// Belirli plakaya ait dosyaları alma
$plateFiles = $legacyManager->getPlateFiles($userId, $plateNumber);

// Admin için dosya yükleme
$result = $legacyManager->uploadFileForUser($userId, $plateNumber, $_FILES['files'], $adminId);

// Dosya indirme
$result = $legacyManager->downloadFile($fileId, $userId);
```

## Veritabanı Tablosu

```sql
legacy_files:
- id (VARCHAR 36) - Unique file identifier
- user_id (VARCHAR 36) - User who owns the file
- plate_number (VARCHAR 50) - Vehicle plate number
- original_filename (VARCHAR 255) - Original file name
- stored_filename (VARCHAR 255) - Stored file name (unique)
- file_path (VARCHAR 500) - Full file path
- file_size (BIGINT) - File size in bytes
- file_type (VARCHAR 100) - MIME type
- uploaded_by_admin (VARCHAR 36) - Admin who uploaded the file
- upload_date (DATETIME) - Upload timestamp
- created_at (DATETIME) - Creation timestamp
- updated_at (DATETIME) - Last update timestamp
```

## Menü Linkleri

### Admin Panel
- **Dosya Yönetimi > Eski Dosyalar** - Ana yönetim sayfası
- Badge ile toplam dosya sayısı gösterilir

### User Panel  
- **Dosya İşlemleri > Eski Dosyalarım** - Kullanıcı dosyaları
- Badge ile kullanıcıya ait dosya sayısı gösterilir

## Troubleshooting

### Dosya Yükleme Sorunları
- PHP upload limitleri: `upload_max_filesize`, `post_max_size`, `max_file_uploads`
- Klasör izinleri: `uploads/legacy_files/` yazılabilir olmalı
- MySQL dosya boyutu: `max_allowed_packet` ayarı

### Dosya Görünmüyor
- Veritabanı bağlantısını kontrol edin
- `legacy_files` tablosunun var olduğunu kontrol edin
- User ID'lerin doğru olduğunu kontrol edin

### Dosya İndirme Problemi
- Dosya path'lerinin doğru olduğunu kontrol edin
- Fiziksel dosyaların var olduğunu kontrol edin
- Web server dosya okuma izinlerini kontrol edin

## Güncellemeler

### v1.0.0 (İlk Sürüm)
- Temel legacy files sistemi
- Admin panel yönetimi
- User panel görüntüleme
- Plaka bazında organizasyon
- Güvenli dosya indirme
- Dosya önizleme (resimler için)

## Destek

Herhangi bir sorunla karşılaştığınızda:
1. Error loglarını kontrol edin
2. Veritabanı bağlantısını test edin
3. Dosya izinlerini kontrol edin
4. Browser Developer Tools'da JavaScript hatalarını kontrol edin
