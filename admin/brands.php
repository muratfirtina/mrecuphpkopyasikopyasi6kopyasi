<?php
/**
 * Araç Yönetim Sistemi - Marka/Model/Seri/Motor/Stage CRUD
 * Mr ECU Admin Panel - Debug ve Güncelleme Destekli
 */
// Hata gösterimi (debug için)
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/TuningModel.php';
// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}
$tuning = new TuningModel($pdo);
$message = '';
$messageType = '';
// AJAX Request Handler
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    try {
        switch ($_GET['ajax']) {
            case 'get_models':
                $brandId = $_GET['brand_id'] ?? '';
                if (isValidUUID($brandId)) {
                    $models = $tuning->getModelsByBrand($brandId);
                    echo json_encode(['success' => true, 'data' => $models]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Geçersiz marka ID']);
                }
                break;
            case 'get_series':
                $modelId = $_GET['model_id'] ?? '';
                if (isValidUUID($modelId)) {
                    $series = $tuning->getSeriesByModel($modelId);
                    echo json_encode(['success' => true, 'data' => $series]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Geçersiz model ID']);
                }
                break;
            case 'get_engines':
                $seriesId = $_GET['series_id'] ?? '';
                if (isValidUUID($seriesId)) {
                    $engines = $tuning->getEnginesBySeries($seriesId);
                    echo json_encode(['success' => true, 'data' => $engines]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Geçersiz seri ID']);
                }
                break;
            case 'get_stages':
                $engineId = $_GET['engine_id'] ?? '';
                if (isValidUUID($engineId)) {
                    $stages = $tuning->getStagesByEngine($engineId);
                    echo json_encode(['success' => true, 'data' => $stages]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Geçersiz motor ID']);
                }
                break;
            case 'get_item_details':
                $itemId = $_GET['item_id'] ?? '';
                $itemType = $_GET['item_type'] ?? '';
                if (isValidUUID($itemId)) {
                    $tables = [
                        'brand' => 'brands',
                        'model' => 'models',
                        'series' => 'series',
                        'engine' => 'engines',
                        'stage' => 'stages'
                    ];
                    if (isset($tables[$itemType])) {
                        $stmt = $pdo->prepare("SELECT * FROM {$tables[$itemType]} WHERE id = ?");
                        $stmt->execute([$itemId]);
                        $item = $stmt->fetch();
                        if ($item) {
                            echo json_encode(['success' => true, 'data' => $item]);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Öğe bulunamadı']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Geçersiz öğe tipi']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Geçersiz ID']);
                }
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
// POST İşlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        error_log("POST Action: " . $action); // Debug
        switch ($action) {
            case 'add_brand':
                $name = sanitize($_POST['brand_name']);
                if (empty($name)) {
                    throw new Exception("Marka adı boş olamaz!");
                }
                $slug = createSlug($name);
                $brandId = generateUUID();
                error_log("Adding brand: $name, $slug, $brandId"); // Debug
                $stmt = $pdo->prepare("INSERT INTO brands (id, name, slug) VALUES (?, ?, ?)");
                if ($stmt->execute([$brandId, $name, $slug])) {
                    $message = "Marka '$name' başarıyla eklendi!";
                    $messageType = 'success';
                } else {
                    $error = $stmt->errorInfo();
                    throw new Exception("Marka eklenirken hata: " . $error[2]);
                }
                break;
            case 'update_brand':
                $brandId = sanitize($_POST['brand_id']);
                $name = sanitize($_POST['brand_name']);
                if (!isValidUUID($brandId)) {
                    throw new Exception("Geçersiz marka ID!");
                }
                if (empty($name)) {
                    throw new Exception("Marka adı boş olamaz!");
                }
                $slug = createSlug($name);
                $stmt = $pdo->prepare("UPDATE brands SET name = ?, slug = ? WHERE id = ?");
                if ($stmt->execute([$name, $slug, $brandId])) {
                    $message = "Marka '$name' başarıyla güncellendi!";
                    $messageType = 'success';
                } else {
                    throw new Exception("Marka güncellenirken hata oluştu!");
                }
                break;
            case 'add_model':
                $brandId = sanitize($_POST['brand_id']);
                $name = sanitize($_POST['model_name']);
                if (!isValidUUID($brandId)) {
                    throw new Exception("Geçersiz marka ID!");
                }
                if (empty($name)) {
                    throw new Exception("Model adı boş olamaz!");
                }
                $slug = createSlug($name);
                $modelId = generateUUID();
                $stmt = $pdo->prepare("INSERT INTO models (id, brand_id, name, slug) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$modelId, $brandId, $name, $slug])) {
                    $message = "Model '$name' başarıyla eklendi!";
                    $messageType = 'success';
                } else {
                    throw new Exception("Model eklenirken hata oluştu!");
                }
                break;
            case 'update_model':
                $modelId = sanitize($_POST['model_id']);
                $name = sanitize($_POST['model_name']);
                if (!isValidUUID($modelId)) {
                    throw new Exception("Geçersiz model ID!");
                }
                if (empty($name)) {
                    throw new Exception("Model adı boş olamaz!");
                }
                $slug = createSlug($name);
                $stmt = $pdo->prepare("UPDATE models SET name = ?, slug = ? WHERE id = ?");
                if ($stmt->execute([$name, $slug, $modelId])) {
                    $message = "Model '$name' başarıyla güncellendi!";
                    $messageType = 'success';
                } else {
                    throw new Exception("Model güncellenirken hata oluştu!");
                }
                break;
            case 'add_series':
                $modelId = sanitize($_POST['model_id']);
                $name = sanitize($_POST['series_name']);
                $yearRange = sanitize($_POST['year_range']);
                if (!isValidUUID($modelId)) {
                    throw new Exception("Geçersiz model ID!");
                }
                if (empty($name) || empty($yearRange)) {
                    throw new Exception("Seri adı ve yıl aralığı boş olamaz!");
                }
                $slug = createSlug($name);
                $seriesId = generateUUID();
                $stmt = $pdo->prepare("INSERT INTO series (id, model_id, name, year_range, slug) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$seriesId, $modelId, $name, $yearRange, $slug])) {
                    $message = "Seri '$name' başarıyla eklendi!";
                    $messageType = 'success';
                } else {
                    throw new Exception("Seri eklenirken hata oluştu!");
                }
                break;
            case 'update_series':
                $seriesId = sanitize($_POST['series_id']);
                $name = sanitize($_POST['series_name']);
                $yearRange = sanitize($_POST['year_range']);
                if (!isValidUUID($seriesId)) {
                    throw new Exception("Geçersiz seri ID!");
                }
                if (empty($name) || empty($yearRange)) {
                    throw new Exception("Seri adı ve yıl aralığı boş olamaz!");
                }
                $slug = createSlug($name);
                $stmt = $pdo->prepare("UPDATE series SET name = ?, year_range = ?, slug = ? WHERE id = ?");
                if ($stmt->execute([$name, $yearRange, $slug, $seriesId])) {
                    $message = "Seri '$name' başarıyla güncellendi!";
                    $messageType = 'success';
                } else {
                    throw new Exception("Seri güncellenirken hata oluştu!");
                }
                break;
            case 'add_engine':
                $seriesId = sanitize($_POST['series_id']);
                $name = sanitize($_POST['engine_name']);
                $fuelType = sanitize($_POST['fuel_type']);
                if (!isValidUUID($seriesId)) {
                    throw new Exception("Geçersiz seri ID!");
                }
                if (empty($name) || empty($fuelType)) {
                    throw new Exception("Motor adı ve yakıt tipi boş olamaz!");
                }
                $slug = createSlug($name);
                $engineId = generateUUID();
                $stmt = $pdo->prepare("INSERT INTO engines (id, series_id, name, slug, fuel_type) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$engineId, $seriesId, $name, $slug, $fuelType])) {
                    $message = "Motor '$name' başarıyla eklendi!";
                    $messageType = 'success';
                } else {
                    throw new Exception("Motor eklenirken hata oluştu!");
                }
                break;
            case 'update_engine':
                $engineId = sanitize($_POST['engine_id']);
                $name = sanitize($_POST['engine_name']);
                $fuelType = sanitize($_POST['fuel_type']);
                if (!isValidUUID($engineId)) {
                    throw new Exception("Geçersiz motor ID!");
                }
                if (empty($name) || empty($fuelType)) {
                    throw new Exception("Motor adı ve yakıt tipi boş olamaz!");
                }
                $slug = createSlug($name);
                $stmt = $pdo->prepare("UPDATE engines SET name = ?, slug = ?, fuel_type = ? WHERE id = ?");
                if ($stmt->execute([$name, $slug, $fuelType, $engineId])) {
                    $message = "Motor '$name' başarıyla güncellendi!";
                    $messageType = 'success';
                } else {
                    throw new Exception("Motor güncellenirken hata oluştu!");
                }
                break;
            case 'add_stage':
                $engineId = sanitize($_POST['engine_id']);
                $stageName = sanitize($_POST['stage_name']);
                $fullname = sanitize($_POST['fullname']);
                $originalPower = (int)$_POST['original_power'];
                $tuningPower = (int)$_POST['tuning_power'];
                $originalTorque = (int)$_POST['original_torque'];
                $tuningTorque = (int)$_POST['tuning_torque'];
                $ecu = sanitize($_POST['ecu']);
                $price = (float)$_POST['price'];
                if (!isValidUUID($engineId)) {
                    throw new Exception("Geçersiz motor ID!");
                }
                if (empty($stageName) || empty($fullname)) {
                    throw new Exception("Stage adı ve tam açıklama boş olamaz!");
                }
                $stageId = generateUUID();
                $differencePower = $tuningPower - $originalPower;
                $differenceTorque = $tuningTorque - $originalTorque;
                $stmt = $pdo->prepare("
                    INSERT INTO stages (
                        id, engine_id, stage_name, fullname,
                        original_power, tuning_power, difference_power,
                        original_torque, tuning_torque, difference_torque,
                        ecu, price
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt->execute([
                    $stageId, $engineId, $stageName, $fullname,
                    $originalPower, $tuningPower, $differencePower,
                    $originalTorque, $tuningTorque, $differenceTorque,
                    $ecu, $price
                ])) {
                    $message = "Stage '$stageName' başarıyla eklendi!";
                    $messageType = 'success';
                } else {
                    throw new Exception("Stage eklenirken hata oluştu!");
                }
                break;
            case 'update_stage':
                $stageId = sanitize($_POST['stage_id']);
                $stageName = sanitize($_POST['stage_name']);
                $fullname = sanitize($_POST['fullname']);
                $originalPower = (int)$_POST['original_power'];
                $tuningPower = (int)$_POST['tuning_power'];
                $originalTorque = (int)$_POST['original_torque'];
                $tuningTorque = (int)$_POST['tuning_torque'];
                $ecu = sanitize($_POST['ecu']);
                $price = (float)$_POST['price'];
                if (!isValidUUID($stageId)) {
                    throw new Exception("Geçersiz stage ID!");
                }
                if (empty($stageName) || empty($fullname)) {
                    throw new Exception("Stage adı ve tam açıklama boş olamaz!");
                }
                $differencePower = $tuningPower - $originalPower;
                $differenceTorque = $tuningTorque - $originalTorque;
                $stmt = $pdo->prepare("
                    UPDATE stages SET
                        stage_name = ?, fullname = ?,
                        original_power = ?, tuning_power = ?, difference_power = ?,
                        original_torque = ?, tuning_torque = ?, difference_torque = ?,
                        ecu = ?, price = ?
                    WHERE id = ?
                ");
                if ($stmt->execute([
                    $stageName, $fullname,
                    $originalPower, $tuningPower, $differencePower,
                    $originalTorque, $tuningTorque, $differenceTorque,
                    $ecu, $price, $stageId
                ])) {
                    $message = "Stage '$stageName' başarıyla güncellendi!";
                    $messageType = 'success';
                } else {
                    throw new Exception("Stage güncellenirken hata oluştu!");
                }
                break;
            case 'delete_item':
                $itemId = sanitize($_POST['item_id']);
                $itemType = sanitize($_POST['item_type']);
                if (!isValidUUID($itemId)) {
                    throw new Exception("Geçersiz ID!");
                }
                $tables = [
                    'brand' => 'brands',
                    'model' => 'models',
                    'series' => 'series',
                    'engine' => 'engines',
                    'stage' => 'stages'
                ];
                if (isset($tables[$itemType])) {
                    $stmt = $pdo->prepare("DELETE FROM {$tables[$itemType]} WHERE id = ?");
                    if ($stmt->execute([$itemId])) {
                        $message = ucfirst($itemType) . " başarıyla silindi!";
                        $messageType = 'success';
                    } else {
                        throw new Exception(ucfirst($itemType) . " silinirken hata oluştu!");
                    }
                } else {
                    throw new Exception("Geçersiz öğe tipi!");
                }
                break;
            default:
                throw new Exception("Geçersiz işlem: $action");
        }
    } catch (Exception $e) {
        $message = "Hata: " . $e->getMessage();
        $messageType = 'error';
        error_log("Brands.php Error: " . $e->getMessage()); // Debug
    }
}
// Tüm markaları getir
try {
    $brands = $tuning->getAllBrands();
} catch (Exception $e) {
    $brands = [];
    $message = "Markalar yüklenirken hata: " . $e->getMessage();
    $messageType = 'error';
}

$pageTitle = 'Marka/Model Yönetimi';
$pageDescription = 'Araç markalarını ve modellerini yönetin';
$pageIcon = 'fas fa-car';

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>
<style>
:root {
    /* Modern renk paleti */
    --primary-color: #2563eb; /* Daha canlı mavi */
    --primary-light: #3b82f6;
    --primary-dark: #1d4ed8;
    --secondary-color: #8b5cf6; /* Mor tonu ekledim */
    --success-color: #10b981; /* Daha canlı yeşil */
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --light-color: #f8fafc;
    --dark-color: #1e293b;
    --gray-100: #f1f5f9;
    --gray-200: #e2e8f0;
    --gray-300: #cbd5e1;
    --border-radius: 12px;
    --box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.08);
    --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    --transition-fast: all 0.15s ease-in-out;
    --header-height: 60px;
}
/* Modern Google Fonts ekledim */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
.vehicle-manager {
    max-width: 1600px;
    margin: 1.5rem auto;
    padding: 0 1.25rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #1e293b;
}
.vehicle-manager h1 {
    color: var(--primary-dark);
    margin-bottom: 1.5rem;
    font-size: 1.875rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--gray-200);
}
/* Modern mesajlar */
.message {
    padding: 1rem;
    border-radius: var(--border-radius);
    margin-bottom: 1.5rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-width: 1px;
    border-style: solid;
    box-shadow: var(--box-shadow);
}
.message.success {
    background-color: #ecfdf5;
    color: #047857;
    border-color: #d1fae5;
}
.message.error {
    background-color: #fff1f2;
    color: #b91c1c;
    border-color: #fee2e2;
}
.message.warning {
    background-color: #fff7ed;
    color: #c2410c;
    border-color: #ffedd5;
}
/* Modern Breadcrumb */
.breadcrumb {
    background-color: white;
    padding: 0.75rem 1.25rem;
    border-radius: var(--border-radius);
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
    color: var(--dark-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid var(--gray-200);
    box-shadow: var(--box-shadow);
}
.breadcrumb span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.breadcrumb span:not(:last-child):after {
    content: "›";
    margin: 0 0.5rem;
    color: var(--gray-300);
}
/* Modern İstatistikler */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.75rem;
}
.stats-card {
    background: white;
    padding: 1.5rem;
    border-radius: var(--border-radius);
    text-align: center;
    box-shadow: var(--box-shadow);
    transition: var(--transition);
    border-left: 4px solid var(--primary-color);
    position: relative;
    overflow: hidden;
}
.stats-card:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
}
.stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}
.stats-number {
    font-size: 2rem;
    font-weight: 800;
    color: var(--primary-dark);
    margin-bottom: 0.5rem;
    line-height: 1;
}
.stats-label {
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
}

/* Farklı renkler için istatistik kartları */
.stats-card:nth-child(1) {
    border-left-color: var(--primary-color);
}
.stats-card:nth-child(1):before {
    background: linear-gradient(90deg, var(--primary-color), #60a5fa);
}
.stats-card:nth-child(1) .stats-number {
    color: #1e3a8a;
}
.stats-card:nth-child(1) .stats-label {
    color: #1e293b;
}

.stats-card:nth-child(2) {
    border-left-color: var(--secondary-color);
}
.stats-card:nth-child(2):before {
    background: linear-gradient(90deg, var(--secondary-color), #a78bfa);
}
.stats-card:nth-child(2) .stats-number {
    color: #7c3aed;
}
.stats-card:nth-child(2) .stats-label {
    color: #4c1d95;
}

.stats-card:nth-child(3) {
    border-left-color: var(--warning-color);
}
.stats-card:nth-child(3):before {
    background: linear-gradient(90deg, var(--warning-color), #fbbf24);
}
.stats-card:nth-child(3) .stats-number {
    color: #d97706;
}
.stats-card:nth-child(3) .stats-label {
    color: #92400e;
}

.stats-card:nth-child(4) {
    border-left-color: var(--danger-color);
}
.stats-card:nth-child(4):before {
    background: linear-gradient(90deg, var(--danger-color), #f87171);
}
.stats-card:nth-child(4) .stats-number {
    color: #dc2626;
}
.stats-card:nth-child(4) .stats-label {
    color: #991b1b;
}

.stats-card:nth-child(5) {
    border-left-color: var(--success-color);
}
.stats-card:nth-child(5):before {
    background: linear-gradient(90deg, var(--success-color), #34d399);
}
.stats-card:nth-child(5) .stats-number {
    color: #059669;
}
.stats-card:nth-child(5) .stats-label {
    color: #065f46;
}

/* Modern Hiyerarşik Yönetim Panelleri */
.hierarchy-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.hierarchy-panel {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: var(--transition);
    border: 1px solid var(--gray-200);
}
.hierarchy-panel:hover {
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    border-color: var(--gray-300);
}
.hierarchy-panel-header {
    padding: 1.125rem 1.5rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.hierarchy-panel-header h3 {
    margin: 0;
    color: var(--dark-color);
    font-size: 1.125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
/* Marka Arama Kutusu */
.search-container {
    padding: 0.75rem 1.5rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.search-container input {
    flex: 1;
    padding: 0.6rem 0.8rem;
    border: 1px solid var(--gray-300);
    border-radius: 8px;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.2s;
}
.search-container input:focus {
    border-color: var(--primary-color);
}
.search-container .clear-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: #94a3b8;
    font-size: 1.1rem;
    padding: 0.25rem;
    transition: color 0.2s;
}
.search-container .clear-btn:hover {
    color: #64748b;
}
.search-container .search-icon {
    color: var(--gray-400);
    font-size: 1.1rem;
}

/* Modern Add Form */
.add-form {
    padding: 1rem 1.5rem;
    background-color: var(--gray-50);
    border-top: 1px solid var(--gray-200);
    display: none;
    transition: var(--transition);
}
.add-form.active {
    display: block;
    animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
.add-form form {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.add-form .form-group {
    flex: 1 1 200px;
    margin-bottom: 0;
}
.add-form .form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--gray-300);
    border-radius: 8px;
    font-size: 0.9375rem;
    transition: var(--transition);
    background-color: white;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
}
.add-form .form-control:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
    z-index: 10;
}
.add-form .btn {
    align-self: flex-end;
    height: fit-content;
    padding: 0.625rem 1.25rem;
}
/* Modern Toggle Button */
.toggle-add-form {
    background: none;
    border: none;
    color: var(--primary-color);
    cursor: pointer;
    font-size: 1.25rem;
    padding: 0.25rem;
    border-radius: 8px;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
}
.toggle-add-form:hover {
    background-color: rgba(59, 130, 246, 0.1);
    color: var(--primary-dark);
    transform: rotate(90deg);
}
.toggle-add-form:active {
    transform: scale(0.95);
}
/* Modern Item List */
.item-list {
    list-style: none;
    padding: 0;
    margin: 0;
    overflow-y: auto;
    flex-grow: 1;
    max-height: 450px;
    scrollbar-width: thin;
    scrollbar-color: var(--gray-300) var(--gray-100);
}
.item-list::-webkit-scrollbar {
    width: 6px;
}
.item-list::-webkit-scrollbar-track {
    background: var(--gray-100);
    border-radius: 3px;
}
.item-list::-webkit-scrollbar-thumb {
    background-color: var(--gray-300);
    border-radius: 3px;
}
.item-list li {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}
.item-list li:last-child {
    border-bottom: none;
}
.item-list li:hover {
    background-color: #f0f9ff;
    transform: translateX(2px);
}
.item-list li.active {
    background-color: #dbeafe;
    border-left: 3px solid var(--primary-color);
    transform: translateX(0);
}
.item-list li .item-name {
    flex-grow: 1;
    font-weight: 600;
    color: var(--dark-color);
    transition: var(--transition);
}
.item-list li .item-meta {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.25rem;
    display: block;
}
.item-list li.active .item-name {
    color: var(--primary-dark);
    font-weight: 700;
}
.item-list li .item-actions {
    display: flex;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.2s;
}
.item-list li:hover .item-actions {
    opacity: 1;
}
.btn-mini {
    padding: 0.375rem 0.625rem;
    font-size: 0.8125rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: var(--transition-fast);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}
.btn-mini:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}
.btn-edit {
    background-color: #f0f9ff;
    color: var(--primary-color);
}
.btn-edit:hover {
    background-color: #dbeafe;
}
.btn-delete {
    background-color: #fff1f2;
    color: var(--danger-color);
}
.btn-delete:hover {
    background-color: #fee2e2;
}
/* Modern Empty State */
.empty-state {
    text-align: center;
    color: #94a3b8;
    padding: 2rem 1rem;
    font-size: 0.875rem;
    font-style: normal;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 200px;
}
.empty-state i {
    font-size: 2rem;
    margin-bottom: 0.75rem;
    opacity: 0.3;
}
/* Modern Loading */
.loading {
    text-align: center;
    padding: 2rem;
    color: #64748b;
    font-size: 0.875rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.loading:after {
    content: '';
    display: inline-block;
    width: 24px;
    height: 24px;
    margin-top: 0.75rem;
    border: 3px solid rgba(37, 99, 235, 0.25);
    border-top-color: var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
/* Modern Buttons */
.btn {
    padding: 0.625rem 1.25rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9375rem;
    font-weight: 500;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.btn-primary {
    background-color: var(--primary-color);
    color: white;
}
.btn-primary:hover {
    background-color: var(--primary-dark);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.btn-success {
    background-color: var(--success-color);
    color: white;
}
.btn-success:hover {
    background-color: #0d9488;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.btn-warning {
    background-color: var(--warning-color);
    color: white;
}
.btn-warning:hover {
    background-color: #d97706;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.btn-danger {
    background-color: var(--danger-color);
    color: white;
}
.btn-danger:hover {
    background-color: #dc2626;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
}
/* Modern Modal */
.edit-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    transition: opacity 0.2s ease;
}
.edit-modal.active {
    display: flex;
    opacity: 1;
}
.edit-modal-content {
    background: white;
    padding: 2rem;
    border-radius: var(--border-radius);
    width: 90%;
    max-width: 550px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 50px -15px rgba(0, 0, 0, 0.15);
    animation: modalFadeIn 0.3s ease-out;
    transform-origin: center;
}
@keyframes modalFadeIn {
    from { 
        opacity: 0; 
        transform: scale(0.95);
        filter: blur(4px);
    }
    to { 
        opacity: 1; 
        transform: scale(1);
        filter: blur(0);
    }
}
.edit-modal h3 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    color: var(--dark-color);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 700;
    font-size: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    padding-bottom: 1rem;
}
.modal-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--gray-100);
}
.modal-buttons .btn {
    min-width: 100px;
    font-weight: 600;
}
/* Form grupları */
.form-group {
    margin-bottom: 1.25rem;
}
.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--dark-color);
    font-size: 0.875rem;
}
/* Responsive */
@media (max-width: 768px) {
    .hierarchy-container {
        grid-template-columns: 1fr;
    }
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .edit-modal-content {
        width: 95%;
        padding: 1.5rem;
    }
    .add-form form {
        flex-direction: column;
    }
    .add-form .form-group {
        flex: 1 1 100%;
    }
    .add-form .btn {
        align-self: stretch;
    }
    .vehicle-manager {
        padding: 0 1rem;
        margin: 1rem auto;
    }
    .hierarchy-panel-header h3 {
        font-size: 1rem;
    }
    .search-container {
        flex-wrap: wrap;
    }
    .search-container input {
        min-width: 200px;
    }
}
/* Mobil menü */
@media (max-width: 640px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .vehicle-manager h1 {
        font-size: 1.5rem;
    }
}
</style>
<div class="vehicle-manager">
    <?php if ($message): ?>
        <div class="message <?= $messageType ?>">
            <?php if ($messageType === 'success'): ?>
                <span>✅</span>
            <?php elseif ($messageType === 'error'): ?>
                <span>❌</span>
            <?php endif; ?>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <!-- Breadcrumb -->
    <nav class="breadcrumb" id="breadcrumb">
        <span>🏠</span>
        <span>Markalar</span>
    </nav>
    <!-- İstatistikler -->
    <div class="stats-grid">
        <div class="stats-card">
            <div class="stats-number"><?= count($brands) ?></div>
            <div class="stats-label">Toplam Marka</div>
        </div>
        <div class="stats-card">
            <div class="stats-number" id="total-models">-</div>
            <div class="stats-label">Seçili Marka Model</div>
        </div>
        <div class="stats-card">
            <div class="stats-number" id="total-series">-</div>
            <div class="stats-label">Seçili Model Seri</div>
        </div>
        <div class="stats-card">
            <div class="stats-number" id="total-engines">-</div>
            <div class="stats-label">Seçili Seri Motor</div>
        </div>
        <div class="stats-card">
            <div class="stats-number" id="total-stages">-</div>
            <div class="stats-label">Seçili Motor Stage</div>
        </div>
    </div>
    <!-- Hiyerarşik Yönetim Panelleri -->
    <div class="hierarchy-container">
        <!-- Markalar -->
        <div class="hierarchy-panel">
            <div class="hierarchy-panel-header">
                <h3>🏢 Markalar</h3>
                <button class="toggle-add-form" title="Marka Ekle" onclick="toggleAddForm('brand')">➕</button>
            </div>
            <!-- Arama Kutusu -->
            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" id="search-brands" placeholder="Marka ara..." autocomplete="off">
                <button class="clear-btn" id="clear-search" title="Temizle">✖️</button>
            </div>
            <div class="add-form" id="brand-form">
                <form method="post" id="brand-add-form">
                    <input type="hidden" name="action" value="add_brand">
                    <div class="form-group">
                        <input type="text" name="brand_name" class="form-control" placeholder="Marka adı" required>
                    </div>
                    <button type="submit" class="btn btn-success">Ekle</button>
                </form>
            </div>
            <div class="hierarchy-panel-body">
                <ul class="item-list" id="brands-list">
                    <?php if (empty($brands)): ?>
                        <li class="empty-state">Henüz marka eklenmemiş</li>
                    <?php else: ?>
                        <?php foreach ($brands as $brand): ?>
                        <li data-id="<?= $brand['id'] ?>" onclick="selectBrand('<?= $brand['id'] ?>', '<?= htmlspecialchars($brand['name']) ?>')">
                            <div>
                                <span class="item-name"><?= htmlspecialchars($brand['name']) ?></span>
                                <span class="item-meta"><?= $brand['model_count'] ?> model</span>
                            </div>
                            <div class="item-actions">
                                <button class="btn-mini btn-edit" onclick="editItem(event, '<?= $brand['id'] ?>', 'brand', '<?= htmlspecialchars($brand['name']) ?>')" title="Düzenle">✏️</button>
                                <button class="btn-mini btn-delete" onclick="deleteItem(event, '<?= $brand['id'] ?>', 'brand')" title="Sil">🗑️</button>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <!-- Modeller -->
        <div class="hierarchy-panel">
            <div class="hierarchy-panel-header">
                <h3>🚗 Modeller</h3>
                <button class="toggle-add-form" title="Model Ekle" onclick="toggleAddForm('model')" disabled>➕</button>
            </div>
            <div class="add-form" id="model-form">
                <form method="post">
                    <input type="hidden" name="action" value="add_model">
                    <input type="hidden" name="brand_id" id="model-brand-id">
                    <div class="form-group">
                        <input type="text" name="model_name" class="form-control" placeholder="Model adı" required>
                    </div>
                    <button type="submit" class="btn btn-success">Ekle</button>
                </form>
            </div>
            <div class="hierarchy-panel-body">
                <ul class="item-list" id="models-list">
                    <li class="empty-state">Bir marka seçin</li>
                </ul>
            </div>
        </div>
        <!-- Seriler -->
        <div class="hierarchy-panel">
            <div class="hierarchy-panel-header">
                <h3>📅 Seriler</h3>
                <button class="toggle-add-form" title="Seri Ekle" onclick="toggleAddForm('series')" disabled>➕</button>
            </div>
            <div class="add-form" id="series-form">
                <form method="post">
                    <input type="hidden" name="action" value="add_series">
                    <input type="hidden" name="model_id" id="series-model-id">
                    <div class="form-group">
                        <input type="text" name="series_name" class="form-control" placeholder="Seri adı" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="year_range" class="form-control" placeholder="Yıl aralığı (örn: 2015-2020)" required>
                    </div>
                    <button type="submit" class="btn btn-success">Ekle</button>
                </form>
            </div>
            <div class="hierarchy-panel-body">
                <ul class="item-list" id="series-list">
                    <li class="empty-state">Bir model seçin</li>
                </ul>
            </div>
        </div>
        <!-- Motorlar -->
        <div class="hierarchy-panel">
            <div class="hierarchy-panel-header">
                <h3>🔧 Motorlar</h3>
                <button class="toggle-add-form" title="Motor Ekle" onclick="toggleAddForm('engine')" disabled>➕</button>
            </div>
            <div class="add-form" id="engine-form">
                <form method="post">
                    <input type="hidden" name="action" value="add_engine">
                    <input type="hidden" name="series_id" id="engine-series-id">
                    <div class="form-group">
                        <input type="text" name="engine_name" class="form-control" placeholder="Motor adı" required>
                    </div>
                    <div class="form-group">
                        <select name="fuel_type" class="form-control" required>
                            <option value="">Yakıt Tipi</option>
                            <option value="Benzin">Benzin</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Hybrid">Hybrid</option>
                            <option value="Electric">Electric</option>
                            <option value="LPG">LPG</option>
                            <option value="CNG">CNG</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Ekle</button>
                </form>
            </div>
            <div class="hierarchy-panel-body">
                <ul class="item-list" id="engines-list">
                    <li class="empty-state">Bir seri seçin</li>
                </ul>
            </div>
        </div>
        <!-- Stage'ler -->
        <div class="hierarchy-panel">
            <div class="hierarchy-panel-header">
                <h3>⚡ Stage'ler</h3>
                <button class="toggle-add-form" title="Stage Ekle" onclick="toggleAddForm('stage')" disabled>➕</button>
            </div>
            <div class="add-form" id="stage-form">
                <form method="post">
                    <input type="hidden" name="action" value="add_stage">
                    <input type="hidden" name="engine_id" id="stage-engine-id">
                    <div class="form-group">
                        <select name="stage_name" class="form-control" required>
                            <option value="">Stage</option>
                            <option value="Stage1">Stage 1</option>
                            <option value="Stage2">Stage 2</option>
                            <option value="Stage3">Stage 3</option>
                            <option value="Stage1+">Stage 1+</option>
                            <option value="Stage2+">Stage 2+</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="text" name="fullname" class="form-control" placeholder="Tam açıklama" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <input type="number" name="original_power" class="form-control" placeholder="Orijinal güç (HP)" required>
                        <input type="number" name="tuning_power" class="form-control" placeholder="Tuning güç (HP)" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 0.75rem;">
                        <input type="number" name="original_torque" class="form-control" placeholder="Orijinal tork (Nm)" required>
                        <input type="number" name="tuning_torque" class="form-control" placeholder="Tuning tork (Nm)" required>
                    </div>
                    <div class="form-group" style="margin-top: 0.75rem;">
                        <input type="text" name="ecu" class="form-control" placeholder="ECU bilgisi">
                    </div>
                    <div class="form-group">
                        <input type="number" name="price" class="form-control" placeholder="Fiyat (₺)" step="0.01">
                    </div>
                    <button type="submit" class="btn btn-success">Ekle</button>
                </form>
            </div>
            <div class="hierarchy-panel-body">
                <ul class="item-list" id="stages-list">
                    <li class="empty-state">Bir motor seçin</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<!-- Edit Modal -->
<div class="edit-modal" id="edit-modal">
    <div class="edit-modal-content">
        <h3 id="edit-modal-title">Düzenle</h3>
        <form method="post" id="edit-form">
            <input type="hidden" name="action" id="edit-action">
            <input type="hidden" name="item_id" id="edit-item-id">
            <div id="edit-form-fields">
                <!-- Dynamic fields will be added here -->
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn btn-danger" onclick="closeEditModal()">İptal</button>
                <button type="submit" class="btn btn-success">💾 Güncelle</button>
            </div>
        </form>
    </div>
</div>
<script>
let selectedBrand = null;
let selectedModel = null;
let selectedSeries = null;
let selectedEngine = null;

// Marka arama ve temizleme
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search-brands');
    const clearBtn = document.getElementById('clear-search');

    searchInput.addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase();
        const brandItems = document.querySelectorAll('#brands-list li[data-id]');

        brandItems.forEach(li => {
            const brandName = li.querySelector('.item-name').textContent.toLowerCase();
            li.style.display = brandName.includes(searchTerm) ? '' : 'none';
        });
    });

    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
    });
});

