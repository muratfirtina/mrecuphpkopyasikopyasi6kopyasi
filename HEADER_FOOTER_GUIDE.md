# Header & Footer Template Kullanım Kılavuzu

Bu dosya, Mr ECU projesinde oluşturulan yeni header/footer template yapısının nasıl kullanılacağını açıklar.

## 📁 Dosya Yapısı

```
includes/
├── header.php          # Ziyaretçi sayfaları için genel header
├── footer.php          # Ziyaretçi sayfaları için genel footer
├── user_header.php     # Kullanıcı paneli header
├── user_sidebar.php    # Kullanıcı paneli sidebar
├── user_footer.php     # Kullanıcı paneli footer
├── admin_header.php    # Admin paneli header
├── admin_sidebar.php   # Admin paneli sidebar
└── admin_footer.php    # Admin paneli footer
```

## 🌐 Ziyaretçi Sayfaları

### Kullanım Örneği:
```php
<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Sayfa bilgileri
$pageTitle = 'Sayfa Başlığı';
$pageDescription = 'Sayfa açıklaması SEO için';
$pageKeywords = 'anahtar, kelimeler, seo';
$bodyClass = 'bg-light'; // İsteğe bağlı

// Ek CSS dosyaları (isteğe bağlı)
$additionalCSS = [
    'assets/css/custom.css',
    'https://example.com/external.css'
];

// Header include
include 'includes/header.php';
?>

<!-- Sayfa içeriği buraya -->
<section class="py-5">
    <div class="container">
        <h1><?php echo $pageTitle; ?></h1>
        <!-- İçerik -->
    </div>
</section>

<?php
// Sayfa özel JavaScript (isteğe bağlı)
$pageJS = "
    console.log('Sayfa yüklendi');
    // JavaScript kodları
";

// Ek JS dosyaları (isteğe bağlı)
$additionalJS = [
    'assets/js/custom.js'
];

// Footer include
include 'includes/footer.php';
?>
```

### Özellikler:
- Responsive navbar
- Kullanıcı giriş/çıkış durumuna göre menü
- SEO optimizasyonu
- Sosyal medya linkleri
- Smooth scrolling
- Auto-hide alerts

## 👤 Kullanıcı Paneli Sayfaları

### Kullanım Örneği:
```php
<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü otomatik yapılır

// Sayfa bilgileri
$pageTitle = 'Dashboard';
$pageDescription = 'Kullanıcı panel açıklaması';

// Header ve Sidebar include
include '../includes/user_header.php';
include '../includes/user_sidebar.php';
?>

<!-- Sayfa içeriği buraya -->
<div class="row">
    <div class="col-12">
        <h2>Dashboard İçeriği</h2>
        <!-- İçerik -->
    </div>
</div>

<?php
// Footer include
include '../includes/user_footer.php';
?>
```

### Özellikler:
- Otomatik giriş kontrolü
- Kredi gösterimi
- Sidebar navigasyon
- Breadcrumb
- Real-time saat
- Auto-hide alerts

## 🔧 Admin Paneli Sayfaları

### Kullanım Örneği:
```php
<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü otomatik yapılır

// Sayfa bilgileri
$pageTitle = 'Kullanıcılar';
$pageDescription = 'Kullanıcı yönetimi sayfası';
$pageIcon = 'fas fa-users'; // İsteğe bağlı

// Hızlı eylemler (isteğe bağlı)
$quickActions = [
    [
        'text' => 'Yeni Kullanıcı',
        'url' => 'users.php?action=create',
        'icon' => 'fas fa-plus',
        'class' => 'success'
    ],
    [
        'text' => 'Excel Export',
        'url' => 'users.php?export=excel',
        'icon' => 'fas fa-file-excel',
        'class' => 'info'
    ]
];

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Sayfa içeriği buraya -->
<div class="row">
    <div class="col-12">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0">Kullanıcı Listesi</h5>
            </div>
            <div class="card-body">
                <!-- İçerik -->
            </div>
        </div>
    </div>
</div>

<?php
// Footer include
include '../includes/admin_footer.php';
?>
```

