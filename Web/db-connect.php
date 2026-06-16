<?php
function getDatabaseConnection($userType = 'customer') {
    $host = 'localhost';
    $dbname = 'sushi';
    
    if ($userType === 'admin') {
        $username = 'sushi_admin';
        $password = 'sushiroisthebest';
    } else {
        $username = 'sushi_customer';
        $password = 'customerpass';
    }
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>