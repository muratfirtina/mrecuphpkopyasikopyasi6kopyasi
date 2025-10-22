# SQL Database Yapısı

Bu klasör database kurulum scriptlerini içerir.

## Dosyalar

- `full_database_structure.sql` - 76 tablonun tamamını içeren SQL scripti
- `README_SQL.md` - Bu dosya

## Kullanım

`install-guid.php` dosyası çalıştırıldığında otomatik olarak `full_database_structure.sql` dosyasını okur ve tüm tabloları oluşturur.

## Tablolar (76 adet)

### Kullanıcı ve Yetkilendirme (8)
- users
- user_credits
- user_email_preferences  
- user_permissions
- failed_logins
- csrf_tokens
- notifications
- system_logs

### Araç Bilgileri (7)
- brands
- models
- series
- engines
- devices
- ecus
- stages

### Dosya Yönetimi (9)
- file_uploads
- file_responses
- file_chats
- file_security_scans
- file_cancellations
- additional_files
- revisions
- revision_files
- legacy_files

### Ürün ve Kategori (9)
- categories
- products
- product_brands
- product_images
- product_attributes
- services
- service_contacts
- testimonials
- social_media_links

### İletişim ve İçerik (10)
- contact_messages
- contact_settings
- contact_cards
- contact_form_settings
- contact_office
- about_content
- about_vision
- about_core_values
- about_service_features
- content_management

### Email Sistemi (8)
- email_config
- email_templates
- email_queue
- email_logs
- email_bounces
- email_campaigns
- email_statistics
- email_test_logs

### Tasarım ve SEO (4)
- design_sliders
- design_settings
- design_logs
- seo_settings

### Güvenlik (7)
- security_config
- security_logs
- ip_security
- rate_limits
- waf_rules
- websocket_config
- websocket_logs

### Kredi İşlemleri (2)
- credit_transactions
- legacy_wallet_log

### Destek ve Ticket (3)
- legacy_tickets
- legacy_ticket_admin
- legacy_ticket_user

### Mapping Tabloları (3)
- temp_brand_mapping
- temp_model_mapping
- temp_user_mapping

### Medya ve Diğer (6)
- media_files
- settings
- chat_unread_counts
- tuning_search_view (VIEW)
- brands_backup
- models_backup

## Önemli Notlar

1. **UUID/GUID Sistemi**: Tüm ID alanları CHAR(36) formatında UUID kullanır
2. **Foreign Key İlişkileri**: Tablolar arası ilişkiler tanımlı
3. **Timestamp Alanları**: created_at, updated_at alanları otomatik güncellenir
4. **Enum Alanlar**: Status, role gibi alanlar enum tipindedir
5. **Index'ler**: Primary key'ler ve foreign key'ler index'li

## Kurulum Öncesi

- MySQL 8.0 veya üzeri gereklidir
- InnoDB engine kullanılır
- utf8mb4_unicode_ci collation kullanılır
- Minimum 100MB database alanı önerilir

## Güncelleme

Database yapısı güncellendiğinde:
1. `development-database.txt` dosyasını güncelleyin
2. `full_database_structure.sql` dosyasını yeniden oluşturun
3. Migration script'i hazırlayın (gerekirse)