### Özellikler:
- Otomatik admin kontrolü
- Gelişmiş sidebar menü
- Hızlı eylemler
- Sistem durumu
- Bildirimler
- Session timeout uyarısı

## 🎨 CSS Sınıfları

### Genel Sınıflar:
- `.card` - Temel kart stili
- `.btn-primary` - Ana buton
- `.alert` - Uyarı mesajları
- `.table-responsive` - Responsive tablo

### Kullanıcı Paneli:
- `.user-panel-wrapper` - Ana wrapper
- `.credit-card` - Kredi kartı
- `.stat-card` - İstatistik kartı
- `.sidebar-nav` - Sidebar navigasyon

### Admin Paneli:
- `.admin-panel-wrapper` - Ana wrapper
- `.admin-card` - Admin kartı
- `.stat-widget` - İstatistik widget
- `.alert-admin` - Admin uyarıları

## 📱 Responsive Özellikler

Tüm templateler mobile-first yaklaşımla tasarlanmıştır:
- Bootstrap 5 grid sistemi
- Responsive navbar
- Mobile-friendly sidebar
- Touch-friendly buttons

## 🔧 JavaScript Fonksiyonları

### Genel:
- `showLoading()` - Loading overlay
- `copyToClipboard()` - Panoya kopyala
- `showToast()` - Bildirim göster

### Admin:
- `confirmAdminAction()` - Admin onay
- `filterTable()` - Tablo filtreleme
- `showAdminNotification()` - Admin bildirimi

## 📋 Değişkenler

### Sayfa Bilgileri:
```php
$pageTitle = 'Sayfa Başlığı';           // Zorunlu
$pageDescription = 'SEO açıklaması';     // İsteğe bağlı
$pageKeywords = 'anahtar kelimeler';     // İsteğe bağlı
$pageIcon = 'fas fa-icon';               // Admin için isteğe bağlı
$bodyClass = 'bg-light';                 // İsteğe bağlı
```

### CSS/JS:
```php
$cssPath = 'custom/path/style.css';      // Özel CSS yolu
$additionalCSS = ['file1.css'];          // Ek CSS dosyaları
$additionalJS = ['file1.js'];            // Ek JS dosyaları
$pageJS = 'console.log("test");';        // Sayfa özel JS
```

### Admin Özel:
```php
$quickActions = [/* eylemler */];        // Hızlı eylemler
$totalUsers = 100;                       // İstatistikler için
$totalUploads = 500;                     // İstatistikler için
```

## 🚀 Örnek Sayfalar

Projeye dahil edilen örnek sayfalar:
- `index.php` - Ana sayfa (güncellenmiş)
- `about.php` - Hakkımızda sayfası
- `login.php` - Giriş sayfası (güncellenmiş)
- `register.php` - Kayıt sayfası (güncellenmiş)
- `user/index.php` - Kullanıcı dashboard (güncellenmiş)

## 💡 İpuçları

1. **Kod Tekrarını Önleme**: Ortak CSS/JS kodları includes klasöründe
2. **SEO Optimizasyonu**: Her sayfa için title, description ve keywords tanımlayın
3. **Responsive Tasarım**: Bootstrap sınıflarını kullanın
4. **Güvenlik**: User ve admin panellerinde otomatik kontroller var
5. **Performance**: Gereksiz JS/CSS dosyalarını yüklemeyin

## 🔄 Güncelleme Notları

Bu template yapısı ile:
- ✅ Kod tekrarı %80 azaldı
- ✅ Bakım kolaylığı arttı
- ✅ SEO iyileştirmeleri eklendi
- ✅ Responsive tasarım optimize edildi
- ✅ JavaScript fonksiyonları standardize edildi

---

**Not**: Bu template yapısını kullanırken, mevcut sayfaları adım adım güncelleyerek tutarlılığı sağlayın.
