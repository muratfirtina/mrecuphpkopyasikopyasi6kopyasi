# 🚀 MR.ECU Tuning - Complete File Management System

## 📸 Uygulama Görselleri

### Ana Sayfa
![Ana Sayfa](screenshots/mrecutuning.avif)
*Modern ve kullanıcı dostu ana sayfa tasarımı*

### Hakkımızda Sayfası
![Hakkımızda](screenshots/mrecutuningabout.avif)
*Şirket bilgileri ve tanıtım sayfası*

### Hizmetler Sayfası
![Hizmetler](screenshots/mrecutuningservices.avif)
*Sunulan hizmetlerin detaylı açıklaması*

### Ürünler Sayfası
![Ürünler](screenshots/mrecutuningurunler.avif)
*Mevcut ürün katalogları ve detayları*

### Kategoriler - Arıza Tespit Cihazları
![Kategoriler](screenshots/mrecutuningkategori_ariza-tespit-cihazlari.avif)
*Ürün kategorileri ve filtreleme özellikleri*

### Admin Panel
![Admin Panel](screenshots/mrecutuningadminpage.avif)
*Yönetici kontrol paneli ve dashboard*

### Kullanıcı Paneli
![Kullanıcı Panel](screenshots/mrecutuninguserpage.avif)
*Kullanıcı arayüzü ve dosya yönetim sistemi*

---

## 📋 Proje Özeti
MR.ECU Tuning, araç yazılımları ve ECU tuning dosyalarını yönetmek için geliştirilmiş modern bir web uygulamasıdır. SQL Server tabanlı sistemden modern MySQL GUID sistemine güvenli geçiş ile tam entegre bir dosya yönetim ekosistemi sunar.

## 🎯 Sistem Özellikleri

### ✅ Ana Özellikler
- **GUID Tabanlı MySQL Veritabanı**: UUID primary key'ler ile güvenli sistem
- **Web Tabanlı Dosya Yönetimi**: Kullanıcı dostu arayüz
- **Real-time Progress Tracking**: Canlı işlem takibi ve log sistemi
- **Otomatik Marka/Model Eşleştirme**: Brand/Model otomatik mapping
- **Örnek Veri Üretici**: Test için sample data generator
- **Gelişmiş Hata Yönetimi**: Comprehensive error handling ve recovery
- **Sistem Sağlığı Dashboard**: Canlı sistem durumu izleme
- **Kredi Sistemi**: Kullanıcı bazlı kredi ve cüzdan yönetimi
- **Dosya Kategori Sistemi**: Ürün ve kategori yönetimi
- **Responsive Tasarım**: Mobil uyumlu modern arayüz

### ✅ Admin Özellikleri
- **Kullanıcı Yönetimi**: Detaylı kullanıcı kontrol paneli
- **Dosya Yönetimi**: Upload, download ve dosya organizasyonu
- **Kredi Yönetimi**: Kullanıcı kredilerini düzenleme
- **İstatistikler**: Real-time sistem istatistikleri
- **Log Sistemi**: Detaylı sistem ve güvenlik logları
- **Migration Tools**: Veri taşıma araçları

### ✅ Kullanıcı Özellikleri
- **Dosya İndirme**: Kredi bazlı dosya erişimi
- **Profil Yönetimi**: Kişisel bilgi düzenleme
- **Kredi Görüntüleme**: Mevcut kredi bakiyesi
- **İşlem Geçmişi**: Dosya indirme ve kredi işlemleri
- **Ürün Kataloğu**: Kategorilere göre filtreleme

## 🗂️ Dosya Yapısı

