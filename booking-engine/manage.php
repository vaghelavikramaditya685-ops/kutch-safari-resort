<?php
/* ===========================================================================
 *  "My booking" — a guest looks their reservation up and can cancel it.
 *  Reference plus the phone or email on the booking; no account needed.
 * ======================================================================== */
require_once __DIR__ . '/lib/db.php';
$ref = htmlspecialchars($_GET['ref'] ?? '', ENT_QUOTES);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My booking</title>
<meta name="robots" content="noindex">
<link href="https://fonts.googleapis.com/css2?family=Marcellus&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/engine.css">
</head>
<body>
<header class="eng-header">
  <div class="wrap">
    <div class="eng-brand"><strong>My booking</strong></div>
    <nav><a href="index.php">Make a new booking</a></nav>
  </div>
</header>

<div class="wrap" style="max-width:720px;padding-block:32px">
  <div id="alert"></div>

  <div class="panel" id="lookupCard">
    <h2>Find your booking</h2>
    <p style="font-size:.88rem">Enter your reference and the mobile number or email you booked with.</p>
    <form id="lookupForm">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="field">
          <label for="ref">Booking reference</label>
          <input id="ref" value="<?= $ref ?>" placeholder="KSR-XXXXXX" required style="text-transform:uppercase">
        </div>
        <div class="field">
          <label for="contact">Mobile or email</label>
          <input id="contact" required>
        </div>
      </div>
      <button class="btn" type="submit" style="margin-top:16px">Find booking</button>
    </form>
  </div>

  <div id="result"></div>
</div>

<script>
const $ = s => document.querySelector(s);
const rupees = n => '₹' + Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
const niceDate = s => new Date(s + 'T00:00:00').toLocaleDateString('en-IN',
    { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
let current = null;

function notice(msg, kind) {
  $('#alert').innerHTML = msg ? `<div class="notice notice--${kind || 'err'}">${msg}</div>` : '';
}

$('#lookupForm').addEventListener('submit', async e => {
  e.preventDefault();
  notice('');
  const params = new URLSearchParams({ ref: $('#ref').value.trim(), contact: $('#contact').value.trim() });
  const res = await fetch('api/booking-lookup.php?' + params).then(r => r.json());
  if (!res.ok) { notice(res.error); $('#result').innerHTML = ''; return; }
  current = res.booking;
  render(current);
});

function render(b) {
  const statusColour = { confirmed: 'var(--ok)', pending: 'var(--warn)', cancelled: 'var(--err)' }[b.status] || 'var(--muted)';
  $('#lookupCard').style.display = 'none';

  $('#result').innerHTML = `
    <div class="panel">
      <div style="display:flex;justify-content:space-between;align-items:start;gap:16px;flex-wrap:wrap">
        <div>
          <h2 style="margin-bottom:.1em">${b.property}</h2>
          <p style="margin:0;font-size:.86rem;color:var(--muted)">Reference ${b.ref}</p>
        </div>
        <span style="color:${statusColour};font-weight:600;text-transform:uppercase;
                     letter-spacing:.1em;font-size:.72rem">${b.status}</span>
      </div>

      <div class="summary__dates" style="margin-top:18px">
        <div><span>Check in</span><b>${niceDate(b.check_in)}</b></div>
        <div><span>Check out</span><b>${niceDate(b.check_out)}</b></div>
        <div><span>Guests</span><b>${b.adults} adults${b.children ? ', ' + b.children + ' children' : ''}</b></div>
      </div>

      ${b.rooms.map(r => `<div class="sline"><span>${r.rooms} × ${r.name}<small>${r.plan}</small></span>
        <span>${rupees(r.subtotal)}</span></div>`).join('')}
      ${b.addons.map(a => `<div class="sline"><span>${a.name} × ${a.quantity}</span>
        <span>${rupees(a.subtotal)}</span></div>`).join('')}
      <div class="sline sline--total"><span>Total</span><span>${rupees(b.total)}</span></div>
      <div class="sline"><span>Paid</span><span>${rupees(b.amount_paid)}</span></div>
      ${b.balance > 0.5 ? `<div class="sline sline--due"><span>Balance due</span><span>${rupees(b.balance)}</span></div>` : ''}
    </div>

    ${b.can_cancel ? `
      <div class="panel" style="border-left-color:var(--muted)">
        <h3 style="font-size:1rem">Need to cancel?</h3>
        <table style="width:100%;font-size:.84rem;margin-bottom:14px">
          ${b.cancellation.map(c => `<tr>
            <td style="padding:3px 0;color:var(--muted)">
              ${c.charge_percent === 0 ? 'Up to' : 'From'} ${niceDate(c.until)}</td>
            <td style="padding:3px 0;text-align:right">
              ${c.charge_percent === 0 ? 'No charge' : c.charge_percent + '% of the total'}</td></tr>`).join('')}
        </table>
        <button class="btn btn--ghost btn--sm" id="cancelBtn">Cancel this booking</button>
      </div>` : `
      <div class="notice notice--info">
        ${b.status === 'cancelled'
          ? 'This booking has been cancelled.'
          : 'This booking can no longer be cancelled online. Please call ' + b.property_phone + '.'}
      </div>`}

    <p style="font-size:.85rem;color:var(--muted)">
      Anything else — an early check-in, a change of dates, another room — just call
      <a href="tel:${(b.property_phone || '').replace(/\s/g, '')}">${b.property_phone}</a>.
    </p>`;

  const btn = $('#cancelBtn');
  if (btn) btn.addEventListener('click', cancelBooking);
}

async function cancelBooking() {
  const why = prompt('Cancelling this booking. If you would like to tell us why, type it here:') || '';
  if (!confirm('Cancel booking ' + current.ref + '? This cannot be undone.')) return;

  const res = await fetch('api/booking-cancel.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ref: current.ref, manage_token: current.manage_token, reason: why }),
  }).then(r => r.json());

  if (!res.ok) { notice(res.error); return; }
  notice('Your booking has been cancelled. ' + res.note, 'ok');
  current.status = 'cancelled';
  current.can_cancel = false;
  render(current);
}

if ($('#ref').value) $('#contact').focus();
</script>
</body>
</html>
