<?php
/**
 * Chip Tuning Results Page
 * Mevcut database yapısı kullanılarak sonuçları gösterir
 */

require_once 'config/config.php';
require_once 'config/database.php';

$pageTitle = 'Chip Tuning Sonuçları';
$pageDescription = 'Araç performans artış sonuçları ve stage bilgileri';
$pageKeywords = 'chip tuning, ECU, performans artışı, stage1, stage2';

// Motor ID'yi al
$engineId = $_GET['engine_id'] ?? '';

if (empty($engineId)) {
    header('Location: index.php');
    exit;
}

try {
    // Motor ve araç bilgilerini al
    $stmt = $pdo->prepare("
        SELECT 
            e.*,
            s.name as series_name,
            s.year_range,
            m.name as model_name,
            b.name as brand_name
        FROM engines e
        JOIN series s ON e.series_id = s.id
        JOIN models m ON s.model_id = m.id
        JOIN brands b ON m.brand_id = b.id
        WHERE e.id = ?
    ");
    $stmt->execute([$engineId]);
    $engineData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$engineData) {
        header('Location: index.php');
        exit;
    }
    
    // Stage bilgilerini al
    $stmt = $pdo->prepare("
        SELECT stage_name, fullname, original_power, tuning_power, difference_power,
               original_torque, tuning_torque, difference_torque, ecu, notes,
               ROUND((difference_power / original_power) * 100, 2) as hp_percentage,
               ROUND((difference_torque / original_torque) * 100, 2) as torque_percentage
        FROM stages 
        WHERE engine_id = ? AND is_active = 1 
        ORDER BY 
            CASE stage_name 
                WHEN 'Stage1' THEN 1 
                WHEN 'Stage2' THEN 2 
                WHEN 'Stage3' THEN 3 
                ELSE 4 
            END
    ");
    $stmt->execute([$engineId]);
    $stageData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // En iyi stage'i belirle (en yüksek HP gain)
    $bestStage = null;
    $maxHpGain = 0;
    foreach ($stageData as $stage) {
        if ($stage['difference_power'] > $maxHpGain) {
            $maxHpGain = $stage['difference_power'];
            $bestStage = $stage;
        }
    }
    
    // Eğer stage yoksa varsayılan değerler
    if (empty($stageData) || !$bestStage) {
        $bestStage = [
            'stage_name' => 'Stage1',
            'difference_power' => 15,
            'difference_torque' => 25,
            'hp_percentage' => 10.0,
            'torque_percentage' => 12.0,
            'fullname' => 'ECU Remapping ile güvenli performans artışı',
            'original_power' => 150,
            'tuning_power' => 165,
            'original_torque' => 200,
            'tuning_torque' => 225
        ];
        
        // Varsayılan değerler ekle
        $engineData['original_power'] = 150;
        $engineData['original_torque'] = 200;
    } else {
        // Stages tablosundan gelen verileri engine'e ekle
        $engineData['original_power'] = $bestStage['original_power'];
        $engineData['original_torque'] = $bestStage['original_torque'];
    }

} catch (Exception $e) {
    error_log('Tuning Results Error: ' . $e->getMessage());
    header('Location: index.php');
    exit;
}

// Header include
include 'includes/header.php';
?>

<style>
/* Tuning Results Page Styles */
.tuning-hero {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    padding: 4rem 0;
    position: relative;
    overflow: hidden;
    border-radius: 0 0 30px 30px;
    height: 340px;
}

.tuning-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>') repeat;
    opacity: 0.3;
}

.car-info-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    padding: 2rem;
    margin-top: -3rem;
    position: relative;
    z-index: 10;
}

.performance-chart {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 2rem;
    margin: 2rem 0;
}

.stage-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.stage-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.stage-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.stage-card.recommended {
    border-color: #dc3545;
    background: linear-gradient(135deg, #fff 0%, #fff5f5 100%);
}

.stage-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 0.8rem;
    margin-bottom: 1rem;
}

