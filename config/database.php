<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
    $config = [];

    foreach ($required as $key) {
        $value = getenv($key);

        if ($value === false || trim($value) === '') {
            throw new RuntimeException(
                'Required database configuration is missing.'
            );
        }

        $config[$key] = $value;
    }

    $port = getenv('DB_PORT') ?: '3306';

    if (
        !ctype_digit($port) ||
        (int) $port < 1 ||
        (int) $port > 65535
    ) {
        throw new RuntimeException(
            'Database configuration is invalid.'
        );
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['DB_HOST'],
        (int) $port,
        $config['DB_NAME']
    );

    $pdo = new PDO(
        $dsn,
        $config['DB_USER'],
        $config['DB_PASSWORD'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]
    );

    return $pdo;
}
