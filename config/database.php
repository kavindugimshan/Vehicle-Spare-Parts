<?php

declare(strict_types=1);

/**
 * config/database.php - shared PDO connection.
 * FROZEN: see docs/PROJECT_BRIEF.md, Section 3.
 *
 * Every query in the project goes through getDB(). It returns the same
 * PDO instance on every call within a request (a simple static-variable
 * singleton), configured for exceptions and associative fetches so no
 * caller has to repeat that setup.
 */

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}
