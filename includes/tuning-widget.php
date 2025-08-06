<?php
/**
 * Tuning Sistemi - Hızlı Erişim Widget'ı
 * Ana sayfaya eklenecek
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/TuningModel.php';

try {
    $tuning = new TuningModel($pdo);
    $stats = [
        'brands' => count($tuning->getAllBrands()),
        'total_stages' => count($tuning->searchDetailed(['limit' => 999999])),
        'popular' => $tuning->getPopularEngines(3)
    ];
} catch (Exception $e) {
    $stats = ['brands' => 0, 'total_stages' => 0, 'popular' => []];
}
?>

<style>
.tuning-widget {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 25px;
    color: white;
    margin: 20px 0;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.tuning-widget h3 {
    margin: 0 0 20px 0;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.tuning-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.tuning-stat {
    text-align: center;
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 10px;
    backdrop-filter: blur(10px);
}

.tuning-stat-number {
    font-size: 2rem;
    font-weight: bold;
    display: block;
    color: #fff;
}

.tuning-stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-top: 5px;
}

.tuning-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.tuning-link {
    background: rgba(255,255,255,0.15);
    border: 2px solid rgba(255,255,255,0.2);
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s;
    font-weight: 500;
}

.tuning-link:hover {
    background: rgba(255,255,255,0.25);
    border-color: rgba(255,255,255,0.4);
    transform: translateY(-2px);
    color: white;
}

.popular-engines {
    margin-top: 20px;
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 15px;
}

.popular-engines h4 {
    margin: 0 0 10px 0;
    font-size: 1.1rem;
}

.popular-engine {
    background: rgba(255,255,255,0.1);
    padding: 8px 12px;
    border-radius: 6px;
    margin: 5px 0;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .tuning-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .tuning-links {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="tuning-widget">
    <h3>🚗 Araç Tuning Sistemi</h3>
    
    <div class="tuning-stats">
        <div class="tuning-stat">
            <span class="tuning-stat-number"><?= $stats['brands'] ?></span>
            <div class="tuning-stat-label">Marka</div>
        </div>
        <div class="tuning-stat">
            <span class="tuning-stat-number"><?= $stats['total_stages'] ?></span>
            <div class="tuning-stat-label">Tuning Stage</div>
        </div>
        <div class="tuning-stat">
            <span class="tuning-stat-number"><?= count($stats['popular']) ?></span>
            <div class="tuning-stat-label">Popüler Motor</div>
        </div>
    </div>
    
    <div class="tuning-links">
        <a href="tuning-search.php" class="tuning-link">
            🔍 Araç Arama
        </a>
        
        <?php if (isLoggedIn() && isAdmin()): ?>
        <a href="admin/tuning-management.php" class="tuning-link">
            ⚙️ Tuning Yönetimi
        </a>
        
        <a href="tuning-import.php" class="tuning-link">
            📁 Veri Import
        </a>
        <?php endif; ?>
        
        <a href="tuning-api.php?action=brands" class="tuning-link" target="_blank">
            🔌 API Test
        </a>
    </div>
    
    <?php if (!empty($stats['popular'])): ?>
    <div class="popular-engines">
        <h4>🔥 Popüler Motorlar</h4>
        <?php foreach ($stats['popular'] as $engine): ?>
        <div class="popular-engine">
            <strong><?= htmlspecialchars($engine['brand_name'] . ' ' . $engine['model_name']) ?></strong>
            <?= htmlspecialchars($engine['name']) ?>
            <span style="float: right; opacity: 0.8;"><?= $engine['stage_count'] ?> stage</span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>