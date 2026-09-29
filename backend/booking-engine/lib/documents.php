<?php
/* ===========================================================================
 *  The PDFs a guest or the desk can open: the booking receipt (with the terms
 *  that apply to it) and the terms & conditions on their own.
 *
 *  The terms are written only from what the engine is actually set to do —
 *  check-in times, the cancellation ladder, payment options, tax — plus any
 *  house rules the property adds in config.php (terms.house_rules). Nothing
 *  is invented here.
 * ======================================================================== */

require_once __DIR__ . '/booking.php';
require_once __DIR__ . '/inventory.php';
require_once __DIR__ . '/pdf.php';

/** ₹1,23,456.00 — Indian digit grouping. */
function inr_pdf(float $n): string {
    $neg = $n < 0;
    [$whole, $paise] = explode('.', number_format(abs($n), 2, '.', ''));
    $last3 = substr($whole, -3);
    $rest = substr($whole, 0, -3);
    if ($rest !== '') $last3 = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) . ',' . $last3;
    return ($neg ? '− ' : '') . '₹' . $last3 . '.' . $paise;
}

function hex_rgb(string $hex, array $fallback = [184, 92, 46]): array {
    return preg_match('/^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', trim($hex), $m)
        ? [hexdec($m[1]), hexdec($m[2]), hexdec($m[3])] : $fallback;
}

/** "Cancel 30 or more days before arrival: no charge." — one line per step of the ladder. */
function cancellation_terms(): array {
    $ladder = cfg('cancellation', []);
    usort($ladder, fn($a, $b) => (int) $b['days_before'] <=> (int) $a['days_before']);
    $out = [];
    foreach ($ladder as $i => $step) {
        $d = (int) $step['days_before'];
        $prev = $i > 0 ? (int) $ladder[$i - 1]['days_before'] : null;
        $when = $prev === null ? "$d or more days before arrival"
              : ($d === 0 ? "less than $prev days before arrival, or not arriving"
                          : "$d to " . ($prev - 1) . " days before arrival");
        $pct = (float) $step['charge_percent'];
        $out[] = 'Cancelling ' . $when . ': '
               . ($pct <= 0 ? 'no charge.' : rtrim(rtrim(number_format($pct, 2), '0'), '.') . '% of the total is charged.');
    }
    return $out;
}

/** The terms for a property, and for one booking when $b is given. */
function booking_terms(array $property, ?array $b = null): array {
    $t = [];
    $t[] = sprintf('Check-in is from %s and check-out is by %s.', $property['check_in_time'], $property['check_out_time']);
    if (!empty($property['season_start'])) {
        $t[] = sprintf('%s is open from %s to %s only.', $property['name'],
            date('j M Y', strtotime($property['season_start'])), date('j M Y', strtotime($property['season_end'])));
    }
    $t[] = sprintf('Cancellations are made by our reservations team: call or WhatsApp %s with your booking code. '
                 . 'The charge depends on how many days before arrival you cancel:', $property['phone']);
    foreach (cancellation_terms() as $line) $t[] = $line;

    if ($b) {
        $mode = cfg('payment_modes.' . $b['payment_mode']);
        if ($mode) $t[] = 'Payment: ' . $mode['label'] . '. ' . ($mode['note'] ?? '');
    } else {
        foreach (cfg('payment_modes', []) as $m) {
            if (!empty($m['enabled'])) $t[] = 'Payment option — ' . $m['label'] . ': ' . ($m['note'] ?? '');
        }
    }
    $t[] = prices_include_tax($property)
        ? 'All prices include GST.'
        : 'GST is charged on top of the tariff at the rate that applies to the nightly room price.';
    $t[] = sprintf('Online bookings are for up to %d rooms and %d nights. For larger groups or longer stays, please contact us.',
        (int) cfg('rules.max_rooms_online', 5), (int) cfg('rules.max_nights', 21));
    foreach ((array) cfg('terms.house_rules', []) as $rule) {
        if (trim((string) $rule) !== '') $t[] = trim((string) $rule);
    }
    $t[] = sprintf('Questions about your booking: %s%s.', $property['phone'],
        !empty($property['email']) ? ' · ' . $property['email'] : '');
    return $t;
}

