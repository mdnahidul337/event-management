<?php
$host = 'localhost';
$db = 'scc_db';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$charset = 'utf8mb4';
$port = '3307';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
     PDO::ATTR_EMULATE_PREPARES => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int) $e->getCode());
}

// ─── Auto-sync Event Statuses ───
$now_sync = date('Y-m-d H:i:s');
$pdo->exec("
    UPDATE events SET status = 
    CASE 
        WHEN '$now_sync' < start_date THEN 'Upcoming'
        WHEN end_date IS NOT NULL AND '$now_sync' > end_date THEN 'Completed'
        WHEN end_date IS NULL AND '$now_sync' > DATE_ADD(start_date, INTERVAL 6 HOUR) THEN 'Completed'
        ELSE 'Ongoing'
    END
");
?>