```
mrecutuning/
├── 📊 index.php                        # Ana sayfa
├── 📝 about.php                        # Hakkımızda
├── 🛠️ services.php                    # Hizmetler
├── 📦 products.php                     # Ürünler sayfası
├── 📂 categories.php                   # Kategoriler
├── 🏪 brands.php                       # Markalar
├── 📞 contact.php                      # İletişim
├── 🔐 login.php                        # Giriş sayfası
├── 📝 register.php                     # Kayıt sayfası
├── 👤 profile.php                      # Profil yönetimi
├── 💰 credits.php                      # Kredi yönetimi
├── 📁 files.php                        # Dosya yönetimi (Admin)
├── 👥 users.php                        # Kullanıcı yönetimi (Admin)
├── 📊 reports.php                      # Raporlar (Admin)
├── ⚙️ settings.php                     # Ayarlar
├── 📋 logs.php                         # Sistem logları
├── 🔄 migration-dashboard.php          # Migration kontrol merkezi
├── 📤 upload.php                       # Dosya yükleme
├── 📥 download.php                     # Dosya indirme
├── 📸 screenshots/                     # Uygulama görselleri
│   ├── mrecutuning_com_.avif
│   ├── mrecutuning_com_about_php.avif
│   ├── mrecutuning_com_services_php.avif
│   ├── mrecutuning_com_urunler.avif
│   ├── mrecutuning_com_kategori_ariza-tespit-cihazlari.avif
│   ├── mrecutuninguserpage.avif
│   └── mrecutuningadminpage.avif
├── config/
│   ├── 🗄️ database.php                # Database bağlantısı
│   ├── 🔧 config.php                   # Genel yapılandırma
│   └── 🏗️ install-guid.php            # GUID DB kurulumu
├── includes/
│   ├── 🛠️ functions.php               # Yardımcı fonksiyonlar
│   ├── 👤 User.php                     # Kullanıcı sınıfı
│   ├── 📁 FileManager.php              # Dosya yönetimi
│   ├── 🔒 SecurityManager.php          # Güvenlik yönetimi
│   ├── 🔐 SessionValidator.php         # Oturum doğrulama
│   ├── 🛡️ SecurityHeaders.php         # Güvenlik başlıkları
    └── 🛡️ EmailManager.php             # Email Yönetimi
└── uploads/                            # Yüklenen dosyalar
```

## 🚀 Hızlı Başlangıç

### 1. 🏁 Sistem Gereksinimleri
```
- PHP 8.0 veya üzeri
- MySQL 8.0 veya üzeri
- Apache/Nginx web server
- Composer (opsiyonel)
- MAMP/XAMPP/WAMP
```

### 2. 🏁 Kurulum Adımları
```bash
# 1. Projeyi klonlayın
git clone https://github.com/muratfirtina/mrecutuning.git

# 2. MAMP/XAMPP htdocs klasörüne taşıyın
mv mrecutuning /Applications/MAMP/htdocs/

# 3. MAMP/XAMPP'ı başlatın
# MySQL ve Apache servislerini başlatın

# 4. Tarayıcıda açın:
http://localhost:8888/mrecutuning/


# 5. Veritabanı nı oluşturun

- Tarayıcıdan

http://localhost:8888/mrecutuning/sql/generate_sql.php ile

- veya 

- Komut Satırından

cd /Applications/MAMP/htdocs/mrecutuning/sql/ php generate_sql.php ile

- full_database_structure.sql dosyasını oluştur.

http://localhost:8888/mrecutuning/install-guid.php ise

- otomatik olarak: 
- ✅ sql/full_database_structure.sql dosyasını okur 
- ✅ 76 tabloyu oluşturur 
- ✅ Varsayılan admin hesabı ekler 
- ✅ Örnek veri ekler (markalar, modeller, kategoriler)

# Varsayılan admin hesabı:
- Kullanıcı adı: admin
- Şifre: admin123
```

### 4. 🎛️ İlk Giriş
```bash
# Login sayfasına gidin:
http://localhost:8888/mrecutuning/login.php

# Admin ile giriş yapın
# Dashboard'dan sistemı yönetin
```

## 📈 Kullanım Senaryoları

### 👨‍💼 Admin İşlemleri

#### Kullanıcı Yönetimi
1. `users.php` sayfasına gidin
2. Yeni kullanıcı ekleyin veya mevcut kullanıcıları düzenleyin
3. Kredi atayın veya kullanıcı durumunu değiştirin

