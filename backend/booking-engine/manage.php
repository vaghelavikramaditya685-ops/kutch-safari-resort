<?php
/* ===========================================================================
 *  "Already booked? Check status" — a guest looks their reservation up, opens the
 *  receipt, or calls us. Cancelling is done by the reservations desk in the admin panel.
 *  Reference plus the phone or email on the booking; no account needed.
 * ======================================================================== */
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/debug.php';
$ref = htmlspecialchars($_GET['ref'] ?? '', ENT_QUOTES);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Check your booking</title>
<link rel="icon" href="favicon.ico" sizes="any">
<link rel="icon" type="image/svg+xml" href="assets/icon.svg">
<meta name="robots" content="noindex">
<link href="https://fonts.googleapis.com/css2?family=Marcellus&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/engine.css?v=<?= filemtime(__DIR__ . '/assets/engine.css') ?>">
</head>
<body>
<?php debug_bar(); /* local only; see lib/debug.php */ ?>
<header class="eng-header">
  <div class="wrap">
    <div class="eng-brand"><strong>Check your booking</strong></div>
    <nav>
      <a href="index.php">Make a new booking</a>
      <a href="tel:<?= htmlspecialchars(preg_replace('/\s/', '', (string) cfg('contact.phone')), ENT_QUOTES) ?>"><?= htmlspecialchars((string) cfg('contact.phone')) ?></a>
    </nav>
  </div>
</header>

<div class="wrap" style="max-width:720px;padding-block:32px">
  <h1 class="sr-only">Check your booking</h1>
  <div id="alert" role="alert"></div>

  <div class="panel" id="lookupCard">
    <h2>Already booked? Check status</h2>
    <p style="font-size:.88rem">Enter your booking code (for example KSR-4XY7PQ) and the mobile number or email
       you booked with. You can see your booking, open the receipt, or call us.</p>
    <form id="lookupForm">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="field">
          <label for="ref">Booking reference</label>
          <input id="ref" name="ref" value="<?= $ref ?>" placeholder="KSR-XXXXXX" autocomplete="off" required style="text-transform:uppercase">
        </div>
        <div class="field">
          <label for="contact">Mobile or email</label>
          <input id="contact" name="contact" required>
        </div>
      </div>
      <button class="btn" type="submit" style="margin-top:16px">Find booking</button>
    </form>
  </div>

  <div id="result"></div>
</div>

