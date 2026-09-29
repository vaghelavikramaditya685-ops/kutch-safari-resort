<?php
/* ===========================================================================
 *  The booking receipt as a PDF, shown in the browser (opens in a new tab).
 *    receipt.php?ref=KSR-XXXXXX&token=<manage_token>   — the guest
 *    receipt.php?ref=KSR-XXXXXX                        — signed-in staff
 *  The token is the booking's private manage_token, so a reference alone
 *  never reveals someone else's booking.
 * ======================================================================== */
require_once __DIR__ . '/lib/documents.php';

$ref   = strtoupper(trim((string) ($_GET['ref'] ?? '')));
$token = (string) ($_GET['token'] ?? '');
$row   = $ref !== '' ? q1("SELECT id, manage_token FROM bookings WHERE ref = ?", [$ref]) : null;

$allowed = $row && $token !== '' && hash_equals((string) $row['manage_token'], $token);
if ($row && !$allowed) {
    // Staff signed in to the admin panel may open any receipt.
    require_once __DIR__ . '/admin/_auth.php';
    $allowed = (bool) current_user();
}
if (!$allowed) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit("We could not find that booking. Use 'Already booked? Check status' on the booking page.");
}

$pdf = receipt_pdf(get_booking((int) $row['id']));
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $ref . '-receipt.pdf"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex');
echo $pdf;
