<?php
/**
 * JsonDB - A PHP port of the custom JSON database logic
 */

class JsonDB {
    private $table;
    private $filePath;
    private static $cache = [];

    public function __construct($table) {
        $this->table = $table;
        
        // Try to find the data directory robustly
        if (!is_dir(DATA_DIR)) {
            // Fallback for case sensitivity or common locations
            $possible = [DATA_DIR, BASE_DIR . '/Data', dirname(BASE_DIR) . '/data'];
            foreach ($possible as $p) {
                if (is_dir($p)) {
                    $found_data_dir = $p;
                    break;
                }
            }
            if (!isset($found_data_dir)) {
                // Last resort: create it
                if (@mkdir(DATA_DIR, 0755, true)) {
                    $found_data_dir = DATA_DIR;
                } else {
                    die("CRITICAL ERROR: Data directory not found and could not be created at " . DATA_DIR . ". Please ensure a folder named 'data' exists in the root.");
                }
            }
        } else {
            $found_data_dir = DATA_DIR;
        }

        $this->filePath = $found_data_dir . '/' . $table . '.json';
        
        // Try case-insensitive filename match if file doesn't exist
        if (!file_exists($this->filePath)) {
            $files = glob($found_data_dir . '/*.json');
            foreach ($files as $f) {
                if (strtolower(basename($f)) === strtolower($table . '.json')) {
                    $this->filePath = $f;
                    break;
                }
            }
        }

        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    private function read() {
        if (isset(self::$cache[$this->table])) {
            $stats = stat($this->filePath);
            if ($stats['mtime'] === self::$cache[$this->table]['mtime']) {
                return self::$cache[$this->table]['data'];
            }
        }

        $content = file_get_contents($this->filePath);
        $data = json_decode($content, true) ?: [];
        
        self::$cache[$this->table] = [
            'data' => $data,
            'mtime' => filemtime($this->filePath)
        ];

        return $data;
    }

    private function write($data) {
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
        self::$cache[$this->table] = [
            'data' => $data,
            'mtime' => time()
        ];
    }

    private function generateId() {
        return bin2hex(random_bytes(10));
    }

    public function findMany($args = []) {
        $data = $this->read();

        // Where filtering
        if (isset($args['where'])) {
            $data = array_values(array_filter($data, function($item) use ($args) {
                return $this->matchCriteria($item, $args['where']);
            }));
        }

        // OrderBy
        if (isset($args['orderBy'])) {
            $order = $args['orderBy'];
            usort($data, function($a, $b) use ($order) {
                foreach ($order as $key => $dir) {
                    if ($a[$key] == $b[$key]) continue;
                    return ($dir === 'asc') ? ($a[$key] < $b[$key] ? -1 : 1) : ($a[$key] > $b[$key] ? -1 : 1);
                }
                return 0;
            });
        }

        // Take (Limit)
        if (isset($args['take'])) {
            $data = array_slice($data, 0, $args['take']);
        }

        return $data;
    }

    public function count($args = []) {
        $data = $this->findMany($args);
        return count($data);
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
        $data = $this->read();
        $newItem = array_merge([
            'id' => $args['data']['id'] ?? $this->generateId(),
            'createdAt' => date('Y-m-d\TH:i:s.v\Z'),
            'updatedAt' => date('Y-m-d\TH:i:s.v\Z'),
            'isDeleted' => false
        ], $args['data']);

        $data[] = $newItem;
        $this->write($data);
        return $newItem;
    }

    public function update($args) {
        $data = $this->read();
        foreach ($data as &$item) {
            if ($this->matchCriteria($item, $args['where'])) {
                $item = array_merge($item, $args['data']);
                $item['updatedAt'] = date('Y-m-d\TH:i:s.v\Z');
                $this->write($data);
                return $item;
            }
        }
        throw new Exception("Record not found for update in {$this->table}");
    }

    public function delete($args) {
        $data = $this->read();
        foreach ($data as $i => $item) {
            if ($this->matchCriteria($item, $args['where'])) {
                $deleted = array_splice($data, $i, 1)[0];
                $this->write($data);
                return $deleted;
            }
        }
        throw new Exception("Record not found for delete in {$this->table}");
    }

    private function matchCriteria($item, $where) {
        foreach ($where as $key => $val) {
            if ($key === 'OR') {
                $matched = false;
                foreach ($val as $sub) {
                    if ($this->matchCriteria($item, $sub)) { $matched = true; break; }
                }
                if (!$matched) return false;
                continue;
            }
            if ($key === 'AND') {
                foreach ($val as $sub) {
                    if (!$this->matchCriteria($item, $sub)) return false;
                }
                continue;
            }

            if (is_array($val)) {
                if (isset($val['equals']) && $item[$key] != $val['equals']) return false;
                if (isset($val['in']) && !in_array($item[$key], $val['in'])) return false;
                if (isset($val['not']) && $item[$key] == $val['not']) return false;
                if (isset($val['contains']) && stripos($item[$key], $val['contains']) === false) return false;
                // Basic gte/lte for strings/dates
                if (isset($val['gte']) && strcasecmp($item[$key], $val['gte']) < 0) return false;
                if (isset($val['lte']) && strcasecmp($item[$key], $val['lte']) > 0) return false;
            } else {
                if ($item[$key] != $val) return false;
            }
        }
        return true;
    }
}

/**
 * Convenience function to get a table instance
 */
function db($table) {
    static $instances = [];
    if (!isset($instances[$table])) {
        $instances[$table] = new JsonDB($table);
    }
    return $instances[$table];
}
