<?php
$ports = ["3306", "3307"];
foreach ($ports as $port) {
    try {
        $dsn = "mysql:host=127.0.0.1;port=$port;charset=utf8mb4";
        $conn = new PDO($dsn, "root", "");
        echo "Port $port: Success (no password)\n";
    } catch(PDOException $e) {
        echo "Port $port (no password): " . $e->getMessage() . "\n";
    }
}
