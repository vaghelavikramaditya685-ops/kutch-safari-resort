<?php
/* ===========================================================================
 *  Outgoing email. Uses PHP's mail(), which works on nearly every cPanel host.
 *  If mail lands in spam, turn on SMTP in config.php and send through the
 *  mailbox cPanel gives you — mail sent from your own domain authenticates.
 * ======================================================================== */

require_once __DIR__ . '/db.php';

/**
 * A header value as mail allows it. Subjects such as "Booking confirmed — KSR-…"
 * contain characters outside plain ASCII, which must be encoded in a header or
 * some mail apps show them as "â€"".
 */
function mail_header_text(string $s): string {
    return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
}

/** The body, base64 in short lines: safe for any server (no 998-character lines, no lone dots). */
function mail_body(string $html): string {
    return chunk_split(base64_encode($html), 76, "\r\n");
}

function send_mail(string $to, string $subject, string $html, ?string $bcc = null): bool {
    if (!$to) return false;
    // Switched off (config mail.enabled = false, e.g. on a development PC): nothing is sent.
    if (!cfg('mail.enabled', true)) {
        audit('mail_skipped', 'email', null, ['to' => $to, 'subject' => $subject, 'why' => 'mail.enabled is off']);
        return false;
    }

    $from_email = cfg('mail.from_email');
    $from_name  = cfg('mail.from_name');

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n";
    $headers .= sprintf("From: %s <%s>\r\n", mail_header_text((string) $from_name), $from_email);
    $headers .= sprintf("Reply-To: %s\r\n", $from_email);
    if ($bcc) $headers .= sprintf("Bcc: %s\r\n", $bcc);

    if (cfg('mail.smtp.enabled')) return smtp_send($to, $subject, $html, $bcc);

    // PHP's mail() on Windows talks to the SMTP server in php.ini (localhost:25 unless
    // set). With nothing listening it waited about 2 seconds before failing, on every
    // payment confirmation and enquiry. Check quickly first and fail at once.
    if (PHP_OS_FAMILY === 'Windows') {
        $host = (string) (ini_get('SMTP') ?: 'localhost'); $port = (int) (ini_get('smtp_port') ?: 25);
        $probe = @fsockopen($host, $port, $errno, $errstr, 0.3);
        if (!$probe) {
            audit('mail_failed', 'email', null, ['to' => $to, 'subject' => $subject, 'error' => "no mail server at $host:$port"]);
            return false;
        }
        fclose($probe);
    }
    $sent = @mail($to, mail_header_text($subject), mail_body($html), $headers);
    audit($sent ? 'mail_sent' : 'mail_failed', 'email', null, ['to' => $to, 'subject' => $subject]);
    return $sent;
}

/**
 * A small SMTP client — enough for transactional mail, no library needed.
 * Turn it on in config when mail() is unreliable.
 */
