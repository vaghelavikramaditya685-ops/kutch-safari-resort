<?php
/* Chaos: nonsense into every admin form and URL parameter, against the THROWAWAY copy on :8090. */
$B = 'http://127.0.0.1:8090/';
$JAR = __DIR__ . '/chaos_cookies.txt'; @unlink($JAR);
function http($method, $path, $form = null, &$code = null, &$loc = null) {
    global $B, $JAR;
    $ch = curl_init($B . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_COOKIEJAR => $JAR,
        CURLOPT_COOKIEFILE => $JAR, CURLOPT_HEADER => true]);
    if ($form !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($form));
    $raw = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $loc = preg_match('/^Location:\s*(.+)$/mi', substr($raw, 0, $hs), $m) ? trim($m[1]) : null;
    return substr($raw, $hs);
}
function csrf($h) { return preg_match('/name="_csrf" value="([^"]+)"/', $h, $m) ? $m[1] : ''; }
function db() { static $d; return $d ??= new PDO('sqlite:' . __DIR__ . '/engine-copy/data/booking.sqlite'); }
function row($sql, $p = []) { $s = db()->prepare($sql); $s->execute($p); return $s->fetch(PDO::FETCH_ASSOC); }
function notice($h) {
    if (preg_match('/class="notice notice--(err|ok|info)"[^>]*>(.*?)<\/div>/s', $h, $m)) return $m[1] . ': ' . trim(preg_replace('/\s+/', ' ', strip_tags($m[2])));
    if (preg_match('/<b>(Fatal error|Warning|Deprecated|Notice)<\/b>:(.*?)<br/s', $h, $m)) return 'PHP ' . $m[1] . ':' . trim(strip_tags($m[2]));
    return '(no message)';
}
function rec($id, $field, $value, $result) { printf("%-4s %-30s %-26s %s\n", $id, $field, mb_substr(is_string($value) ? (strlen($value) > 30 ? substr($value, 0, 20) . '…(' . strlen($value) . ')' : $value) : json_encode($value), 0, 26), $result); }

// Sign in (SHA-256, like the page).
$h = http('GET', 'admin/login.php', null, $c);
http('POST', 'admin/login.php', ['_csrf' => csrf($h), 'user_sha256' => hash('sha256', 'manvir'), 'pass_sha256' => hash('sha256', '1234')], $c, $loc);
if (!str_contains((string) $loc, 'index.php')) exit("login failed\n");

// A pending booking and a confirmed one to work on (the demo bookings in the copy).
$pend = row("SELECT id FROM bookings WHERE ref = 'KSR-LL6CZZ'")['id'];
$conf = row("SELECT id FROM bookings WHERE ref = 'KSR-NZQNSB'")['id'];

