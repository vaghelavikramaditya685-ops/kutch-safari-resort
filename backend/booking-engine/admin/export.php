<?php
/* Download the filtered booking list as a spreadsheet. */
require_once __DIR__ . '/_auth.php';
$user = require_login();

$where = ['1=1']; $params = [];
if (!empty($_GET['status']))   { $where[] = 'b.status = ?';      $params[] = $_GET['status']; }
if (!empty($_GET['property'])) { $where[] = 'b.property_id = ?'; $params[] = (int) $_GET['property']; }
if (!empty($_GET['from']))     { $where[] = 'b.check_out >= ?';  $params[] = $_GET['from']; }
if (!empty($_GET['to']))       { $where[] = 'b.check_in <= ?';   $params[] = $_GET['to']; }
if (($search = trim($_GET['q'] ?? '')) !== '') {   // same search as the bookings list
    $where[] = '(b.ref LIKE ? OR b.guest_name LIKE ? OR b.guest_phone LIKE ? OR b.guest_email LIKE ?)';
    array_push($params, "%$search%", "%$search%", "%$search%", "%$search%");
}

$rows = q('SELECT b.*, p.name AS property_name FROM bookings b
             JOIN properties p ON p.id = b.property_id
            WHERE ' . implode(' AND ', $where) . ' ORDER BY b.check_in', $params);

// Text typed by guests could start with = + - @, which Excel would run as a formula
// when the file is opened. A leading apostrophe makes it plain text.
function csv_safe($v) {
    $v = (string) $v;
    return $v !== '' && strpbrk($v[0], "=+-@	") !== false ? "'" . $v : $v;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="bookings-' . date('Y-m-d') . '.csv"');
$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF");                 // so Excel reads the rupee sign correctly
fputcsv($out, ['Reference','Property','Status','Check in','Check out','Nights','Guest','Phone','Email',
               'City','Adults','Children','Rooms','Total','Paid','Balance','Payment mode','Booked on','Stayflexi']);
foreach ($rows as $b) {
    $rooms = q("SELECT room_type_name, rooms FROM booking_rooms WHERE booking_id = ?", [$b['id']]);
    $desc = implode('; ', array_map(fn($r) => $r['rooms'] . '× ' . $r['room_type_name'], $rooms));
    fputcsv($out, [
        $b['ref'], $b['property_name'], $b['status'], $b['check_in'], $b['check_out'], $b['nights'],
        csv_safe($b['guest_name']), csv_safe($b['guest_phone']), csv_safe($b['guest_email']), csv_safe($b['guest_city']),
        $b['adults'], $b['children'], $desc,
        $b['total'], $b['amount_paid'], round((float) $b['total'] - (float) $b['amount_paid'], 2),
        $b['payment_mode'], $b['created_at'],
        $b['sf_synced_at'] ? 'synced' : ($b['sf_sync_error'] ? 'FAILED' : 'not sent'),
    ]);
}
fclose($out);
audit('export_csv', null, null, ['rows' => count($rows)], $user['name']);