// Toggle Add Form Visibility
function toggleAddForm(level) {
    const formId = `${level}-form`;
    const form = document.getElementById(formId);
    if (form) {
        form.classList.toggle('active');
        if (form.classList.contains('active')) {
            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
}

// Marka seçimi
function selectBrand(brandId, brandName) {
    selectedBrand = { id: brandId, name: brandName };
    selectedModel = null;
    selectedSeries = null;
    selectedEngine = null;
    document.querySelectorAll('#brands-list li').forEach(li => li.classList.remove('active'));
    document.querySelector(`#brands-list li[data-id="${brandId}"]`).classList.add('active');
    updateBreadcrumb(['🏠', 'Markalar', brandName]);
    document.querySelector('.hierarchy-panel:nth-child(2) .toggle-add-form').disabled = false;
    document.getElementById('model-brand-id').value = brandId;
    clearLevel('models');
    clearLevel('series');
    clearLevel('engines');
    clearLevel('stages');
    loadModels(brandId);
}

// Model seçimi
function selectModel(modelId, modelName) {
    selectedModel = { id: modelId, name: modelName };
    selectedSeries = null;
    selectedEngine = null;
    document.querySelectorAll('#models-list li').forEach(li => li.classList.remove('active'));
    if (document.querySelector(`#models-list li[data-id="${modelId}"]`)) {
        document.querySelector(`#models-list li[data-id="${modelId}"]`).classList.add('active');
    }
    updateBreadcrumb(['🏠', 'Markalar', selectedBrand.name, modelName]);
    document.querySelector('.hierarchy-panel:nth-child(3) .toggle-add-form').disabled = false;
    document.getElementById('series-model-id').value = modelId;
    clearLevel('series');
    clearLevel('engines');
    clearLevel('stages');
    loadSeries(modelId);
}

// Seri seçimi
function selectSeries(seriesId, seriesName) {
    selectedSeries = { id: seriesId, name: seriesName };
    selectedEngine = null;
    document.querySelectorAll('#series-list li').forEach(li => li.classList.remove('active'));
    if (document.querySelector(`#series-list li[data-id="${seriesId}"]`)) {
        document.querySelector(`#series-list li[data-id="${seriesId}"]`).classList.add('active');
    }
    updateBreadcrumb(['🏠', 'Markalar', selectedBrand.name, selectedModel.name, seriesName]);
    document.querySelector('.hierarchy-panel:nth-child(4) .toggle-add-form').disabled = false;
    document.getElementById('engine-series-id').value = seriesId;
    clearLevel('engines');
    clearLevel('stages');
    loadEngines(seriesId);
}

// Motor seçimi
function selectEngine(engineId, engineName) {
    selectedEngine = { id: engineId, name: engineName };
    document.querySelectorAll('#engines-list li').forEach(li => li.classList.remove('active'));
    if (document.querySelector(`#engines-list li[data-id="${engineId}"]`)) {
        document.querySelector(`#engines-list li[data-id="${engineId}"]`).classList.add('active');
    }
    updateBreadcrumb(['🏠', 'Markalar', selectedBrand.name, selectedModel.name, selectedSeries.name, engineName]);
    document.querySelector('.hierarchy-panel:nth-child(5) .toggle-add-form').disabled = false;
    document.getElementById('stage-engine-id').value = engineId;
    loadStages(engineId);
}

// AJAX Yükleyiciler
function loadModels(brandId) {
    const container = document.getElementById('models-list');
    container.innerHTML = '<li class="loading">🔄 Yükleniyor...</li>';
    fetch(`?ajax=get_models&brand_id=${brandId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.data.length === 0) {
                    container.innerHTML = '<li class="empty-state">Model bulunamadı</li>';
                } else {
                    container.innerHTML = data.data.map(model => `
                        <li data-id="${model.id}" onclick="selectModel('${model.id}', '${model.name}')">
                            <div>
                                <span class="item-name">${model.name}</span>
                                <span class="item-meta">${model.series_count} seri</span>
                            </div>
                            <div class="item-actions">
                                <button class="btn-mini btn-edit" onclick="editItem(event, '${model.id}', 'model', '${model.name}')" title="Düzenle">✏️</button>
                                <button class="btn-mini btn-delete" onclick="deleteItem(event, '${model.id}', 'model')" title="Sil">🗑️</button>
                            </div>
                        </li>
                    `).join('');
                }
                updateStats();
            } else {
                container.innerHTML = `<li class="empty-state">Hata: ${data.message}</li>`;
            }
        })
        .catch(error => {
            container.innerHTML = '<li class="empty-state">Yükleme hatası</li>';
            console.error('Error:', error);
        });
}

function loadSeries(modelId) {
    const container = document.getElementById('series-list');
    container.innerHTML = '<li class="loading">🔄 Yükleniyor...</li>';
    fetch(`?ajax=get_series&model_id=${modelId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.data.length === 0) {
                    container.innerHTML = '<li class="empty-state">Seri bulunamadı</li>';
                } else {
                    container.innerHTML = data.data.map(series => `
                        <li data-id="${series.id}" onclick="selectSeries('${series.id}', '${series.name}')">
                            <div>
                                <span class="item-name">${series.name}</span>
                                <span class="item-meta">${series.year_range}</span>
                            </div>
                            <div class="item-actions">
                                <button class="btn-mini btn-edit" onclick="editSeries(event, '${series.id}', '${series.name}', '${series.year_range}')" title="Düzenle">✏️</button>
                                <button class="btn-mini btn-delete" onclick="deleteItem(event, '${series.id}', 'series')" title="Sil">🗑️</button>
                            </div>
                        </li>
                    `).join('');
                }
                updateStats();
            } else {
                container.innerHTML = `<li class="empty-state">Hata: ${data.message}</li>`;
            }
        })
        .catch(error => {
            container.innerHTML = '<li class="empty-state">Yükleme hatası</li>';
            console.error('Error:', error);
        });
}

