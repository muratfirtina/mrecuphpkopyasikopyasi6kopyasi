# 🎨 Mr ECU Design System

Bu proje, Mr ECU web sitesi için geliştirilmiş kapsamlı bir **Database Temelli Design Yönetim Sistemi**'dir. Admin ve Design yetkili kullanıcılar, site tasarımını kod yazmadan kolayca yönetebilir.

## ✨ Özellikler

### 🖼️ Hero Slider Yönetimi
- Database temelli dinamik slider sistemi
- Drag & drop ile sıralama
- Resim, metin, buton ve renk düzenleme
- Gerçek zamanlı önizleme
- Responsive tasarım desteği

### 🎨 Tema ve Renk Ayarları
- Site renk paletini düzenleme
- Typewriter efekti kontrolü
- Hero animasyon hızı ayarlama
- Özel CSS ve JS ekleme
- Sosyal medya bağlantıları

### 📝 İçerik Yönetimi
- Sayfa içeriklerini database'den yönetme
- Bölümlere göre organize edilmiş içerik
- Toplu güncelleme özelliği
- Farklı içerik türleri (metin, resim, renk, JSON)

### 🖼️ Medya Yönetimi
- Drag & drop dosya yükleme
- Resim önizleme ve düzenleme
- Alt metin ve başlık ekleme
- Dosya boyutu ve türü kontrolü
- URL kopyalama özelliği

### 👤 Kullanıcı Rol Sistemi
- **Admin**: Tam yetki
- **Design**: Sadece design paneli erişimi
- **User**: Normal kullanıcı yetkisi

## 📁 Dosya Yapısı

```
mrecuphpkopyasikopyasi6kopyasi/
├── design/                          # Design Panel Klasörü
│   ├── index.php                    # Dashboard
│   ├── sliders.php                  # Slider Yönetimi
│   ├── settings.php                 # Site Ayarları
│   ├── content.php                  # İçerik Yönetimi
│   ├── media.php                    # Medya Yönetimi
│   └── ajax.php                     # AJAX Endpoint'leri
├── includes/
│   ├── design_header.php            # Design Panel Header
│   └── design_footer.php            # Design Panel Footer
├── assets/
│   └── images/                      # Medya dosyaları
├── config/
│   ├── database.php                 # Database bağlantısı
│   └── config.php                   # Genel ayarlar
├── index.php                        # Database temelli ana sayfa
├── design_installation_guide.php    # Kurulum kılavuzu
├── design_system_demo.php          # Demo sayfası
├── install_design_sliders.php      # Slider verilerini yükleme
├── update_user_roles.php           # Kullanıcı rollerini güncelleme
└── create_folders.php              # Eksik klasörleri oluşturma
```

## 🗃️ Database Tabloları