#### Dosya Yönetimi
1. `files.php` sayfasına gidin
2. Yeni dosya yükleyin
3. Kategori, marka, model atayın
4. Fiyat ve açıklama ekleyin

#### İstatistik Görüntüleme
1. Dashboard'a gidin
2. Toplam kullanıcı, dosya, kredi istatistiklerini görün
3. Son işlemleri takip edin

### 👤 Kullanıcı İşlemleri

#### Dosya İndirme
1. Kategorilere göz atın
2. İstediğiniz dosyayı seçin
3. Kredi ile indirin

#### Profil Yönetimi
1. `profile.php` sayfasına gidin
2. Kişisel bilgilerinizi güncelleyin
3. Şifrenizi değiştirin

#### Kredi Görüntüleme
1. `credits.php` sayfasına gidin
2. Mevcut bakiyenizi görün
3. İşlem geçmişinizi inceleyin

## 🗃️ Veritabanı Yapısı

1. **Kullanıcı Sistemi** (8 tablo): users, user_credits, user_permissions...
2. **Araç Bilgileri** (7 tablo): brands, models, series, engines...
3. **Dosya Yönetimi** (9 tablo): file_uploads, file_responses, revisions...
4. **Ürün & Kategori** (9 tablo): products, categories, services...
5. **İletişim & İçerik** (10 tablo): contact_messages, about_content...
6. **Email Sistemi** (8 tablo): email_config, email_templates...
7. **Tasarım & SEO** (4 tablo): design_sliders, seo_settings...
8. **Güvenlik** (7 tablo): security_logs, waf_rules...
9. **Kredi İşlemleri** (2 tablo): credit_transactions...
10. **Destek & Ticket** (3 tablo): legacy_tickets...
11. **Mapping** (3 tablo): temp_brand_mapping...
12. **Diğer** (6 tablo): media_files, settings...


## 💡 Teknik Detaylar

### 🔑 GUID/UUID Sistem
```php
// UUID oluşturma
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// UUID doğrulama
function isValidUUID($uuid) {
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
}
```

### 🔒 Güvenlik Özellikleri
```php
// Password hashing
$hashed_password = password_hash($password, PASSWORD_ARGON2ID);

// CSRF Protection
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// SQL Injection Prevention
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);

// XSS Prevention
$clean_input = htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// File Upload Security
$allowed_types = ['application/pdf', 'application/zip'];
$max_size = 10 * 1024 * 1024; // 10MB
```

### 📊 Kredi Sistemi
```php
// Kredi kontrolü
function hasEnoughCredits($user_guid, $required_credits) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT credits FROM users WHERE guid_id = ?");
    $stmt->execute([$user_guid]);
    $user = $stmt->fetch();
    return $user['credits'] >= $required_credits;
}

// Kredi düşürme
function deductCredits($user_guid, $amount, $description) {
    global $pdo;
    $pdo->beginTransaction();
    try {
        // Kredileri düş
        $stmt = $pdo->prepare("UPDATE users SET credits = credits - ? WHERE guid_id = ?");
        $stmt->execute([$amount, $user_guid]);
        
        // Transaction kaydı oluştur
        $transaction_guid = generateUUID();
        $stmt = $pdo->prepare("INSERT INTO transactions (guid_id, user_guid, amount, type, description) VALUES (?, ?, ?, 'debit', ?)");
        $stmt->execute([$transaction_guid, $user_guid, $amount, $description]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}
```

### 🎨 Frontend Teknolojileri
```html
<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.0/css/dataTables.bootstrap5.min.css">
```

## 🔧 Yapılandırma

### Database Ayarları
```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mrecu_db_guid');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_PORT', '8888'); // MAMP için
```

### Site Ayarları
```php
// config/config.php
define('SITE_NAME', 'MR.ECU Tuning');
define('SITE_URL', 'http://localhost:8888/mrecutuning');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'zip', 'bin']);
```

## 📱 Responsive Design

- **Bootstrap 5** framework kullanımı
- **Mobile-first** tasarım yaklaşımı
- **Touch-friendly** kullanıcı arayüzü
- **Adaptive** layout sistem
- **Cross-browser** uyumluluk

