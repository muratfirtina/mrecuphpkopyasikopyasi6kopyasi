#!/bin/bash

# ==================================================
# Mr ECU - Quick Production Deployment Script
# mrecutuning.com için hızlı deployment
# ==================================================

set -e  # Hata durumunda durdur

echo "🚀 Mr ECU Production Deployment Started..."
echo "Domain: https://www.mrecutuning.com"
echo "Timestamp: $(date)"
echo "=================================="

# Renk kodları
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Başarı/hata fonksiyonları
success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
    exit 1
}

info() {
    echo -e "${BLUE}ℹ️ $1${NC}"
}

# 1. BACKUP OLUŞTUR
echo -e "\n${BLUE}📁 STEP 1: Creating Backup${NC}"
BACKUP_DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="mrecu_backup_${BACKUP_DATE}.tar.gz"

if [ -d ".git" ]; then
    info "Git repository detected, creating backup..."
    tar -czf "${BACKUP_FILE}" --exclude='.git' --exclude='node_modules' --exclude='*.log' .
else
    info "Creating full backup..."
    tar -czf "${BACKUP_FILE}" --exclude='*.log' .
fi

success "Backup created: ${BACKUP_FILE}"

# 2. PRODUCTION DOSYALARINI AKTİF ET
echo -e "\n${BLUE}⚙️ STEP 2: Activating Production Configuration${NC}"

# .env.production -> .env
if [ -f ".env.production" ]; then
    if [ -f ".env" ]; then
        cp ".env" ".env.backup"
        info "Current .env backed up to .env.backup"
    fi
    cp ".env.production" ".env"
    success ".env.production activated"
else
    error ".env.production file not found!"
fi

# .htaccess.production -> .htaccess
if [ -f ".htaccess.production" ]; then
    if [ -f ".htaccess" ]; then
        cp ".htaccess" ".htaccess.backup"
        info "Current .htaccess backed up to .htaccess.backup"
    fi
    cp ".htaccess.production" ".htaccess"
    success ".htaccess.production activated"
else
    warning ".htaccess.production not found, using existing .htaccess"
fi

# Production marker oluştur
echo "Deployed on $(date)" > .production
success "Production marker created"

# 3. DOSYA İZİNLERİNİ AYARLA
echo -e "\n${BLUE}🔐 STEP 3: Setting File Permissions${NC}"

# Klasör izinleri
chmod 755 . 2>/dev/null || warning "Could not set permissions for root directory"
chmod 755 assets/ 2>/dev/null || warning "Could not set permissions for assets/"
chmod 755 uploads/ 2>/dev/null || warning "Could not set permissions for uploads/"
chmod 750 logs/ 2>/dev/null || warning "Could not set permissions for logs/"
chmod 750 config/ 2>/dev/null || warning "Could not set permissions for config/"
chmod 750 security/ 2>/dev/null || warning "Could not set permissions for security/"

# Dosya izinleri
chmod 644 .env 2>/dev/null || warning "Could not set permissions for .env"
chmod 644 .htaccess 2>/dev/null || warning "Could not set permissions for .htaccess"
find . -name "*.php" -type f -exec chmod 644 {} \; 2>/dev/null || warning "Could not set PHP file permissions"

success "File permissions configured"

# 4. LOGS KLASÖRÜ OLUŞTUR
echo -e "\n${BLUE}📝 STEP 4: Creating Logs Directory${NC}"

if [ ! -d "logs" ]; then
    mkdir -p logs
    success "Logs directory created"
else
    info "Logs directory already exists"
fi

