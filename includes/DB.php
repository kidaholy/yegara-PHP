<?php
/**
 * MySQL Database Wrapper (Proxy for Eloquent/Prisma-like syntax in PHP)
 */
require_once 'db_config.php';

class DB {
    private $table;
    private $pdo;

    public function __construct($table) {
        $this->table = $table;
        $this->pdo = getDB();
    }

    public static function table($name) {
        return new self($name);
    }

    public function findMany($params = []) {
        $sql = "SELECT * FROM {$this->table}";
        $where = $params['where'] ?? null;
        $orderBy = $params['orderBy'] ?? null;
        $limit = $params['take'] ?? null;

        $values = [];
        if ($where) {
            $sql .= " WHERE " . $this->buildWhere($where, $values);
        }

        if ($orderBy) {
            $sql .= " ORDER BY " . key($orderBy) . " " . current($orderBy);
        }

        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll();
    }

    public function findUnique($params = []) {
        $results = $this->findMany(array_merge($params, ['take' => 1]));
        return $results[0] ?? null;
    }

    public function create($params = []) {
        $data = $params['data'];
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $data;
    }

    public function update($params = []) {
        $data = $params['data'];
        $where = $params['where'];
        
        $set = [];
        $values = [];
        foreach ($data as $col => $val) {
            $set[] = "$col = ?";
            $values[] = $val;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $set);
        
        if ($where) {
            $sql .= " WHERE " . $this->buildWhere($where, $values);
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        
        return $this->findUnique(['where' => $where]);
    }

    public function count($params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $where = $params['where'] ?? null;
        $values = [];
        if ($where) {
            $sql .= " WHERE " . $this->buildWhere($where, $values);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        $res = $stmt->fetch();
        return (int)$res['count'];
    }

    private function buildWhere($where, &$values) {
        $parts = [];
        foreach ($where as $col => $val) {
            if (is_array($val)) {
                $op = key($val);
                $actualVal = current($val);
                if ($op === 'not') {
                    $parts[] = "$col != ?";
                    $values[] = $actualVal;
                } elseif ($op === 'in') {
                    $placeholders = implode(',', array_fill(0, count($actualVal), '?'));
                    $parts[] = "$col IN ($placeholders)";
                    $values = array_merge($values, (array)$actualVal);
                }
            } else {
                $parts[] = "$col = ?";
                $values[] = $val;
            }
        }
        return implode(' AND ', $parts);
    }
}

/**
 * Global helper function to match existing JsonDB interface
 */
function db($table) {
    return DB::table($table);
}
