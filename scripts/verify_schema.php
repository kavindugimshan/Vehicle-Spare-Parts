<?php

declare(strict_types=1);

/**
 * scripts/verify_schema.php - asserts all 15 tables from
 * database/schema.sql exist. Called by the CI "database" job
 * (.github/workflows/ci.yml) right after importing schema.sql and the
 * seed files, so a broken import fails the build loudly instead of
 * silently.
 *
 * Connects using DB_HOST / DB_NAME / DB_USER / DB_PASS environment
 * variables (set by the CI job) rather than config/database.php,
 * since config.local.php doesn't exist in CI.
 */

$expectedTables = [
    'admin',
    'registered_user',
    'guest_user',
    'category',
    'brand',
    'country',
    'spare_part',
    'search_log',
    'cart',
    'cart_item',
    'orders',
    'order_item',
    'payment_gateway',
    'payment',
    'product_request',
];

$host = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'vspms_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbName};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "Could not connect to the database: {$e->getMessage()}\n");
    exit(1);
}

$stmt = $pdo->prepare(
    'SELECT table_name FROM information_schema.tables WHERE table_schema = ?'
);
$stmt->execute([$dbName]);
$existingTables = array_map('strtolower', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'table_name'));

$missing = array_filter($expectedTables, static fn (string $table): bool => !in_array(strtolower($table), $existingTables, true));

if (!empty($missing)) {
    fwrite(STDERR, "Missing tables: " . implode(', ', $missing) . "\n");
    exit(1);
}

echo 'All ' . count($expectedTables) . " expected tables are present.\n";
exit(0);
