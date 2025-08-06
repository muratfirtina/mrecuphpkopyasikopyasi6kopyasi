# 🚗 Araç Tuning Sistemi - Kurulum ve Kullanım Rehberi

## 📋 Genel Bilgi

Bu sistem, chip tuning verilerinizi MySQL veritabanında saklamanıza ve web üzerinden aramalar yapmanıza olanak sağlar. JSON formatındaki verilerinizi import edebilir, API üzerinden erişebilir ve modern web arayüzü ile yönetebilirsiniz.

## 🏗️ Sistem Özellikleri

- ✅ **Hiyerarşik Yapı**: Marka → Model → Seri → Motor → Stage
- ✅ **JSON Import Sistemi**: Mevcut verilerinizi kolayca import edin
- ✅ **Güçlü Arama**: Marka, model, motor, yakıt tipi ve güç aralığına göre arama
- ✅ **REST API**: JSON formatında veri erişimi
- ✅ **Admin Panel**: Fiyat güncelleme, durum değiştirme, istatistikler
- ✅ **Responsive Tasarım**: Mobil ve desktop uyumlu
- ✅ **İstatistikler**: Marka bazında ve yakıt tipi istatistikleri

## 🔧 Kurulum

### 1. Veritabanı Tablolarını Oluşturma

```sql
-- MySQL'de aşağıdaki dosyayı çalıştırın:
SOURCE config/install-tuning-system.sql;
```

**Alternatif olarak:**
```bash
mysql -u root -p mrecu_db_guid < config/install-tuning-system.sql
```

### 2. Veri Import İşlemi

1. **Import sayfasına gidin**: `http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/tuning-import.php`
2. **"Örnek Veriyi Import Et"** butonuna tıklayın
3. Import işleminin tamamlanmasını bekleyin

**Manuel Import için:**
```bash
# sample-tuning-data.json dosyasındaki veriyi kendi JSON verilerinizle değiştirin
# Ardından "Özel JSON Verisi Import Et" bölümünü kullanın
```

### 3. Dosya Yapısı Kontrolü

Aşağıdaki dosyaların mevcut olduğundan emin olun:

```
mrecuphpkopyasikopyasi6kopyasi/
├── config/
│   └── install-tuning-system.sql      # Veritabanı tabloları
├── includes/
│   └── TuningModel.php               # Veri modeli
├── admin/
│   └── tuning-management.php         # Admin panel
├── tuning-import.php                 # Import sistemi
├── tuning-api.php                   # REST API
├── tuning-search.php                # Arama sayfası
└── sample-tuning-data.json          # Örnek veri
```

## 🎯 Kullanım

### 1. Arama Sayfası
**URL**: `tuning-search.php`

**Özellikler:**
- Marka ve model seçimi (dinamik yükleme)
- Yakıt tipi filtreleme
- Güç aralığı belirleme
- Genel arama (motor, ECU, model)
- Kart ve tablo görünümü
- Popüler motorlar
- En yüksek güç artışları

### 2. Admin Panel
**URL**: `admin/tuning-management.php`

**Özellikler:**
- İstatistikler (marka, yakıt tipi)
- Fiyat güncelleme
- Stage aktif/pasif yapma
- Popüler motorlar listesi
- API dokümantasyonu

### 3. REST API
**Base URL**: `tuning-api.php`

**Temel Endpoint'ler:**

```javascript
// Tüm markaları getir
GET tuning-api.php?action=brands

// Markaya göre modelleri getir
GET tuning-api.php?action=models&brand_id=1

// Detaylı arama
GET tuning-api.php?action=search&brand=BMW&fuel_type=Petrol&min_power=200

// Popüler motorlar
GET tuning-api.php?action=popular&limit=10

// İstatistikler
GET tuning-api.php?action=brand_stats
GET tuning-api.php?action=fuel_stats

// Tüm veriyi JSON olarak export
GET tuning-api.php?action=export
```

## 📁 JSON Veri Formatı

Sisteme import edilecek JSON verisi aşağıdaki formatta olmalıdır:

```json
{
  "Marka": {
    "Model": {
      "Yıl Aralığı": {
        "Motor Adı": {
          "Stage1": {
            "fullname": "Tam Motor Adı",
            "original_power": 150,
            "tuning_power": 190,
            "difference_power": 40,
            "original_torque": 320,
            "tuning_torque": 400,
            "difference_torque": 80,
            "fuel": "Diesel",
            "ECU": "Bosch EDC17CP14"
          },
          "Stage2": {
            // İsteğe bağlı ek stage'ler
          }
        }
      }
    }
  }
}
```

### Örnek JSON Girişi:

