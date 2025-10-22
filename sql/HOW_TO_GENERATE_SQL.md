# 📋 SQL Database Oluşturma Rehberi

## 🎯 Amaç

Development-database.txt dosyasından otomatik olarak 76 tablonun tamamını içeren SQL dosyası oluşturur.

## 🚀 Hızlı Başlangıç

### 1. SQL Dosyasını Oluştur

**Komut Satırından:**
```bash
cd /Applications/MAMP/htdocs/mrecutuning/sql/
php generate_sql.php
```

**Veya Tarayıcıdan:**
```
http://localhost:8888/mrecutuning/sql/generate_sql.php
```

### 2. Sonuç

Script çalıştırıldığında:
- ✅ `full_database_structure.sql` dosyası oluşturulur
- ✅ 76 tablo CREATE TABLE statement'ı içerir
- ✅ Toplam ~150-200 KB boyutunda olur
- ✅ `install-guid.php` tarafından otomatik kullanılır

---

## 📁 Dosya Yapısı

```
/sql/
├── generate_sql.php          # SQL oluşturucu script
├── full_database_structure.sql   # Oluşturulan SQL (76 tablo)
├── README_SQL.md             # SQL klasörü dokümantasyonu
└── HOW_TO_GENERATE_SQL.md   # Bu dosya
```

---

## 🔧 Manuel SQL Oluşturma

Eğer script çalışmazsa veya özel bir SQL dosyası oluşturmak isterseniz:

### Adım 1: development-database.txt'yi İnceleyin

Dosya formatı:
```
Tablo: table_name
Kolon Adı    Veri Tipi    Null    Key    Default    Extra
column1      varchar(36)  NO      PRIMARY           
column2      text         YES     NULL   NULL
```

### Adım 2: CREATE TABLE Statement Oluşturun

```sql
CREATE TABLE IF NOT EXISTS `table_name` (
  `column1` varchar(36) NOT NULL,
  `column2` text NULL DEFAULT NULL,
  PRIMARY KEY (`column1`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## ✅ Doğrulama

SQL dosyası oluşturulduktan sonra:

### 1. Dosya Kontrolü
```bash
ls -lh /Applications/MAMP/htdocs/mrecutuning/sql/full_database_structure.sql
```

Beklenen: ~150-200 KB

### 2. İçerik Kontrolü
```bash
head -50 /Applications/MAMP/htdocs/mrecutuning/sql/full_database_structure.sql
```

Beklenen çıktı:
```sql
-- MR.ECU Tuning Database Structure
-- Generated from development-database.txt
-- Date: 2025-XX-XX XX:XX:XX
-- Total Tables: 76

SET FOREIGN_KEY_CHECKS=0;
...
```

### 3. Tablo Sayısı Kontrolü
```bash
grep -c "CREATE TABLE" /Applications/MAMP/htdocs/mrecutuning/sql/full_database_structure.sql
```

Beklenen: 76

---

## 🔄 Otomatik Kurulum

`install-guid.php` çalıştırıldığında:

1. `sql/full_database_structure.sql` dosyasını arar
2. Dosya varsa tüm 76 tabloyu oluşturur
3. Dosya yoksa fallback olarak temel tabloları manuel oluşturur

---

## 🛠️ Sorun Giderme

### Problem 1: PHP CLI bulunamıyor

```bash
# MAMP PHP'sini kullan
/Applications/MAMP/bin/php/php8.3.1/bin/php generate_sql.php
```

### Problem 2: development-database.txt bulunamadı

```bash
# Dosyanın yerini kontrol edin
ls -la /Applications/MAMP/htdocs/mrecutuning/development-database.txt
```

### Problem 3: İzin hatası

```bash
# İzinleri düzeltin
chmod 755 /Applications/MAMP/htdocs/mrecutuning/sql/
chmod 644 /Applications/MAMP/htdocs/mrecutuning/development-database.txt
```

### Problem 4: SQL dosyası boş

- `development-database.txt` format kontrolü yapın
- Tab karakterlerinin doğru olduğundan emin olun
- UTF-8 encoding kullanıldığını doğrulayın

---

## 📊 Beklenen Çıktı

```
SQL Generator başlatılıyor...
Input: /Applications/MAMP/htdocs/mrecutuning/development-database.txt
Output: /Applications/MAMP/htdocs/mrecutuning/sql/full_database_structure.sql

✓ SQL dosyası başarıyla oluşturuldu!
Toplam Boyut: 187,432 bytes
Dosya: /Applications/MAMP/htdocs/mrecutuning/sql/full_database_structure.sql
```

---

## 🎓 İpuçları

1. **Yedekleme**: SQL oluşturmadan önce mevcut database'i yedekleyin
2. **Test**: İlk önce test database'de deneyin
3. **Foreign Keys**: Foreign key ilişkileri doğru tanımlandığından emin olun
4. **Encoding**: UTF-8 encoding kullanın
5. **Collation**: utf8mb4_unicode_ci kullanın (emoji desteği için)

---

## 🔗 İlgili Dosyalar

- `../development-database.txt` - Kaynak database yapısı
- `../install-guid.php` - Otomatik kurulum scripti
- `full_database_structure.sql` - Oluşturulan SQL
- `README_SQL.md` - SQL klasörü dokümantasyonu

---

## 📞 Yardım

Sorun yaşarsanız:

1. `generate_sql.php` script'ini kontrol edin
2. `development-database.txt` formatını doğrulayın
3. PHP error log'larını inceleyin
4. Manuel CREATE TABLE statement'ları yazın

---

**Oluşturan:** Database Generator Script  
**Versiyon:** 1.0  
**Tarih:** Ekim 2025
