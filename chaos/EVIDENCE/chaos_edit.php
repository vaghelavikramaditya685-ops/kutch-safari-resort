<?php
$B = 'http://127.0.0.1:8090/'; $JAR = __DIR__ . '/chaos_cookies.txt';
function http($m, $p, $f = null) { global $B, $JAR; $ch = curl_init($B . $p); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $m, CURLOPT_COOKIEJAR => $JAR, CURLOPT_COOKIEFILE => $JAR]); if ($f !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($f)); return curl_exec($ch); }
function csrf($h) { return preg_match('/name="_csrf" value="([^"]+)"/', $h, $m) ? $m[1] : ''; }
@unlink($JAR); $h = http('GET', 'admin/login.php'); http('POST', 'admin/login.php', ['_csrf' => csrf($h), 'user_sha256' => hash('sha256', 'manvir'), 'pass_sha256' => hash('sha256', '1234')]);
$d = new PDO('sqlite:' . __DIR__ . '/engine-copy/data/booking.sqlite');
$eid = $d->query("SELECT id FROM bookings WHERE ref='KSR-LL6CZZ'")->fetchColumn(); $b = $d->query("SELECT check_in, check_out FROM bookings WHERE id=$eid")->fetch(PDO::FETCH_ASSOC);
$inn = $d->query("SELECT id FROM addons WHERE code='transfer-innova'")->fetchColumn();
$base = ['check_in' => $b['check_in'], 'check_out' => $b['check_out'], 'room[0][cottage]' => '1:1', 'room[0][guests]' => '2', 'do' => 'preview'];
foreach ([['C1', ['room[0][guests]' => '0']], ['C2', ['room[0][guests]' => '9']], ['C3', ['room[0][guests]' => 'abc']], ['C4', ['room[0][cottage]' => '999:999']], ['C5', ['room[0][cottage]' => 'abc']],
          ['C6', ['check_in' => 'abc']], ['C7', ['check_in' => $b['check_out'], 'check_out' => $b['check_in']]], ['C8', ['check_in' => '3000-01-01', 'check_out' => '3000-01-03']],
          ['C9', ["addon[$inn]" => '-1']], ['C10', ["addon[$inn]" => '1000000']], ['C11', ["addon[$inn]" => 'abc']]] as [$id, $patch]) {
    $h = http('GET', "admin/edit.php?id=$eid"); $r = http('POST', "admin/edit.php?id=$eid", ['_csrf' => csrf($h)] + array_replace($base, $patch));
    $err = preg_match('/notice--err[^>]*>(.*?)<\/div>/s', $r, $m) ? 'REFUSED: ' . trim(strip_tags($m[1])) : '';
    $tot = preg_match('/<strong>New total<\/strong><\/td><td class="num"><strong>([^<]+)/', $r, $m) ? 'ACCEPTED, new total ' . $m[1] : '';
    $lines = preg_match_all('/<tr class="chg--(add|rem|chg)"><td>(.*?)<\/td><td class="num">(.*?)<\/td>/s', $r, $mm, PREG_SET_ORDER) ? implode('; ', array_map(fn($x) => $x[2] . ' ' . html_entity_decode($x[3]), $mm)) : '';
    printf("%-4s %s %s %s\n", $id, $err ?: $tot ?: '(nothing?)', $lines ? '| ' . mb_substr($lines, 0, 160) : '', preg_match('/(Warning|Fatal)/', $r) ? '| PHP ERROR' : '');
}
