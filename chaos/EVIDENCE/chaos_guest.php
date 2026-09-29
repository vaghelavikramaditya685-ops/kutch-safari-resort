<?php
/* Chaos: nonsense into every guest-facing input, against the THROWAWAY copy on :8090. */
$B = 'http://127.0.0.1:8090/';
function call($method, $path, $data = null, &$code = null) {
    global $B;
    $ch = curl_init($B . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Origin: http://localhost:3000']]);
    if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($data) ? $data : json_encode($data));
    $out = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $j = json_decode((string) $out, true);
    return is_array($j) ? $j : ['_raw' => substr((string) $out, 0, 160)];
}
function db() { static $d; return $d ??= new PDO('sqlite:' . __DIR__ . '/engine-copy/data/booking.sqlite'); }
function row($sql, $p = []) { $s = db()->prepare($sql); $s->execute($p); return $s->fetch(PDO::FETCH_ASSOC); }
$rows = [];
function rec($id, $field, $value, $res, $code, $note = '') {
    global $rows;
    $ok = !empty($res['ok']);
    $msg = $res['error'] ?? ($res['message'] ?? ($res['_raw'] ?? ''));
    $v = is_string($value) ? (strlen($value) > 40 ? substr($value, 0, 30) . '…(' . strlen($value) . ' chars)' : $value) : json_encode($value);
    $rows[] = [$id, $field, $v, $code, $ok ? 'ACCEPTED' : 'refused', mb_substr((string) $msg, 0, 90), $note];
    printf("%-4s %-26s %-34s HTTP %s %-8s %s %s\n", $id, $field, mb_substr($v, 0, 34), $code, $ok ? 'ACCEPTED' : 'refused', mb_substr((string) $msg, 0, 80), $note);
}

$P = 'kutch-safari-resort';
$in = date('Y-m-d', strtotime('+50 days')); $out = date('Y-m-d', strtotime('+52 days'));
$K = ['room_type_id' => 1, 'rate_plan_id' => 1, 'rooms' => 1];
$D = ['room_type_id' => 2, 'rate_plan_id' => 4, 'rooms' => 1];
$inn = (int) row("SELECT id FROM addons WHERE code='transfer-innova'")['id'];
$gala = (int) row("SELECT id FROM addons WHERE code='gala-dinner'")['id'];
$wrcAddon = (int) (row("SELECT a.id FROM addons a JOIN properties p ON p.id=a.property_id WHERE p.code='white-rann-camp' LIMIT 1")['id'] ?? 0);
$wrcRoom = row("SELECT rt.id rt, rp.id rp FROM room_types rt JOIN rate_plans rp ON rp.room_type_id=rt.id JOIN properties p ON p.id=rt.property_id WHERE p.code='white-rann-camp' LIMIT 1");

echo "== availability.php\n";
$av = fn($q) => call('GET', 'api/availability.php?' . http_build_query($q + ['property' => $P, 'check_in' => $in, 'check_out' => $out, 'rooms' => 1, 'occupancy' => '2']), null, $c);
foreach ([
    ['V1', 'check_in', ['check_in' => 'not-a-date']],
    ['V2', 'check_in', ['check_in' => '2026-13-45']],
    ['V3', 'check_in (past)', ['check_in' => '1800-01-01', 'check_out' => '1800-01-03']],
    ['V4', 'check_in (far future)', ['check_in' => '3000-01-01', 'check_out' => '3000-01-03']],
    ['V5', 'stay length', ['check_out' => date('Y-m-d', strtotime("$in +60 days"))]],
    ['V6', 'rooms', ['rooms' => -3]],
    ['V7', 'rooms', ['rooms' => 'abc']],
    ['V8', 'rooms', ['rooms' => 999]],
    ['V9', 'occupancy', ['occupancy' => '0']],
    ['V10', 'occupancy', ['occupancy' => '9']],
    ['V11', 'occupancy', ['occupancy' => 'abc,😀']],
    ['V12', 'property', ['property' => '<script>alert(1)</script>']],
    ['V13', 'property (switched off)', ['property' => 'white-rann-camp']],
] as [$id, $f, $q]) { $r = $av($q); global $c; $extra = !empty($r['ok']) ? ('rooms_wanted=' . ($r['rooms_wanted'] ?? '?') . ' occ=' . json_encode($r['occupancy'] ?? null) . ' results=' . count($r['results'] ?? [])) : ''; rec($id, $f, $q[array_key_first($q)], $r, $c, $extra); }