function smtp_send(string $to, string $subject, string $html, ?string $bcc = null): bool {
    $host = cfg('mail.smtp.host'); $port = (int) cfg('mail.smtp.port', 587);
    $user = cfg('mail.smtp.user'); $pass = cfg('mail.smtp.pass');
    $from = cfg('mail.from_email');

    $transport = cfg('mail.smtp.secure') === 'ssl' ? "ssl://$host" : $host;
    $fp = @stream_socket_client("$transport:$port", $errno, $errstr, 15);
    if (!$fp) { audit('smtp_connect_failed', 'email', null, ['error' => $errstr]); return false; }

    $read = function () use ($fp) {
        $data = '';
        while ($line = fgets($fp, 515)) { $data .= $line; if (substr($line, 3, 1) === ' ') break; }
        return $data;
    };
    $say = function (string $cmd) use ($fp, $read) { fputs($fp, $cmd . "\r\n"); return $read(); };

    $read();
    $say('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    if (cfg('mail.smtp.secure') === 'tls') {
        // If the server will not switch to TLS, stop: carrying on would send the
        // mailbox password below in plain text.
        $ok_tls = str_starts_with($say('STARTTLS'), '220')
               && @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!$ok_tls) {
            fclose($fp);
            audit('smtp_tls_failed', 'email', null, ['host' => $host]);
            return false;
        }
        $say('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    }
    if ($user) {
        $say('AUTH LOGIN');
        $say(base64_encode($user));
        $say(base64_encode($pass));
    }
    $say("MAIL FROM:<$from>");
    $say("RCPT TO:<$to>");
    if ($bcc) $say("RCPT TO:<$bcc>");
    $say('DATA');

    // Date and Message-ID: mail without them is more likely to be filed as spam.
    $domain = substr(strrchr((string) $from, '@') ?: '@localhost', 1);
    $msg  = "Date: " . date('r') . "\r\n";
    $msg .= "Message-ID: <" . bin2hex(random_bytes(12)) . "@$domain>\r\n";
    $msg .= "From: " . mail_header_text((string) cfg('mail.from_name')) . " <$from>\r\n";
    $msg .= "To: <$to>\r\n";
    $msg .= "Subject: " . mail_header_text($subject) . "\r\n";
    $msg .= "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $msg .= mail_body($html) . ".";
    $resp = $say($msg);
    $say('QUIT');
    fclose($fp);

    $ok = strpos($resp, '250') !== false;
    audit($ok ? 'smtp_sent' : 'smtp_failed', 'email', null, ['to' => $to]);
    return $ok;
}

/* ---------------------------------------------------------------------------
 * Templates. Plain tables, because that is what email clients render reliably.
 * ------------------------------------------------------------------------ */

function email_shell(string $accent, string $title, string $body): string {
    return '<div style="font-family:Helvetica,Arial,sans-serif;background:#f5efe6;padding:24px">
      <div style="max-width:600px;margin:0 auto;background:#fff;border-top:4px solid ' . htmlspecialchars($accent) . '">
        <div style="padding:28px 30px">
          <h1 style="margin:0 0 6px;font-size:20px;color:#241f1b;font-weight:normal">' . $title . '</h1>
          ' . $body . '
        </div>
      </div>
      <p style="text-align:center;color:#8a8078;font-size:12px;margin-top:18px">
        This email was sent by ' . htmlspecialchars((string) cfg('mail.from_name')) . '.
      </p>
    </div>';
}

function money_row(string $label, $value, bool $bold = false): string {
    $w = $bold ? 'font-weight:bold;' : '';
    return '<tr><td style="padding:6px 0;color:#5c544c;' . $w . '">' . $label . '</td>
            <td style="padding:6px 0;text-align:right;color:#241f1b;' . $w . '">' . $value . '</td></tr>';
}

function send_booking_confirmation(int $booking_id): bool {
    require_once __DIR__ . '/booking.php';
    $b = get_booking($booking_id);
    if (!$b || !$b['guest_email']) return false;

    $rows = '';
    foreach ($b['rooms'] as $r) {
        $rows .= money_row(
            htmlspecialchars($r['rooms'] . ' × ' . $r['room_type_name']) .
            '<br><span style="font-size:12px;color:#8a8078">' . htmlspecialchars($r['rate_plan_name']) . '</span>',
            '₹' . number_format((float) $r['subtotal'], 2));
    }
    foreach ($b['addons'] as $a) {
        $rows .= money_row(htmlspecialchars($a['addon_name'] . ' × ' . $a['quantity']),
                           '₹' . number_format((float) $a['subtotal'], 2));
    }
    if ((float) $b['discount'] > 0) $rows .= money_row('Discount', '− ₹' . number_format((float) $b['discount'], 2));
    $rows .= money_row('Taxes', '₹' . number_format((float) $b['tax_amount'], 2));
    $rows .= money_row('Total', '₹' . number_format((float) $b['total'], 2), true);
    $rows .= money_row('Paid', '₹' . number_format((float) $b['amount_paid'], 2));
    $balance = (float) $b['total'] - (float) $b['amount_paid'];
    if ($balance > 0.5) $rows .= money_row('Balance due', '₹' . number_format($balance, 2), true);

    $manage = rtrim((string) cfg('base_url'), '/') . '/manage.php?ref=' . urlencode($b['ref']);

    $body = '
      <p style="color:#5c544c;font-size:14px">Dear ' . htmlspecialchars($b['guest_name']) . ',</p>
      <p style="color:#5c544c;font-size:14px">Your booking at <strong>' . htmlspecialchars($b['property_name'])
      . '</strong> is confirmed. We look forward to welcoming you.</p>

      <table style="width:100%;border-collapse:collapse;margin:20px 0;font-size:14px">
        <tr><td style="padding:6px 0;color:#8a8078">Reference</td>
            <td style="padding:6px 0;text-align:right;font-weight:bold;letter-spacing:1px">' . $b['ref'] . '</td></tr>
        <tr><td style="padding:6px 0;color:#8a8078">Check in</td>
            <td style="padding:6px 0;text-align:right">' . date('D, j M Y', strtotime($b['check_in']))
            . ' from ' . htmlspecialchars($b['check_in_time']) . '</td></tr>
        <tr><td style="padding:6px 0;color:#8a8078">Check out</td>
            <td style="padding:6px 0;text-align:right">' . date('D, j M Y', strtotime($b['check_out']))
            . ' by ' . htmlspecialchars($b['check_out_time']) . '</td></tr>
        <tr><td style="padding:6px 0;color:#8a8078">Guests</td>
            <td style="padding:6px 0;text-align:right">' . (int) $b['adults'] . ' adults'
            . ((int) $b['children'] ? ', ' . (int) $b['children'] . ' children' : '') . '</td></tr>
      </table>

      <table style="width:100%;border-collapse:collapse;font-size:14px;border-top:1px solid #e4dcd1">' . $rows . '</table>

      <p style="margin:22px 0"><a href="' . htmlspecialchars($manage) . '"
         style="background:' . htmlspecialchars($b['accent']) . ';color:#fff;padding:12px 22px;
         text-decoration:none;font-size:13px;letter-spacing:1px;text-transform:uppercase">View your booking</a>
         &nbsp; <a href="' . htmlspecialchars(rtrim((string) cfg('base_url'), '/') . '/document.php?' . http_build_query(
             ['doc' => 'receipt', 'ref' => $b['ref'], 'token' => $b['manage_token']])) . '"
         style="color:' . htmlspecialchars($b['accent']) . ';font-size:13px">Receipt (PDF)</a></p>

      <p style="color:#5c544c;font-size:13px">' . nl2br(htmlspecialchars((string) $b['property_address'])) . '<br>
         ' . htmlspecialchars((string) $b['property_phone']) . ' · '
         . htmlspecialchars((string) $b['property_email']) . '</p>';

    return send_mail($b['guest_email'],
        'Booking confirmed — ' . $b['ref'] . ' · ' . $b['property_name'],
        email_shell($b['accent'], 'Your stay is confirmed', $body),
        cfg('mail.bcc_office'));
}

function send_enquiry_notification(array $enquiry): bool {
    $body = '<table style="width:100%;font-size:14px;border-collapse:collapse">';
    foreach ([
        'Name' => $enquiry['name'], 'Phone' => $enquiry['phone'], 'Email' => $enquiry['email'],
        'Check in' => $enquiry['check_in'], 'Check out' => $enquiry['check_out'],
        'Guests' => $enquiry['guests'], 'Interested in' => $enquiry['interest'],
        'Message' => $enquiry['message'],
    ] as $label => $value) {
        if (!$value) continue;
        $body .= '<tr><td style="padding:6px 12px 6px 0;color:#8a8078;vertical-align:top">' . $label . '</td>
                      <td style="padding:6px 0;color:#241f1b">' . nl2br(htmlspecialchars((string) $value)) . '</td></tr>';
    }
    $body .= '</table>';

    return send_mail(cfg('mail.bcc_office'),
        'Website enquiry — ' . $enquiry['name'],
        email_shell('#B85C2E', 'New enquiry from the website', $body));
}
