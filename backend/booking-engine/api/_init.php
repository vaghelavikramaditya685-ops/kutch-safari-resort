<?php
/* ===========================================================================
 *  Shared setup for every JSON endpoint: CORS, input parsing, error handling.
 * ======================================================================== */

// A stray PHP notice printed before the JSON would corrupt every response,
// so errors are logged and never echoed.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/db.php';

/* --- Only the sites we know about may call this from a browser ---------- */
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && in_array($origin, cfg('allowed_origins', []), true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

/** Send JSON and stop. */
function json_out($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_fail(string $message, int $status = 400): void {
    json_out(['ok' => false, 'error' => $message], $status);
}

/** Request body, whether it arrives as JSON or a form post. */
function input(): array {
    static $data = null;
    if ($data !== null) return $data;
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw ?: '', true);
    // Something that claims to be JSON but is not: say so, instead of a confusing
    // "unknown property" further down.
    if ($raw !== '' && $raw !== false && $decoded === null && str_contains((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'json')) {
        $data = [];
        json_fail('We could not read that request. Please reload the page and try again.');
    }
    $data = is_array($decoded) ? $decoded : array_merge($_GET, $_POST);
    return $data;
}

function in_str(string $key, string $default = ''): string {
    $v = input()[$key] ?? $default;
    return is_scalar($v) ? trim((string) $v) : $default;
}

function in_int(string $key, int $default = 0): int {
    $v = input()[$key] ?? $default;
    return is_numeric($v) ? (int) $v : $default;
}

function in_arr(string $key): array {
    $v = input()[$key] ?? [];
    return is_array($v) ? $v : [];
}

function require_post(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_fail('POST required.', 405);
}

/**
 * A crude per-IP rate limit, stored in the audit table. Enough to stop someone
 * hammering the availability endpoint or brute-forcing a booking reference.
 */
function rate_limit(string $bucket, int $max, int $per_seconds): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $since = date('Y-m-d H:i:s', time() - $per_seconds);
    $count = (int) qval("SELECT COUNT(*) FROM audit_log WHERE action = ? AND ip = ? AND created_at > ?",
                        ['rl_' . $bucket, $ip, $since], 0);
    if ($count >= $max) json_fail('Too many attempts. Please wait a minute and try again.', 429);
    audit('rl_' . $bucket, null, null, null);
}

/* --- Never leak a stack trace to a guest ------------------------------- */
set_exception_handler(function (Throwable $e) {
    audit('api_exception', null, null, ['msg' => $e->getMessage(), 'where' => $e->getFile() . ':' . $e->getLine()]);
    if (cfg('debug')) {
        json_out(['ok' => false, 'error' => $e->getMessage(),
                  'where' => $e->getFile() . ':' . $e->getLine()], 500);
    }
    json_fail('Something went wrong at our end. Please try again or call us.', 500);
});
