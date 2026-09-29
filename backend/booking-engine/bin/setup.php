<?php
/* ===========================================================================
 *  One-time setup. Run from the command line:
 *
 *      php bin/setup.php                       create tables + starting data
 *      php bin/setup.php --admin "username-or-email" "Your Name" "password"
 *
 *  Works against MySQL (cPanel) and SQLite (local testing). The schema file is
 *  written for MySQL; when the driver is sqlite it is translated on the way in,
 *  so there is only ever one schema to maintain.
 * ======================================================================== */

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/inventory.php';

$args = $argv ?? [];
$driver = cfg('db.driver');

echo "Database driver: $driver\n";

/* --- Translate the MySQL schema for SQLite ----------------------------- */
function to_sqlite(string $sql): string {
    $sql = preg_replace('/\bINT\s+AUTO_INCREMENT\s+PRIMARY\s+KEY\b/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
    $sql = preg_replace('/\)\s*ENGINE=\w+\s+DEFAULT\s+CHARSET=\w+/i', ')', $sql);
    $sql = preg_replace('/\bDECIMAL\(\d+,\d+\)/i', 'REAL', $sql);
    $sql = preg_replace('/\bTINYINT\(\d+\)/i', 'INTEGER', $sql);
    $sql = preg_replace('/\bINT\(\d+\)/i', 'INTEGER', $sql);
    $sql = preg_replace('/\bVARCHAR\(\d+\)/i', 'TEXT', $sql);
    $sql = preg_replace('/\bDATETIME\b/i', 'TEXT', $sql);
    $sql = preg_replace('/\bDATE\b(?!_)/i', 'TEXT', $sql);
    // UNIQUE KEY name (a,b)  ->  UNIQUE (a,b)     KEY name (a,b) -> dropped
    $sql = preg_replace('/UNIQUE\s+KEY\s+\w+\s*\(/i', 'UNIQUE (', $sql);
    $sql = preg_replace('/,\s*\n\s*KEY\s+\w+\s*\([^)]*\)/i', '', $sql);
    $sql = preg_replace('/^\s*SET\s+(NAMES|time_zone).*$/im', '', $sql);
    return $sql;
}

/**
 * Split a script into statements.
 *
 * One pass that understands SQL string literals ('' escapes included), double
 * quotes, and both comment styles — because an apostrophe inside a comment
 * ("the room type's total") would otherwise look like the start of a string
 * and swallow the rest of the file.
 */
function split_statements(string $sql): array {
    $out = [];
    $buf = '';
    $len = strlen($sql);
    $i = 0;

    while ($i < $len) {
        $ch   = $sql[$i];
        $next = $i + 1 < $len ? $sql[$i + 1] : '';

        // -- line comment
        if ($ch === '-' && $next === '-') {
            while ($i < $len && $sql[$i] !== "\n") $i++;
            continue;
        }
        // /* block comment */
        if ($ch === '/' && $next === '*') {
            $i += 2;
            while ($i < $len && !($sql[$i] === '*' && ($sql[$i + 1] ?? '') === '/')) $i++;
            $i += 2;
            continue;
        }
        // string literal — copied through verbatim
        if ($ch === "'" || $ch === '"') {
            $quote = $ch;
            $buf .= $ch;
            $i++;
            while ($i < $len) {
                $c = $sql[$i];
                if ($c === '\\' && $quote === '"') { $buf .= $c . ($sql[$i + 1] ?? ''); $i += 2; continue; }
                if ($c === $quote) {
                    // '' inside a single-quoted string is an escaped quote
                    if ($quote === "'" && ($sql[$i + 1] ?? '') === "'") { $buf .= "''"; $i += 2; continue; }
                    $buf .= $c; $i++; break;
                }
                $buf .= $c; $i++;
            }
            continue;
        }
        if ($ch === ';') {
            if (trim($buf) !== '') $out[] = trim($buf);
            $buf = '';
            $i++;
            continue;
        }
        $buf .= $ch;
        $i++;
    }
    if (trim($buf) !== '') $out[] = trim($buf);
    return $out;
}

function run_script(string $file): int {
    $sql = file_get_contents($file);
    if (cfg('db.driver') === 'sqlite') $sql = to_sqlite($sql);
    $n = 0;
    foreach (split_statements($sql) as $stmt) {
        try { db()->exec($stmt); $n++; }
        catch (Throwable $e) {
            echo "  ! " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 70) . "\n    " . $e->getMessage() . "\n";
        }
    }
    return $n;
}

