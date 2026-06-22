<?php
$passwords = ["root", "admin", "password", "mysql", "123456", "1234", "12345678", "root123", "admin123", "Tirth", "tirth", "tirth123"];
foreach ($passwords as $pwd) {
    try {
        $dsn = "mysql:host=127.0.0.1;port=3307;charset=utf8mb4";
        $conn = new PDO($dsn, "root", $pwd);
        echo "Password '$pwd' SUCCESS!\n";
        exit;
    } catch(PDOException $e) {
        // Echo nothing for failed password
    }
}
echo "All passwords failed.\n";
