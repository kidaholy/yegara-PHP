<?php
/**
 * JsonDB - Legacy wrapper for SqliteDB to maintain backward compatibility.
 */

require_once __DIR__ . '/SqliteDB.php';

class JsonDB {
    private $adapter;

    public function __construct($table) {
        $this->adapter = new SqliteDB($table);
    }

    public function findMany($args = []) {
        return $this->adapter->findMany($args);
    }

    public function count($args = []) {
        return $this->adapter->count($args);
    }

    public function findUnique($args) {
        return $this->adapter->findUnique($args);
    }

    public function findFirst($args = []) {
        return $this->adapter->findFirst($args);
    }

    public function create($args) {
        return $this->adapter->create($args);
    }

    public function update($args) {
        return $this->adapter->update($args);
    }

    public function updateMany($args) {
        return $this->adapter->updateMany($args);
    }

    public function delete($args) {
        return $this->adapter->delete($args);
    }

    public function deleteMany($args) {
        return $this->adapter->deleteMany($args);
    }

    public function insert($data) {
        return $this->adapter->create(['data' => $data]);
    }

    public function truncate() {
        return $this->adapter->truncate();
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