echo "\n== quote.php / book.php (cart)\n";
$cart = ['property' => $P, 'check_in' => $in, 'check_out' => $out, 'occupancy' => [2], 'payment_mode' => 'full', 'rooms' => [$K], 'addons' => []];
$qt = function ($id, $f, $v, $patch) use ($cart) { $r = call('POST', 'api/quote.php', array_replace($cart, $patch), $c); rec($id, $f, $v, $r, $c, !empty($r['ok']) ? 'total ' . ($r['total'] ?? '?') : ''); return $r; };
$qt('Q1', 'rooms[].room_type_id', 'abc', ['rooms' => [['room_type_id' => 'abc', 'rate_plan_id' => 1, 'rooms' => 1]]]);
$qt('Q2', 'rate plan of another cottage', 'Kutchi + Deluxe plan', ['rooms' => [['room_type_id' => 1, 'rate_plan_id' => 4, 'rooms' => 1]]]);
$qt('Q3', 'room from other property', 'WRC tent', ['rooms' => [['room_type_id' => (int) $wrcRoom['rt'], 'rate_plan_id' => (int) $wrcRoom['rp'], 'rooms' => 1]]]);
$qt('Q4', 'rooms[].rooms', -3, ['rooms' => [['room_type_id' => 1, 'rate_plan_id' => 1, 'rooms' => -3]]]);
$qt('Q5', 'rooms[].rooms', 1000, ['rooms' => [['room_type_id' => 1, 'rate_plan_id' => 1, 'rooms' => 1000]]]);
$qt('Q6', 'occupancy', [-2], ['occupancy' => [-2]]);
$qt('Q7', 'occupancy', [7], ['occupancy' => [7]]);
$qt('Q8', 'occupancy', ['abc'], ['occupancy' => ['abc']]);
$qt('Q9', 'addons[].quantity', -5, ['addons' => [['addon_id' => $inn, 'quantity' => -5]]]);
$qt('Q10', 'addons[].quantity', 0, ['addons' => [['addon_id' => $inn, 'quantity' => 0]]]);
$qt('Q11', 'addons[].quantity', 1000000, ['addons' => [['addon_id' => $inn, 'quantity' => 1000000]]]);
$qt('Q12', 'addons[].quantity', 'abc', ['addons' => [['addon_id' => $inn, 'quantity' => 'abc']]]);
$qt('Q13', 'addons[].addon_id', 99999, ['addons' => [['addon_id' => 99999, 'quantity' => 1]]]);
$qt('Q14', 'addon from other property', 'WRC addon', ['addons' => [['addon_id' => $wrcAddon, 'quantity' => 1]]]);
$qt('Q15', 'gala dinner qty (min 10)', 9, ['addons' => [['addon_id' => $gala, 'quantity' => 9]]]);
$qt('Q16', 'payment_mode', 'free', ['payment_mode' => 'free']);
$qt('Q17', 'payment_mode (switched off)', 'hotel', ['payment_mode' => 'hotel']);
$qt('Q18', 'coupon', "' OR 1=1 --", ['coupon' => "' OR 1=1 --"]);
$qt('Q19', 'dates reversed', "$out → $in", ['check_in' => $out, 'check_out' => $in]);
$qt('Q20', 'check_in', 'not-a-date', ['check_in' => 'not-a-date']);
$qt('Q21', 'rooms', 'not a list', ['rooms' => 'lots']);
$qt('Q22', '6 rooms online (max 5)', 6, ['rooms' => array_fill(0, 6, $K), 'occupancy' => array_fill(0, 6, 2)]);
$r = call('POST', 'api/quote.php', '{not json', $c); rec('Q23', 'body', 'broken JSON', $r, $c);

