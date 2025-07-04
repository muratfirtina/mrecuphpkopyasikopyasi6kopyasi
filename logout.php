<?php
/**
 * Mr ECU - Çıkış İşlemi
 */

require_once 'config/config.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    $user = new User($pdo);
    $user->logout();
}

redirect('index.php');
?>