echo "== Record payment / refund / note (booking.php)\n";
$post = function ($id, $form) { $h = http('GET', "admin/booking.php?id=$id", null, $c); return http('POST', "admin/booking.php?id=$id", ['_csrf' => csrf($h)] + $form, $c); };
$paid = fn($id) => row("SELECT amount_paid a FROM bookings WHERE id = ?", [$id])['a'];
foreach ([['A1', 'amount', 'abc'], ['A2', 'amount', '-100'], ['A3', 'amount', '0'], ['A4', 'amount', '1e12'], ['A5', 'amount', '9500.999']] as [$i, $f, $v]) {
    $before = $paid($conf); $r = $post($conf, ['action' => 'record_payment', 'amount' => $v, 'method' => 'cash', 'note' => '']);
    rec($i, "record_payment $f", $v, notice($r) . " | paid $before → " . $paid($conf));
}
$before = $paid($conf); $r = $post($conf, ['action' => 'record_payment', 'amount' => '1', 'method' => 'bitcoin', 'note' => '']);
rec('A6', 'record_payment method', 'bitcoin', notice($r) . " | paid $before → " . $paid($conf) . ' | method saved: ' . (row("SELECT method FROM payments WHERE booking_id = ? ORDER BY id DESC LIMIT 1", [$conf])['method'] ?? ''));
$r = $post($conf, ['action' => 'record_payment', 'amount' => '1', 'method' => 'cash', 'note' => str_repeat('n', 100000)]);
rec('A7', 'record_payment note', str_repeat('n', 100000), notice($r) . ' | note length saved: ' . strlen((string) (json_decode((string) row("SELECT raw_response r FROM payments WHERE booking_id = ? ORDER BY id DESC LIMIT 1", [$conf])['r'], true)['note'] ?? '')));
foreach ([['A8', 'amount', '-50'], ['A9', 'amount', 'abc']] as [$i, $f, $v]) {
    $before = $paid($conf); $r = $post($conf, ['action' => 'record_refund', 'amount' => $v, 'method' => 'cash', 'note' => '']);
    rec($i, "record_refund $f", $v, notice($r) . " | paid $before → " . $paid($conf));
}
$r = $post($conf, ['action' => 'note', 'special_requests' => str_repeat('x', 200000)]);
rec('A10', 'booking note', str_repeat('x', 200000), notice($r) . ' | saved length ' . strlen((string) row("SELECT special_requests s FROM bookings WHERE id = ?", [$conf])['s']));
$r = $post($conf, ['action' => 'cancel', 'reason' => str_repeat('r', 50000)]);
rec('A11', 'cancel reason', str_repeat('r', 50000), notice($r) . ' | status ' . row("SELECT status s FROM bookings WHERE id = ?", [$conf])['s'] . ', reason length ' . strlen((string) row("SELECT cancel_reason c FROM bookings WHERE id = ?", [$conf])['c']));

echo "\n== Change a booking (edit.php, Check price)\n";
$eid = row("SELECT id FROM bookings WHERE ref = 'KSR-6JQ9JK'")['id'];
$b = row("SELECT check_in, check_out FROM bookings WHERE id = ?", [$eid]);
$base = ['check_in' => $b['check_in'], 'check_out' => $b['check_out'], 'room[0][cottage]' => '1:1', 'room[0][guests]' => '2', 'do' => 'preview'];
$edit = function ($patch) use ($eid, $base) { $h = http('GET', "admin/edit.php?id=$eid", null, $c); $r = http('POST', "admin/edit.php?id=$eid", ['_csrf' => csrf($h)] + array_replace($base, $patch), $c);
    return notice($r) . (preg_match('/New total<\/strong><\/td><td class="num"><strong>([^<]+)/', $r, $m) ? " | NEW TOTAL {$m[1]}" : ''); };
rec('C1', 'guests', '0', $edit(['room[0][guests]' => '0']));
rec('C2', 'guests', '9', $edit(['room[0][guests]' => '9']));
rec('C3', 'guests', 'abc', $edit(['room[0][guests]' => 'abc']));
rec('C4', 'cottage', '999:999', $edit(['room[0][cottage]' => '999:999']));
rec('C5', 'cottage', 'abc', $edit(['room[0][cottage]' => 'abc']));
rec('C6', 'check_in', 'abc', $edit(['check_in' => 'abc']));
rec('C7', 'dates reversed', 'out < in', $edit(['check_in' => $b['check_out'], 'check_out' => $b['check_in']]));
rec('C8', 'dates', '3000-01-01', $edit(['check_in' => '3000-01-01', 'check_out' => '3000-01-03']));
$inn = row("SELECT id FROM addons WHERE code = 'transfer-innova'")['id'];
rec('C9', 'extra quantity', '-1', $edit(["addon[$inn]" => '-1']));
rec('C10', 'extra quantity', '1000000', $edit(["addon[$inn]" => '1000000']));
rec('C11', 'extra quantity', 'abc', $edit(["addon[$inn]" => 'abc']));

