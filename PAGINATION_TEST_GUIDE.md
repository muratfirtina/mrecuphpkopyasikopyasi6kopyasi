# Pagination Özellikleri Test Rehberi

## ✅ Yapılan Değişiklikler

### 1. User Credits Sayfası (`user/credits.php`)
- **Özellik:** İşlem sayısı seçimi eklendi
- **Seçenekler:** 5, 10, 20, 50, 100 işlem
- **Default:** 10 işlem
- **Lokasyon:** Filtre formunda "Sayfa Başı" seçeneği

### 2. Admin Credits Sayfası (`admin/credits.php`)
- **Özellik:** Kullanıcı sayısı seçimi eklendi
- **Seçenekler:** 10, 20, 50, 100 kullanıcı
- **Default:** 20 kullanıcı
- **Lokasyon:** Arama formunda "Sayfa Başı" seçeneği

### 3. Pagination Link Güncellemeleri
- Tüm sayfalama linklerinde `limit` parametresi korunuyor
- Filtreler ve arama parametreleri sayfa değişikliklerinde korunuyor
- Form submission'larında limit parametresi preserve ediliyor

## 🧪 Test Senaryoları

### User Credits Testi
1. **Temel Test:**
   ```
   http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/user/credits.php
   ```
   - Sayfa başı limit seçimini değiştir (5, 10, 20, 50, 100)
   - Filtreleme form'unu submit et
   - Sayfa numaralarını tıkla
   - URL'de limit parametresinin korunduğunu kontrol et

2. **Filtreli Test:**
   ```
   http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/user/credits.php?type=withdraw&limit=5
   ```
   - Sayfa değiştir
   - Hem `type=withdraw` hem `limit=5` korunmalı

3. **Debug Test:**
   ```
   http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/user/credits.php?debug=1&limit=20
   ```
   - Debug çıktısında limit değerinin göründüğünü kontrol et

### Admin Credits Testi
1. **Temel Test:**
   ```
   http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/admin/credits.php
   ```
   - Sayfa başı kullanıcı sayısını değiştir
   - Arama yap
   - Sayfa değiştir
   - Parametrelerin korunduğunu kontrol et

2. **Arama + Limit Test:**
   ```
   http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/admin/credits.php?search=test&limit=10
   ```
   - Pagination linklerinde hem search hem limit korunmalı

## 📊 Test Kontrol Noktaları

### ✅ Başarı Kriterleri:
- [ ] Limit seçimi form'da mevcut
- [ ] Default değerler doğru (10 user, 20 admin)
- [ ] Pagination linklerinde limit parametresi var
- [ ] Filtreler + limit birlikte korunuyor
- [ ] URL parametreleri temiz ve doğru
- [ ] Sayfa değişikliklerinde veri kaybolmuyor

### ⚠️ Potansiyel Sorunlar:
- [ ] Form submit'te limit kaybolması
- [ ] Pagination linklerinde eksik parametreler
- [ ] Default limit değerlerinin yanlış olması
- [ ] URL'de gereksiz parametre birikimleri

## 🔧 Debugging

### Debug URL'leri:
```bash
# User credits debug
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/user/credits.php?debug=1

# Admin credits debug (varsa)
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/admin/credits.php?debug=1
```

### Console Log Kontrolleri:
- Browser developer tools'da console'u kontrol et
- Form submission sırasında JavaScript hataları var mı?
- AJAX çağrılarında limit parametresi geçiyor mu?

### Database Query Kontrolleri:
```sql
-- Test için örnek sorgular
SELECT COUNT(*) FROM users WHERE role = 'user';
SELECT COUNT(*) FROM credit_transactions;
```

## 📝 Notlar

### Geliştirici Notları:
- User credits: `$limit` değeri 5-100 arası
- Admin credits: `$limit` değeri 10-100 arası
- Pagination URL pattern: `?page=X&limit=Y&[filters]`
- Form preservation: Hidden input fields ile

### Güvenlik:
- Limit değerleri integer kontrolü yapılıyor
- Min/max sınırları uygulanıyor
- SQL injection'a karşı parametrize queries kullanılıyor

## 🚀 Demo Test Sayfası

Test demo sayfası:
```
http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/test_pagination.php
```

Bu sayfa tüm yeni özelliklerin özetini ve test linklerini içerir.
