<?php
/**
 * Veritabanı Yapısı İnceleme Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Veritabanı Yapısı';
$pageDescription = 'Veritabanı tablolarının yapısını inceleme';
$pageIcon = 'fas fa-database';

// Belirli revizyon ID'sini kontrol et
$checkRevisionId = isset($_GET['revision_id']) ? sanitize($_GET['revision_id']) : '';

// Tabloları listele
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $tables = [];
    $error = 'Tablolar listelenemedi: ' . $e->getMessage();
}

// Seçili tablo detayını göster
$selectedTable = isset($_GET['table']) ? sanitize($_GET['table']) : '';
$tableStructure = [];
$sampleData = [];

if ($selectedTable && in_array($selectedTable, $tables)) {
    try {
        // Tablo yapısını al
        $stmt = $pdo->query("DESCRIBE `$selectedTable`");
        $tableStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Örnek veri al (ilk 5 kayıt)
        $stmt = $pdo->query("SELECT * FROM `$selectedTable` LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = 'Tablo detayı alınamadı: ' . $e->getMessage();
    }
}

// Revizyon ID kontrolü
$revisionData = [];
if ($checkRevisionId) {
    try {
        // Revisions tablosu
        $stmt = $pdo->prepare("SELECT * FROM revisions WHERE id = ?");
        $stmt->execute([$checkRevisionId]);
        $revisionData['revision'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($revisionData['revision']) {
            // File_uploads tablosu
            $stmt = $pdo->prepare("SELECT * FROM file_uploads WHERE id = ?");
            $stmt->execute([$revisionData['revision']['upload_id']]);
            $revisionData['file_upload'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // File_responses tablosu (eğer response_id varsa)
            if ($revisionData['revision']['response_id']) {
                $stmt = $pdo->prepare("SELECT * FROM file_responses WHERE id = ?");
                $stmt->execute([$revisionData['revision']['response_id']]);
                $revisionData['file_response'] = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // Revision_files tablosu
            $stmt = $pdo->prepare("SELECT * FROM revision_files WHERE revision_id = ?");
            $stmt->execute([$checkRevisionId]);
            $revisionData['revision_files'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $revisionError = 'Revizyon verileri alınamadı: ' . $e->getMessage();
    }
}

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Hata Mesajları -->
<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($revisionError)): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $revisionError; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Navigation Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
        <li class="breadcrumb-item active" aria-current="page">Veritabanı Yapısı</li>
    </ol>
</nav>

<!-- Revizyon ID Kontrol Formu -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-search me-2"></i>Revizyon Kontrolü</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <label for="revision_id" class="form-label">Revizyon ID</label>
                        <input type="text" class="form-control" id="revision_id" name="revision_id" 
                               value="<?php echo htmlspecialchars($checkRevisionId); ?>" 
                               placeholder="de188dec-9ab0-4d80-9fee-e9320e22abd6">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Kontrol Et
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Revizyon Verisi Analizi -->
<?php if ($checkRevisionId && !empty($revisionData)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Revizyon Verisi Analizi</h5>
            </div>
            <div class="card-body">
                <?php if ($revisionData['revision']): ?>
                    <!-- Revisions Tablosu -->
                    <h6 class="text-primary mb-3">📋 Revisions Tablosu</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <?php foreach ($revisionData['revision'] as $key => $value): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($key); ?></strong></td>
                                    <td>
                                        <?php if ($value === null): ?>
                                            <span class="text-muted">NULL</span>
                                        <?php elseif (is_bool($value)): ?>
                                            <span class="badge bg-<?php echo $value ? 'success' : 'danger'; ?>">
                                                <?php echo $value ? 'TRUE' : 'FALSE'; ?>
                                            </span>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($value); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                    <!-- File_uploads Tablosu -->
                    <?php if ($revisionData['file_upload']): ?>
                        <h6 class="text-success mb-3">📁 File_uploads Tablosu</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered">
                                <?php foreach ($revisionData['file_upload'] as $key => $value): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($key); ?></strong></td>
                                        <td>
                                            <?php if ($value === null): ?>
                                                <span class="text-muted">NULL</span>
                                            <?php elseif ($key === 'file_size'): ?>
                                                <?php echo htmlspecialchars($value); ?> bytes 
                                                <small class="text-muted">(<?php echo formatFileSize($value); ?>)</small>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($value); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            File_uploads tablosunda veri bulunamadı!
                        </div>
                    <?php endif; ?>

                    <!-- File_responses Tablosu -->
                    <?php if (isset($revisionData['file_response']) && $revisionData['file_response']): ?>
                        <h6 class="text-info mb-3">💬 File_responses Tablosu</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered">
                                <?php foreach ($revisionData['file_response'] as $key => $value): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($key); ?></strong></td>
                                        <td>
                                            <?php if ($value === null): ?>
                                                <span class="text-muted">NULL</span>
                                            <?php elseif ($key === 'file_size'): ?>
                                                <?php echo htmlspecialchars($value); ?> bytes 
                                                <small class="text-muted">(<?php echo formatFileSize($value); ?>)</small>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($value); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php elseif ($revisionData['revision']['response_id']): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-2"></i>
                            Response ID mevcut ama file_responses tablosunda veri bulunamadı!
                        </div>
                    <?php endif; ?>

                    <!-- Revision_files Tablosu -->
                    <?php if (!empty($revisionData['revision_files'])): ?>
                        <h6 class="text-warning mb-3">🔄 Revision_files Tablosu (<?php echo count($revisionData['revision_files']); ?> adet)</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <?php foreach (array_keys($revisionData['revision_files'][0]) as $column): ?>
                                            <th><?php echo htmlspecialchars($column); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($revisionData['revision_files'] as $file): ?>
                                        <tr>
                                            <?php foreach ($file as $key => $value): ?>
                                                <td>
                                                    <?php if ($value === null): ?>
                                                        <span class="text-muted">NULL</span>
                                                    <?php elseif ($key === 'file_size'): ?>
                                                        <?php echo htmlspecialchars($value); ?> bytes 
                                                        <small class="text-muted">(<?php echo formatFileSize($value); ?>)</small>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($value); ?>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Revision_files tablosunda veri yok.
                        </div>
                    <?php endif; ?>

                    <!-- Sorun Tespiti -->
                    <div class="alert alert-secondary">
                        <h6><i class="fas fa-diagnoses me-2"></i>Sorun Tespiti</h6>
                        <ul class="mb-0">
                            <?php if (!$revisionData['file_upload']): ?>
                                <li class="text-danger">❌ Ana dosya bilgisi (file_uploads) eksik!</li>
                            <?php else: ?>
                                <li class="text-success">✅ Ana dosya bilgisi mevcut</li>
                                <?php if (empty($revisionData['file_upload']['file_size'])): ?>
                                    <li class="text-warning">⚠️ Ana dosya boyutu (file_size) boş!</li>
                                <?php endif; ?>
                                <?php if (empty($revisionData['file_upload']['created_at'])): ?>
                                    <li class="text-warning">⚠️ Ana dosya tarihi (created_at) boş!</li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($revisionData['revision']['response_id'] && !$revisionData['file_response']): ?>
                                <li class="text-danger">❌ Yanıt dosyası bilgisi (file_responses) eksik!</li>
                            <?php elseif ($revisionData['revision']['response_id']): ?>
                                <li class="text-success">✅ Yanıt dosyası bilgisi mevcut</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle me-2"></i>
                        Revizyon bulunamadı!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tablo Listesi -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Veritabanı Tabloları (<?php echo count($tables); ?> adet)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($tables as $table): ?>
                        <div class="col-md-3 mb-2">
                            <a href="?table=<?php echo urlencode($table); ?>" 
                               class="btn btn-outline-primary btn-sm w-100 <?php echo $table === $selectedTable ? 'active' : ''; ?>">
                                <i class="fas fa-table me-1"></i><?php echo htmlspecialchars($table); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Seçili Tablo Detayı -->
<?php if ($selectedTable && !empty($tableStructure)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Tablo Yapısı: <?php echo htmlspecialchars($selectedTable); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Sütun Adı</th>
                                <th>Veri Tipi</th>
                                <th>Null</th>
                                <th>Anahtar</th>
                                <th>Varsayılan</th>
                                <th>Extra</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tableStructure as $column): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($column['Field']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($column['Type']); ?></code></td>
                                    <td>
                                        <span class="badge bg-<?php echo $column['Null'] === 'YES' ? 'warning' : 'success'; ?>">
                                            <?php echo $column['Null']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($column['Key']): ?>
                                            <span class="badge bg-<?php echo $column['Key'] === 'PRI' ? 'danger' : 'info'; ?>">
                                                <?php echo $column['Key']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($column['Default'] !== null): ?>
                                            <code><?php echo htmlspecialchars($column['Default']); ?></code>
                                        <?php else: ?>
                                            <span class="text-muted">NULL</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($column['Extra']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($column['Extra']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Örnek Veri -->
<?php if (!empty($sampleData)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-database me-2"></i>Örnek Veriler (İlk 5 Kayıt)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <?php foreach (array_keys($sampleData[0]) as $column): ?>
                                    <th><?php echo htmlspecialchars($column); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sampleData as $row): ?>
                                <tr>
                                    <?php foreach ($row as $value): ?>
                                        <td>
                                            <?php if ($value === null): ?>
                                                <span class="text-muted">NULL</span>
                                            <?php elseif (is_bool($value)): ?>
                                                <span class="badge bg-<?php echo $value ? 'success' : 'danger'; ?>">
                                                    <?php echo $value ? 'TRUE' : 'FALSE'; ?>
                                                </span>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars(substr($value, 0, 50)); ?>
                                                <?php if (strlen($value) > 50): ?>...<?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<style>
.table code {
    background-color: #f8f9fa;
    color: #e83e8c;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.875em;
}

.alert ul {
    margin-bottom: 0;
}

.btn.active {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}
</style>

<?php include '../includes/admin_footer.php'; ?>
