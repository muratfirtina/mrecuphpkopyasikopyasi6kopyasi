#!/bin/bash

# Mr ECU - Email Queue Cron Job Setup Script
# Bu script email queue işleme cron job'ını kurar

echo "======================================="
echo "Mr ECU Email Queue Cron Job Setup"
echo "======================================="

# Proje dizinini belirle
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
PHP_PATH=$(which php)
CRON_SCRIPT="$SCRIPT_DIR/process_email_queue.php"
LOG_DIR="$PROJECT_DIR/logs"
LOG_FILE="$LOG_DIR/email_queue.log"

echo "Proje dizini: $PROJECT_DIR"
echo "PHP path: $PHP_PATH"
echo "Cron script: $CRON_SCRIPT"

# Log dizinini oluştur
if [ ! -d "$LOG_DIR" ]; then
    echo "Log dizini oluşturuluyor: $LOG_DIR"
    mkdir -p "$LOG_DIR"
fi

# Log dosyasını oluştur
if [ ! -f "$LOG_FILE" ]; then
    echo "Log dosyası oluşturuluyor: $LOG_FILE"
    touch "$LOG_FILE"
fi

# Dosya izinlerini ayarla
chmod +x "$CRON_SCRIPT"
chmod 664 "$LOG_FILE"

echo ""
echo "Cron job seçenekleri:"
echo "1. Her dakika çalıştır (yoğun sistem için)"
echo "2. Her 5 dakikada çalıştır (orta yoğunluk için)"
echo "3. Her 15 dakikada çalıştır (düşük yoğunluk için)"
echo "4. Manuel kurulum (kendi crontab'ınıza ekleyeceksiniz)"
echo ""

read -p "Seçiminizi yapın (1-4): " choice

case $choice in
    1)
        CRON_TIMING="* * * * *"
        DESCRIPTION="Her dakika"
        ;;
    2)
        CRON_TIMING="*/5 * * * *"
        DESCRIPTION="Her 5 dakikada"
        ;;
    3)
        CRON_TIMING="*/15 * * * *"
        DESCRIPTION="Her 15 dakikada"
        ;;
    4)
        echo ""
        echo "Manuel kurulum için aşağıdaki satırı crontab'ınıza ekleyin:"
        echo ""
        echo "# Mr ECU Email Queue Processor"
        echo "*/5 * * * * $PHP_PATH $CRON_SCRIPT >> $LOG_FILE 2>&1"
        echo ""
        echo "Crontab düzenlemek için: crontab -e"
        exit 0
        ;;
    *)
        echo "Geçersiz seçim!"
        exit 1
        ;;
esac

# Cron job satırını oluştur
CRON_LINE="$CRON_TIMING $PHP_PATH $CRON_SCRIPT >> $LOG_FILE 2>&1"

echo ""
echo "Oluşturulacak cron job:"
echo "$CRON_LINE"
echo "Açıklama: $DESCRIPTION"
echo ""

read -p "Bu cron job'ı kurmak istediğinizden emin misiniz? (y/n): " confirm

if [[ $confirm =~ ^[Yy]$ ]]; then
    # Mevcut crontab'ı al
    (crontab -l 2>/dev/null | grep -v "process_email_queue.php"; echo "# Mr ECU Email Queue Processor - $DESCRIPTION"; echo "$CRON_LINE") | crontab -
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "✓ Cron job başarıyla kuruldu!"
        echo ""
        echo "Kurulum detayları:"
        echo "- Çalışma sıklığı: $DESCRIPTION"
        echo "- Script: $CRON_SCRIPT"
        echo "- Log dosyası: $LOG_FILE"
        echo ""
        echo "Cron job'ları görüntülemek için: crontab -l"
        echo "Log dosyasını takip etmek için: tail -f $LOG_FILE"
        echo ""
        
        # İlk test çalıştırması
        echo "Test çalıştırması yapılıyor..."
        $PHP_PATH "$CRON_SCRIPT"
        
        if [ $? -eq 0 ]; then
            echo "✓ Test çalıştırması başarılı!"
        else
            echo "✗ Test çalıştırması başarısız! Log dosyasını kontrol edin."
        fi
        
    else
        echo "✗ Cron job kurulumu başarısız!"
        exit 1
    fi
else
    echo "Kurulum iptal edildi."
    exit 0
fi

echo ""
echo "======================================="
echo "Kurulum Tamamlandı!"
echo "======================================="
echo ""
echo "Önemli Notlar:"
echo "1. Email queue sistemini aktif hale getirmek için admin panel > Email Ayarları'ndan 'Email Queue' seçeneğini aktifleştirin"
echo "2. Log dosyasını düzenli olarak temizlemeyi unutmayın"
echo "3. Server yeniden başlatıldığında cron job'lar otomatik devam eder"
echo "4. Sorun yaşarsanız log dosyasını kontrol edin: $LOG_FILE"
echo ""
echo "Email queue durumunu kontrol etmek için: $PHP_PATH $CRON_SCRIPT"
echo ""