# Log dosyalarını oluştur
touch logs/error.log logs/security.log logs/access.log
chmod 640 logs/*.log 2>/dev/null || warning "Could not set log file permissions"
success "Log files initialized"

# 5. UPLOADS KLASÖRÜ YAPISINI OLUŞTUR
echo -e "\n${BLUE}📁 STEP 5: Creating Upload Directory Structure${NC}"

UPLOAD_DIRS=("uploads" "uploads/products" "uploads/brands" "uploads/users" "uploads/temp")

for dir in "${UPLOAD_DIRS[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        info "Created directory: $dir"
    else
        info "Directory exists: $dir"
    fi
done

# .htaccess dosyası uploads klasöründe
cat > uploads/.htaccess << 'EOF'
# Mr ECU - Uploads Security
Options -Indexes
<FilesMatch "\.php$">
    Require all denied
</FilesMatch>

# Allow image files
<FilesMatch "\.(jpg|jpeg|png|gif|webp|avif|bmp|svg)$">
    Require all granted
</FilesMatch>

# Allow document files  
<FilesMatch "\.(pdf|doc|docx|txt|zip|rar)$">
    Require all granted
</FilesMatch>

# Allow ECU files
<FilesMatch "\.(bin|hex|ori|mod|edc)$">
    Require all granted
</FilesMatch>
EOF

success "Upload directory structure created with security"

# 6. KONFIGÜRASYON KONTROLÜ
echo -e "\n${BLUE}🔍 STEP 6: Configuration Validation${NC}"

# .env dosyası kontrolleri
if grep -q "DB_HOST=localhost" .env && grep -q "mrecutuning_com_mrecu_db_guid" .env; then
    success "Database configuration looks correct"
else
    warning "Database configuration may need review"
fi

if grep -q "SITE_URL=https://www.mrecutuning.com" .env; then
    success "Site URL configured correctly"
else
    warning "Site URL configuration may need review"
fi

if grep -q "DEBUG=false" .env; then
    success "Production debug mode disabled"
else
    warning "Debug mode should be false in production"
fi

# 7. GÜVENLİK KONTROLÜ
echo -e "\n${BLUE}🛡️ STEP 7: Security Verification${NC}"

SECURITY_FILES=("security/SecurityManager.php" "security/SecureDatabase.php" "security/SecurityHeaders.php")

for file in "${SECURITY_FILES[@]}"; do
    if [ -f "$file" ]; then
        success "Security file present: $file"
    else
        warning "Security file missing: $file"
    fi
done

# Hassas dosyaların korunup korunmadığını kontrol et
if grep -q "\.env" .htaccess && grep -q "config/" .htaccess; then
    success "Sensitive files protected in .htaccess"
else
    warning "Sensitive files may not be properly protected"
fi

# 8. DATABASE KONTROL HAZIRLIĞI
echo -e "\n${BLUE}💾 STEP 8: Database Preparation${NC}"

info "Database connection details:"
echo "  Host: localhost:3306"
echo "  Database: mrecutuning_com_mrecu_db_guid"
echo "  Username: mrecu_admin"
echo "  Password: [CONFIGURED]"

warning "Don't forget to import your database to the hosting MySQL!"

# 9. SSL/HTTPS KONTROL HAZIRLIĞI
echo -e "\n${BLUE}🔐 STEP 9: SSL/HTTPS Configuration${NC}"

info "HTTPS redirect configured in .htaccess"
info "Make sure SSL certificate is active on Natro hosting"

# 10. TEMIZLIK
echo -e "\n${BLUE}🧹 STEP 10: Cleanup${NC}"

# Geliştirme dosyalarını temizle
DEV_FILES=("README.md" "package-lock.json" ".eslintrc.json" ".prettierrc.json" ".stylelintrc.json" ".gitignore")

for file in "${DEV_FILES[@]}"; do
    if [ -f "$file" ]; then
        info "Development file present: $file (consider removing for production)"
    fi
done

# Temp dosyaları temizle
find . -name "*.tmp" -type f -delete 2>/dev/null || true
find . -name "*.temp" -type f -delete 2>/dev/null || true
find . -name "*~" -type f -delete 2>/dev/null || true

success "Cleanup completed"

# 11. SON KONTROLLER
echo -e "\n${BLUE}✅ STEP 11: Final Verification${NC}"

# Kritik dosya varlık kontrolü
CRITICAL_FILES=(".env" ".htaccess" "index.php" "config/config.php" "config/database.php")

for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        success "Critical file present: $file"
    else
        error "Critical file missing: $file"
    fi
done

# 12. DEPLOYMENT RAPORU
echo -e "\n${GREEN}📊 DEPLOYMENT REPORT${NC}"
echo "=================================="
echo "✅ Backup Created: ${BACKUP_FILE}"
echo "✅ Production Configuration: ACTIVE"
echo "✅ File Permissions: SET"
echo "✅ Security Configuration: CHECKED"
echo "✅ Upload Directories: CREATED"
echo "✅ Log System: INITIALIZED"
echo ""
echo "🚨 MANUAL STEPS REQUIRED:"
echo "1. Upload all files to public_html/ via FTP"
echo "2. Import database to Natro hosting MySQL"
echo "3. Activate SSL certificate on domain"
echo "4. Test website: https://www.mrecutuning.com"
echo "5. Check health: https://www.mrecutuning.com/health-check.php"
echo ""
echo -e "${GREEN}🎉 Production deployment preparation completed!${NC}"
echo -e "${BLUE}Next: Upload via FTP and complete database import${NC}"

# 13. HEALTH CHECK URL
echo -e "\n${YELLOW}🏥 Health Check URL:${NC}"
echo "https://www.mrecutuning.com/health-check.php"

echo -e "\n${BLUE}Deployment script completed at $(date)${NC}"
