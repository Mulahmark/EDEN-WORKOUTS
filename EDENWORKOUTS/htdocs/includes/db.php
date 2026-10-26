<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (Throwable $e) {
        error_log('[EDEN] DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Database unavailable. Check config/database.php.');
    }
    return $pdo;
}

function db_all(string $sql, array $p = []): array
{
    $st = db()->prepare($sql); $st->execute($p); return $st->fetchAll();
}

function db_one(string $sql, array $p = []): ?array
{
    $st = db()->prepare($sql); $st->execute($p); $r = $st->fetch(); return $r === false ? null : $r;
}

function db_val(string $sql, array $p = [])
{
    $st = db()->prepare($sql); $st->execute($p); $v = $st->fetchColumn(); return $v === false ? null : $v;
}

function db_run(string $sql, array $p = []): int
{
    $st = db()->prepare($sql); $st->execute($p); return $st->rowCount();
}

function db_insert(string $sql, array $p = []): int
{
    db_run($sql, $p); return (int) db()->lastInsertId();
}

/** Runs $fn inside a transaction; rolls back on any exception. */
function tx(callable $fn)
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $result = $fn($pdo);
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
