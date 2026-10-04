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
        // Two guests saving at the same moment: the second waits for the first
        // instead of failing at once with "database is locked" (it used to refuse
        // 17 of 20 simultaneous bookings). WAL lets pages read while one writes.
        $pdo->exec('PRAGMA busy_timeout = 15000');
        $pdo->exec('PRAGMA journal_mode = WAL');
    } else {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', cfg('db.host'), cfg('db.name'));
        $pdo = new PDO($dsn, cfg('db.user'), cfg('db.pass'));
        $pdo->exec("SET time_zone = '+05:30'");
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    db_upgrade($pdo);
    return $pdo;
}

/**
 * Small additions to an existing database, applied once, on first use after the
 * code is updated: there are no migration scripts and nobody runs setup.php on
 * the live site. Only ever adds (a table, an index); never changes or removes data.
 */
const DB_VERSION = 2;
function db_upgrade(PDO $pdo): void {
    try {
        $v = $pdo->query("SELECT value FROM settings WHERE name = 'db_version'")->fetchColumn();
    } catch (Throwable $e) { return; }   // no tables yet: bin/setup.php creates them from schema.sql
    if ((int) $v >= DB_VERSION) return;
    $sqlite = cfg('db.driver') === 'sqlite';
    try {
        // One admin at a time (admin/_auth.php): who holds the admin panel, and since when.
        $pdo->exec($sqlite
            ? "CREATE TABLE IF NOT EXISTS admin_lock (id INTEGER PRIMARY KEY, session_id TEXT,
                 admin_id INTEGER, admin_name TEXT, since TEXT, last_seen TEXT, last_beat TEXT)"
            : "CREATE TABLE IF NOT EXISTS admin_lock (id INT PRIMARY KEY, session_id VARCHAR(128),
                 admin_id INT, admin_name VARCHAR(120), since DATETIME, last_seen DATETIME, last_beat DATETIME) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // The rate limit counts audit_log rows by action, address and time.
        $has = $sqlite
            ? $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'index' AND name = 'idx_audit_rl'")->fetchColumn()
            : $pdo->query("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE()
                            AND table_name = 'audit_log' AND index_name = 'idx_audit_rl'")->fetchColumn();
        if (!$has) $pdo->exec('CREATE INDEX idx_audit_rl ON audit_log (action, ip, created_at)');
        $pdo->prepare($sqlite ? "INSERT OR REPLACE INTO settings (name, value) VALUES ('db_version', ?)"
                              : "REPLACE INTO settings (name, value) VALUES ('db_version', ?)")->execute([(string) DB_VERSION]);
    } catch (Throwable $e) {
        error_log('db_upgrade: ' . $e->getMessage());   // tried again on the next request
    }
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

/**
 * Start a write transaction. On SQLite it takes the write lock straight away
 * (BEGIN IMMEDIATE), so "check, then write" steps from two requests at the same
 * moment run one after the other instead of interleaving; MySQL gets the same
 * from the FOR UPDATE row locks (for_update()). Nested calls join the outer one.
 */
function db_begin(): void {
    if (db_in_tx()) { $GLOBALS['__db_tx_depth']++; return; }
    if (cfg('db.driver') === 'sqlite') db()->exec('BEGIN IMMEDIATE');
    else db()->beginTransaction();
    $GLOBALS['__db_tx_depth'] = 1;
}
function db_in_tx(): bool { return ($GLOBALS['__db_tx_depth'] ?? 0) > 0; }
function db_commit(): void {
    if (!db_in_tx()) return;
    if (--$GLOBALS['__db_tx_depth'] > 0) return;
    if (cfg('db.driver') === 'sqlite') db()->exec('COMMIT'); else db()->commit();
}
function db_rollback(): void {
    if (!db_in_tx()) return;
    $GLOBALS['__db_tx_depth'] = 0;
    try { if (cfg('db.driver') === 'sqlite') db()->exec('ROLLBACK'); else db()->rollBack(); }
    catch (Throwable $e) { /* already rolled back by the database */ }
}

/** Run $fn inside a write transaction; rolled back if it throws. */
function db_tx(callable $fn) {
    db_begin();
    try { $r = $fn(); db_commit(); return $r; }
    catch (Throwable $e) { db_rollback(); throw $e; }
}

/** Read a booking row for changing it: locked until the transaction ends (MySQL; SQLite holds the whole file). */
function lock_booking(int $id): ?array {
    return q1("SELECT * FROM bookings WHERE id = ?" . for_update(), [$id]);
}

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

/**
 * A plain error page for the guest pages in this folder (index.php, document.php):
 * a proper HTML page with the engine's look, a way back and the phone number,
 * instead of a bare line of text. Sends the status and stops.
 */
function guest_error_page(int $status, string $title, string $message): void {
    http_response_code($status);
    $e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES);
    $phone = (string) cfg('contact.phone');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<meta name="robots" content="noindex"><title>' . $e($title) . '</title>'
       . '<link rel="icon" href="favicon.ico" sizes="any"><link rel="stylesheet" href="assets/engine.css"></head><body>'
       . '<div class="wrap" style="max-width:620px;padding-block:60px"><div class="panel"><h1 style="font-size:1.4rem">' . $e($title) . '</h1>'
       . '<p>' . $e($message) . '</p><p><a class="btn btn--sm" href="index.php">Book a stay</a> '
       . '<a class="btn btn--plain btn--sm" href="tel:' . $e(preg_replace('/\s/', '', $phone)) . '">Call ' . $e($phone) . '</a></p>'
       . '</div></div></body></html>';
    exit;
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
            'ip'         => client_ip(),
            'created_at' => now(),
        ]);
    } catch (Throwable $e) { /* logging must never break a booking */ }
}

function now(): string { return date('Y-m-d H:i:s'); }

/**
 * The visitor's address. Behind a proxy or CDN every request arrives from the
 * proxy's address, so the rate limits would count all guests as one; when the
 * request comes from an address listed in config trusted_proxies, the guest's
 * own address is read from X-Forwarded-For instead (the right-most entry that is
 * not one of those proxies, so a guest cannot invent one).
 */
function client_ip(): ?string {
    $remote = $_SERVER['REMOTE_ADDR'] ?? null;
    $trusted = (array) cfg('trusted_proxies', []);
    if ($remote === null || !$trusted || !in_array($remote, $trusted, true)) return $remote;
    $hops = array_reverse(array_map('trim', explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))));
    foreach ($hops as $ip) {
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) && !in_array($ip, $trusted, true)) return $ip;
    }
    return $remote;
}

/* Address values are always text in this engine: no page reads a list from the
 * query string. A hand-made link such as ?check_in[]=x or ?ref[]=x would otherwise
 * crash the pages that trim() or match the value, so a list becomes blank and is
 * refused like a missing value. */
foreach ($_GET as $k => $v) {
    if (is_array($v)) $_GET[$k] = '';
}

// Don't advertise the PHP version to every visitor ("X-Powered-By: PHP/8.3.x").
if (PHP_SAPI !== 'cli' && !headers_sent()) header_remove('X-Powered-By');

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
