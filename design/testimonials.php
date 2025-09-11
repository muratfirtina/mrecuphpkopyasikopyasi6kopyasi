<?php
/**
 * Mr ECU - Design Panel - Testimonials Management
 * Müşteri yorumları yönetimi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Sayfa bilgileri
$pageTitle = 'Testimonials Yönetimi';
$pageDescription = 'Müşteri yorumları ve değerlendirmelerini yönetin';
$pageKeywords = 'testimonials, müşteri yorumları, değerlendirmeler, yönetim';

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Design Panel', 'url' => 'index.php'],
    ['title' => 'Testimonials Yönetimi']
];

$message = '';
$messageType = '';

// Testimonials tablosu var mı kontrol et
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'testimonials'");
    if ($stmt->rowCount() === 0) {
        redirect('../admin/setup-testimonials.php');
    }
} catch (PDOException $e) {
    redirect('../admin/setup-testimonials.php');
}

// Form işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $name = trim($_POST['name'] ?? '');
                $position = trim($_POST['position'] ?? '');
                $comment = trim($_POST['comment'] ?? '');
                $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
                $avatarUrl = trim($_POST['avatar_url'] ?? '');
                $displayOrder = (int)($_POST['display_order'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($name) || empty($position) || empty($comment)) {
                    throw new Exception('Ad, pozisyon ve yorum alanları zorunludur.');
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO testimonials (name, position, comment, rating, avatar_url, display_order, is_active, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $currentDateTime = date('Y-m-d H:i:s');
                $stmt->execute([$name, $position, $comment, $rating, $avatarUrl ?: null, $displayOrder, $isActive, $currentDateTime, $currentDateTime]);
                
                $message = 'Yeni testimonial başarıyla eklendi.';
                $messageType = 'success';
                break;
                
            case 'edit':
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $position = trim($_POST['position'] ?? '');
                $comment = trim($_POST['comment'] ?? '');
                $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
                $avatarUrl = trim($_POST['avatar_url'] ?? '');
                $displayOrder = (int)($_POST['display_order'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if ($id <= 0 || empty($name) || empty($position) || empty($comment)) {
                    throw new Exception('Geçersiz ID veya eksik bilgiler.');
                }
                
                $stmt = $pdo->prepare("
                    UPDATE testimonials 
                    SET name = ?, position = ?, comment = ?, rating = ?, avatar_url = ?, display_order = ?, is_active = ?, updated_at = ?
                    WHERE id = ?
                ");
                $currentDateTime = date('Y-m-d H:i:s');
                $stmt->execute([$name, $position, $comment, $rating, $avatarUrl ?: null, $displayOrder, $isActive, $currentDateTime, $id]);
                
                $message = 'Testimonial başarıyla güncellendi.';
                $messageType = 'success';
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Geçersiz ID.');
                }
                
                $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
                $stmt->execute([$id]);
                
                $message = 'Testimonial başarıyla silindi.';
                $messageType = 'success';
                break;
                
            case 'toggle_status':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Geçersiz ID.');
                }
                
                $stmt = $pdo->prepare("UPDATE testimonials SET is_active = NOT is_active, updated_at = ? WHERE id = ?");
                $stmt->execute([date('Y-m-d H:i:s'), $id]);
                
                $message = 'Durum başarıyla güncellendi.';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Testimonials listesi
$stmt = $pdo->query("SELECT * FROM testimonials ORDER BY display_order ASC, created_at DESC");
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Düzenleme için seçili testimonial
$editTestimonial = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editTestimonial = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Header include
include '../includes/design_header.php';
?>

<!-- Testimonials Management Content -->
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="bi bi-chat-quote me-2"></i>Testimonials Yönetimi</h2>
                        <a href="../index.php" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>Ana Sayfada Gör
                        </a>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Form Bölümü -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <?php echo $editTestimonial ? 'Testimonial Düzenle' : 'Yeni Testimonial Ekle'; ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="<?php echo $editTestimonial ? 'edit' : 'add'; ?>">
                                        <?php if ($editTestimonial): ?>
                                            <input type="hidden" name="id" value="<?php echo $editTestimonial['id']; ?>">
                                        <?php endif; ?>

                                        <div class="mb-3">
                                            <label for="name" class="form-label">Ad Soyad *</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($editTestimonial['name'] ?? ''); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="position" class="form-label">Pozisyon/Meslek *</label>
                                            <input type="text" class="form-control" id="position" name="position" 
                                                   value="<?php echo htmlspecialchars($editTestimonial['position'] ?? ''); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="comment" class="form-label">Yorum *</label>
                                            <textarea class="form-control" id="comment" name="comment" rows="4" required><?php echo htmlspecialchars($editTestimonial['comment'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label for="rating" class="form-label">Rating</label>
                                            <select class="form-select" id="rating" name="rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <option value="<?php echo $i; ?>" <?php echo ($editTestimonial['rating'] ?? 5) == $i ? 'selected' : ''; ?>>
                                                        <?php echo $i; ?> Yıldız
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="avatar_url" class="form-label">Avatar URL (Opsiyonel)</label>
                                            <input type="url" class="form-control" id="avatar_url" name="avatar_url" 
                                                   value="<?php echo htmlspecialchars($editTestimonial['avatar_url'] ?? ''); ?>">
                                            <small class="form-text text-muted">Boş bırakılırsa varsayılan avatar kullanılır</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="display_order" class="form-label">Sıralama</label>
                                            <input type="number" class="form-control" id="display_order" name="display_order" 
                                                   value="<?php echo $editTestimonial['display_order'] ?? 0; ?>" min="0">
                                            <small class="form-text text-muted">Düşük sayı önce gösterilir</small>
                                        </div>

                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                                   <?php echo ($editTestimonial['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_active">Aktif</label>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-<?php echo $editTestimonial ? 'pencil' : 'plus'; ?> me-1"></i>
                                                <?php echo $editTestimonial ? 'Güncelle' : 'Ekle'; ?>
                                            </button>
                                            <?php if ($editTestimonial): ?>
                                                <a href="testimonials.php" class="btn btn-secondary">
                                                    <i class="bi bi-x me-1"></i>İptal
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Testimonials Listesi -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Mevcut Testimonials (<?php echo count($testimonials); ?>)</h5>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($testimonials)): ?>
                                        <div class="text-center p-4">
                                            <i class="bi bi-chat-quote text-muted" style="font-size: 3rem;"></i>
                                            <p class="text-muted mt-2">Henüz testimonial eklenmemiş.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>Sıra</th>
                                                        <th>Müşteri</th>
                                                        <th>Yorum</th>
                                                        <th>Rating</th>
                                                        <th>Durum</th>
                                                        <th>Tarih</th>
                                                        <th>İşlemler</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($testimonials as $testimonial): ?>
                                                        <tr class="<?php echo $testimonial['is_active'] ? '' : 'table-secondary'; ?>">
                                                            <td>
                                                                <span class="badge bg-info"><?php echo $testimonial['display_order']; ?></span>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php if ($testimonial['avatar_url']): ?>
                                                                        <img src="<?php echo htmlspecialchars($testimonial['avatar_url']); ?>" 
                                                                             class="rounded-circle me-2" width="40" height="40" alt="Avatar">
                                                                    <?php else: ?>
                                                                        <div class="avatar-placeholder me-2" style="width: 40px; height: 40px; font-size: 0.8rem;">
                                                                            <?php echo strtoupper(substr($testimonial['name'], 0, 2)); ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <strong><?php echo htmlspecialchars($testimonial['name']); ?></strong><br>
                                                                        <small class="text-muted"><?php echo htmlspecialchars($testimonial['position']); ?></small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div style="max-width: 200px;">
                                                                    <?php 
                                                                    $comment = htmlspecialchars($testimonial['comment']);
                                                                    echo strlen($comment) > 100 ? substr($comment, 0, 100) . '...' : $comment;
                                                                    ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="stars">
                                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                        <i class="bi bi-star<?php echo $i <= $testimonial['rating'] ? '-fill' : ''; ?>"></i>
                                                                    <?php endfor; ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="action" value="toggle_status">
                                                                    <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-outline-<?php echo $testimonial['is_active'] ? 'success' : 'secondary'; ?>">
                                                                        <i class="bi bi-<?php echo $testimonial['is_active'] ? 'eye' : 'eye-slash'; ?>"></i>
                                                                        <?php echo $testimonial['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('d.m.Y H:i', strtotime($testimonial['created_at'])); ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    <a href="?edit=<?php echo $testimonial['id']; ?>" class="btn btn-outline-primary">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </a>
                                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Bu testimonial silinecek. Emin misiniz?')">
                                                                        <input type="hidden" name="action" value="delete">
                                                                        <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
                                                                        <button type="submit" class="btn btn-outline-danger">
                                                                            <i class="bi bi-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Testimonials Specific Styles -->
<style>
    .testimonial-card {
        border-left: 4px solid #007bff;
        background: #f8f9fa;
    }
    .stars {
        color: #ffc107;
    }
    .avatar-placeholder {
        width: 50px;
        height: 50px;
        background: #dc3545;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
    }
</style>

<?php 
// Additional JS for testimonials page
$additionalJS = '
    <script>
        // Testimonials specific JavaScript can go here
        document.addEventListener("DOMContentLoaded", function() {
            console.log("Testimonials page loaded");
        });
    </script>
';

// Footer include
include '../includes/design_footer.php';
?>