/* ---------------------------------------------------------------------------
 * Layout helpers shared by both documents.
 * ------------------------------------------------------------------------ */

final class DocWriter {
    const L = 48;                 // left margin
    const R = 547.28;             // right edge (A4 width − 48)
    const BOTTOM = 790;           // last usable y before the footer

    public Pdf $pdf;
    public float $y = 0;
    public array $accent;
    private int $page = 0;
    private string $footer;

    const INK = [36, 31, 27];
    const BODY = [92, 84, 76];
    const MUTED = [138, 128, 120];
    const LINE = [228, 220, 209];

    public function __construct(array $accent, string $footer) {
        $this->pdf = new Pdf();
        $this->accent = $accent;
        $this->footer = $footer;
        $this->newPage();
    }

    public function newPage(): void {
        $this->pdf->addPage();
        $this->page++;
        $this->pdf->box(0, 0, Pdf::W, 6, $this->accent);
        $this->pdf->line(self::L, 812, self::R, 812, self::LINE);
        $this->pdf->text(self::L, 826, $this->footer, 7.5, false, self::MUTED);
        $this->pdf->text(self::R, 826, 'Page ' . $this->page, 7.5, false, self::MUTED, 'right');
        $this->y = 48;
    }

    /** Start a new page when the next $h points will not fit. */
    public function need(float $h): void {
        if ($this->y + $h > self::BOTTOM) $this->newPage();
    }

    public function heading(string $s): void {
        $this->need(40);
        $this->y += 22;
        $this->pdf->text(self::L, $this->y, strtoupper($s), 8.5, true, $this->accent);
        $this->y += 7;
        $this->pdf->line(self::L, $this->y, self::R, $this->y, self::LINE);
        $this->y += 14;
    }

    /** A label on the left and a value on the right, e.g. a money row. */
    public function row(string $label, string $value, bool $bold = false, ?string $sub = null): void {
        $w = self::R - self::L - 120;
        $lines = $this->pdf->wrap($label, 9.5, $w, $bold);
        $h = count($lines) * 13 + ($sub ? 12 : 0) + 6;
        $this->need($h);
        $this->pdf->text(self::R, $this->y, $value, 9.5, $bold, self::INK, 'right');
        foreach ($lines as $i => $ln) {
            $this->pdf->text(self::L, $this->y + $i * 13, $ln, 9.5, $bold, self::INK);
        }
        $yy = $this->y + count($lines) * 13;
        if ($sub) { $this->pdf->text(self::L, $yy - 1, $sub, 8, false, self::MUTED); $yy += 12; }
        $this->y = $yy + 4;
    }

    public function rule(): void {
        $this->pdf->line(self::L, $this->y - 6, self::R, $this->y - 6, self::LINE);
        $this->y += 4;
    }

    /** Numbered, wrapped paragraphs. */
    public function numbered(array $items): void {
        foreach ($items as $i => $item) {
            $lines = $this->pdf->wrap($item, 9, self::R - self::L - 18);
            $this->need(count($lines) * 12.5 + 4);
            $this->pdf->text(self::L, $this->y, ($i + 1) . '.', 9, true, $this->accent);
            foreach ($lines as $j => $ln) $this->pdf->text(self::L + 18, $this->y + $j * 12.5, $ln, 9, false, self::BODY);
            $this->y += count($lines) * 12.5 + 5;
        }
    }

    /** Property name, address and contact in the top-left; $right lines in the top-right. */
    public function letterhead(array $p, array $right): void {
        $this->pdf->text(self::L, $this->y + 8, $p['name'], 18, true, self::INK);
        $yy = $this->y + 26;
        foreach ($this->pdf->wrap((string) $p['address'], 8.5, 270) as $ln) {
            $this->pdf->text(self::L, $yy, $ln, 8.5, false, self::BODY);
            $yy += 11.5;
        }
        $contact = trim($p['phone'] . (!empty($p['email']) ? '  ·  ' . $p['email'] : ''));
        $this->pdf->text(self::L, $yy, $contact, 8.5, false, self::BODY);
        $yy += 11.5;
        if (!empty($p['gst_number'])) { $this->pdf->text(self::L, $yy, 'GSTIN ' . $p['gst_number'], 8.5, false, self::BODY); $yy += 11.5; }

        $ry = $this->y + 4;
        foreach ($right as [$text, $size, $bold, $color]) {
            $this->pdf->text(self::R, $ry, $text, $size, $bold, $color, 'right');
            $ry += $size + 5;
        }
        $this->y = max($yy, $ry) + 6;
    }
}

