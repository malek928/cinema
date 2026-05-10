<?php
$host = 'localhost';
$dbname = 'cinema';
$username = 'root';
$password = '92442505';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("DB error: " . $e->getMessage());
}
?>