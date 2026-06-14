<?php
require_once 'includes/config.php';
require_once 'includes/JsonDB.php';
$db = new JsonDB('users');
$users = $db->findMany(['where' => ['isDeleted' => false]]);
foreach ($users as $u) {
    echo "ID: " . $u['id'] . " | Email: " . $u['email'] . " | Name: " . $u['name'] . "\n";
}
