# 🔧 Mr ECU Proje Başlatma Hızlı Çözümler

## ❌ Olası Sorunlar ve Çözümleri

### 1. Veritabanı Bağlantı Hatası
```bash
# Çözüm 1: GUID veritabanını kurun
http://localhost:8888/mrecuphpkopyasi/config/install-guid.php

# Çözüm 2: MAMP'ı yeniden başlatın
- MAMP'ı kapatın
- MAMP'ı açın ve "Start Servers" 
```

### 2. Tablo Bulunamadı Hatası
```bash
# GUID veritabanını yeniden kurun:
http://localhost:8888/mrecuphpkopyasi/config/install-guid.php
```

### 3. GUID Fonksiyonları Çalışmıyor
```bash
# config.php dosyasını kontrol edin
# Eğer hata varsa temel kurulum yapın:
http://localhost:8888/mrecuphpkopyasi/config/install.php
```

## ✅ Başarılı Kurulum Sonrası

### 🏠 Ana Sayfa
```bash
http://localhost:8888/mrecuphpkopyasi/
```

### 👤 Admin Girişi
```bash
http://localhost:8888/mrecuphpkopyasi/login.php

Kullanıcı: admin
Şifre: admin123
```

### 📝 Yeni Kullanıcı Kaydı
```bash
http://localhost:8888/mrecuphpkopyasi/register.php
```

### ⚙️ Admin Panel
```bash
http://localhost:8888/mrecuphpkopyasi/admin/
```

## 🧪 Test Araçları

### GUID Sistem Testleri
```bash
# Kapsamlı GUID test:
http://localhost:8888/mrecuphpkopyasi/final-guid-migration-complete.php

# Temel sistem test:
http://localhost:8888/mrecuphpkopyasi/test-guid-system.php

# Hızlı kontrol:
http://localhost:8888/mrecuphpkopyasi/final-guid-check.php
```

## 📱 Mobil Uyumluluk
- Tüm sayfalar responsive tasarım
- Mobil cihazlardan test edebilirsiniz

## 🔒 Güvenlik Notları
- Admin şifresini değiştirmeyi unutmayın
- GUID sistem aktif (güvenli ID'ler)
- SQL injection koruması aktif