.stage1 { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
.stage2 { background: linear-gradient(135deg, #fd7e14, #ffc107); color: white; }
.stage3 { background: linear-gradient(135deg, #dc3545, #e83e8c); color: white; }

.performance-bars {
    display: flex;
    gap: 2rem;
    margin: 2rem 0;
}

.performance-bar {
    flex: 1;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
}

.bar-container {
    height: 200px;
    display: flex;
    align-items: end;
    justify-content: center;
    gap: 1rem;
    margin: 1rem 0;
}

.bar {
    width: 60px;
    border-radius: 4px 4px 0 0;
    position: relative;
    transition: height 1s ease-out;
}

.bar-original {
    background: linear-gradient(180deg, #037be6, #043b72);
}

.bar-tuned {
    background: linear-gradient(180deg, #f75161, #97000f)
}

.bar-label {
    position: absolute;
    bottom: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.8rem;
    font-weight: bold;
}

.bar-value {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.9rem;
    font-weight: bold;
    color: #495057;
}

.comparison-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin: 2rem 0;
}

.comparison-table .table {
    margin: 0;
}

.comparison-table .table th {
    background: #f8f9fa;
    border: none;
    font-weight: 600;
    padding: 1rem;
}

.comparison-table .table td {
    padding: 1rem;
    border-color: #f1f3f4;
}

.gain-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: bold;
}

.gain-positive {
    background: #d4edda;
    color: #155724;
}

.contact-cta {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 3rem 0;
    border-radius: 15px;
    text-align: center;
    margin: 3rem 0;
}
</style>

<!-- Hero Section -->
<section class="tuning-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">
                    <?php echo htmlspecialchars($engineData['brand_name']); ?> 
                    <?php echo htmlspecialchars($engineData['model_name']); ?>
                </h1>
                <h2 class="h3 mb-4 text-light">
                    <?php echo htmlspecialchars($engineData['series_name']); ?> - 
                    <?php echo htmlspecialchars($engineData['name']); ?>
                </h2>
                <p class="lead mb-4">
                    Profesyonel ECU yazılım güncellemesi ile aracınızın performansını artırın.
                    Güvenli ve kaliteli chip tuning hizmeti.
                </p>
            </div>
            <div class="col-lg-4 text-center">
                <div class="position-relative">
                    <i class="fas fa-car fa-8x" style="opacity: 0.3;"></i>
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <i class="fas fa-tachometer-alt fa-4x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Car Info Card -->
<div class="container">
    <div class="car-info-card">
        <div class="row">
            <div class="col-md-3 text-center">
                <h6 class="text-muted mb-2">MARKA</h6>
                <h4 class="fw-bold"><?php echo htmlspecialchars($engineData['brand_name']); ?></h4>
            </div>
            <div class="col-md-3 text-center">
                <h6 class="text-muted mb-2">MODEL</h6>
                <h4 class="fw-bold"><?php echo htmlspecialchars($engineData['model_name']); ?></h4>
            </div>
            <div class="col-md-3 text-center">
                <h6 class="text-muted mb-2">SERİ</h6>
                <h4 class="fw-bold"><?php echo htmlspecialchars($engineData['series_name']); ?></h4>
            </div>
            <div class="col-md-3 text-center">
                <h6 class="text-muted mb-2">MOTOR</h6>
                <h4 class="fw-bold"><?php echo htmlspecialchars($engineData['name']); ?> 
                    <?php echo strtoupper($engineData['fuel_type']); ?>
                </h4>
            </div>
        </div>
    </div>
</div>

<!-- Performance Comparison -->
<div class="container">
    <div class="performance-chart">
        <h3 class="text-center mb-4">Performans Karşılaştırması</h3>
        
        <div class="performance-bars">
            <!-- HP Comparison -->
            <div class="performance-bar">
                <h5 class="mb-3">Motor Gücü (HP)</h5>
                <div class="bar-container">
                    <div class="bar bar-original" 
                         style="height: <?php echo ($engineData['original_power'] / ($engineData['original_power'] + $bestStage['difference_power'])) * 180; ?>px;">
                        <div class="bar-value"><?php echo $engineData['original_power']; ?></div>
                        <div class="bar-label">Orijinal</div>
                    </div>
                    <div class="bar bar-tuned" 
                         style="height: 180px;">
                        <div class="bar-value"><?php echo $engineData['original_power'] + $bestStage['difference_power']; ?></div>
                        <div class="bar-label">Mr.ECU</div>
                    </div>
                </div>
                <div class="gain-badge gain-positive">
                    +<?php echo $bestStage['difference_power']; ?> HP (+<?php echo number_format($bestStage['hp_percentage'], 1); ?>%)
                </div>
            </div>
            
            <!-- Torque Comparison -->
            <div class="performance-bar">
                <h5 class="mb-3">Tork (Nm)</h5>
                <div class="bar-container">
                    <div class="bar bar-original" 
                         style="height: <?php echo ($engineData['original_torque'] / ($engineData['original_torque'] + $bestStage['difference_torque'])) * 180; ?>px;">
                        <div class="bar-value"><?php echo $engineData['original_torque']; ?></div>
                        <div class="bar-label">Orijinal</div>
                    </div>
                    <div class="bar bar-tuned" 
                         style="height: 180px;">
                        <div class="bar-value"><?php echo $engineData['original_torque'] + $bestStage['difference_torque']; ?></div>
                        <div class="bar-label">Mr.ECU</div>
                    </div>
                </div>
                <div class="gain-badge gain-positive">
                    +<?php echo $bestStage['difference_torque']; ?> Nm (+<?php echo number_format($bestStage['torque_percentage'], 1); ?>%)
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stage Options -->
<?php if (!empty($stageData)): ?>
<div class="container">
    <h3 class="text-center mb-5">Performans Paketleri</h3>
    
    <div class="stage-cards">
        <?php foreach ($stageData as $stage): ?>
        <div class="stage-card <?php echo $stage['difference_power'] == $maxHpGain ? 'recommended' : ''; ?>">
            <?php if ($stage['difference_power'] == $maxHpGain): ?>
                <div class="text-end mb-2">
                    <span class="badge bg-danger">ÖNERİLEN</span>
                </div>
            <?php endif; ?>
            
            <div class="stage-badge <?php echo strtolower(str_replace('Stage', 'stage', $stage['stage_name'])); ?>">
                <?php echo strtoupper($stage['stage_name']); ?>
            </div>
            
            <h4 class="mb-3"><?php echo htmlspecialchars($stage['fullname']); ?></h4>
            
            <div class="row mb-3">
                <div class="col-6">
                    <strong>+<?php echo $stage['difference_power']; ?> HP</strong>
                    <small class="d-block text-muted">
                        (+<?php echo number_format($stage['hp_percentage'], 1); ?>%)
                    </small>
                </div>
                <div class="col-6">
                    <strong>+<?php echo $stage['difference_torque']; ?> Nm</strong>
                    <small class="d-block text-muted">
                        (+<?php echo number_format($stage['torque_percentage'], 1); ?>%)
                    </small>
                </div>
            </div>
            
            <p class="text-muted mb-3"><?php echo htmlspecialchars($stage['ecu'] ?? 'ECU Software Update'); ?></p>
            
            <div class="d-flex justify-content-between align-items-center">
                <button class="btn btn-outline-primary">Detay Al</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Detailed Comparison Table -->
<div class="container">
    <div class="comparison-table">
        <table class="table">
            <thead>
                <tr>
                    <th>Özellik</th>
                    <th>Orijinal</th>
                    <th>Mr.ECU'dan Sonra</th>
                    <th>Artış</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Güç (HP)</strong></td>
                    <td><?php echo $engineData['original_power']; ?> HP</td>
                    <td><?php echo $engineData['original_power'] + $bestStage['difference_power']; ?> HP</td>
                    <td>
                        <span class="gain-badge gain-positive">
                            +<?php echo $bestStage['difference_power']; ?> HP
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Tork (Nm)</strong></td>
                    <td><?php echo $engineData['original_torque']; ?> Nm</td>
                    <td><?php echo $engineData['original_torque'] + $bestStage['difference_torque']; ?> Nm</td>
                    <td>
                        <span class="gain-badge gain-positive">
                            +<?php echo $bestStage['difference_torque']; ?> Nm
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Call to Action -->
<div class="container">
    <div class="contact-cta">
        <h2 class="mb-4">Aracınızın Performansını Artırmaya Hazır mısınız?</h2>
        <p class="lead mb-4">
            Profesyonel ekibimiz ile iletişime geçin ve aracınız için en uygun chip tuning çözümünü keşfedin.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="tel:+905551234567" class="btn btn-light btn-lg">
                <i class="fas fa-phone me-2"></i>Hemen Ara
            </a>
            <a href="mailto:<?php echo SITE_EMAIL; ?>" class="btn btn-outline-light btn-lg">
                <i class="fas fa-envelope me-2"></i>E-posta Gönder
            </a>
            <a href="register.php" class="btn btn-warning btn-lg">
                <i class="fas fa-upload me-2"></i>Dosya Yükle
            </a>
        </div>
        
        <div class="row mt-5 text-center">
            <div class="col-md-4">
                <i class="fas fa-shield-alt fa-2x mb-3"></i>
                <h5>Güvenli İşlem</h5>
                <p>Aracınızın garantisi bozulmaz</p>
            </div>
            <div class="col-md-4">
                <i class="fas fa-clock fa-2x mb-3"></i>
                <h5>Hızlı Teslimat</h5>
                <p>24 saat içinde dosyanız hazır</p>
            </div>
            <div class="col-md-4">
                <i class="fas fa-undo fa-2x mb-3"></i>
                <h5>Geri Dönüş Garantisi</h5>
                <p>İstediğiniz zaman eski haline döndürülebilir</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animasyonlu bar loading
    const bars = document.querySelectorAll('.bar');
    bars.forEach((bar, index) => {
        const originalHeight = bar.style.height;
        bar.style.height = '0px';
        
        setTimeout(() => {
            bar.style.transition = 'height 1.5s ease-out';
            bar.style.height = originalHeight;
        }, 500 + (index * 200));
    });
    
    // Stage cards hover effect
    const stageCards = document.querySelectorAll('.stage-card');
    stageCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});
</script>

<?php
// Footer include
include 'includes/footer.php';
?>
