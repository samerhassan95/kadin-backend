<?php
try {
    $host = '127.0.0.1';
    $port = '3307';
    $dbname = 'kadin';
    $username = 'root';
    $password = 'samerhassan11';
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30,
    ]);
    
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM categories');
    $result = $stmt->fetch();
    
    echo "Database connection successful!\n";
    echo "Categories count: " . $result['count'] . "\n";
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?>