function loadEngines(seriesId) {
    const container = document.getElementById('engines-list');
    container.innerHTML = '<li class="loading">🔄 Yükleniyor...</li>';
    fetch(`?ajax=get_engines&series_id=${seriesId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.data.length === 0) {
                    container.innerHTML = '<li class="empty-state">Motor bulunamadı</li>';
                } else {
                    container.innerHTML = data.data.map(engine => `
                        <li data-id="${engine.id}" onclick="selectEngine('${engine.id}', '${engine.name}')">
                            <div>
                                <span class="item-name">${engine.name}</span>
                                <span class="item-meta">${engine.fuel_type}</span>
                            </div>
                            <div class="item-actions">
                                <button class="btn-mini btn-edit" onclick="editEngine(event, '${engine.id}', '${engine.name}', '${engine.fuel_type}')" title="Düzenle">✏️</button>
                                <button class="btn-mini btn-delete" onclick="deleteItem(event, '${engine.id}', 'engine')" title="Sil">🗑️</button>
                            </div>
                        </li>
                    `).join('');
                }
                updateStats();
            } else {
                container.innerHTML = `<li class="empty-state">Hata: ${data.message}</li>`;
            }
        })
        .catch(error => {
            container.innerHTML = '<li class="empty-state">Yükleme hatası</li>';
            console.error('Error:', error);
        });
}

function loadStages(engineId) {
    const container = document.getElementById('stages-list');
    container.innerHTML = '<li class="loading">🔄 Yükleniyor...</li>';
    fetch(`?ajax=get_stages&engine_id=${engineId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.data.length === 0) {
                    container.innerHTML = '<li class="empty-state">Stage bulunamadı</li>';
                } else {
                    container.innerHTML = data.data.map(stage => `
                        <li data-id="${stage.id}">
                            <div>
                                <div><strong>${stage.stage_name}</strong></div>
                                <div><small>${stage.original_power}HP → ${stage.tuning_power}HP (+${stage.difference_power})</small></div>
                                <div><small>${stage.original_torque}Nm → ${stage.tuning_torque}Nm (+${stage.difference_torque})</small></div>
                                <div><small>${stage.ecu ? `ECU: ${stage.ecu}` : 'ECU bilgisi yok'}</small></div>
                            </div>
                            <div class="item-actions">
                                <button class="btn-mini btn-edit" onclick="editStage(event, '${stage.id}')" title="Düzenle">✏️</button>
                                <button class="btn-mini btn-delete" onclick="deleteItem(event, '${stage.id}', 'stage')" title="Sil">🗑️</button>
                            </div>
                        </li>
                    `).join('');
                }
                updateStats();
            } else {
                container.innerHTML = `<li class="empty-state">Hata: ${data.message}</li>`;
            }
        })
        .catch(error => {
            container.innerHTML = '<li class="empty-state">Yükleme hatası</li>';
            console.error('Error:', error);
        });
}

