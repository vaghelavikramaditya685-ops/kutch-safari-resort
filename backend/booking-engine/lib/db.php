<?php
/* ===========================================================================
 *  Database access. Thin wrappers over PDO — no ORM, no magic.
 *  Works against MySQL (cPanel) and SQLite (local testing) unchanged.
 * ======================================================================== */

require_once __DIR__ . '/../config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $driver = cfg('db.driver', 'mysql');

    if ($driver === 'sqlite') {
        $path = cfg('db.sqlite_path');
        if (!is_dir(dirname($path))) @mkdir(dirname($path), 0775, true);
        $pdo = new PDO('sqlite:' . $path);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', cfg('db.host'), cfg('db.name'));
        $pdo = new PDO($dsn, cfg('db.user'), cfg('db.pass'));
        $pdo->exec("SET time_zone = '+05:30'");
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
}

/** Every row matching the query. */
function q(string $sql, array $params = []): array {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** The first row, or null. */
function q1(string $sql, array $params = []) {
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

/** A single value from the first row. */
function qval(string $sql, array $params = [], $default = null) {
    $st = db()->prepare($sql);
    $st->execute($params);
    $v = $st->fetchColumn();
    return $v === false ? $default : $v;
}

/** Run an INSERT/UPDATE/DELETE. Returns rows affected. */
function exec_sql(string $sql, array $params = []): int {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

/** Insert an associative array, returning the new id. */
function insert(string $table, array $data): int {
    $cols = array_keys($data);
    $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)',
        $table, implode(',', $cols), ':' . implode(',:', $cols));
    $st = db()->prepare($sql);
    $st->execute($data);
    return (int) db()->lastInsertId();
}

/** Update by id. */
function update(string $table, int $id, array $data): int {
    $sets = [];
    foreach (array_keys($data) as $c) $sets[] = "$c = :$c";
    $data['__id'] = $id;
    $sql = sprintf('UPDATE %s SET %s WHERE id = :__id', $table, implode(', ', $sets));
    $st = db()->prepare($sql);
    $st->execute($data);
    return $st->rowCount();
}

function db_begin(): void { db()->beginTransaction(); }
function db_commit(): void { if (db()->inTransaction()) db()->commit(); }
function db_rollback(): void { if (db()->inTransaction()) db()->rollBack(); }

/**
 * Lock rows while we check availability and write a booking, so two guests
 * cannot both take the last cottage. MySQL supports it; SQLite serialises
 * writes anyway, so the clause is simply omitted there.
 */
function for_update(): string {
    return cfg('db.driver') === 'sqlite' ? '' : ' FOR UPDATE';
}

/* ---------------------------------------------------------------------------
 * Input checks shared by the guest pages, the enquiry form and the admin.
 * The limits match the database columns (MySQL refuses longer values).
 * ------------------------------------------------------------------------ */
const LIMITS = [
    'name' => 120, 'email' => 160, 'city' => 80, 'guest_note' => 2000, 'enquiry_message' => 3000,
    'staff_note' => 2000, 'payment_note' => 300, 'cancel_reason' => 250, 'interest' => 150,
];

/** Too long for its field? (counts characters, not bytes). */
function too_long($value, string $field): bool {
    return mb_strlen(trim((string) $value)) > LIMITS[$field];
}

/** A phone number: 7–15 digits, spaces, dashes, brackets and a leading + allowed. */
function valid_phone(string $phone): bool {
    return (bool) preg_match('/^\+?\d{7,15}$/', preg_replace('/[\s\-().]/', '', $phone));
}

/** An arrival time as the booking page's scroll wheel writes it ("4:30 PM"), or nothing. */
function valid_arrival_time(string $t): bool {
    return $t === '' || (bool) preg_match('/^(1[0-2]|[1-9]):[0-5]\d (AM|PM)$/', $t);
}

/** A real calendar date written YYYY-MM-DD (not "2026-13-45"). */
function valid_date(string $d): bool {
    return (bool) preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
}

/** Read a row from the settings table. */
function setting(string $name, $default = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (q('SELECT name, value FROM settings') as $r) $cache[$r['name']] = $r['value'];
        } catch (Throwable $e) { /* table may not exist yet */ }
    }
    return $cache[$name] ?? $default;
}

/** Record anything that touches money or inventory. */
function audit(string $action, ?string $entity = null, $entity_id = null, $detail = null, string $actor = 'system'): void {
    try {
        insert('audit_log', [
            'actor'      => $actor,
            'action'     => $action,
            'entity'     => $entity,
            'entity_id'  => $entity_id === null ? null : (string) $entity_id,
            'detail'     => is_string($detail) ? $detail : json_encode($detail, JSON_UNESCAPED_UNICODE),
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => now(),
        ]);
    } catch (Throwable $e) { /* logging must never break a booking */ }
}

function now(): string { return date('Y-m-d H:i:s'); }

/**
 * PHP on Windows ships without a list of trusted certificates, so HTTPS calls
 * (Razorpay, Stayflexi) fail there. Use the Windows certificate store instead.
 * Linux hosts (cPanel) already have a certificate bundle and are left alone.
 */
function curl_trust_system_certs($ch): void {
    if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA')) {
        curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
    }
}
