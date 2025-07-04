# Kategori ve Ürün Yönetimi Kurulum Kılavuzu

## 1. Veritabanı Tablolarını Oluşturma

Aşağıdaki adımları sırayla takip edin:

### Adım 1: Tarayıcıda Kurulum Dosyasını Çalıştırın
```
http://localhost:8888/mrecuphpkopyasi/config/install-categories-products.php
```

Bu dosya şu tabloları oluşturacak:
- `categories` - Kategoriler tablosu
- `products` - Ürünler tablosu  
- `product_images` - Ürün fotoğrafları tablosu
- `product_attributes` - Ürün özellikleri tablosu

### Adım 2: Upload Klasörlerini Kontrol Edin
Aşağıdaki klasörlerin oluşturulduğunu kontrol edin:
- `/uploads/categories/` - Kategori fotoğrafları için
- `/uploads/products/` - Ürün fotoğrafları için

## 2. Admin Panel Özellikleri

### Kategori Yönetimi (`admin/categories.php`)
- ✅ Kategori ekleme, düzenleme, silme
- ✅ Hiyerarşik kategori yapısı (ana/alt kategoriler)
- ✅ Kategori fotoğrafı yükleme
- ✅ SEO ayarları (meta title, description)
- ✅ Aktif/pasif durumu
- ✅ Sıralama özelliği

### Ürün Yönetimi (`admin/products.php`)
- ✅ Ürün ekleme, düzenleme, silme
- ✅ 5 adet ürün fotoğrafı yükleme
- ✅ Stok yönetimi
- ✅ Fiyat ve indirimli fiyat
- ✅ Ürün özellikleri (dinamik alanlar)
- ✅ Kategori atama
- ✅ Öne çıkan ürün işaretleme
- ✅ SEO ayarları
- ✅ Fiziksel özellikler (ağırlık, boyut)

### Dashboard Güncellemeleri
- ✅ Kategori istatistikleri
- ✅ Ürün istatistikleri
- ✅ Öne çıkan ürün sayısı
- ✅ Aktif ürün oranı
- ✅ Hızlı erişim butonları

## 3. Veritabanı Yapısı

### Categories Tablosu
```sql
- id (Primary Key)
- name (Kategori adı)
- slug (URL dostu ad)
- description (Açıklama)
- image (Fotoğraf yolu)
- parent_id (Üst kategori ID)
- sort_order (Sıralama)
- is_active (Aktif/Pasif)
- meta_title (SEO başlık)
- meta_description (SEO açıklama)
- created_at, updated_at (Tarihler)
```

### Products Tablosu
```sql
- id (Primary Key)
- name (Ürün adı)
- slug (URL dostu ad)
- description (Detaylı açıklama)
- short_description (Kısa açıklama)
- sku (Ürün kodu)
- price (Fiyat)
- sale_price (İndirimli fiyat)
- stock_quantity (Stok miktarı)
- manage_stock (Stok takibi)
- stock_status (Stok durumu)
- weight (Ağırlık)
- dimensions (Boyutlar)
- category_id (Kategori ID)
- featured (Öne çıkan)
- is_active (Aktif/Pasif)
- sort_order (Sıralama)
- meta_title, meta_description (SEO)
- views (Görüntülenme)
- created_at, updated_at (Tarihler)
```

### Product Images Tablosu
```sql
- id (Primary Key)
- product_id (Ürün ID)
- image_path (Fotoğraf yolu)
- alt_text (Alt metin)
- sort_order (Sıralama)
- is_primary (Ana fotoğraf)
- created_at (Tarih)
```

### Product Attributes Tablosu
```sql
- id (Primary Key)
- product_id (Ürün ID)
- attribute_name (Özellik adı)
- attribute_value (Özellik değeri)
- sort_order (Sıralama)
- created_at (Tarih)
```

## 4. Kullanım

### Kategori Ekleme
1. Admin Panel → Kategoriler
2. "Yeni Kategori" butonuna tıklayın
3. Kategori bilgilerini doldurun
4. İsteğe bağlı fotoğraf yükleyin
5. Kaydet

### Ürün Ekleme
1. Admin Panel → Ürünler
2. "Yeni Ürün" butonuna tıklayın
3. Ürün bilgilerini doldurun
4. Kategori seçin
5. Fotoğrafları yükleyin (maksimum 5 adet)
6. Ürün özelliklerini ekleyin
7. Kaydet

### Filtreleme ve Arama
- Ürünler sayfasında kategori bazlı filtreleme
- Ürün adı veya SKU ile arama
- Sayfalama desteği

## 5. Dosya Yükleme Limitleri

- **Kategori fotoğrafı:** Maksimum 5MB
- **Ürün fotoğrafları:** Her biri maksimum 5MB
- **Desteklenen formatlar:** JPG, PNG, GIF, WEBP

## 6. SEO Özellikleri

- Otomatik slug oluşturma
- Meta title ve description alanları
- Türkçe karakter desteği
- URL dostu yapı

## 7. Güvenlik

- Admin yetkisi kontrolü
- Dosya tipi kontrolü
- SQL injection koruması
- XSS koruması
- CSRF token (gelecek güncellemede)

## 8. Sorun Giderme

### Fotoğraf Yüklenmiyor
- Upload klasörlerinin yazma iznini kontrol edin (chmod 777)
- PHP'nin upload_max_filesize ayarını kontrol edin
- Web sunucusunun dosya yükleme limitini kontrol edin

### Veritabanı Hatası
- Veritabanı bağlantı ayarlarını kontrol edin
- MySQL servisinin çalıştığından emin olun
- Gerekli tabloların oluşturulduğunu kontrol edin

### Sayfa Yüklenmiyor
- Web sunucusunun çalıştığından emin olun
- PHP hatalarını kontrol edin (error_log)
- .htaccess dosyasının doğru olduğunu kontrol edin

## 9. Gelecek Güncellemeler

- [ ] Toplu ürün yükleme (Excel/CSV)
- [ ] Ürün varyantları (renk, beden vb.)
- [ ] Stok uyarı sistemi
- [ ] Kategori ve ürün SEO raporları
- [ ] API desteği
- [ ] Ürün yorumları
- [ ] İlgili ürünler önerisi
- [ ] Ürün etiketleri
- [ ] Gelişmiş filtreleme seçenekleri
- [ ] Ürün import/export

## 10. Teknik Detaylar

- **PHP Versiyonu:** 7.4+
- **MySQL Versiyonu:** 5.7+
- **Bootstrap:** 5.1.3
- **Font Awesome:** 6.0.0
- **JavaScript:** Vanilla JS (jQuery bağımlılığı yok)

Herhangi bir sorun yaşarsanız, admin panel log bölümünden detaylı hata mesajlarını kontrol edebilirsiniz.