// Edit fonksiyonları
function editItem(event, itemId, itemType, itemName) {
    event.stopPropagation();
    const titles = {
        'brand': 'Marka Düzenle',
        'model': 'Model Düzenle'
    };
    document.getElementById('edit-modal-title').textContent = titles[itemType] || 'Düzenle';
    document.getElementById('edit-action').value = `update_${itemType}`;
    document.getElementById('edit-item-id').value = itemId;
    const fieldsContainer = document.getElementById('edit-form-fields');
    if (itemType === 'brand') {
        fieldsContainer.innerHTML = `
            <div class="form-group">
                <label>Marka Adı:</label>
                <input type="text" name="${itemType}_name" class="form-control" value="${itemName}" required>
            </div>
            <input type="hidden" name="brand_id" value="${itemId}">
        `;
    } else if (itemType === 'model') {
        fieldsContainer.innerHTML = `
            <div class="form-group">
                <label>Model Adı:</label>
                <input type="text" name="${itemType}_name" class="form-control" value="${itemName}" required>
            </div>
            <input type="hidden" name="model_id" value="${itemId}">
        `;
    }
    document.getElementById('edit-modal').classList.add('active');
}

function editSeries(event, seriesId, seriesName, yearRange) {
    event.stopPropagation();
    document.getElementById('edit-modal-title').textContent = 'Seri Düzenle';
    document.getElementById('edit-action').value = 'update_series';
    document.getElementById('edit-item-id').value = seriesId;
    document.getElementById('edit-form-fields').innerHTML = `
        <input type="hidden" name="series_id" value="${seriesId}">
        <div class="form-group">
            <label>Seri Adı:</label>
            <input type="text" name="series_name" class="form-control" value="${seriesName}" required>
        </div>
        <div class="form-group">
            <label>Yıl Aralığı:</label>
            <input type="text" name="year_range" class="form-control" value="${yearRange}" required>
        </div>
    `;
    document.getElementById('edit-modal').classList.add('active');
}