/* --- Admin user only --------------------------------------------------- */
if (in_array('--admin', $args, true)) {
    $i = array_search('--admin', $args, true);
    $email = $args[$i + 1] ?? null;
    $name  = $args[$i + 2] ?? 'Owner';
    $pass  = $args[$i + 3] ?? null;
    if (!$email || !$pass) { echo "Usage: php bin/setup.php --admin USERNAME NAME PASSWORD (username may be an email)\n"; exit(1); }

    $existing = q1("SELECT id FROM admin_users WHERE email = ?", [$email]);
    // The sign-in page sends SHA-256 of the password, so store bcrypt of that (admin/login.php).
    $hash = password_hash(hash('sha256', $pass), PASSWORD_DEFAULT);
    if ($existing) {
        update('admin_users', (int) $existing['id'], ['password_hash' => $hash, 'name' => $name, 'active' => 1]);
        echo "Updated the password for $email\n";
    } else {
        insert('admin_users', ['email' => $email, 'name' => $name, 'password_hash' => $hash,
                               'role' => 'owner', 'active' => 1, 'created_at' => now()]);
        echo "Created admin user $email\n";
    }
    exit(0);
}

/* --- Full setup -------------------------------------------------------- */
echo "Creating tables ... ";
echo run_script(__DIR__ . '/../schema.sql') . " statements\n";

echo "Loading starting data ... ";
echo run_script(__DIR__ . '/../seed.sql') . " statements\n";

/* --- Peak-date rates for the camp -------------------------------------
 * Only the nights that differ from the base price are stored. Edit the
 * ranges here and re-run; existing rows for those dates are replaced.
 */
$peaks = [
    ['2026-12-19', '2027-01-04', 'Christmas and New Year'],
    ['2027-01-13', '2027-01-15', 'Uttarayan'],
    ['2027-01-21', '2027-01-23', 'Full moon'],
];
$peak_prices = [
    7 => 8450,   // rate_plan 7 = Deluxe Air-Cool tent
    8 => 7499,   // rate_plan 8 = Non-AC Swiss tent
];

$written = 0;
foreach ($peaks as [$from, $to, $label]) {
    $d = new DateTime($from);
    $end = new DateTime($to);
    while ($d <= $end) {
        foreach ($peak_prices as $plan_id => $price) {
            exec_sql("DELETE FROM rates WHERE rate_plan_id = ? AND stay_date = ?", [$plan_id, $d->format('Y-m-d')]);
            insert('rates', ['rate_plan_id' => $plan_id, 'stay_date' => $d->format('Y-m-d'),
                             'price' => $price, 'min_stay' => 1, 'closed' => 0]);
            $written++;
        }
        $d->modify('+1 day');
    }
}
echo "Peak-date rates written: $written\n";

/* --- A first admin so you can log in ----------------------------------- */
if (!qval("SELECT id FROM admin_users LIMIT 1")) {
    $pass = bin2hex(random_bytes(4));
    insert('admin_users', [
        'email' => 'admin@kutchsafariresort.com', 'name' => 'Reservations',
        'password_hash' => password_hash(hash('sha256', $pass), PASSWORD_DEFAULT),   // see admin/login.php
        'role' => 'owner', 'active' => 1, 'created_at' => now(),
    ]);
    echo "\n  Admin login created\n";
    echo "    email:    admin@kutchsafariresort.com\n";
    echo "    password: $pass\n";
    echo "  Change it with:  php bin/setup.php --admin EMAIL NAME NEWPASSWORD\n";
}

$counts = [];
foreach (['properties', 'room_types', 'rate_plans', 'rates', 'addons', 'packages'] as $t) {
    $counts[] = "$t=" . qval("SELECT COUNT(*) FROM $t", [], 0);
}
echo "\nReady. " . implode('  ', $counts) . "\n";