echo "\n== book.php guest details (sample mode ON: name/phone optional)\n";
$bk = function ($id, $f, $v, $guest) use ($cart) {
    $r = call('POST', 'api/book.php', $cart + ['guest' => $guest], $c);
    $saved = '';
    if (!empty($r['ok'])) { $b = row("SELECT guest_name, guest_phone, guest_email, arrival_time, special_requests, guest_city FROM bookings WHERE id = ?", [$r['booking_id']]);
        foreach ($b as $k => $val) if ($val !== null && $val !== '') $saved .= "$k=" . (strlen((string) $val) > 30 ? substr((string) $val, 0, 20) . '…(' . strlen((string) $val) . ')' : $val) . ' '; }
    rec($id, $f, $v, $r, $c, $saved ? "SAVED: $saved" : '');
};
$bk('G1', 'guest name', str_repeat('A', 5000), ['name' => str_repeat('A', 5000), 'phone' => '9000000201']);
$bk('G2', 'guest name', '<script>alert(1)</script>', ['name' => '<script>alert(1)</script>', 'phone' => '9000000202']);
$bk('G3', 'guest name', 'Émile 😀 राम', ['name' => 'Émile 😀 राम', 'phone' => '9000000203']);
$bk('G4', 'phone', 'abc', ['name' => 'Chaos Phone', 'phone' => 'abc']);
$bk('G5', 'phone', '12', ['name' => 'Chaos Phone2', 'phone' => '12']);
$bk('G6', 'phone', str_repeat('9', 60), ['name' => 'Chaos Phone3', 'phone' => str_repeat('9', 60)]);
$bk('G7', 'email', 'no-at-sign', ['name' => 'Chaos Mail', 'phone' => '9000000207', 'email' => 'no-at-sign']);
$bk('G8', 'email', 'a@b', ['name' => 'Chaos Mail2', 'phone' => '9000000208', 'email' => 'a@b']);
$bk('G9', 'arrival_time', '25:99 PM', ['name' => 'Chaos Time', 'phone' => '9000000209', 'arrival_time' => '25:99 PM']);
$bk('G10', 'arrival_time', str_repeat('x', 3000), ['name' => 'Chaos Time2', 'phone' => '9000000210', 'arrival_time' => str_repeat('x', 3000)]);
$bk('G11', 'special_requests', str_repeat('note ', 40000), ['name' => 'Chaos Notes', 'phone' => '9000000211', 'special_requests' => str_repeat('note ', 40000)]);
$bk('G12', 'city', str_repeat('C', 1000), ['name' => 'Chaos City', 'phone' => '9000000212', 'city' => str_repeat('C', 1000)]);
$bk('G13', 'guest', 'a string, not an object', 'just text');

echo "\n== booking-lookup.php / payment-test.php / payment-create.php\n";
$r = call('GET', 'api/booking-lookup.php?ref=' . urlencode(str_repeat('Z', 500)) . '&contact=x', null, $c); rec('L1', 'ref', str_repeat('Z', 500), $r, $c);
$r = call('GET', 'api/booking-lookup.php?ref=&contact=', null, $c); rec('L2', 'ref + contact', '(empty)', $r, $c);
$r = call('POST', 'api/payment-test.php', ['booking_id' => 'abc', 'manage_token' => 'x'], $c); rec('P1', 'booking_id', 'abc', $r, $c);
$r = call('POST', 'api/payment-test.php', ['booking_id' => -1, 'manage_token' => ''], $c); rec('P2', 'booking_id', -1, $r, $c);
$r = call('POST', 'api/payment-create.php', ['booking_id' => 1e12, 'method' => 'bitcoin', 'manage_token' => 'x'], $c); rec('P3', 'method', 'bitcoin', $r, $c);

echo "\n== enquiry.php\n";
$eq = function ($id, $f, $v, $data) { $r = call('POST', 'api/enquiry.php', $data + ['name' => 'Chaos Enq', 'phone' => '9000000300', 'message' => 'hi', 'property' => 'kutch-safari-resort'], $c);
    $saved = !empty($r['id']) ? json_encode(array_filter(row("SELECT name, phone, email, check_in, check_out, guests, message FROM enquiries WHERE id = ?", [$r['id']]), fn($x) => $x !== null && $x !== '')) : '';
    rec($id, $f, $v, $r, $c, $saved ? 'SAVED: ' . mb_substr($saved, 0, 150) : ''); };
$eq('E1', 'name', '(empty)', ['name' => '']);
$eq('E2', 'phone', 'abc', ['phone' => 'abc']);
$eq('E3', 'email', 'no-at', ['email' => 'no-at']);
$eq('E4', 'guests', -3, ['guests' => -3]);
$eq('E5', 'guests', 'lots', ['guests' => 'lots']);
$eq('E6', 'check_in', 'not-a-date', ['check_in' => 'not-a-date']);
$eq('E7', 'check_in/out reversed', "$out → $in", ['check_in' => $out, 'check_out' => $in]);
$eq('E8', 'message', str_repeat('m', 100000), ['message' => str_repeat('m', 100000)]);
$eq('E9', 'property', 'nowhere', ['property' => 'nowhere']);
$eq('E10', 'website (bot trap)', 'filled', ['website' => 'http://spam']);

file_put_contents(__DIR__ . '/chaos_guest.json', json_encode($rows, JSON_UNESCAPED_UNICODE));
