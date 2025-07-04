<?php
/**
 * Mr ECU - Database Query Security Wrapper
 * SQL Injection koruması için güvenli database sınıfı
 */

class SecureDatabase {
    private $pdo;
    private $security;
    
    public function __construct($database, $security = null) {
        $this->pdo = $database;
        $this->security = $security ?: new SecurityManager($database);
    }
    
    /**
     * Güvenli SELECT sorgusu
     */
    public function secureSelect($table, $columns = '*', $where = [], $orderBy = null, $limit = null) {
        // Tablo adını sanitize et
        $table = $this->sanitizeTableName($table);
        
        // Kolon adlarını sanitize et
        if (is_array($columns)) {
            $columns = array_map([$this, 'sanitizeColumnName'], $columns);
            $columns = implode(', ', $columns);
        } else {
            $columns = $columns === '*' ? '*' : $this->sanitizeColumnName($columns);
        }
        
        $query = "SELECT $columns FROM $table";
        $params = [];
        
        // WHERE koşulları
        if (!empty($where)) {
            $whereClause = $this->buildWhereClause($where, $params);
            $query .= " WHERE $whereClause";
        }
        
        // ORDER BY
        if ($orderBy) {
            $orderBy = $this->sanitizeOrderBy($orderBy);
            $query .= " ORDER BY $orderBy";
        }
        
        // LIMIT
        if ($limit) {
            $limit = (int)$limit;
            $query .= " LIMIT $limit";
        }
        
        return $this->executeQuery($query, $params);
    }
    
    /**
     * Güvenli INSERT sorgusu
     */
    public function secureInsert($table, $data) {
        $table = $this->sanitizeTableName($table);
        
        $columns = array_keys($data);
        $columns = array_map([$this, 'sanitizeColumnName'], $columns);
        
        $placeholders = array_fill(0, count($columns), '?');
        
        $query = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        // Değerleri sanitize et
        $values = array_values($data);
        $sanitizedValues = [];
        foreach ($values as $value) {
            $sanitizedValues[] = $this->security->sanitizeInput($value);
        }
        
        $stmt = $this->executeQuery($query, $sanitizedValues);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Güvenli UPDATE sorgusu
     */
    public function secureUpdate($table, $data, $where) {
        $table = $this->sanitizeTableName($table);
        
        $setParts = [];
        $params = [];
        
        // SET kısmı
        foreach ($data as $column => $value) {
            $column = $this->sanitizeColumnName($column);
            $setParts[] = "$column = ?";
            $params[] = $this->security->sanitizeInput($value);
        }
        
        $query = "UPDATE $table SET " . implode(', ', $setParts);
        
        // WHERE koşulları
        if (!empty($where)) {
            $whereClause = $this->buildWhereClause($where, $params);
            $query .= " WHERE $whereClause";
        }
        
        $stmt = $this->executeQuery($query, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Güvenli DELETE sorgusu
     */
    public function secureDelete($table, $where) {
        $table = $this->sanitizeTableName($table);
        
        $params = [];
        $query = "DELETE FROM $table";
        
        // WHERE koşulları (DELETE için zorunlu)
        if (empty($where)) {
            throw new Exception('DELETE sorgusu için WHERE koşulu zorunludur.');
        }
        
        $whereClause = $this->buildWhereClause($where, $params);
        $query .= " WHERE $whereClause";
        
        $stmt = $this->executeQuery($query, $params);
        return $stmt->rowCount();
    }
    
    /**
     * WHERE koşullarını güvenli oluştur
     */
    private function buildWhereClause($where, &$params) {
        $conditions = [];
        
        foreach ($where as $column => $value) {
            $column = $this->sanitizeColumnName($column);
            
            if (is_array($value)) {
                // IN operatörü
                $placeholders = array_fill(0, count($value), '?');
                $conditions[] = "$column IN (" . implode(', ', $placeholders) . ")";
                foreach ($value as $v) {
                    $params[] = $this->security->sanitizeInput($v);
                }
            } elseif (strpos($column, ' ') !== false) {
                // LIKE, >, <, != gibi operatörler
                $parts = explode(' ', $column, 2);
                $col = $this->sanitizeColumnName($parts[0]);
                $operator = $this->sanitizeOperator($parts[1]);
                $conditions[] = "$col $operator ?";
                $params[] = $this->security->sanitizeInput($value);
            } else {
                // Basit eşitlik
                $conditions[] = "$column = ?";
                $params[] = $this->security->sanitizeInput($value);
            }
        }
        
        return implode(' AND ', $conditions);
    }
    
    /**
     * Tablo adını sanitize et
     */
    private function sanitizeTableName($table) {
        // Sadece alfanumerik ve underscore
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new Exception('Geçersiz tablo adı: ' . $table);
        }
        return $table;
    }
    
    /**
     * Kolon adını sanitize et
     */
    private function sanitizeColumnName($column) {
        // Sadece alfanumerik ve underscore
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $column)) {
            throw new Exception('Geçersiz kolon adı: ' . $column);
        }
        return $column;
    }
    