<script>
const $ = s => document.querySelector(s);
// Names come from the booking (cottages, extras): shown as text, never as markup.
const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
// Looked up with POST, so the phone number or email never ends up in an address,
// a server log or the browser history.
const lookup = body => fetch('api/booking-lookup.php', {
  method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body),
}).then(r => r.json()).catch(() => ({ ok: false, error: 'We could not reach our booking system. Please check your connection and try again.' }));
const rupees = n => '₹' + Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
const niceDate = s => new Date(s + 'T00:00:00').toLocaleDateString('en-IN',
    { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
let current = null;
const WHATSAPP = <?= json_encode((string) cfg('contact.whatsapp')) ?>;
const todayISO = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
/* "Up to 20 Sep" for the first period, "From 21 Sep" for the rest (today, if it already began). */
const cancelLabel = c => c.from ? 'From ' + niceDate(c.from > todayISO ? c.from : todayISO) : 'Up to ' + niceDate(c.to);

function notice(msg, kind) {
  $('#alert').innerHTML = msg ? `<div class="notice notice--${kind || 'err'}">${msg}</div>` : '';
}

$('#lookupForm').addEventListener('submit', async e => {
  e.preventDefault();
  notice('');
  const res = await lookup({ ref: $('#ref').value.trim(), contact: $('#contact').value.trim() });
  if (!res.ok) { notice(esc(res.error)); $('#result').innerHTML = ''; return; }
  current = res.booking;
  render(current);
});

// Opened from "Check status" on the confirmation, or after a refresh: the link
// carries the booking's private token, so the guest does not have to type anything.
(async () => {
  const q = new URLSearchParams(location.search);
  if (!q.get('ref') || !q.get('token')) return;
  const res = await lookup({ ref: q.get('ref'), token: q.get('token') });
  if (res.ok) { current = res.booking; render(current); }
})();

function render(b) {
  const incl = Boolean(b.prices_include_tax);
  const statusColour = { confirmed: 'var(--ok)', 'checked out': 'var(--ok)', pending: 'var(--warn)', cancelled: 'var(--err)',
                         'not paid': 'var(--err)', 'no-show': 'var(--err)' }[b.status] || 'var(--muted)';
  $('#lookupCard').style.display = 'none';

  $('#result').innerHTML = `
    <div class="panel">
      <div style="display:flex;justify-content:space-between;align-items:start;gap:16px;flex-wrap:wrap">
        <div>
          <h2 style="margin-bottom:.1em">${esc(b.property)}</h2>
          <p style="margin:0;font-size:.86rem;color:var(--muted)">Reference ${esc(b.ref)}</p>
        </div>
        <span style="color:${statusColour};font-weight:600;text-transform:uppercase;
                     letter-spacing:.1em;font-size:.72rem">${esc(b.status)}</span>
      </div>

      ${b.modified_at ? `<div class="notice notice--info" style="margin:14px 0 0">Updated by the resort on
        ${new Date(b.modified_at.replace(' ', 'T')).toLocaleString('en-IN', { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' })}.
        These are your current details.</div>` : ''}

      <div class="summary__dates" style="margin-top:18px">
        <div><span>Check in</span><b>${niceDate(b.check_in)}</b></div>
        <div><span>Check out</span><b>${niceDate(b.check_out)}</b></div>
        <div><span>Guests</span><b>${b.adults} adults${b.children ? ', ' + b.children + ' children' : ''}</b></div>
      </div>

      ${b.rooms.map(r => `<div class="sline"><span>${r.rooms} × ${esc(r.name)}<small>${esc(r.plan)}</small></span>
        <span>${rupees(incl ? r.subtotal + r.tax_amount : r.subtotal)}</span></div>`).join('')}
      ${b.addons.map(a => `<div class="sline"><span>${esc(a.name)} × ${a.quantity}</span>
        <span>${rupees(incl ? a.subtotal + a.tax_amount : a.subtotal)}</span></div>`).join('')}
      ${incl ? '' : `<div class="sline"><span>GST</span><span>${rupees(b.tax_amount)}</span></div>`}
      <div class="sline sline--total"><span>Total</span><span>${rupees(b.total)}</span></div>
      ${incl ? `<div class="sline"><span style="color:var(--muted)">Includes GST</span>
        <span style="color:var(--muted)">${rupees(b.tax_amount)}</span></div>` : ''}
      <div class="sline"><span>Paid</span><span>${rupees(b.amount_paid)}</span></div>
      ${b.due_now > 0.5 ? `<div class="sline sline--due"><span>${b.arrived ? 'To pay at the desk' : 'Due now'}</span><span>${rupees(b.due_now)}</span></div>` : ''}
      ${b.due_later > 0.5 ? `<div class="sline"><span>Due before arrival</span><span>${rupees(b.due_later)}</span></div>` : ''}
      ${b.refund_due > 0.5 ? `<div class="sline sline--due"><span>Refund due to you</span><span>${rupees(b.refund_due)}</span></div>
        <p style="font-size:.8rem;color:var(--muted);margin:4px 0 0">Your booking was changed and now costs less than you paid.
           The resort will give this back to you.</p>` : ''}

      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px">
        <a class="btn btn--sm" target="_blank" rel="noopener"
           href="document.php?${new URLSearchParams({ doc: 'receipt', ref: b.ref, token: b.manage_token })}">Receipt (PDF)</a>
        <a class="btn btn--plain btn--sm" target="_blank" rel="noopener"
           href="document.php?${new URLSearchParams({ doc: 'terms', property: b.property_code })}">Terms (PDF)</a>
        <a class="btn btn--plain btn--sm" href="tel:${(b.property_phone || '').replace(/\s/g, '')}">Call ${b.property_phone}</a>
      </div>
    </div>

    ${b.status === 'cancelled' ? `
      <div class="panel" style="border-left-color:var(--err)">
        <h3 style="font-size:1rem">This booking has been cancelled</h3>
        ${b.cancelled_at ? `<p style="font-size:.86rem;margin:0 0 8px">Cancelled on ${niceDate(b.cancelled_at.slice(0, 10))}.</p>` : ''}
        ${b.cancellation_percent !== null ? `<div class="sline"><span>Cancellation charge (${b.cancellation_percent}%)</span><span>${rupees(b.cancellation_charge)}</span></div>` : ''}
        ${b.refund_amount > 0.5 ? `<div class="sline sline--due"><span>Refund to you</span><span>${rupees(b.refund_amount)}</span></div>
          <p style="font-size:.8rem;color:var(--muted);margin:4px 0 0">${b.refund_outstanding > 0.5
            ? (b.refund_given > 0.5 ? `${rupees(b.refund_given)} has been given back so far; ${rupees(b.refund_outstanding)} is still to come.`
                                    : 'The resort will give this back to you, usually within 5–7 working days.')
            : 'This has been given back to you in full.'}</p>`
          : '<p style="font-size:.84rem;margin:0">No refund is due under the cancellation policy.</p>'}
      </div>` : !b.can_cancel ? '' : `
      <div class="panel" style="border-left-color:var(--muted)">
        <h3 style="font-size:1rem">Need to cancel?</h3>
        <p style="font-size:.86rem;margin-bottom:10px">Cancellations are made by our reservations team.
           Call or WhatsApp us with your booking code <strong>${b.ref}</strong>.</p>
        <table style="width:100%;font-size:.84rem;margin-bottom:6px">
          ${b.cancellation.filter(c => !c.past).map(c => `<tr>
            <td style="padding:3px 0;color:var(--muted)">
              ${cancelLabel(c)}</td>
            <td style="padding:3px 0;text-align:right">
              ${c.charge_percent === 0 ? 'No charge' : `${c.charge_percent}% of the total (${rupees(c.charge_amount)})`}</td></tr>`).join('')}
        </table>
        ${(() => {
          // What cancelling today would mean in money for this guest: never "you owe more".
          const now = b.cancellation.filter(c => !c.past)[0];
          if (!now || !b.amount_paid) return '';
          const back = Math.max(0, b.amount_paid - now.charge_amount);
          return `<p style="font-size:.8rem;color:var(--muted);margin:0 0 14px">You have paid ${rupees(b.amount_paid)}. ${back > 0.5
            ? `Cancelling today, ${rupees(back)} would come back to you.`
            : 'Cancelling today, what you have paid would be kept and nothing more would be asked of you.'}</p>`;
        })()}
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <a class="btn btn--sm" href="tel:${(b.property_phone || '').replace(/\s/g, '')}">Call ${b.property_phone}</a>
          <a class="btn btn--plain btn--sm" target="_blank" rel="noopener"
             href="https://wa.me/${WHATSAPP}?text=${encodeURIComponent('Hello, I would like to cancel my booking ' + b.ref + '.')}">WhatsApp us</a>
        </div>
      </div>`}

    <p style="font-size:.85rem;color:var(--muted)">
      Anything else — an early check-in, a change of dates, another room — just call
      <a href="tel:${(b.property_phone || '').replace(/\s/g, '')}">${b.property_phone}</a>.
    </p>`;

}

if ($('#ref').value) $('#contact').focus();
</script>
</body>
</html>
