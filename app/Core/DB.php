<?php
class DB {
    private static ?PDO $pdo = null;
    static function pdo(): PDO {
        if (!self::$pdo) {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                env('DB_HOST', '127.0.0.1'), env('DB_PORT', '3306'), env('DB_NAME', 'company_management'));
            self::$pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::$pdo->exec("SET time_zone = '+07:00'");
        }
        return self::$pdo;
    }
    static function q(string $sql, array $p = []): PDOStatement { $s = self::pdo()->prepare($sql); $s->execute($p); return $s; }
    static function all(string $sql, array $p = []): array { return self::q($sql, $p)->fetchAll(); }
    static function one(string $sql, array $p = []): ?array { $r = self::q($sql, $p)->fetch(); return $r ?: null; }
    static function val(string $sql, array $p = []) { $r = self::q($sql, $p)->fetchColumn(); return $r === false ? null : $r; }
    static function exec(string $sql, array $p = []): int { return self::q($sql, $p)->rowCount(); }
    static function pairs(string $sql, array $p = []): array { return self::q($sql, $p)->fetchAll(PDO::FETCH_KEY_PAIR); }
    static function id(): int { return (int)self::pdo()->lastInsertId(); }
    static function tx(callable $fn) {
        $pdo = self::pdo(); $pdo->beginTransaction();
        try { $r = $fn(); $pdo->commit(); return $r; }
        catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
    static function paginate(string $select, string $from, string $where, array $params, string $order, int $page, int $per = 0): array {
        $per = $per ?: (int)cfg('per_page', 10);
        $total = (int)self::val("SELECT COUNT(*) FROM $from $where", $params);
        $pages = max(1, (int)ceil($total / $per));
        $page = max(1, min($page, $pages));
        $off = ($page - 1) * $per;
        $rows = self::all("SELECT $select FROM $from $where ORDER BY $order LIMIT $per OFFSET $off", $params);
        return ['rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }
}