function editEngine(event, engineId, engineName, fuelType) {
    event.stopPropagation();
    document.getElementById('edit-modal-title').textContent = 'Motor Düzenle';
    document.getElementById('edit-action').value = 'update_engine';
    document.getElementById('edit-item-id').value = engineId;
    const fuelOptions = ['Benzin', 'Diesel', 'Hybrid', 'Electric', 'LPG', 'CNG'];
    const fuelOptionsHtml = fuelOptions.map(fuel =>
        `<option value="${fuel}" ${fuel === fuelType ? 'selected' : ''}>${fuel}</option>`
    ).join('');
    document.getElementById('edit-form-fields').innerHTML = `
        <input type="hidden" name="engine_id" value="${engineId}">
        <div class="form-group">
            <label>Motor Adı:</label>
            <input type="text" name="engine_name" class="form-control" value="${engineName}" required>
        </div>
        <div class="form-group">
            <label>Yakıt Tipi:</label>
            <select name="fuel_type" class="form-control" required>
                ${fuelOptionsHtml}
            </select>
        </div>
    `;
    document.getElementById('edit-modal').classList.add('active');
}

function editStage(event, stageId) {
    event.stopPropagation();
    fetch(`?ajax=get_item_details&item_id=${stageId}&item_type=stage`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stage = data.data;
                document.getElementById('edit-modal-title').textContent = 'Stage Düzenle';
                document.getElementById('edit-action').value = 'update_stage';
                document.getElementById('edit-item-id').value = stageId;
                const stageOptions = ['Stage1', 'Stage2', 'Stage3', 'Stage1+', 'Stage2+'];
                const stageOptionsHtml = stageOptions.map(s =>
                    `<option value="${s}" ${s === stage.stage_name ? 'selected' : ''}>${s}</option>`
                ).join('');
                document.getElementById('edit-form-fields').innerHTML = `
                    <input type="hidden" name="stage_id" value="${stageId}">
                    <div class="form-group">
                        <label>Stage Tipi:</label>
                        <select name="stage_name" class="form-control" required>
                            ${stageOptionsHtml}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tam Açıklama:</label>
                        <input type="text" name="fullname" class="form-control" value="${stage.fullname}" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label>Orijinal Güç (HP):</label>
                            <input type="number" name="original_power" class="form-control" value="${stage.original_power}" required>
                        </div>
                        <div class="form-group">
                            <label>Tuning Güç (HP):</label>
                            <input type="number" name="tuning_power" class="form-control" value="${stage.tuning_power}" required>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label>Orijinal Tork (Nm):</label>
                            <input type="number" name="original_torque" class="form-control" value="${stage.original_torque}" required>
                        </div>
                        <div class="form-group">
                            <label>Tuning Tork (Nm):</label>
                            <input type="number" name="tuning_torque" class="form-control" value="${stage.tuning_torque}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>ECU Bilgisi:</label>
                        <input type="text" name="ecu" class="form-control" value="${stage.ecu || ''}">
                    </div>
                    <div class="form-group">
                        <label>Fiyat (₺):</label>
                        <input type="number" name="price" class="form-control" value="${stage.price || ''}" step="0.01">
                    </div>
                `;
                document.getElementById('edit-modal').classList.add('active');
            } else {
                alert('Stage detayları yüklenemedi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Stage detayları yüklenirken hata oluştu');
        });
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.remove('active');
}