/* ---------------------------------------------------------------------------
 * The booking receipt.
 * ------------------------------------------------------------------------ */

function receipt_pdf(array $b): string {
    $p = q1("SELECT * FROM properties WHERE id = ?", [$b['property_id']]);
    $accent = hex_rgb((string) $p['accent']);
    $incl = prices_include_tax($p);
    $doc = new DocWriter($accent, $p['name'] . '  ·  ' . $b['ref']);
    $pdf = $doc->pdf;

    $status = booking_lapsed($b) ? 'NOT CONFIRMED — NOT PAID'
            : (['confirmed' => 'CONFIRMED', 'pending' => 'AWAITING PAYMENT', 'cancelled' => 'CANCELLED'][$b['status']]
               ?? strtoupper((string) $b['status']));
    $doc->letterhead($p, [
        ['BOOKING RECEIPT', 8.5, true, $accent],
        [$b['ref'], 15, true, DocWriter::INK],
        ['Issued ' . date('j M Y, g:i a'), 8.5, false, DocWriter::MUTED],
        ['Booked ' . date('j M Y', strtotime($b['created_at'])), 8.5, false, DocWriter::MUTED],
        ...(($m = booking_modified_at((int) $b['id'])) ? [['Updated by the resort ' . date('j M Y', strtotime($m)), 8.5, false, DocWriter::MUTED]] : []),
        [$status, 9, true, $b['status'] === 'cancelled' ? [192, 57, 43] : ($b['status'] === 'confirmed' ? [46, 125, 50] : [178, 106, 0])],
    ]);

    // Guest and stay, side by side.
    $doc->heading('Guest and stay');
    $col2 = DocWriter::L + 260;
    $left = [
        ['Guest', trim((string) $b['guest_name']) !== '' ? $b['guest_name'] : '—'],
        ['Mobile', trim((string) $b['guest_phone']) !== '' ? $b['guest_phone'] : '—'],
        ['Email', $b['guest_email'] ?: '—'],
        ['City', $b['guest_city'] ?: '—'],
    ];
    $right = [
        ['Check in', date('D, j M Y', strtotime($b['check_in'])) . ', from ' . $p['check_in_time']],
        ['Check out', date('D, j M Y', strtotime($b['check_out'])) . ', by ' . $p['check_out_time']],
        ['Nights', (string) (int) $b['nights']],
        ['Guests', (int) $b['adults'] . ' adult' . ((int) $b['adults'] === 1 ? '' : 's')
                   . ((int) $b['children'] ? ', ' . (int) $b['children'] . ' children' : '')],
    ];
    $y0 = $doc->y;
    foreach ([[$left, DocWriter::L], [$right, $col2]] as [$rows, $x]) {
        $yy = $y0;
        foreach ($rows as [$k, $v]) {
            $pdf->text($x, $yy, $k, 8, false, DocWriter::MUTED);
            $pdf->text($x + 62, $yy, (string) $v, 9.5, false, DocWriter::INK);
            $yy += 15;
        }
    }
    $doc->y = $y0 + 4 * 15;
    if (!empty($b['arrival_time']) || !empty($b['special_requests'])) {
        foreach ([['Arriving', $b['arrival_time']], ['Requests', $b['special_requests']]] as [$k, $v]) {
            if (!$v) continue;
            $lines = $pdf->wrap((string) $v, 9.5, DocWriter::R - DocWriter::L - 62);
            $doc->need(count($lines) * 13);
            $pdf->text(DocWriter::L, $doc->y, $k, 8, false, DocWriter::MUTED);
            foreach ($lines as $i => $ln) $pdf->text(DocWriter::L + 62, $doc->y + $i * 13, $ln, 9.5, false, DocWriter::INK);
            $doc->y += count($lines) * 13 + 2;
        }
    }

    // What was booked.
    $doc->heading('Your booking');
    foreach ($b['rooms'] as $r) {
        $amount = $incl ? (float) $r['subtotal'] + (float) $r['tax_amount'] : (float) $r['subtotal'];
        $doc->row($r['rooms'] . ' × ' . $r['room_type_name'], inr_pdf($amount), false, $r['rate_plan_name']);
    }
    foreach ($b['addons'] as $a) {
        $amount = $incl ? (float) $a['subtotal'] + (float) $a['tax_amount'] : (float) $a['subtotal'];
        $doc->row($a['addon_name'] . ' × ' . $a['quantity'], inr_pdf($amount));
    }
    if ((float) $b['discount'] > 0) $doc->row('Discount' . ($b['coupon_code'] ? ' (' . $b['coupon_code'] . ')' : ''), inr_pdf(-(float) $b['discount']));
    $doc->rule();
    if (!$incl) $doc->row('GST', inr_pdf((float) $b['tax_amount']));
    $doc->row('Total', inr_pdf((float) $b['total']), true);
    if ($incl) $doc->row('Includes GST', inr_pdf((float) $b['tax_amount']));
    $doc->row('Paid', inr_pdf((float) $b['amount_paid']));
    $balance = round((float) $b['total'] - (float) $b['amount_paid'], 2);
    $m = booking_money($b);
    if ($m['due_now'] > 0.5)    $doc->row('Due now', inr_pdf($m['due_now']), true);
    if ($m['later'] > 0.5) $doc->row('Due before arrival', inr_pdf($m['later']), true);
    if ($b['status'] !== 'cancelled' && $balance < -0.5) $doc->row('Refund due to you', inr_pdf(-$balance), true);
    if ($b['status'] === 'cancelled' && (float) $b['refund_amount'] > 0) $doc->row('Refund', inr_pdf((float) $b['refund_amount']), true);

    // Money received.
    $paid = array_filter($b['payments'], fn($x) => in_array($x['status'], ['paid', 'refunded'], true));
    if ($paid) {
        $doc->heading('Payments');
        foreach ($paid as $x) {
            $how = $x['purpose'] === 'refund' ? 'Refund to you' . ($x['method'] ? ' · ' . $x['method'] : '') : [
                'razorpay' => 'Online payment (Razorpay)' . ($x['method'] ? ' · ' . $x['method'] : ''),
                'upi_qr'   => 'UPI to bank account',
                'offline'  => 'Paid at the property' . ($x['method'] ? ' · ' . $x['method'] : ''),
                'test'     => 'TEST PAYMENT — no money was taken',
            ][$x['provider']] ?? $x['provider'];
            $ref = $x['payment_id'] ?: ($x['upi_ref'] ?: '');
            $doc->row($how, inr_pdf((float) $x['amount']), false,
                trim(date('j M Y', strtotime($x['paid_at'] ?: $x['created_at'])) . ($ref ? '  ·  Ref ' . $ref : '')));
        }
    }

    // What cancelling would cost, with this booking's own dates.
    if ($b['status'] !== 'cancelled') {
        $doc->heading('If you need to cancel');
        foreach (cancellation_schedule($b['check_in'], (float) $b['total']) as $s) {
            if ($s['past']) continue;      // a period that is over is no longer a choice
            $doc->row(cancellation_label($s), $s['charge_percent'] <= 0 ? 'No charge'
                : rtrim(rtrim(number_format($s['charge_percent'], 2), '0'), '.') . '% · ' . inr_pdf($s['charge_amount']));
        }
    }

    $doc->heading('Terms and conditions');
    $doc->numbered(booking_terms($p, $b));

    return $pdf->output();
}

/* ---------------------------------------------------------------------------
 * The terms and conditions on their own.
 * ------------------------------------------------------------------------ */

function terms_pdf(array $p): string {
    $accent = hex_rgb((string) $p['accent']);
    $doc = new DocWriter($accent, $p['name'] . '  ·  Terms and conditions');
    $doc->letterhead($p, [
        ['TERMS AND CONDITIONS', 8.5, true, $accent],
        ['Booking terms', 15, true, DocWriter::INK],
        ['As of ' . date('j M Y'), 8.5, false, DocWriter::MUTED],
    ]);
    $doc->heading('Conditions of booking');
    $doc->numbered(booking_terms($p));
    return $doc->pdf->output();
}
