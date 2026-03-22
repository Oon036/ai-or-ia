<?php
$host = '127.0.0.1';
$dbname = 'school_mgt_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    if ($e->getCode() == 1049) {
        // Database does not exist
        die("Database 'school_mgt_db' does not exist. Please run setup_db.php first.");
    }
    die("Database Connection failed: " . $e->getMessage());
}
?>
