# Mr ECU - Ürünler Dropdown Sistemi Kurulum Kılavuzu

Bu kılavuz, header'da Ürünler dropdown menüsü ve ilgili sayfa yapısının kurulumu için gerekli adımları açıklar.

## 🎯 Özellikler

### ✅ Tamamlanan İşlemler:

1. **Header Dropdown Menüsü**
   - `includes/header.php` dosyasına Ürünler dropdown menüsü eklendi
   - Kategoriler dinamik olarak listeleniyor
   - Her kategorinin yanında ürün sayısı gösteriliyor

2. **Kategori Sayfası** (`category.php`)
   - `/kategori/kategori-slug` URL yapısı
   - Kategorideki tüm markaları listeler
   - Her markadaki ürün sayısını gösterir
   - Öne çıkan ürünleri sergiler

3. **Kategori-Marka Ürünleri Sayfası** (`category-brand-products.php`)
   - `/kategori/kategori-slug/marka/marka-slug` URL yapısı
   - Belirli kategorideki belirli markaya ait ürünleri listeler
   - Sayfalama ve sıralama özellikleri
   - İlgili markalar bölümü

4. **SEO Dostu URL Yapısı**
   - `.htaccess` dosyası güncellendi
   - Tüm sayfalar için SEO dostu URL rewrite kuralları eklendi

5. **Veri Kurulum Sistemi**
   - `install-categories-system.php` - Categories tablosu ve örnek kategoriler
   - Otomatik kategori atama sistemi

## 🛠️ Kurulum Adımları

### Adım 1: Veritabanı Kurulumu

```bash
# 1. Categories sistemini kur
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/install-categories-system.php

# 2. Product Brands sistemini kur (eğer daha önce kurulmadıysa)
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/install-product-system.php
```

### Adım 2: Sistem Testi

1. **Ana sayfaya git**: `http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/`
2. **Header'daki "Ürünler" dropdown'ına tıkla**
3. **Test linkleri**:
   - `/kategori/ecu-programlama-cihazlari` - ECU kategorisi
   - `/kategori/ecu-programlama-cihazlari/marka/autotuner` - AutoTuner ürünleri
   - `/urun/autotuner-ecu-programlama-cihazi-guc-ve-verimliligi-bir-arada-sunun` - Ürün detayı

## 🏗️ Sistem Mimarisi

### URL Yapısı:
```
/                                    -> Ana sayfa
/urunler                            -> Tüm ürünler  
/kategori/{slug}                    -> Kategori sayfası (markaları listeler)
/kategori/{slug}/marka/{slug}       -> Kategori+Marka ürünleri
/urun/{slug}                        -> Ürün detay sayfası
/marka/{slug}                       -> Marka ürünleri (tüm kategorilerden)
```

### Veritabanı İlişkileri:
```
categories (id, name, slug, description...)
    ↓ (1:N)
products (id, name, category_id, brand_id...)
    ↓ (N:1)
product_brands (id, name, slug, logo...)
    ↓ (1:N)  
product_images (id, product_id, image_path...)
```

### Navigasyon Akışı:
```
Header Dropdown → Kategoriler listesi
    ↓ (kategori seçildi)
Kategori Sayfası → O kategorideki markalar
    ↓ (marka seçildi)  
Kategori+Marka Sayfası → O kombinasyondaki ürünler
    ↓ (ürün seçildi)
Ürün Detay Sayfası
```

## 🎨 Algoritma Özellikleri

### Kategori-Marka İlişkisi:
- Bir kategorideki markalar, **sadece o kategoride ürünü olan markalar** olarak listelenir
- Bu sayede boş marka sayfaları oluşmaz
- Her markanın yanında o kategorideki ürün sayısı gösterilir

### Performans Optimizasyonları:
- JOIN sorguları ile veritabanı çağrıları optimize edildi
- Sayfalama ile büyük listelerde performans korundu
- Lazy loading ile resim yüklemeleri optimize edildi

### SEO Optimizasyonları:
- Her sayfa için özel meta title/description
- Breadcrumb navigasyon
- JSON-LD structured data
- Clean URL yapısı

## 🔧 Özelleştirme

### Dropdown Özelleştirme:
`includes/header.php` dosyasında `LIMIT 10` değerini değiştirerek dropdown'da gösterilen kategori sayısını ayarlayabilirsiniz.

### Sayfa Başına Ürün Sayısı:
`config/config.php` dosyasında `PRODUCTS_PER_PAGE` değerini değiştirin.

### Stil Özelleştirmesi:
Her sayfada kendi CSS'i bulunmaktadır. Genel stiller `assets/css/style.css` dosyasından yönetilir.

## 🐛 Sorun Giderme

### Header Dropdown Çalışmıyor:
- Veritabanı bağlantısını kontrol edin
- Categories tablosunun var olduğunu kontrol edin
- `is_active = 1` olan kategoriler olduğunu kontrol edin

### 404 Sayfaları:
- `.htaccess` dosyasının doğru yüklendiğini kontrol edin
- Apache mod_rewrite modulünün aktif olduğunu kontrol edin
- MAMP'ta rewrite kurallarının çalıştığını kontrol edin

### Boş Sayfalar:
- Kategori ve markaların `is_active = 1` olduğunu kontrol edin
- İlgili ürünlerin var olduğunu ve aktif olduğunu kontrol edin

## 📁 Dosya Listesi

### Yeni Oluşturulan Dosyalar:
- `category.php` - Kategori sayfası
- `category-brand-products.php` - Kategori+marka ürün listesi
- `install-categories-system.php` - Kurulum dosyası

### Güncellenen Dosyalar:
- `includes/header.php` - Dropdown menü eklendi
- `.htaccess` - URL rewrite kuralları eklendi

### Mevcut Dosyalar (Kullanılan):
- `products.php` - Ürün listesi sayfası
- `product-detail.php` - Ürün detay sayfası
- `config/config.php` - Konfigürasyon ayarları
- `config/database.php` - Veritabanı bağlantısı

Bu sistem sayesinde ziyaretçiler:
1. Header'dan kategori seçebilir
2. Kategori sayfasında o kategorideki markaları görebilir  
3. Marka seçerek o kategorideki o markaya ait ürünleri görüntüleyebilir
4. Ürün detayına gidebilir

Sistem tamamen dinamik çalışır ve yeni kategori/marka/ürün eklendiğinde otomatik olarak menülerde görünür.
