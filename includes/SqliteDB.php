<?php
/**
 * SqliteDB - A SQLite-backed replacement for JsonDB
 * Mimics the JsonDB interface to provide a seamless migration paths.
 */

class SqliteDB {
    private $table;
    private $dbPath;
    private static $pdo = null;

    public function __construct($table) {
        $this->table = $table;
        $this->dbPath = DATA_DIR . '/database.sqlite';
        
        $this->initPDO();
        $this->initTable();
    }

    private function initPDO() {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO("sqlite:" . $this->dbPath);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                // Basic performance tuning for SQLite
                self::$pdo->exec("PRAGMA journal_mode = WAL;");
                self::$pdo->exec("PRAGMA synchronous = NORMAL;");
            } catch (PDOException $e) {
                die("CRITICAL ERROR: Could not connect to SQLite database: " . $e->getMessage());
            }
        }
    }

    private function initTable() {
        $stmt = self::$pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:table");
        $stmt->execute(['table' => $this->table]);
        if (!$stmt->fetch()) {
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS `{$this->table}` (
                id TEXT PRIMARY KEY,
                data TEXT
            )");
        }
    }

    private function decode($row) {
        if (!$row) return null;
        $data = json_decode($row['data'], true);
        $data['id'] = $row['id']; // Ensure ID from column is used
        return $data;
    }

    private function encode($data) {
        $id = $data['id'] ?? null;
        unset($data['id']);
        return [
            'id' => $id,
            'data' => json_encode($data)
        ];
    }

    private function buildWhere($where, &$params, $prefix = '') {
        $clauses = [];
        foreach ($where as $key => $val) {
            if ($key === 'OR') {
                $subClauses = [];
                foreach ($val as $i => $sub) {
                    $subClauses[] = "(" . $this->buildWhere($sub, $params, $prefix . "or{$i}_") . ")";
                }
                $clauses[] = "(" . implode(" OR ", $subClauses) . ")";
                continue;
            }
            if ($key === 'AND') {
                $subClauses = [];
                foreach ($val as $i => $sub) {
                    $subClauses[] = "(" . $this->buildWhere($sub, $params, $prefix . "and{$i}_") . ")";
                }
                $clauses[] = "(" . implode(" AND ", $subClauses) . ")";
                continue;
            }

            // Standard field
            $paramName = $prefix . str_replace(['.', '-'], '_', $key);
            
            // Special case: id is a top-level column
            $sqlField = ($key === 'id') ? "id" : "json_extract(data, '$.{$key}')";

            if (is_array($val)) {
                $isInsensitive = ($val['mode'] ?? '') === 'insensitive';
                $currentSqlField = $isInsensitive ? "LOWER({$sqlField})" : $sqlField;

                if (isset($val['equals'])) {
                    $op = is_numeric($val['equals']) ? "CAST({$sqlField} AS NUMERIC)" : $currentSqlField;
                    $clauses[] = "{$op} = :{$paramName}";
                    $params[$paramName] = $isInsensitive ? strtolower($val['equals']) : $val['equals'];
                }
                if (isset($val['in'])) {
                    $inParams = [];
                    foreach ($val['in'] as $i => $v) {
                        $p = "{$paramName}_in{$i}";
                        $inParams[] = ":{$p}";
                        $params[$p] = $isInsensitive ? strtolower($v) : $v;
                    }
                    $clauses[] = "{$currentSqlField} IN (" . implode(",", $inParams) . ")";
                }
                if (isset($val['notIn'])) {
                    $inParams = [];
                    foreach ($val['notIn'] as $i => $v) {
                        $p = "{$paramName}_notin{$i}";
                        $inParams[] = ":{$p}";
                        $params[$p] = $isInsensitive ? strtolower($v) : $v;
                    }
                    $clauses[] = "{$currentSqlField} NOT IN (" . implode(",", $inParams) . ")";
                }
                if (isset($val['not'])) {
                    $clauses[] = "{$currentSqlField} != :{$paramName}_not";
                    $params[$paramName . "_not"] = $isInsensitive ? strtolower($val['not']) : $val['not'];
                }
                if (isset($val['contains'])) {
                    $clauses[] = "{$currentSqlField} LIKE :{$paramName}_like";
                    $params[$paramName . "_like"] = "%" . ($isInsensitive ? strtolower($val['contains']) : $val['contains']) . "%";
                }
                if (isset($val['gte'])) {
                    $op = is_numeric($val['gte']) ? "CAST({$sqlField} AS NUMERIC)" : $sqlField;
                    $clauses[] = "{$op} >= :{$paramName}_gte";
                    $params[$paramName . "_gte"] = $val['gte'];
                }
                if (isset($val['lte'])) {
                    $op = is_numeric($val['lte']) ? "CAST({$sqlField} AS NUMERIC)" : $sqlField;
                    $clauses[] = "{$op} <= :{$paramName}_lte";
                    $params[$paramName . "_lte"] = $val['lte'];
                }
                if (isset($val['gt'])) {
                    $op = is_numeric($val['gt']) ? "CAST({$sqlField} AS NUMERIC)" : $sqlField;
                    $clauses[] = "{$op} > :{$paramName}_gt";
                    $params[$paramName . "_gt"] = $val['gt'];
                }
                if (isset($val['lt'])) {
                    $op = is_numeric($val['lt']) ? "CAST({$sqlField} AS NUMERIC)" : $sqlField;
                    $clauses[] = "{$op} < :{$paramName}_lt";
                    $params[$paramName . "_lt"] = $val['lt'];
                }
            } else {
                // Special case: if val is false and key doesn't exist
                if ($val === false) {
                    $clauses[] = "({$sqlField} = 0 OR {$sqlField} IS NULL OR {$sqlField} = 'false')";
                } elseif ($val === true) {
                    $clauses[] = "({$sqlField} = 1 OR {$sqlField} = 'true')";
                } else {
                    $op = is_numeric($val) ? "CAST({$sqlField} AS NUMERIC)" : $sqlField;
                    $clauses[] = "{$op} = :{$paramName}";
                    $params[$paramName] = $val;
                }
            }
        }
        return implode(" AND ", $clauses) ?: "1=1";
    }

    public function findMany($args = []) {
        $params = [];
        $select = "*";
        
        if (isset($args['exclude']) && is_array($args['exclude']) && !empty($args['exclude'])) {
            $paths = array_map(function($field) {
                return "'$.{$field}'";
            }, $args['exclude']);
            $select = "id, json_remove(data, " . implode(", ", $paths) . ") as data";
        }

        $sql = "SELECT {$select} FROM `{$this->table}`";
        
        if (isset($args['where'])) {
            $sql .= " WHERE " . $this->buildWhere($args['where'], $params);
        }

        if (isset($args['orderBy'])) {
            $orders = [];
            foreach ($args['orderBy'] as $key => $dir) {
                $sqlField = ($key === 'id') ? "id" : "json_extract(data, '$.{$key}')";
                
                // Special handling for numeric fields to avoid lexicographical sorting (1, 10, 2)
                $numericFields = ['menuId', 'price', 'quantity', 'stockConsumption', 'reportQuantity', 'total', 'amount'];
                if (in_array($key, $numericFields) || strpos(strtolower($key), 'price') !== false) {
                    $sqlField = "CAST({$sqlField} AS NUMERIC)";
                }
                
                $orders[] = "{$sqlField} " . strtoupper($dir);
            }
            $sql .= " ORDER BY " . implode(", ", $orders);
        }

        if (isset($args['take'])) {
            $sql .= " LIMIT " . (int)$args['take'];
        }

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        return array_map([$this, 'decode'], $results);
    }

    public function count($args = []) {
        $params = [];
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        if (isset($args['where'])) {
            $sql .= " WHERE " . $this->buildWhere($args['where'], $params);
        }
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function findUnique($args) {
        $results = $this->findMany(['where' => $args['where'], 'take' => 1]);
        return $results[0] ?? null;
    }

    public function findFirst($args = []) {
        $args['take'] = 1;
        $results = $this->findMany($args);
        return $results[0] ?? null;
    }

    public function create($args) {
        $id = $args['data']['id'] ?? bin2hex(random_bytes(10));
        $now = date('Y-m-d H:i:s');
        
        $data = array_merge([
            'id' => $id,
            'createdAt' => $now,
            'updatedAt' => $now,
            'isDeleted' => false
        ], $args['data']);

        $encoded = $this->encode($data);
        
        $stmt = self::$pdo->prepare("INSERT INTO `{$this->table}` (id, data) VALUES (:id, :data)");
        $stmt->execute($encoded);
        
        return $data;
    }

    public function update($args) {
        $item = $this->findUnique(['where' => $args['where']]);
        if (!$item) throw new Exception("Record not found for update in {$this->table}");

        $updatedData = array_merge($item, $args['data']);
        $updatedData['updatedAt'] = date('Y-m-d H:i:s');
        
        $encoded = $this->encode($updatedData);
        
        $stmt = self::$pdo->prepare("UPDATE `{$this->table}` SET data = :data WHERE id = :id");
        $stmt->execute($encoded);
        
        return $updatedData;
    }

    public function updateMany($args) {
        $items = $this->findMany(['where' => $args['where']]);
        $count = 0;
        foreach ($items as $item) {
            $this->update([
                'where' => ['id' => $item['id']],
                'data' => $args['data']
            ]);
            $count++;
        }
        return $count;
    }

    public function delete($args) {
        $item = $this->findUnique(['where' => $args['where']]);
        if (!$item) throw new Exception("Record not found for delete in {$this->table}");

        $stmt = self::$pdo->prepare("DELETE FROM `{$this->table}` WHERE id = :id");
        $stmt->execute(['id' => $item['id']]);
        
        return $item;
    }

    public function deleteMany($args) {
        $params = [];
        $where = $this->buildWhere($args['where'], $params);
        
        // SQLite doesn't support JOIN/Complex where in DELETE easily with params if we use json_extract
        // So we'll do it in two steps for safety or use a subquery
        $sql = "DELETE FROM `{$this->table}` WHERE id IN (SELECT id FROM `{$this->table}` WHERE {$where})";
        
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function truncate() {
        return self::$pdo->exec("DELETE FROM `{$this->table}`");
    }

    public static function beginTransaction() {
        self::$pdo->beginTransaction();
    }

    public static function commit() {
        self::$pdo->commit();
    }

    public static function rollBack() {
        self::$pdo->rollBack();
    }
}
