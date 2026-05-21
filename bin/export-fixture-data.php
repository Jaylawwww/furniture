<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

$url = $_ENV['DATABASE_URL'] ?? '';
if (!preg_match('#mysql://([^:]+):([^@]+)@([^:]+):(\d+)/([^?]+)#', $url, $m)) {
    fwrite(STDERR, "Invalid DATABASE_URL\n");
    exit(1);
}

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $m[3], $m[4], $m[5]),
    $m[1],
    $m[2],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$outDir = dirname(__DIR__) . '/var/fixture-export';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$tables = ['user', 'category', 'product', 'customer_order', 'customer_order_item', 'customer_booking', 'customer_payment', 'activity_log'];

foreach ($tables as $table) {
    $rows = $pdo->query('SELECT * FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents($outDir . '/' . $table . '.json', json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo $table . ': ' . count($rows) . PHP_EOL;
}
