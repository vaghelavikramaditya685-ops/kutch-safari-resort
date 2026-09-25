/* ===========================================================================
   Booking engine — the whole guest journey in one file.

   Four steps:  rooms → extras → details → payment
   Each one renders into #stage; the running total lives in #summary.
   Everything server-side is a JSON call to /api.
   ======================================================================== */

(function () {
  'use strict';

  const $  = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
  const body = document.body;
  const PHONE_DISPLAY = (document.querySelector('.eng-header nav a[href^="tel:"]') || {}).textContent || '';
  const PHONE_TEL = ((document.querySelector('.eng-header nav a[href^="tel:"]') || {}).getAttribute
    ? document.querySelector('.eng-header nav a[href^="tel:"]').getAttribute('href').replace('tel:', '')
    : '');

  /* ---- The cart. One object; every screen reads and writes it. --------- */
  const cart = {
    property:  body.dataset.property,
    check_in:  body.dataset.checkin || '',
    check_out: body.dataset.checkout || '',
    adults:    parseInt(body.dataset.adults, 10) || 2,
    children:  parseInt(body.dataset.children, 10) || 0,
    rooms_wanted: parseInt(body.dataset.rooms, 10) || 1,
    rooms:  [],       // [{room_type_id, rate_plan_id, rooms, name, plan_name, price}]
    addons: [],       // [{addon_id, quantity, name, price, price_type}]
    payment_mode: 'full',
    coupon: '',
  };

  let available = null;   // last availability response
  let quote = null;       // last server-priced quote
  let step = 1;
  let booking = null;     // once created

  const rupees = n => '₹' + Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
  const niceDate = s => s ? new Date(s + 'T00:00:00').toLocaleDateString('en-IN',
      { weekday: 'short', day: 'numeric', month: 'short' }) : '—';

  /* Every call to the server goes through here.
     A booking engine must never sit on a spinner: if the network drops, the
     request is retried once, and if it still fails the guest is told plainly
     and offered a way to try again. */
  const REQUEST_TIMEOUT = 15000;

  async function request(path, data) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), REQUEST_TIMEOUT);
    try {
      const res = await fetch('api/' + path, Object.assign(
        { signal: controller.signal },
        data ? { method: 'POST', headers: { 'Content-Type': 'application/json' },
                 body: JSON.stringify(data) } : {}));
      clearTimeout(timer);
      if (!res.ok) return { ok: false, error: 'server-' + res.status, _network: true };
      return await res.json();
    } catch (err) {
      clearTimeout(timer);
      return { ok: false, error: err.name === 'AbortError' ? 'timeout' : 'offline', _network: true };
    }
  }

  async function api(path, data) {
    let out = await request(path, data);
    // One quiet retry — most failures are a momentary blip.
    if (out._network) {
      await new Promise(r => setTimeout(r, 900));
      out = await request(path, data);
    }
    return out;
  }

  /* Shown instead of an endless spinner. `retry` re-runs whatever failed. */
  function renderFailure(result, retry) {
    const why = {
      timeout: 'The connection is taking too long.',
      offline: 'We could not reach our booking system.',
    }[result.error] || 'Our booking system did not respond properly.';

    $('#stage').innerHTML = `
      <div class="soldout" style="text-align:left">
        <h2>Something went wrong at our end</h2>
        <p>${why} Your dates and choices have been kept — please try again.</p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
          <button class="btn" type="button" id="retryBtn">Try again</button>
          <a class="btn btn--plain" href="tel:${PHONE_TEL}">Call ${PHONE_DISPLAY}</a>
        </div>
        <p style="font-size:.84rem;color:var(--muted);margin-top:16px">
          If this keeps happening, call or WhatsApp us and we will book it for you in a minute.
        </p>
      </div>`;
    const b = $('#retryBtn');
    if (b && retry) b.addEventListener('click', retry);
  }

  function alertBox(message, kind) {
    $('#alert').innerHTML = message
      ? `<div class="notice notice--${kind || 'err'}">${message}</div>` : '';
    if (message) window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function setStep(n) {
    step = n;
    $$('#steps .step').forEach(el => {
      const s = parseInt(el.dataset.step, 10);
      el.classList.toggle('is-active', s === n);
      el.classList.toggle('is-done', s < n);
    });
  }

  function loading(msg) {
    $('#stage').innerHTML = `<div class="loading"><div class="spinner"></div>${msg || 'One moment…'}</div>`;
  }

  /* =====================================================================
     STEP 1 — availability
     ================================================================== */
  async function searchAvailability() {
    if (!cart.check_in || !cart.check_out) {
      $('#stage').innerHTML = `<div class="loading">Choose your dates to see what is available.</div>`;
      renderSummary();
      return;
    }
    alertBox('');
    loading('Checking what is free on those dates…');

    const params = new URLSearchParams({
      property: cart.property, check_in: cart.check_in, check_out: cart.check_out,
      adults: cart.adults, children: cart.children, rooms: cart.rooms_wanted,
    });
    const res = await api('availability.php?' + params);

    // A dropped connection is not the same as "no rooms" — say so, and offer a retry.
    if (res._network) { renderFailure(res, searchAvailability); return; }

    if (!res.ok) {
      available = null;
      $('#stage').innerHTML = `<div class="soldout"><h2>We cannot book those dates</h2><p>${res.error}</p></div>`;
      renderSummary();
      return;
    }

    available = res;
    cart.rooms = [];          // a new search clears the old selection
    setStep(1);
    renderRooms();
    renderSummary();
  }

  function renderRooms() {
    const r = available;
    if (!r.results.length) {
      const chips = (r.next_available || []).map(d =>
        `<button type="button" data-in="${d.check_in}" data-out="${d.check_out}">${d.label} · from ${rupees(d.from_price)}</button>`
      ).join('');
      $('#stage').innerHTML = `
        <div class="soldout">
          <h2>Fully booked on those dates</h2>
          <p>We have nothing free for ${niceDate(r.check_in)} to ${niceDate(r.check_out)}.
             Call us on ${r.property.phone} — we often hold a room back — or try one of these:</p>
          ${chips ? `<div class="dates">${chips}</div>` : ''}
        </div>`;
      $$('.soldout .dates button').forEach(b => b.addEventListener('click', () => {
        $('#f-in').value = cart.check_in = b.dataset.in;
        $('#f-out').value = cart.check_out = b.dataset.out;
        searchAvailability();
      }));
      return;
    }

    $('#stage').innerHTML = `
      <h2>${r.results.length} option${r.results.length > 1 ? 's' : ''} for ${r.nights} night${r.nights > 1 ? 's' : ''}</h2>
      <p style="font-size:.86rem;color:var(--muted);margin-bottom:18px">
        Prices are for ${r.rooms_wanted} room${r.rooms_wanted > 1 ? 's' : ''},
        ${r.adults} guest${r.adults > 1 ? 's' : ''}, and include taxes.
      </p>
      ${r.results.map(roomCard).join('')}`;

    wireRoomCards();
  }

  function roomCard(room) {
    const img = room.images[0] || '';
    const shown = room.amenities.slice(0, 6);
    const rest = room.amenities.length - shown.length;

    return `
      <article class="room" data-room="${room.id}">
        <div class="room__top">
          <div class="room__gallery">
            ${img ? `<img src="${img}" alt="${room.name}" loading="lazy">` : ''}
          </div>
          <div class="room__info">
            <h3>${room.name}</h3>
            <div class="room__meta">
              <span>Up to ${room.max_adults} adults</span>
              ${room.bed_type ? `<span>${room.bed_type}</span>` : ''}
              ${room.size_label ? `<span>${room.size_label}</span>` : ''}
            </div>
            <p class="room__desc">${room.description || ''}</p>
            <ul class="chips">
              ${shown.map(a => `<li>${a}</li>`).join('')}
              ${rest > 0 ? `<li><button class="more" type="button" data-more="${room.id}">+${rest} more</button></li>` : ''}
            </ul>
            ${room.low_stock ? `<p class="stock">Only ${room.rooms_left} left for these dates</p>` : ''}
          </div>
        </div>
        ${room.rate_plans.map(p => `
          <div class="plan">
            <div>
              <div class="plan__name">${p.name}</div>
              ${p.meal_note ? `<div class="plan__note">${p.meal_note}</div>` : ''}
            </div>
            <div class="plan__price">
              <b>${rupees(p.total)}</b>
              <span>${rupees(p.per_night_with_tax)} per night · incl. taxes</span>
            </div>
            <button class="btn btn--sm" type="button"
                    data-add="${room.id}" data-plan="${p.id}">Select</button>
          </div>`).join('')}
      </article>`;
  }

  function wireRoomCards() {
    $$('[data-more]').forEach(b => b.addEventListener('click', () => {
      const room = available.results.find(r => r.id === +b.dataset.more);
      const ul = b.closest('.chips');
      ul.innerHTML = room.amenities.map(a => `<li>${a}</li>`).join('');
    }));

    $$('[data-add]').forEach(b => b.addEventListener('click', () => {
      const room = available.results.find(r => r.id === +b.dataset.add);
      const plan = room.rate_plans.find(p => p.id === +b.dataset.plan);
      cart.rooms = [{
        room_type_id: room.id, rate_plan_id: plan.id, rooms: cart.rooms_wanted,
        name: room.name, plan_name: plan.name, price: plan.total,
      }];
      $$('.room').forEach(el => el.style.outline = '');
      b.closest('.room').style.outline = '2px solid var(--clay)';
      renderSummary();
      goToAddons();
    }));
  }

  /* =====================================================================
     STEP 2 — extras
     ================================================================== */
  function goToAddons() {
    setStep(2);
    const list = available.addons || [];
    if (!list.length) return goToDetails();

    // A per-person extra starts at the number of guests — that is what people mean.
    const startQty = a => 0;

    $('#stage').innerHTML = `
      <h2>Anything to add?</h2>
      <p style="font-size:.86rem;color:var(--muted);margin-bottom:18px">
        All optional, and all can be arranged later too.</p>
      ${list.map(a => {
        const unit = { per_person: 'per person', per_night: 'per night',
                       per_room_night: 'per room, per night' }[a.price_type] || 'per booking';
        const hint = a.price_type === 'per_person' ? 'Choose how many people' :
                     a.price_type === 'per_room_night' ? 'Choose how many rooms' : '';
        return `
        <div class="addon" data-addon="${a.id}">
          <div>
            <h3>${a.name}</h3>
            <p>${a.description || ''}</p>
            ${hint ? `<p style="font-size:.76rem;color:var(--muted)">${hint}</p>` : ''}
          </div>
          <div>
            <div class="addon__price">${rupees(a.price)}<small>${unit}</small></div>
            <div class="qty" style="margin-top:8px">
              <button type="button" data-step-addon="-1">−</button>
              <span data-qty="${a.id}">${startQty(a)}</span>
              <button type="button" data-step-addon="1">+</button>
            </div>
          </div>
        </div>`;
      }).join('')}
      <div style="display:flex;gap:12px;margin-top:20px">
        <button class="btn btn--plain" type="button" id="backRooms">Back</button>
        <button class="btn" type="button" id="toDetails">Continue</button>
      </div>`;

    $$('[data-step-addon]').forEach(btn => btn.addEventListener('click', () => {
      const wrap = btn.closest('.addon');
      const id = +wrap.dataset.addon;
      const span = $(`[data-qty="${id}"]`);
      const next = Math.max(0, (+span.textContent) + (+btn.dataset.stepAddon));
      span.textContent = next;

      const info = list.find(a => a.id === id);
      cart.addons = cart.addons.filter(a => a.addon_id !== id);
      if (next > 0) cart.addons.push({ addon_id: id, quantity: next, name: info.name });
      renderSummary();
    }));

    $('#backRooms').addEventListener('click', () => { setStep(1); renderRooms(); });
    $('#toDetails').addEventListener('click', goToDetails);
  }

  /* =====================================================================
     STEP 3 — guest details, priced by the server
     ================================================================== */
  async function goToDetails() {
    setStep(3);
    loading('Working out your total…');

    quote = await api('quote.php', cart);
    if (quote._network) { renderFailure(quote, goToDetails); return; }
    if (!quote.ok) { alertBox(quote.error); setStep(1); renderRooms(); return; }
    renderSummary();

    const modes = Object.entries(quote.payment_modes).map(([key, m]) => `
      <label class="paymode ${key === cart.payment_mode ? 'is-on' : ''}">
        <input type="radio" name="payment_mode" value="${key}" ${key === cart.payment_mode ? 'checked' : ''}>
        <b>${m.label}</b>
        ${key === 'advance' ? `<b> — ${rupees(quote.total * m.percent / 100)}</b>` : ''}
        <small>${m.note}</small>
      </label>`).join('');

    $('#stage').innerHTML = `
      <h2>Who is coming?</h2>
      <div class="panel">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="field"><label for="g-name">Full name</label><input id="g-name" required></div>
          <div class="field"><label for="g-phone">Mobile number</label><input id="g-phone" type="tel" required></div>
          <div class="field" style="grid-column:1/-1"><label for="g-email">Email</label>
            <input id="g-email" type="email"><span class="hint">Your confirmation is sent here.</span></div>
          <div class="field"><label for="g-city">City</label><input id="g-city"></div>
          <div class="field"><label for="g-arrival">Approximate arrival time</label>
            <input id="g-arrival" placeholder="e.g. 4 pm"></div>
          <div class="field" style="grid-column:1/-1"><label for="g-notes">Anything we should know</label>
            <textarea id="g-notes" placeholder="Dietary preferences, a celebration, an early check-in."></textarea></div>
        </div>
      </div>

      <h2 style="margin-top:26px">How would you like to pay?</h2>
      <div class="panel">${modes}</div>

      <div class="panel" style="border-left-color:var(--muted)">
        <h3 style="font-size:.95rem">If you need to cancel</h3>
        <table style="width:100%;font-size:.84rem">
          ${quote.cancellation.map(c => `<tr>
            <td style="padding:3px 0;color:var(--muted)">
              ${c.charge_percent === 0 ? 'Up to' : 'From'} ${niceDate(c.until)}</td>
            <td style="padding:3px 0;text-align:right;color:var(--ink)">
              ${c.charge_percent === 0 ? 'No charge' : c.charge_percent + '% of the total'}</td></tr>`).join('')}
        </table>
      </div>

      <div style="display:flex;gap:12px;margin-top:20px">
        <button class="btn btn--plain" type="button" id="backAddons">Back</button>
        <button class="btn" type="button" id="toPayment">Continue to payment</button>
      </div>`;

    $$('input[name="payment_mode"]').forEach(r => r.addEventListener('change', async () => {
      cart.payment_mode = r.value;
      $$('.paymode').forEach(l => l.classList.toggle('is-on', l.contains(r) && r.checked));
      quote = await api('quote.php', cart);
      renderSummary();
    }));

    $('#backAddons').addEventListener('click', goToAddons);
    $('#toPayment').addEventListener('click', createBooking);
  }

  /* =====================================================================
     STEP 4 — create the booking, then take the money
     ================================================================== */
  async function createBooking() {
    const guest = {
      name:  $('#g-name').value.trim(),
      phone: $('#g-phone').value.trim(),
      email: $('#g-email').value.trim(),
      city:  $('#g-city').value.trim(),
      arrival_time: $('#g-arrival').value.trim(),
      special_requests: $('#g-notes').value.trim(),
    };
    if (!guest.name || !guest.phone) { alertBox('Please give your name and mobile number.'); return; }

    alertBox('');
    loading('Holding your room…');

    const res = await api('book.php', Object.assign({}, cart, { guest }));
    if (res._network) { renderFailure(res, () => createBooking()); return; }
    if (!res.ok) { alertBox(res.error); setStep(1); renderRooms(); return; }

    booking = res;
    setStep(4);

    if (res.next === 'done') return renderConfirmed(res, 'We have your booking.');
    renderPaymentChoice(res);
  }

  function renderPaymentChoice(res) {
    const canCard = res.methods && res.methods.razorpay;
    const canUpi  = res.methods && res.methods.upi_qr;

    $('#stage').innerHTML = `
      <h2>Payment</h2>
      <div class="notice notice--info">
        Your booking <strong>${res.ref}</strong> is held while you pay.
        Payable now: <strong>${rupees(res.amount_due_now)}</strong>
        ${res.amount_due_now < res.total
          ? ` — the balance of ${rupees(res.total - res.amount_due_now)} is due later.` : ''}
      </div>

      ${(!canCard && !canUpi) ? `
        <div class="panel">
          <h3>Online payment is not switched on yet</h3>
          <p>Your booking is saved as <strong>${res.ref}</strong> and our reservations team will
             call you to take payment. You can also call us now.</p>
        </div>` : `
        <div class="paymethod">
          ${canCard ? `<button class="btn" type="button" id="payCard">Pay by card, UPI or netbanking</button>` : ''}
          ${canUpi  ? `<button class="btn btn--ghost" type="button" id="payUpi">Pay by UPI QR code</button>` : ''}
        </div>
        ${canCard ? `<p style="font-size:.82rem;color:var(--muted)">
          Card, UPI, netbanking and wallets are handled by Razorpay. Your booking confirms the moment
          the payment succeeds.</p>` : ''}
        ${canUpi ? `<p style="font-size:.82rem;color:var(--muted)">
          The QR code pays our bank account directly. We confirm your booking as soon as the payment
          shows in our account, usually within the hour.</p>` : ''}
      `}

      <div id="payArea" style="margin-top:18px"></div>`;

    if (canCard) $('#payCard').addEventListener('click', payWithRazorpay);
    if (canUpi)  $('#payUpi').addEventListener('click', payWithUpi);
  }

  async function payWithRazorpay() {
    const order = await api('payment-create.php', { booking_id: booking.booking_id, method: 'razorpay' });
    if (order._network) { alertBox('We could not start the payment — please check your connection and try again.'); return; }
    if (!order.ok) { alertBox(order.error); return; }

    const rzp = new Razorpay({
      key: order.key_id,
      amount: order.amount_paise,
      currency: order.currency,
      name: available.property.name,
      description: 'Booking ' + order.booking_ref,
      order_id: order.order_id,
      prefill: order.prefill,
      theme: { color: getComputedStyle(body).getPropertyValue('--clay').trim() },
      handler: async function (r) {
        loading('Confirming your payment…');
        const v = await api('payment-verify.php', {
          razorpay_order_id: r.razorpay_order_id,
          razorpay_payment_id: r.razorpay_payment_id,
          razorpay_signature: r.razorpay_signature,
        });
        if (v.ok) renderConfirmed(Object.assign({}, booking, v), 'Payment received. Your stay is confirmed.');
        else { alertBox(v.error); renderPaymentChoice(booking); }
      },
      modal: { ondismiss: () => renderPaymentChoice(booking) },
    });
    rzp.open();
  }

  async function payWithUpi() {
    const upi = await api('payment-create.php', { booking_id: booking.booking_id, method: 'upi_qr' });
    if (upi._network) { alertBox('We could not generate the QR code — please check your connection and try again.'); return; }
    if (!upi.ok) { alertBox(upi.error); return; }

    $('#payArea').innerHTML = `
      <div class="qr-box">
        <canvas id="qr"></canvas>
        <p style="margin-bottom:6px"><strong>${rupees(upi.amount)}</strong> to ${upi.payee}</p>
        <p style="font-size:.84rem;color:var(--muted)">${upi.vpa}</p>
        <p style="font-size:.84rem;margin:14px 0 6px">Quote this reference if you call us:</p>
        <p class="qr-ref">${upi.reference}</p>
        <p style="font-size:.82rem;color:var(--muted);margin-top:16px">${upi.instructions}</p>
        <a class="btn btn--ghost btn--sm" style="margin-top:10px" href="${upi.intent}">Open a UPI app on this phone</a>
      </div>`;

    new QRious({ element: $('#qr'), value: upi.intent, size: 220, level: 'M' });
    $('#payArea').scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function renderConfirmed(res, headline) {
    setStep(4);
    $$('#steps .step').forEach(el => el.classList.add('is-done'));
    $('#stage').innerHTML = `
      <div class="soldout" style="text-align:left">
        <h2>${headline}</h2>
        <p>Your reference is <strong style="letter-spacing:1px">${res.ref}</strong>.
           Keep it — you will need it to look the booking up or change it.</p>
        <p>${available.property.name} · ${niceDate(cart.check_in)} to ${niceDate(cart.check_out)}</p>
        <p style="font-size:.86rem;color:var(--muted)">
          A confirmation is on its way to your email. If it has not arrived in ten minutes,
          check your spam folder or call us on ${available.property.phone}.</p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px">
          <a class="btn" href="manage.php?ref=${encodeURIComponent(res.ref)}">View my booking</a>
          <a class="btn btn--plain" href="${document.querySelector('.eng-brand').getAttribute('href')}">Back to the website</a>
        </div>
      </div>`;
    $('#searchForm').style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  /* =====================================================================
     The running summary
     ================================================================== */
  function renderSummary() {
    const box = $('#summary');
    if (!cart.rooms.length) {
      box.innerHTML = `<h3>Your booking</h3>
        <div class="summary__dates">
          <div><span>Check in</span><b>${niceDate(cart.check_in)}</b></div>
          <div><span>Check out</span><b>${niceDate(cart.check_out)}</b></div>
        </div>
        <p class="summary__empty">Nothing chosen yet.</p>`;
      return;
    }

    const q = quote && quote.ok ? quote : null;
    const lines = [];

    cart.rooms.forEach(r => lines.push(
      `<div class="sline"><span>${r.rooms} × ${r.name}<small>${r.plan_name}</small></span>
       <span>${rupees(r.price)}</span></div>`));

    if (q) {
      q.addons.forEach(a => lines.push(
        `<div class="sline"><span>${a.addon_name} × ${a.quantity}</span><span>${rupees(a.subtotal)}</span></div>`));
      if (q.discount > 0) lines.push(
        `<div class="sline" style="color:var(--ok)"><span>Discount</span><span>− ${rupees(q.discount)}</span></div>`);
      lines.push(`<div class="sline"><span>Taxes</span><span>${rupees(q.tax_amount)}</span></div>`);
      lines.push(`<div class="sline sline--total"><span>Total</span><span>${rupees(q.total)}</span></div>`);
      if (q.amount_due_now < q.total) {
        lines.push(`<div class="sline sline--due"><span>Pay now</span><span>${rupees(q.amount_due_now)}</span></div>`);
        lines.push(`<div class="sline"><span style="color:var(--muted)">Balance later</span>
                    <span style="color:var(--muted)">${rupees(q.balance_later)}</span></div>`);
      }
    } else {
      cart.addons.forEach(a => lines.push(
        `<div class="sline"><span>${a.name} × ${a.quantity}</span><span style="color:var(--muted)">added</span></div>`));
      lines.push(`<div class="sline" style="color:var(--muted)"><span>Taxes</span><span>calculated next</span></div>`);
    }

    box.innerHTML = `
      <h3>Your booking</h3>
      <div class="summary__dates">
        <div><span>Check in</span><b>${niceDate(cart.check_in)}</b></div>
        <div><span>Check out</span><b>${niceDate(cart.check_out)}</b></div>
      </div>
      ${lines.join('')}`;
  }

  /* =====================================================================
     Wiring
     ================================================================== */
  $('#searchForm').addEventListener('submit', e => {
    e.preventDefault();
    cart.check_in  = $('#f-in').value;
    cart.check_out = $('#f-out').value;
    cart.adults    = parseInt($('#f-guests').value, 10);
    cart.rooms_wanted = parseInt($('#f-rooms').value, 10);
    searchAvailability();
  });

  // Check-out is always after check-in.
  // Dates are built from local parts: toISOString() is UTC, which in India
  // (UTC+5:30) turns local midnight into the previous day.
  const isoLocal = d => [d.getFullYear(), String(d.getMonth() + 1).padStart(2, '0'),
    String(d.getDate()).padStart(2, '0')].join('-');
  $('#f-in').addEventListener('change', () => {
    if (!$('#f-in').value) return;
    const next = new Date($('#f-in').value + 'T00:00:00');
    next.setDate(next.getDate() + 1);
    const min = isoLocal(next);
    $('#f-out').min = min;
    if (!$('#f-out').value || $('#f-out').value <= $('#f-in').value) $('#f-out').value = min;
  });

  // Refresh the room list as soon as the search changes, while still choosing a room.
  ['#f-in', '#f-out', '#f-guests', '#f-rooms'].forEach(sel => $(sel).addEventListener('change', () => {
    if (step === 1 && $('#f-in').value && $('#f-out').value > $('#f-in').value) $('#searchForm').requestSubmit();
  }));

  renderSummary();
  if (cart.check_in && cart.check_out) searchAvailability();
})();