echo "\n== Special prices (rates.php, Check)\n";
$d1 = date('Y-m-d', strtotime('+70 days')); $d2 = date('Y-m-d', strtotime('+72 days'));
$rate = function ($patch) use ($d1, $d2) { $h = http('GET', 'admin/rates.php', null, $c); $r = http('POST', 'admin/rates.php', ['_csrf' => csrf($h)] + array_replace(['do' => 'check', 'from' => $d1, 'to' => $d2, 'plans' => [1], 'price' => '8000'], $patch), $c);
    return notice($r) . (str_contains($r, 'value="save"') ? ' | SAVE BUTTON SHOWN' : ''); };
rec('R1', 'price', 'abc', $rate(['price' => 'abc']));
rec('R2', 'price', '-5', $rate(['price' => '-5']));
rec('R3', 'price', '1e9', $rate(['price' => '1e9']));
rec('R4', 'from > to', "$d2 → $d1", $rate(['from' => $d2, 'to' => $d1]));
rec('R5', 'from', 'abc', $rate(['from' => 'abc']));
rec('R6', 'past dates', '2020-01-01', $rate(['from' => '2020-01-01', 'to' => '2020-01-03']));
rec('R7', 'plans[]', '99999', $rate(['plans' => [99999]]));
rec('R8', 'range', '500 nights', $rate(['to' => date('Y-m-d', strtotime("$d1 +499 days"))]));
rec('R9', 'price', '8000.123456', $rate(['price' => '8000.123456']));
$h = http('GET', 'admin/rates.php', null, $c); $r = http('POST', 'admin/rates.php', ['_csrf' => csrf($h), 'do' => 'save', 'from' => $d1, 'to' => $d2, 'plans' => [1], 'price' => '-5'], $c);
rec('R10', 'SAVE directly, price', '-5', notice($r) . ' | rows saved: ' . row("SELECT COUNT(*) n FROM rates WHERE price < 0")['n']);

echo "\n== Enquiries (enquiries.php)\n";
$eqid = (int) (row("SELECT id FROM enquiries ORDER BY id DESC LIMIT 1")['id'] ?? 0);
if ($eqid) {
    $h = http('GET', 'admin/enquiries.php', null, $c); http('POST', 'admin/enquiries.php', ['_csrf' => csrf($h), 'id' => $eqid, 'status' => 'hacked', 'staff_note' => 'x'], $c);
    rec('N1', 'status', 'hacked', 'saved status: ' . row("SELECT status FROM enquiries WHERE id = ?", [$eqid])['status']);
    $h = http('GET', 'admin/enquiries.php', null, $c); http('POST', 'admin/enquiries.php', ['_csrf' => csrf($h), 'id' => 99999, 'status' => 'closed', 'staff_note' => 'x'], $c, $loc);
    rec('N2', 'id', '99999', "HTTP $c");
    $h = http('GET', 'admin/enquiries.php', null, $c); http('POST', 'admin/enquiries.php', ['_csrf' => csrf($h), 'id' => $eqid, 'status' => 'contacted', 'staff_note' => str_repeat('s', 100000)], $c);
    rec('N3', 'staff_note', str_repeat('s', 100000), 'saved length ' . strlen((string) row("SELECT staff_note FROM enquiries WHERE id = ?", [$eqid])['staff_note']));
} else echo "   (no enquiry in the copy to test)\n";

echo "\n== Page addresses\n";
foreach (['admin/booking.php?id=abc', 'admin/booking.php?id=-1', 'admin/edit.php?id=abc', 'admin/calendar.php?days=abc&start=abc&property=abc', 'admin/calendar.php?start=3000-99-99',
          'admin/index.php?q=' . str_repeat('%27', 300), 'admin/index.php?from=abc&to=zzz&status=hacked', 'admin/export.php?status=hacked&from=abc'] as $i => $u) {
    $r = http('GET', $u, null, $c); rec('U' . ($i + 1), 'URL', $u, "HTTP $c " . notice($r) . (preg_match('/(Fatal error|Uncaught|Warning)/', $r) ? ' | PHP ERROR SHOWN' : ''));
}