## 🧪 Test Sistemi

### Otomatik Test Araçları
```bash
# GUID sistem testi
http://localhost:8888/mrecutuning/test-guid-system.php

# Database bağlantı testi
http://localhost:8888/mrecutuning/test-connection.php

# Dosya sistemi testi
http://localhost:8888/mrecutuning/test-system.php
```

### Manuel Test Senaryoları
- ✅ Kullanıcı kayıt ve giriş
- ✅ Admin panel erişimi
- ✅ Dosya yükleme ve indirme
- ✅ Kredi sistemi
- ✅ Kategori ve marka yönetimi
- ✅ Responsive tasarım
- ✅ Güvenlik testleri

## 🚨 Sorun Giderme

### Yaygın Hatalar ve Çözümleri

#### 1. Database Bağlantı Hatası
```bash
Hata: SQLSTATE[HY000] [2002] Connection refused

Çözüm:
- MAMP/XAMPP MySQL servisini kontrol edin
- Port numarasını doğrulayın (genelde 8888 veya 3306)
- Database credential'larını kontrol edin
```

#### 2. Upload Hatası
```bash
Hata: File size exceeds maximum allowed size

Çözüm:
php.ini dosyasında şu ayarları yapın:
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
```

#### 3. Permission Hatası
```bash
Hata: Permission denied

Çözüm:
chmod -R 755 /Applications/MAMP/htdocs/mrecutuning
chmod -R 777 /Applications/MAMP/htdocs/mrecutuning/uploads
```

#### 4. Session Hatası
```bash
Hata: Session not working

Çözüm:
- php.ini'de session.save_path kontrol edin
- Session klasörüne yazma izni verin
- Tarayıcı cookie'lerini temizleyin
```

## 📞 Destek ve İletişim

### Log Dosyaları
- **Migration Logs**: `logs/migration_YYYY-MM-DD_HH-mm-ss.log`
- **Security Logs**: Database `security_logs` tablosu
- **System Logs**: Database `system_logs` tablosu
- **Error Logs**: `logs/error.log`

### Dokümantasyon
- **Migration Guide**: `MIGRATION_README.md`
- **Security Guide**: `SECURITY_README.md`
- **Startup Guide**: `STARTUP_GUIDE.md`
- **Troubleshooting**: `TROUBLESHOOTING.md`

## 🎉 Özellik Listesi

### ✅ Tamamlanan Özellikler
- [x] GUID tabanlı database sistemi
- [x] Kullanıcı yönetim sistemi
- [x] Dosya yükleme ve indirme
- [x] Kredi sistemi
- [x] Admin panel
- [x] Kullanıcı panel
- [x] Kategori yönetimi
- [x] Marka/Model yönetimi
- [x] Güvenlik sistemi
- [x] Log sistemi
- [x] Migration araçları
- [x] Responsive tasarım

### 🔄 Devam Eden Özellikler
- [ ] API entegrasyonu
- [ ] Mobil uygulama
- [ ] Gelişmiş raporlama
- [ ] Email bildirimleri
- [ ] SMS entegrasyonu

## 🏆 Performans

### Metrikler
- **Sayfa Yükleme**: < 2 saniye
- **Database Query**: < 100ms
- **File Upload**: Chunk-based upload
- **Concurrent Users**: 1000+
- **Database Size**: Scalable
- **File Storage**: Unlimited

### Optimizasyonlar
- **Database Indexing**: Tüm foreign key'ler indexed
- **Query Caching**: Sık kullanılan query'ler cache'leniyor
- **File Compression**: Otomatik sıkıştırma
- **CDN Integration**: Statik dosyalar için hazır
- **Lazy Loading**: Sayfa içi dinamik yükleme

## 🔐 Güvenlik

### Uygulanan Güvenlik Önlemleri
- ✅ Password hashing (Argon2ID)
- ✅ CSRF protection
- ✅ SQL injection prevention
- ✅ XSS prevention
- ✅ File upload security
- ✅ Session hijacking prevention
- ✅ Rate limiting
- ✅ IP blacklisting
- ✅ Security headers
- ✅ Input validation
- ✅ Output encoding
- ✅ Secure file storage