```json
{
  "BMW": {
    "X5": {
      "2018 - 2023": {
        "3.0 xDrive40i": {
          "Stage1": {
            "fullname": "BMW X5 3.0 xDrive40i",
            "original_power": 340,
            "tuning_power": 400,
            "difference_power": 60,
            "original_torque": 450,
            "tuning_torque": 520,
            "difference_torque": 70,
            "fuel": "Petrol",
            "ECU": "Bosch MG1CS003"
          }
        }
      }
    }
  }
}
```

## 🔄 Veri Güncelleme

### Toplu Import
1. **Yeni JSON verisi hazırlayın**
2. **Import sayfasına gidin**: `tuning-import.php`
3. **"Veritabanını Temizle"** (isteğe bağlı)
4. **JSON verisini textarea'ya yapıştırın**
5. **"Import Et"** butonuna tıklayın

### Tekil Güncelleme
1. **Admin panele gidin**: `admin/tuning-management.php`
2. **İlgili stage'i bulun**
3. **Fiyat güncelleyin** veya **durumunu değiştirin**

## 📊 Veritabanı Yapısı

```sql
tuning_brands (markalar)
├── id, name, slug

tuning_models (modeller)  
├── id, brand_id, name, slug

tuning_series (seriler/yıl aralıkları)
├── id, model_id, name, year_range, slug

tuning_engines (motorlar)
├── id, series_id, name, fuel_type, slug

tuning_stages (stage'ler/tuning detayları)
├── id, engine_id, stage_name, fullname
├── original_power, tuning_power, difference_power
├── original_torque, tuning_torque, difference_torque
├── ecu, price, is_active
```

## 🔍 Arama ve Filtreleme

### Arama Kriterleri:
- **Marka**: Dropdown seçimi
- **Model**: Markaya bağlı dinamik yükleme
- **Yakıt Tipi**: Petrol, Diesel, Hybrid, Electric
- **Güç Aralığı**: Min/Max HP değerleri
- **Genel Arama**: Motor adı, ECU, model içinde arama

### Sonuç Görünümleri:
- **Kart Görünümü**: Detaylı bilgilerle kartlar
- **Tablo Görünümü**: Kompakt tablo formatı

## 🚨 Sorun Giderme

### Veritabanı Bağlantı Hatası
```php
// config/database.php dosyasında kontrol edin:
private $host = '127.0.0.1';
private $port = '8889';
private $db_name = 'mrecu_db_guid';
private $username = 'root';
private $password = 'root';
```

### Import Hatası
1. **JSON formatını kontrol edin** (JSON validator kullanın)
2. **Dosya boyutunu kontrol edin** (PHP upload limits)
3. **Veritabanı tablolarının oluşturulduğundan emin olun**

### API Erişim Hatası
```javascript
// CORS hatası için .htaccess'e ekleyin:
Header add Access-Control-Allow-Origin "*"
Header add Access-Control-Allow-Methods "GET, POST, OPTIONS"
```

## 📈 Performans İpuçları

### Veritabanı Optimizasyonu:
- ✅ **İndexler otomatik oluşturulur**
- ✅ **Search view kullanın** (tuning_search_view)
- ✅ **Limit parametresi kullanın** (API'de)

### Frontend Optimizasyonu:
- ✅ **Pagination kullanın** (büyük sonuç setleri için)
- ✅ **Debouncing uygulayın** (arama inputu için)
- ✅ **Cache API sonuçlarını** (LocalStorage)

## 🔐 Güvenlik

### Korunan Alanlar:
- ✅ **Admin panel**: Login kontrolü
- ✅ **Import sistemi**: Admin yetkisi gerekli
- ✅ **Fiyat güncelleme**: Admin yetkisi gerekli

### Güvenlik Önerileri:
- 🔒 **Production'da DEBUG = false** yapın
- 🔒 **Güçlü admin şifreleri** kullanın
- 🔒 **HTTPS kullanın** (production)
- 🔒 **Düzenli backup** alın

## 🆘 Destek

### Loglar:
- **Error Log**: `logs/error.log`
- **PHP Errors**: `error_log()` fonksiyonu kullanılır

### Debug Modu:
```php
// config/config.php
define('DEBUG', true); // Geliştirme için
```

### Test URL'leri:
- **Arama**: `tuning-search.php`
- **API Test**: `tuning-api.php?action=brands`
- **Import**: `tuning-import.php`
- **Admin**: `admin/tuning-management.php`

---

## 🎉 Başarılı Kurulum Kontrolü

Sistem doğru çalışıyorsa:

1. ✅ **Arama sayfasında markalar görünür**
2. ✅ **API JSON yanıt verir** 
3. ✅ **Admin panelde istatistikler görünür**
4. ✅ **Import işlemi başarılı olur**

**İyi tuning'ler! 🚗💨**