<?php
echo "<h1>PHP Test - " . date('Y-m-d H:i:s') . "</h1>";
echo "<p>Eğer bu metni görüyorsanız PHP çalışıyor.</p>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
phpinfo();
?>