// Yardımcı fonksiyonlar
function clearLevel(level) {
    const containers = {
        'models': 'models-list',
        'series': 'series-list',
        'engines': 'engines-list',
        'stages': 'stages-list'
    };
    const forms = {
        'models': 'model-form',
        'series': 'series-form',
        'engines': 'engine-form',
        'stages': 'stage-form'
    };
    if (containers[level]) {
        const container = document.getElementById(containers[level]);
        if (container) {
            container.innerHTML = '<li class="empty-state">Üst seviye seçin</li>';
        }
    }
    if (forms[level]) {
        const form = document.getElementById(forms[level]);
        if (form) {
            form.classList.remove('active');
        }
    }
    if (level === 'models') {
        document.querySelector('.hierarchy-panel:nth-child(3) .toggle-add-form').disabled = true;
        document.querySelector('.hierarchy-panel:nth-child(4) .toggle-add-form').disabled = true;
        document.querySelector('.hierarchy-panel:nth-child(5) .toggle-add-form').disabled = true;
    } else if (level === 'series') {
        document.querySelector('.hierarchy-panel:nth-child(4) .toggle-add-form').disabled = true;
        document.querySelector('.hierarchy-panel:nth-child(5) .toggle-add-form').disabled = true;
    } else if (level === 'engines') {
        document.querySelector('.hierarchy-panel:nth-child(5) .toggle-add-form').disabled = true;
    }
}

function updateBreadcrumb(items) {
    document.getElementById('breadcrumb').innerHTML = items.join(' / ');
}

function updateStats() {
    const modelCount = document.querySelectorAll('#models-list li[data-id]').length || '-';
    const seriesCount = document.querySelectorAll('#series-list li[data-id]').length || '-';
    const engineCount = document.querySelectorAll('#engines-list li[data-id]').length || '-';
    const stageCount = document.querySelectorAll('#stages-list li[data-id]').length || '-';
    document.getElementById('total-models').textContent = modelCount;
    document.getElementById('total-series').textContent = seriesCount;
    document.getElementById('total-engines').textContent = engineCount;
    document.getElementById('total-stages').textContent = stageCount;
}

function deleteItem(event, itemId, itemType) {
    event.stopPropagation();
    if (confirm(`Bu ${itemType} ve bağlı tüm alt öğeler silinecek. Emin misiniz?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_item">
            <input type="hidden" name="item_id" value="${itemId}">
            <input type="hidden" name="item_type" value="${itemType}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Form submission enhancement for better UX
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function () {
            const submitBtn = this.querySelector('[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '🔄 İşleniyor...';
                submitBtn.disabled = true;
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });
});
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>