### `design_sliders`
Hero slider verilerini saklar.
```sql
CREATE TABLE design_sliders (
    id VARCHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255),
    description TEXT,
    button_text VARCHAR(100),
    button_link VARCHAR(500),
    background_image VARCHAR(500),
    background_color VARCHAR(20) DEFAULT '#667eea',
    text_color VARCHAR(20) DEFAULT '#ffffff',
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### `design_settings`
Site ayarlarını saklar.
```sql
CREATE TABLE design_settings (
    id VARCHAR(36) PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### `content_management`
Sayfa içeriklerini saklar.
```sql
CREATE TABLE content_management (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(50) NOT NULL,
    key_name VARCHAR(100) NOT NULL,
    value LONGTEXT NOT NULL,
    type ENUM('text','textarea','image','color','json') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY section_key (section, key_name)
);
```

### `media_files`
Medya dosyalarını saklar.
```sql
CREATE TABLE media_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_type ENUM('image','video','document','other') NOT NULL,
    alt_text VARCHAR(255),
    caption TEXT,
    used_in JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 🚀 Kurulum

### 1. Ön Koşullar
- PHP 8.0+
- MySQL 5.7+ veya MariaDB 10.3+
- Web sunucu (Apache/Nginx)
- MAMP/XAMPP (geliştirme için)

### 2. Kurulum Adımları

#### Adım 1: Database Kurulumu
```
http://localhost:8889/mrecuphpkopyasikopyasi6kopyasi/install-database.php
```

#### Adım 2: Kullanıcı Rollerini Güncelleme
```
http://localhost:8889/mrecuphpkopyasikopyasi6kopyasi/update_user_roles.php
```

#### Adım 3: Design Slider Verilerini Yükleme
```
http://localhost:8889/mrecuphpkopyasikopyasi6kopyasi/install_design_sliders.php
```

#### Adım 4: Kurulum Kontrolü
```
http://localhost:8889/mrecuphpkopyasikopyasi6kopyasi/design_installation_guide.php
```

### 3. Otomatik Kurulum
Tüm adımları tek seferde kontrol etmek için:
```
http://localhost:8889/mrecuphpkopyasikopyasi6kopyasi/design_installation_guide.php
```

## 👤 Test Kullanıcıları

Kurulum tamamlandıktan sonra şu kullanıcılarla giriş yapabilirsiniz:

| Rol | Email | Şifre | Yetki |
|-----|-------|-------|-------|
| Admin | admin@mrecu.com | admin123 | Tam yetki |
| Design | design@mrecu.com | design123 | Design paneli |
| User | test@mrecu.com | test123 | Normal kullanıcı |

## 🎯 Kullanım

### Design Panel'e Erişim
1. `http://localhost:8889/mrecuphpkopyasikopyasi6kopyasi/login.php` ile giriş yapın
2. Admin veya Design yetkili hesap kullanın
3. `http://localhost:8889/mrecuphpkopyasikopyasi6kopyasi/design/` adresinden Design Panel'e erişin

### Hero Slider Yönetimi
1. Design Panel → Hero Slider
2. "Yeni Slider" ile yeni slide ekleyin
3. Resim URL'si, başlık, açıklama ve buton bilgilerini girin
4. Renk ve sıralama ayarlarını yapın
5. Değişiklikleri kaydedin

### Site Ayarları
1. Design Panel → Site Ayarları
2. Renk şemasını düzenleyin
3. Typewriter efekti ayarlarını değiştirin
4. Sosyal medya bağlantılarını ekleyin
5. Özel CSS/JS kodları ekleyin

### İçerik Yönetimi
1. Design Panel → İçerik Yönetimi
2. Bölümlere göre içerikleri düzenleyin
3. Toplu güncelleme yapın
4. Hazır şablonları kullanın

### Medya Yönetimi
1. Design Panel → Medya Yönetimi
2. Drag & drop ile dosya yükleyin
3. Alt metin ve başlık ekleyin
4. URL'leri kopyalayın

## 🔧 Özelleştirme

### Yeni İçerik Türü Ekleme
```php
// content.php dosyasında yeni type ekleyin
case 'custom_type':
    html = `<input type="text" class="form-control" id="value" name="value" value="${currentValue}" required>`;
    break;
```

### Yeni Ayar Ekleme
```sql
INSERT INTO design_settings (id, setting_key, setting_value, description) 
VALUES (UUID(), 'new_setting', 'default_value', 'Setting description');
```

### AJAX Endpoint Ekleme
```php
// design/ajax.php dosyasında yeni action ekleyin
case 'new_action':
    // İşlem kodu
    $response = ['success' => true, 'message' => 'İşlem başarılı'];
    break;
```

## 🎨 Tema Özelleştirme

### CSS Değişkenleri
```css
:root {
    --design-primary: #667eea;
    --design-secondary: #764ba2;
    --design-success: #06d6a0;
    --design-danger: #ef476f;
    --design-warning: #ffd166;
    --design-info: #118ab2;
}
```

### JavaScript Özelleştirme
```javascript
// Typewriter hızını değiştirme
new TypeWriter(element, words, 3000); // 3 saniye bekleme

// Slider hızını değiştirme
data-bs-interval="5000" // 5 saniye
```

## 📱 Responsive Tasarım

Sistem tamamen responsive olarak tasarlanmıştır:
- **Desktop**: Tam özellikli design panel
- **Tablet**: Sıkıştırılmış sidebar
- **Mobile**: Hamburger menü

## 🔐 Güvenlik

### Yetki Kontrolü
```php
// Her design dosyasında kontrol
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'design'])) {
    header('Location: ../index.php?error=Bu sayfaya erişim yetkiniz yok');
    exit;
}
```

### SQL Injection Koruması
```php
// Prepared statements kullanımı
$stmt = $pdo->prepare("SELECT * FROM design_sliders WHERE id = ?");
$stmt->execute([$id]);
```

### Dosya Yükleme Güvenliği
```php
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    throw new Exception('Sadece resim dosyaları yüklenebilir');
}
```

## 🐛 Sorun Giderme

### Sık Karşılaşılan Problemler

**1. Design Panel açılmıyor**
```
Çözüm: Kullanıcı rolünü kontrol edin
UPDATE users SET role = 'design' WHERE email = 'kullanici@email.com';
```

**2. Slider görünmüyor**
```
Çözüm: Slider verilerini kontrol edin
SELECT * FROM design_sliders WHERE is_active = 1;
```

**3. Medya yükleme çalışmıyor**
```
Çözüm: Klasör iznini kontrol edin
chmod 755 assets/images/
```

**4. Database bağlantı hatası**
```
Çözüm: config/database.php dosyasını kontrol edin
```

## 📊 Performans

### Optimizasyon İpuçları
- Resim boyutlarını optimize edin (max 5MB)
- Slider sayısını 10'dan az tutun
- CSS/JS dosyalarını minimize edin
- Database sorguları için index kullanın

### Önbellek Kullanımı
```php
// Design ayarlarını cache'leme
$settings = $_SESSION['design_cache'] ?? loadDesignSettings();
```

## 🔄 Güncelleme

### Yeni Özellik Ekleme
1. Database şemasını güncelleyin
2. AJAX endpoint'lerini ekleyin
3. Frontend formlarını oluşturun
4. Test edin

### Migration Örneği
```sql
-- Yeni kolon ekleme
ALTER TABLE design_sliders ADD COLUMN new_feature VARCHAR(255) DEFAULT NULL;
```

## 📞 Destek

### Demo ve Test
```
Demo Sayfası: /design_system_demo.php
Kurulum Kılavuzu: /design_installation_guide.php
```

### Log Dosyaları
```
logs/design_errors.log    # Design panel hataları
logs/media_uploads.log    # Medya yükleme logları
logs/ajax_requests.log    # AJAX işlem logları
```

## 📈 Gelecek Özellikler

- [ ] Dark mode desteği
- [ ] Çoklu dil desteği
- [ ] Tema şablonları
- [ ] Backup/restore sistemi
- [ ] Real-time collaboration
- [ ] Version control
- [ ] A/B testing
- [ ] Analytics entegrasyonu

## 📄 Lisans

Bu proje Mr ECU için özel olarak geliştirilmiştir.

---

**Geliştirici:** Claude (Anthropic)
**Versiyon:** 1.0.0
**Son Güncelleme:** Ağustos 2025

🚀 **Başarılı kurulum için tüm adımları sırayla takip edin!**
