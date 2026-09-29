<?php
/* ===========================================================================
 *  Availability — one calendar that shows everything.
 *
 *  Rows are the cottages (Kutchi 1–12, Deluxe 1–8…), columns are days, and each
 *  day is a full 24 hours. Every booked room is a bar from its check-in time on
 *  the arrival day to its check-out time on the departure day, so a turnover
 *  (one guest out at 10:00, the next in at 12:00) sits in the same row.
 *
 *  Rows are not fixed cottage numbers — the engine sells room types, not named
 *  cottages — so each room type's bookings are laid out here into as many rows
 *  as it has cottages, never overlapping. More bookings than cottages shows as
 *  an extra red "overbooked" row. Hover a bar for the details, click to open it;
 *  hover a day for everything that happens on it. Click a booking for a side
 *  panel: check-in/out and ETA, rooms, transfers, extras, money.
 *  One view only: by cottage (the "by guest" view was removed on request).
 * ======================================================================== */
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../lib/inventory.php';
require_once __DIR__ . '/../lib/booking.php';
$user = require_login();
check_csrf();

$flash = '';

// Anything odd in the address falls back to the first property on sale, 14 days, from yesterday.
$property_id = (int) ($_GET['property'] ?? 0);
if (!q1("SELECT id FROM properties WHERE id = ? AND active = 1", [$property_id])) {
    $property_id = (int) (q1("SELECT id FROM properties WHERE active = 1 ORDER BY id LIMIT 1")['id'] ?? 1);
}
$days  = in_array((int) ($_GET['days'] ?? 14), [7, 14, 30], true) ? (int) ($_GET['days'] ?? 14) : 14;
$start = valid_date((string) ($_GET['start'] ?? '')) ? $_GET['start'] : date('Y-m-d', strtotime('-1 day'));
$end   = date('Y-m-d', strtotime("$start +$days days"));    // first day NOT shown

$property   = q1("SELECT * FROM properties WHERE id = ?", [$property_id]);
$room_types = q("SELECT * FROM room_types WHERE property_id = ? AND active = 1 ORDER BY sort_order, id", [$property_id]);
$dates = [];
for ($i = 0; $i < $days; $i++) $dates[] = date('Y-m-d', strtotime("$start +$i days"));

