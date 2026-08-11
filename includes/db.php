<?php
/**
 * Thin PDO wrapper. Works with MySQL (live) and SQLite (local testing).
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        if (DB_DRIVER === 'sqlite') {
            $dir = dirname(DB_SQLITE_PATH);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, $opts);
            $pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        }
    } catch (PDOException $e) {
        // The installer handles its own connection errors so it can still
        // render the setup form (e.g. the database does not exist yet).
        if (defined('LP_INSTALLER')) {
            $pdo = null;
            throw $e;
        }
        if (DEBUG) {
            die('<pre style="font:14px monospace;padding:24px">Database connection failed: '
                . htmlspecialchars($e->getMessage())
                . "\n\nCheck the credentials at the top of config.php.</pre>");
        }
        http_response_code(500);
        die('Service temporarily unavailable.');
    }

    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function one(string $sql, array $params = [])
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function insert_id(): int
{
    return (int) db()->lastInsertId();
}

/** Portable "AUTOINCREMENT PRIMARY KEY" for the installer. */
function pk_type(): string
{
    return DB_DRIVER === 'sqlite'
        ? 'INTEGER PRIMARY KEY AUTOINCREMENT'
        : 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
}

function table_suffix(): string
{
    return DB_DRIVER === 'sqlite' ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
}

function tables_exist(): bool
{
    try {
        db()->query('SELECT 1 FROM pages LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
