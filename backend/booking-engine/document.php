<?php
/* ===========================================================================
 *  Shows a receipt or the terms in a browser tab — never as a download.
 *    document.php?doc=receipt&ref=KSR-XXXXXX&token=<manage_token>
 *    document.php?doc=terms&property=kutch-safari-resort
 *  The PDF (receipt.php / terms.php) is drawn on the page with PDF.js, so it
 *  opens the same way in every browser, with Print and Save as PDF buttons.
 * ======================================================================== */
require_once __DIR__ . '/lib/db.php';

$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES);
$doc = ($_GET['doc'] ?? '') === 'terms' ? 'terms' : 'receipt';

if ($doc === 'terms') {
    $code = (string) ($_GET['property'] ?? 'kutch-safari-resort');
    $p = q1("SELECT * FROM properties WHERE code = ? AND active = 1", [$code]);
    if (!$p) { http_response_code(404); exit('Unknown property.'); }
    $src = 'terms.php?' . http_build_query(['property' => $code]);
    $title = 'Terms and conditions — ' . $p['name'];
    $file = $code . '-terms.pdf';
} else {
    $ref = strtoupper(trim((string) ($_GET['ref'] ?? '')));
    $src = 'receipt.php?' . http_build_query(['ref' => $ref, 'token' => (string) ($_GET['token'] ?? '')]);
    $title = 'Booking receipt — ' . $ref;
    $file = $ref . '-receipt.pdf';
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= $e($title) ?></title>
<link rel="icon" href="favicon.ico" sizes="any">
<link rel="icon" type="image/svg+xml" href="assets/icon.svg">
<style>
  :root { --clay: #B85C2E; --ink: #241F1B; --line: #E4DCD1; }
  * { box-sizing: border-box; }
  body { margin: 0; background: #E9E4DC; font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; color: var(--ink); }
  .bar { position: sticky; top: 0; z-index: 2; display: flex; align-items: center; justify-content: space-between; gap: 12px;
         flex-wrap: wrap; padding: 10px 16px; background: #fff; border-bottom: 1px solid var(--line); }
  .bar b { font-size: .92rem; }
  .bar div { display: flex; gap: 8px; }
  .bar a, .bar button { font: 600 .72rem/1 Montserrat, sans-serif; letter-spacing: .12em; text-transform: uppercase;
         padding: 10px 14px; border: 1px solid var(--clay); border-radius: 4px; background: #fff; color: var(--clay);
         text-decoration: none; cursor: pointer; }
  .bar button { background: var(--clay); color: #fff; }
  #pages { padding: 20px 12px 40px; }
  #pages canvas { display: block; margin: 0 auto 16px; max-width: 100%; height: auto; background: #fff;
         box-shadow: 0 8px 28px -12px rgba(36,31,27,.25); }
  .msg { max-width: 520px; margin: 60px auto; padding: 20px; background: #fff; border-radius: 4px; text-align: center; }
  @media print { .bar { display: none; } body { background: #fff; } #pages { padding: 0; }
                 #pages canvas { box-shadow: none; margin: 0 auto; page-break-after: always; } }
</style>
</head>
<body>
<div class="bar">
  <b><?= $e($title) ?></b>
  <div>
    <button type="button" onclick="window.print()">Print</button>
    <a href="<?= $e($src) ?>" download="<?= $e($file) ?>">Save as PDF</a>
  </div>
</div>
<div id="pages"><p class="msg">Opening…</p></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(async function () {
  const box = document.getElementById('pages');
  try {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    const res = await fetch(<?= json_encode($src) ?>, { credentials: 'same-origin' });
    if (!res.ok) throw new Error(await res.text());
    const pdf = await pdfjsLib.getDocument({ data: await res.arrayBuffer() }).promise;
    box.innerHTML = '';
    for (let n = 1; n <= pdf.numPages; n++) {
      const page = await pdf.getPage(n);
      const viewport = page.getViewport({ scale: 2 });          // sharp on phones and when printed
      const canvas = document.createElement('canvas');
      canvas.width = viewport.width; canvas.height = viewport.height;
      canvas.style.width = Math.round(viewport.width / 2) + 'px';
      box.appendChild(canvas);
      await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
    }
  } catch (err) {
    box.innerHTML = '<p class="msg">' + (String(err.message || err).includes('could not find')
      ? 'We could not find that booking. Use “Already booked? Check status” on the booking page.'
      : 'This document could not be shown. Please call us on <?= $e(cfg('contact.phone')) ?>.') + '</p>';
  }
})();
</script>
</body>
</html>