    /**
     * ORDER BY sanitize et
     */
    private function sanitizeOrderBy($orderBy) {
        $allowedDirections = ['ASC', 'DESC'];
        $parts = explode(' ', trim($orderBy));
        
        $column = $this->sanitizeColumnName($parts[0]);
        $direction = isset($parts[1]) ? strtoupper($parts[1]) : 'ASC';
        
        if (!in_array($direction, $allowedDirections)) {
            $direction = 'ASC';
        }
        
        return "$column $direction";
    }
    
    /**
     * SQL operatörünü sanitize et
     */
    private function sanitizeOperator($operator) {
        $allowedOperators = ['=', '!=', '<>', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'];
        $operator = strtoupper(trim($operator));
        
        if (!in_array($operator, $allowedOperators)) {
            throw new Exception('Geçersiz SQL operatörü: ' . $operator);
        }
        
        return $operator;
    }
    
    /**
     * Sorguyu güvenli çalıştır
     */
    private function executeQuery($query, $params = []) {
        try {
            // Güvenlik kontrolü
            if ($this->containsUnsafeSQL($query)) {
                $this->security->logSecurityEvent('unsafe_sql_detected', $query, $this->security->getClientIp());
                throw new Exception('Güvenlik hatası: Unsafe SQL detected');
            }
            
            $stmt = $this->pdo->prepare($query);
            $result = $stmt->execute($params);
            
            if (!$result) {
                throw new PDOException('Query execution failed: ' . implode(', ', $stmt->errorInfo()));
            }
            
            return $stmt;
            
        } catch (PDOException $e) {
            $this->security->logSecurityEvent('database_error', [
                'query' => $query,
                'error' => $e->getMessage()
            ], $this->security->getClientIp());
            throw $e;
        }
    }
    
    /**
     * Güvenli olmayan SQL kontrol
     */
    private function containsUnsafeSQL($query) {
        $dangerous_patterns = [
            '/;\s*(drop|delete|truncate|create|alter|exec|execute|sp_|xp_)/i',
            '/union\s+select(?!\s+from\s+\w+\s+where)/i',
            '/information_schema/i',
            '/mysql\./i',
            '/sys\./i',
            '/load_file/i',
            '/into\s+(outfile|dumpfile)/i',
            '/benchmark\s*\(/i',
            '/sleep\s*\(/i',
            '/waitfor\s+delay/i'
        ];
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Transaction başlat
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Transaction commit
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Transaction rollback
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * Son insert ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Ham PDO erişimi (acil durumlar için)
     */
    public function getRawPDO() {
        $this->security->logSecurityEvent('raw_pdo_access', 'Raw PDO access requested', $this->security->getClientIp());
        return $this->pdo;
    }
}
?>