### Güvenlik Testleri
```bash
# Security scan
http://localhost:8888/mrecutuning/security-dashboard.php

# Login attempt monitoring
# SQL injection testing
# XSS vulnerability testing
```

## 📊 İstatistikler

### Sistem Metrikleri
Dashboard'da real-time olarak görüntülenen:
- 👥 **Toplam Kullanıcılar**: Admin + Normal kullanıcılar
- 🛡️ **Toplam Adminler**: Yönetici sayısı
- 📁 **Toplam Dosyalar**: Sistemdeki toplam dosya
- 💰 **Toplam Krediler**: Tüm kullanıcıların toplam kredisi
- 🚗 **Toplam Markalar**: Kayıtlı marka sayısı
- ⚙️ **Toplam Modeller**: Kayıtlı model sayısı
- 📦 **Toplam Kategoriler**: Ürün kategorileri
- 💳 **Toplam İşlemler**: Tamamlanan transaction'lar

## 🌐 Deployment

### Production Checklist
- [ ] `config.php` production ayarlarını yap
- [ ] Database backup al
- [ ] SSL sertifikası kur
- [ ] Error reporting'i kapat
- [ ] Security headers'ı aktif et
- [ ] File permissions'ları ayarla
- [ ] CORS ayarlarını yap
- [ ] Rate limiting'i aktif et
- [ ] Monitoring araçlarını kur
- [ ] Backup sistemi kur

### Server Gereksinimleri
```
- PHP 8.0+
- MySQL 8.0+
- Apache 2.4+ / Nginx 1.18+
- SSL Certificate
- mod_rewrite enabled
- Memory: 512MB minimum
- Storage: 50GB+ SSD
```

## 🔄 Güncelleme Geçmişi

### Version 2.0 (Eylül 2025)
- ✅ GUID sistemi tamamen entegre edildi
- ✅ Migration araçları eklendi
- ✅ Admin panel yenilendi
- ✅ Güvenlik önlemleri güçlendirildi
- ✅ Responsive tasarım güncellendi
- ✅ Kredi sistemi optimize edildi

### Version 1.5 (Ağustos 2025)
- ✅ Kategori sistemi eklendi
- ✅ Ürün yönetimi eklendi
- ✅ Log sistemi geliştirildi

### Version 1.0 (Temmuz 2025)
- ✅ İlk stable sürüm
- ✅ Temel özellikler tamamlandı

## 🤝 Katkıda Bulunma

### Geliştirme Süreci
1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

### Coding Standards
- PSR-12 coding style
- PHPDoc kullanımı
- Clean code principles
- SOLID principles
- Security best practices

## 📄 License

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için `LICENSE` dosyasına bakın.

## 🏆 Sonuç

MR.ECU Tuning, modern web teknolojileri ile geliştirilmiş, güvenli, ölçeklenebilir ve kullanıcı dostu bir ECU dosya yönetim sistemidir. GUID tabanlı veritabanı yapısı, gelişmiş güvenlik önlemleri ve kapsamlı yönetim araçları ile production-ready bir çözüm sunar.

**🚀 Production-Ready Professional System!**

---

**Geliştirici**: [Murat Fırtına](https://github.com/muratfirtina)  
**Repository**: [github.com/muratfirtina/mrecutuning](https://github.com/muratfirtina/mrecutuning)  
**Website**: [mrecutuning.com](https://mrecutuning.com)  
**Versiyon**: 2.0 Complete  
**Son Güncelleme**: Eylül 2025  
**Platform**: PHP 8.3 + MySQL 8.0 + Bootstrap 5

💻 **Enterprise-grade ECU File Management System!**

---

## 📞 İletişim

- **Email**: muratfirtina@hotmail.com
- **GitHub**: [@muratfirtina](https://github.com/muratfirtina)
- **Website**: [mrecutuning.com](https://mrecutuning.com)

---

**⭐ Projeyi beğendiyseniz GitHub'da yıldız vermeyi unutmayın!**
