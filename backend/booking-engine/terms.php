<?php
/* The terms and conditions as a PDF, shown in the browser.  terms.php?property=kutch-safari-resort */
require_once __DIR__ . '/lib/documents.php';

$p = q1("SELECT * FROM properties WHERE code = ? AND active = 1", [(string) ($_GET['property'] ?? 'kutch-safari-resort')]);
if (!$p) { http_response_code(404); header('Content-Type: text/plain'); exit('Unknown property.'); }

$pdf = terms_pdf($p);
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $p['code'] . '-terms.pdf"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: public, max-age=300');
echo $pdf;
