<?php
/**
 * Quick Fix - Missing Files Creator
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Quick Fix - Missing Files</h1>";

// 404 error page
if (!file_exists('404.php')) {
    $content404 = '<?php
$pageTitle = "Sayfa Bulunamadı";
include "includes/header.php";
?>
<div class="container text-center py-5">
    <h1 class="display-1">404</h1>
    <h2>Sayfa Bulunamadı</h2>
    <p class="lead">Aradığınız sayfa mevcut değil.</p>
    <a href="/" class="btn btn-primary">Ana Sayfaya Dön</a>
</div>
<?php include "includes/footer.php"; ?>';
    
    file_put_contents('404.php', $content404);
    echo "✅ 404.php created<br>";
} else {
    echo "✅ 404.php exists<br>";
}

// 500 error page
if (!file_exists('500.php')) {
    $content500 = '<?php
$pageTitle = "Sunucu Hatası";
include "includes/header.php";
?>
<div class="container text-center py-5">
    <h1 class="display-1">500</h1>
    <h2>Sunucu Hatası</h2>
    <p class="lead">Sistemde bir hata oluştu.</p>
    <a href="/" class="btn btn-primary">Ana Sayfaya Dön</a>
</div>
<?php include "includes/footer.php"; ?>';
    
    file_put_contents('500.php', $content500);
    echo "✅ 500.php created<br>";
} else {
    echo "✅ 500.php exists<br>";
}

// Header basic
if (!file_exists('includes/header.php')) {
    $headerContent = '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . " - " : ""; ?>Mr ECU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">Mr ECU</a>
        </div>
    </nav>
    <main>';
    
    if (!is_dir('includes')) {
        mkdir('includes', 0755, true);
    }
    file_put_contents('includes/header.php', $headerContent);
    echo "✅ includes/header.php created<br>";
} else {
    echo "✅ includes/header.php exists<br>";
}

// Footer basic
if (!file_exists('includes/footer.php')) {
    $footerContent = '    </main>
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2025 Mr ECU. Tüm hakları saklıdır.</p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
    
    file_put_contents('includes/footer.php', $footerContent);
    echo "✅ includes/footer.php created<br>";
} else {
    echo "✅ includes/footer.php exists<br>";
}

echo "<h2>Test Links:</h2>";
echo "<ul>";
echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/' target='_blank'>Ana Sayfa Test</a></li>";
echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/404.php' target='_blank'>404 Page Test</a></li>";
echo "</ul>";
?>