/* ---- Every booked room in view, as a timed bar ------------------------- */
$ci_time = $property['check_in_time'] ?: '12:00';
$co_time = $property['check_out_time'] ?: '10:00';
$labels  = ['Single' => 1, 'Double' => 2, 'Triple' => 3];
$units   = [];   // by room type id
$rows = q("SELECT b.*, br.room_type_id, br.room_type_name, br.rate_plan_name, br.rooms AS line_rooms, br.adults AS line_adults
             FROM bookings b JOIN booking_rooms br ON br.booking_id = b.id
            WHERE b.property_id = ? AND b.status IN ('confirmed','pending')
              AND b.check_in < ? AND b.check_out > ?
            ORDER BY b.check_in, b.id, br.id", [$property_id, $end, $start]);
foreach ($rows as $r) {
    if (booking_lapsed($r)) continue;   // an abandoned, unpaid checkout holds nothing
    preg_match_all('/Room (\d+) (Single|Double|Triple)/', (string) $r['rate_plan_name'], $m, PREG_SET_ORDER);
    $n = max(1, (int) $r['line_rooms']);
    for ($i = 0; $i < $n; $i++) {
        $guests = isset($m[$i][2]) ? $labels[$m[$i][2]] : (int) round((int) $r['line_adults'] / $n);
        $units[(int) $r['room_type_id']][] = [
            'id'      => (int) $r['id'],
            'ref'     => $r['ref'],
            'cottage' => $r['room_type_name'],
            'guest'   => trim((string) $r['guest_name']) !== '' ? $r['guest_name'] : 'No name given',
            'phone'   => (string) $r['guest_phone'],
            'room'    => isset($m[$i][1]) ? 'Room ' . $m[$i][1] . ' · ' . $m[$i][2] : $guests . ' guests',
            'guests'  => $guests,
            'status'  => $r['status'],
            'start'   => strtotime($r['check_in'] . ' ' . $ci_time) * 1000,
            'end'     => strtotime($r['check_out'] . ' ' . $co_time) * 1000,
            'ci'      => $r['check_in'],
            'co'      => $r['check_out'],
            'nights'  => (int) $r['nights'],
            'total'   => (float) $r['total'],
            'paid'    => (float) $r['amount_paid'],
            // Split by how they pay: in full, or 50% now and the rest before arrival.
            'money'   => array_intersect_key(booking_money($r), array_flip(['mode', 'percent', 'due_now', 'later', 'refund'])),
        ];
    }
}

/* ---- Lay each room type's bars into rows that never overlap ------------- */
$groups = [];
foreach ($room_types as $rt) {
    $list = $units[(int) $rt['id']] ?? [];
    usort($list, fn($a, $b) => [$a['start'], $a['end']] <=> [$b['start'], $b['end']]);
    $lane_end = array_fill(0, (int) $rt['total_rooms'], PHP_INT_MIN);
    foreach ($list as &$u) {
        // Put it in the row that became free most recently before it starts (keeps turnovers together).
        $best = null;
        foreach ($lane_end as $i => $e) if ($e <= $u['start'] && ($best === null || $e > $lane_end[$best])) $best = $i;
        if ($best === null) { $best = count($lane_end); $lane_end[] = PHP_INT_MIN; }   // overbooked row
        $lane_end[$best] = $u['end'];
        $u['lane'] = $best;
    }
    unset($u);

    $per_day = [];
    foreach ($dates as $d) {
        $open = rooms_open($rt, $d);
        $per_day[$d] = ['free' => rooms_free($rt, $d), 'closed' => max(0, (int) $rt['total_rooms'] - $open)];
    }
    $groups[] = [
        'id' => (int) $rt['id'], 'name' => $rt['name'], 'total' => (int) $rt['total_rooms'],
        'lanes' => count($lane_end), 'units' => $list, 'days' => $per_day,
    ];
}

/* ---- Everything about each booking, for the side panel ------------------ */
$guests = [];
foreach ($units as $type_units) {
    foreach ($type_units as $u) {
        $g = &$guests[$u['id']];
        if (!$g) {
            $g = ['id' => $u['id'], 'ref' => $u['ref'], 'guest' => $u['guest'], 'phone' => $u['phone'], 'status' => $u['status'],
                  'start' => $u['start'], 'end' => $u['end'], 'ci' => $u['ci'], 'co' => $u['co'], 'nights' => $u['nights'],
                  'total' => $u['total'], 'paid' => $u['paid'], 'rooms' => [], 'guests' => 0, 'addons' => [], 'transfers' => []] + $u['money'];
        }
        $g['rooms'][] = ['cottage' => $u['cottage'], 'room' => $u['room'], 'guests' => $u['guests']];
        $g['guests'] += $u['guests'];
        unset($g);
    }
}
if ($guests) {
    $ids = array_keys($guests);
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    foreach (q("SELECT id, guest_email, arrival_time, special_requests FROM bookings WHERE id IN ($ph)", $ids) as $r) {
        $guests[(int) $r['id']] += ['email' => (string) $r['guest_email'], 'arrival' => (string) $r['arrival_time'], 'notes' => (string) $r['special_requests']];
    }
    foreach (q("SELECT ba.booking_id, ba.addon_name, ba.quantity, a.code FROM booking_addons ba LEFT JOIN addons a ON a.id = ba.addon_id
                WHERE ba.booking_id IN ($ph) ORDER BY ba.id", $ids) as $a) {
        // Car transfers are listed once, under Transfers, with their full name
        // ("Airport transfer, one way — Sedan × 2"); everything else under Extras.
        $line = $a['addon_name'] . ' × ' . (int) $a['quantity'];
        $is_transfer = str_starts_with((string) $a['code'], 'transfer') || str_contains(strtolower((string) $a['addon_name']), 'transfer');
        $guests[(int) $a['booking_id']][$is_transfer ? 'transfers' : 'addons'][] = $line;
    }
}
$guests = array_values($guests);
usort($guests, fn($a, $b) => [$a['start'], strtolower($a['guest'])] <=> [$b['start'], strtolower($b['guest'])]);

$data = [
    'guestRows' => $guests,
    'dates'   => $dates,
    'start'   => strtotime("$start 00:00") * 1000,
    'dayMs'   => 86400000,
    'now'     => time() * 1000,
    'ciTime'  => $ci_time,
    'coTime'  => $co_time,
    'groups'  => $groups,
];

$nav = fn($s, $dd = null) => '?' . http_build_query(['property' => $property_id, 'start' => $s, 'days' => $dd ?? $days]);
admin_head('Availability', $user);
?>
<?php if ($flash): ?><div class="notice notice--ok"><?= h($flash) ?></div><?php endif; ?>

<div class="tc-page">
<div class="tc-bar">
  <form method="get" class="tc-bar__left">
    <select name="property" onchange="this.form.submit()" aria-label="Property">
      <?php foreach (q("SELECT id, name FROM properties WHERE active = 1 ORDER BY id") as $p): ?>
        <option value="<?= $p['id'] ?>" <?= $property_id === (int) $p['id'] ? 'selected' : '' ?>><?= h($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="hidden" name="start" value="<?= h($start) ?>"><input type="hidden" name="days" value="<?= $days ?>">
  </form>
  <div class="tc-bar__nav">
    <a class="btn btn--plain btn--sm" href="<?= h($nav(date('Y-m-d', strtotime("$start -$days days")))) ?>">&larr;</a>
    <a class="btn btn--plain btn--sm" href="<?= h($nav(date('Y-m-d', strtotime('-1 day')))) ?>">Today</a>
    <a class="btn btn--plain btn--sm" href="<?= h($nav(date('Y-m-d', strtotime("$start +$days days")))) ?>">&rarr;</a>
    <strong><?= date('j M', strtotime($start)) ?> – <?= date('j M Y', strtotime($end . ' -1 day')) ?></strong>
  </div>
  <div class="tc-bar__days">
    <?php foreach ([7, 14, 30] as $n): ?>
      <a class="btn btn--sm <?= $n === $days ? '' : 'btn--plain' ?>" href="<?= h($nav($start, $n)) ?>"><?= $n ?> days</a>
    <?php endforeach; ?>
  </div>
</div>

<div class="tc-legend">
  <span><i class="tc-sw tc-sw--confirmed"></i>Booked</span>
  <span><i class="tc-sw tc-sw--pending"></i>Awaiting payment</span>
  <span><i class="tc-sw tc-sw--closed"></i>Not on sale</span>
  <span><i class="tc-sw tc-sw--over"></i>Overbooked</span>
  <span><i class="tc-sw tc-sw--now"></i>Now</span>
  <span class="tc-legend__note">Bars run from check-in (<?= h($ci_time) ?>) to check-out (<?= h($co_time) ?>).
    Click a booking for everything about it and to change it, add a transfer or take a payment. Hover a day for all of its arrivals and departures.</span>
</div>

<div class="tc" id="tc"><p class="tc__empty">Loading…</p></div>
<div class="tc-tip" id="tcTip" role="tooltip"></div>
<aside class="tc-drawer" id="tcDrawer" aria-hidden="true"><button class="tc-drawer__close" type="button" aria-label="Close">×</button><div class="tc-drawer__body"></div></aside>

</div>

<script>
(function () {
  const D = <?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  const box = document.getElementById('tc'), tip = document.getElementById('tcTip');
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const inr = n => '₹' + Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
  const day = s => new Date(s + 'T00:00:00').toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'short' });
  const todayISO = new Date(D.now - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
  const LABEL = 190, ROW = 30;
  const all = D.groups.flatMap(g => g.units.map(u => Object.assign({ cottage: g.name }, u)));

  function draw() {
    const width = Math.max(box.clientWidth - LABEL, D.dates.length * 70);
    const dayW = width / D.dates.length;
    const x = t => ((t - D.start) / D.dayMs) * dayW;
    const span = D.dates.length * D.dayMs;

    // Header: one column per day, with arrivals and departures.
    let head = `<div class="tc__head"><div class="tc__corner">Cottage</div><div class="tc__days" style="width:${width}px">`;
    D.dates.forEach((d, i) => {
      const ins = all.filter(u => u.ci === d).length, outs = all.filter(u => u.co === d).length;
      const wd = new Date(d + 'T00:00:00').getDay();
      head += `<div class="tc__day ${wd === 0 || wd === 6 ? 'is-weekend' : ''} ${d === todayISO ? 'is-today' : ''}"
                    style="left:${i * dayW}px;width:${dayW}px" data-day="${d}">
                 <b>${new Date(d + 'T00:00:00').toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })}</b>
                 <small>${new Date(d + 'T00:00:00').toLocaleDateString('en-IN', { weekday: 'short' })}
                   ${ins ? `<span class="in" title="${ins} check-in">↓${ins}</span>` : ''}${outs ? `<span class="out" title="${outs} check-out">↑${outs}</span>` : ''}</small></div>`;
    });
    head += '</div></div>';

    let body = '';
    D.groups.forEach(g => {
      // Room type row: how many are free each night.
      body += `<div class="tc__group"><div class="tc__label"><b>${esc(g.name)}</b><small>${g.total} cottages</small></div>
               <div class="tc__track" style="width:${width}px">` +
        D.dates.map((d, i) => `<div class="tc__free ${g.days[d].free === 0 ? 'is-full' : g.days[d].free <= 2 ? 'is-low' : ''}"
             style="left:${i * dayW}px;width:${dayW}px">${g.days[d].free} free</div>`).join('') + '</div></div>';

      for (let lane = 0; lane < g.lanes; lane++) {
        const over = lane >= g.total;
        body += `<div class="tc__row ${over ? 'is-over' : ''}"><div class="tc__label">${over ? 'Overbooked' : esc(g.name.replace(/ Cottage$| Swiss Tent$/, '')) + ' ' + (lane + 1)}</div>
                 <div class="tc__track" style="width:${width}px">`;
        // Day grid, and nights taken off sale (shown on the last rows of the type).
        D.dates.forEach((d, i) => {
          const closed = g.days[d].closed;
          if (!over && closed && lane >= g.total - closed) body += `<div class="tc__closed" style="left:${i * dayW}px;width:${dayW}px" title="Not on sale"></div>`;
        });
        g.units.filter(u => u.lane === lane).forEach(u => {
          const l = Math.max(0, x(u.start)), r = Math.min(width, x(u.end));
          if (r <= 0 || l >= width) return;
          const cutL = u.start < D.start, cutR = u.end > D.start + span;
          body += `<a class="tc__bar tc__bar--${u.status} ${cutL ? 'cut-l' : ''} ${cutR ? 'cut-r' : ''}" data-bid="${u.id}" href="booking.php?id=${u.id}"
                      style="left:${l}px;width:${Math.max(6, r - l)}px"
                      data-ref="${esc(u.ref)}" data-key="${g.id}:${lane}:${u.start}">
                     <span>${esc(u.guest)} · ${esc(u.room)}</span></a>`;
        });
        body += '</div></div>';
      }
    });

    const nowX = x(D.now);
    const now = D.now > D.start && D.now < D.start + span ? `<div class="tc__now" style="left:${LABEL + nowX}px"></div>` : '';
    box.innerHTML = `<div class="tc__inner" style="width:${LABEL + width}px">${head}${body}${now}</div>`;

    // Bars carry their own data for the hover card.
    const byKey = {};
    D.groups.forEach(g => g.units.forEach(u => byKey[`${g.id}:${u.lane}:${u.start}`] = Object.assign({ cottage: g.name }, u)));
    box.querySelectorAll('.tc__bar').forEach(el => el._u = byKey[el.dataset.key]);
  }

  const fmt = (dateStr, time) => `${day(dateStr)}, ${time}`;
  function barCard(u) {
    const due = Math.round((u.total - u.paid) * 100) / 100;
    return `<b>${esc(u.ref)}</b> · ${u.status === 'pending' ? 'awaiting payment' : 'booked'}
      <div>${esc(u.guest)}${u.phone ? ' · ' + esc(u.phone) : ''}</div>
      <div>${esc(u.cottage)} — ${esc(u.room)}</div>
      <div class="tc-tip__times"><span>Check in</span> ${fmt(u.ci, D.ciTime)}<br><span>Check out</span> ${fmt(u.co, D.coTime)}
        <br><span>Nights</span> ${u.nights}</div>
      <div>Total ${inr(u.total)} · paid ${inr(u.paid)}${due > 0.5 ? ` · <em>due ${inr(due)}</em>` : due < -0.5 ? ` · refund ${inr(-due)}` : ''}</div>
      <small>Click for details</small>`;
  }
  function dayCard(d) {
    const outs = all.filter(u => u.co === d), ins = all.filter(u => u.ci === d),
          stay = all.filter(u => u.ci < d && u.co > d);
    const list = (xs, empty) => xs.length ? xs.map(u => `<li>${esc(u.guest)} · ${esc(u.cottage.replace(/ Cottage$| Swiss Tent$/, ''))} · ${esc(u.room)} <small>${esc(u.ref)}</small></li>`).join('') : `<li class="none">${empty}</li>`;
    return `<b>${day(d)}</b>
      <div class="tc-tip__h">Check-outs by ${D.coTime} (${outs.length})</div><ul>${list(outs, 'None')}</ul>
      <div class="tc-tip__h">Check-ins from ${D.ciTime} (${ins.length})</div><ul>${list(ins, 'None')}</ul>
      <div class="tc-tip__h">Staying through (${stay.length})</div><ul>${list(stay, 'Nobody')}</ul>`;
  }
  function place(e) {
    const pad = 14, w = tip.offsetWidth, h = tip.offsetHeight;
    let left = e.clientX + pad, top = e.clientY + pad;
    if (left + w > window.innerWidth - 8) left = e.clientX - w - pad;
    if (top + h > window.innerHeight - 8) top = Math.max(8, window.innerHeight - h - 8);
    tip.style.left = left + 'px'; tip.style.top = top + 'px';
  }

  /* The side panel: everything about a guest, and what can be done. */
  const drawer = document.getElementById('tcDrawer');
  // Where the guest is in their stay today.
  function stayState(g) {
    const n = (a, b) => Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 864e5);
    if (todayISO < g.ci) { const d = n(todayISO, g.ci); return ['soon', d === 1 ? 'Arrives tomorrow' : `Arrives in ${d} days`]; }
    if (todayISO === g.ci) return ['today', 'Arrives today'];
    if (todayISO < g.co) return ['in', `Staying now — night ${n(g.ci, todayISO) + 1} of ${g.nights}`];
    if (todayISO === g.co) return ['out', 'Leaves today'];
    return ['gone', 'Checked out'];
  }
  function openDrawer(g) {
    if (!g) return;
    const due = Math.round((g.total - g.paid) * 100) / 100;
    const st = stayState(g);
    const tel = (g.phone || '').replace(/[^\d+]/g, ''), wa = (g.phone || '').replace(/\D/g, '');
    drawer.querySelector('.tc-drawer__body').innerHTML = `
      <p class="tc-drawer__kicker">${esc(g.ref)} · ${g.status === 'pending' ? 'awaiting payment' : 'booked'}</p>
      <h2>${esc(g.guest)}</h2>
      <p class="tc-drawer__state tc-drawer__state--${st[0]}">${st[1]}</p>
      <p class="tc-drawer__contact">${g.phone ? esc(g.phone) : 'No phone given'}${g.email ? '<br>' + esc(g.email) : ''}</p>
      <div class="tc-drawer__times">
        <div><span>Check in</span><b>${fmt(g.ci, D.ciTime)}</b>${g.arrival ? `<small>ETA: ${esc(g.arrival)}</small>` : ''}</div>
        <div><span>Check out</span><b>${fmt(g.co, D.coTime)}</b><small>${g.nights} night${g.nights > 1 ? 's' : ''}</small></div>
      </div>
      <h3>Rooms · ${g.guests} guest${g.guests === 1 ? '' : 's'}</h3>
      <ul class="tc-drawer__list">${g.rooms.map(r => `<li>${esc(r.cottage)} ${esc(r.room)} · ${r.guests} guest${r.guests === 1 ? '' : 's'}</li>`).join('')}</ul>
      <h3>Transfers</h3>
      <ul class="tc-drawer__list tc-drawer__transfers">${g.transfers.length ? g.transfers.map(t => `<li>${esc(t)}</li>`).join('') : '<li class="none">None</li>'}</ul>
      <h3>Extras</h3>
      <ul class="tc-drawer__list">${g.addons.length ? g.addons.map(a => `<li>${esc(a)}</li>`).join('') : '<li class="none">None</li>'}</ul>
      ${g.notes ? `<h3>Notes</h3><p class="tc-drawer__notes">${esc(g.notes)}</p>` : ''}
      <div class="tc-drawer__money">
        <div><span>Total</span><b>${inr(g.total)}</b></div>
        <div><span>Paid</span><b>${inr(g.paid)}</b></div>
        ${g.refund > 0.5 ? `<div class="is-refund"><span>Refund due</span><b>${inr(g.refund)}</b></div>`
          : due <= 0.5 ? `<div class="is-paid"><span>Balance</span><b>Fully paid</b></div>`
          : `${g.due_now > 0.5 ? `<div class="is-due"><span>Due now</span><b>${inr(g.due_now)}</b></div>` : ''}
             ${g.later > 0.5 ? `<div><span>Before arrival</span><b>${inr(g.later)}</b></div>` : ''}`}
      </div>
      <p class="tc-drawer__mode">${g.mode === 'advance' ? `Paying ${g.percent}% now, the rest before arrival` : 'Paying in full'}</p>
      <div class="tc-drawer__actions">
        <a class="btn btn--sm" href="edit.php?id=${g.id}">Change booking</a>
        <a class="btn btn--plain btn--sm" href="edit.php?id=${g.id}#extras">${g.transfers.length ? 'Change transfer' : 'Add a transfer'}</a>
        ${due > 0.5 ? `<a class="btn btn--plain btn--sm" href="booking.php?id=${g.id}#payments">Collect ${inr(g.due_now > 0.5 ? g.due_now : due)}</a>` : ''}
        <a class="btn btn--plain btn--sm" href="booking.php?id=${g.id}">Open booking</a>
        ${tel ? `<a class="btn btn--plain btn--sm" href="tel:${tel}">Call</a>` : ''}
        ${wa ? `<a class="btn btn--plain btn--sm" target="_blank" rel="noopener" href="https://wa.me/${wa.length === 10 ? '91' + wa : wa}">WhatsApp</a>` : ''}
      </div>`;
    drawer.classList.add('is-open'); drawer.setAttribute('aria-hidden', 'false');
  }
  function closeDrawer() {
    drawer.classList.remove('is-open'); drawer.setAttribute('aria-hidden', 'true');
  }
  drawer.querySelector('.tc-drawer__close').addEventListener('click', closeDrawer);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });
  box.addEventListener('click', e => {
    // A booking bar opens the side panel; Ctrl/Cmd-click still opens the booking page.
    const bar = e.target.closest('[data-bid]');
    if (bar && !e.ctrlKey && !e.metaKey) { e.preventDefault(); openDrawer(D.guestRows.find(g => g.id === +bar.dataset.bid)); }
  });

  box.addEventListener('mousemove', e => {
    const bar = e.target.closest('.tc__bar'), dayEl = e.target.closest('.tc__day');
    if (bar && bar._u) { tip.innerHTML = barCard(bar._u); tip.classList.add('is-on'); place(e); }
    else if (dayEl) { tip.innerHTML = dayCard(dayEl.dataset.day); tip.classList.add('is-on'); place(e); }
    else tip.classList.remove('is-on');
  });
  box.addEventListener('mouseleave', () => tip.classList.remove('is-on'));

  draw();
  let t; window.addEventListener('resize', () => { clearTimeout(t); t = setTimeout(draw, 120); });
  if (!all.length) box.insertAdjacentHTML('beforeend', '<p class="tc__empty">No bookings in these dates.</p>');
})();
</script>
<?php admin_foot(); ?>
