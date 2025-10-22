<?php
/**
 * Mr ECU - Database Configuration
 * Veritabanı bağlantı ayarları
 */

class Database {
    private $host = '127.0.0.1';
    private $port = '8888'; // MAMP default MySQL port
    private $db_name = 'mrecu_db';
    private $username = 'root';
    private $password = ''; // MAMP default (boş şifre)
    private $charset = 'utf8mb4';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Bağlantı hatası: " . $e->getMessage();
        }
        
        return $this->conn;
    }
}

// Database sınıfını global olarak kullanılabilir hale getir
$database = new Database();
$pdo = $database->getConnection();
?>
