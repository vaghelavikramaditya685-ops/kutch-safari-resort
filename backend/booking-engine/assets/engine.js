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
  // Sample mode (config rules.guest_details_optional): guest details may be left blank.
  const GUEST_OPTIONAL = body.dataset.guestOptional === '1';
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
    // Guests sleeping in each room, Room 1 first: 1 Single, 2 Double, 3 Triple.
    occupancy: (body.dataset.occupancy || '2').split(',').map(n => parseInt(n, 10) || 2),
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
  /* The receipt opens in a new tab as a page (document.php), never as a download. */
  const receiptUrl = (ref, token) => 'document.php?' + new URLSearchParams({ doc: 'receipt', ref, token });
  const termsUrl = () => 'document.php?' + new URLSearchParams({ doc: 'terms', property: body.dataset.property });
  const LAST_KEY = 'ksr_last_booking';
  // Today as YYYY-MM-DD in the guest's own time zone (toISOString alone is UTC).
  const todayISO = () => new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);

  /* "Room 1 Double, Room 2 Single" from [{guests|label}] or [2,1]. */
  const occupancyText = list => (list || []).map((o, i) => {
    const g = typeof o === 'number' ? o : o.guests;
    return `Room ${i + 1} ${({ 1: 'Single', 2: 'Double', 3: 'Triple' })[g] || g + ' guests'}`;
  }).join(', ');
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

  /* Each new step starts at the top, so the dates, rooms and guests stay in view
     instead of being left above the screen after pressing Select further down. */
  function showStepTop() {
    const top = $('#steps').getBoundingClientRect().top + window.scrollY - 80;
    if (window.scrollY > top) window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
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
    quote = null;
    loading('Checking what is free on those dates…');

    const params = new URLSearchParams({
      property: cart.property, check_in: cart.check_in, check_out: cart.check_out,
      rooms: cart.rooms_wanted, occupancy: cart.occupancy.join(','),
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
    // A new search clears the old selection: one empty pick per room.
    cart.picks = new Array(cart.rooms_wanted).fill(null);
    cart.rooms = [];
    openPicker = null;
    setStep(1);
    renderRooms();
    renderSummary();
  }

  /* ---------------------------------------------------------------------
     Each room can be a different cottage: Room 1 Kutchi (Double), Room 2
     Deluxe (Single). Every cottage has one Select button; with several rooms
     it opens that cottage's room numbers to tick. cart.picks holds one choice
     per room; cart.rooms is what goes to the server — one line per room, in
     room order, so each line lines up with its occupancy.
     ------------------------------------------------------------------ */
  const OCC_LABEL = { 1: 'Single', 2: 'Double', 3: 'Triple' };
  const occLabel = g => OCC_LABEL[g] || `${g} guests`;
  let openPicker = null;   // "roomTypeId:planId" whose room numbers are showing

  function syncRooms() {
    cart.rooms = cart.picks.filter(Boolean).map(p => ({
      room_type_id: p.room_type_id, rate_plan_id: p.rate_plan_id, rooms: 1,
      name: p.name, plan_name: p.plan_name, price: p.price,
    }));
  }

  function pickFor(i, room, plan) {
    cart.picks[i] = {
      room_type_id: room.id, rate_plan_id: plan.id, name: room.name,
      plan_name: `${plan.name} — Room ${i + 1} ${occLabel(cart.occupancy[i])}`,
      price: plan.units[i].total,
    };
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

    const n = cart.rooms_wanted;
    const done = cart.picks.filter(Boolean).length;
    $('#stage').innerHTML = `
      <h2>${r.results.length} option${r.results.length > 1 ? 's' : ''} for ${r.nights} night${r.nights > 1 ? 's' : ''}</h2>
      <p style="font-size:.86rem;color:var(--muted);margin-bottom:18px">
        ${n > 1
          ? `You are booking ${n} rooms (${cart.occupancy.map((g, i) => `Room ${i + 1} ${occLabel(g)}`).join(', ')}).
             Press Select on a cottage and tick the rooms it is for — each room can be a different cottage.
             Prices are per room for the whole stay and include taxes.`
          : `Prices are for one room, ${occLabel(cart.occupancy[0])}, and include taxes.`}
      </p>
      ${r.results.map(roomCard).join('')}
      ${n > 1 ? `
        <div class="assignbar">
          <span><b>${done} of ${n} rooms chosen</b>
            ${done < n ? ` · still to choose: ${cart.picks.map((p, i) => p ? null : `Room ${i + 1}`).filter(Boolean).join(', ')}` : ''}</span>
          <button class="btn" type="button" id="roomsDone" ${done < n ? 'disabled' : ''}>Continue</button>
        </div>` : ''}`;

    wireRoomCards();
  }

  function roomCard(room) {
    const n = cart.rooms_wanted;
    const img = room.images[0] || '';
    const shown = room.amenities.slice(0, 6);
    const rest = room.amenities.length - shown.length;
    const mine = cart.picks.filter(p => p && p.room_type_id === room.id).length;

    return `
      <article class="room" data-room="${room.id}" ${mine ? 'style="outline:2px solid var(--clay)"' : ''}>
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
            ${room.rooms_left <= 3 ? `<p class="stock">Only ${room.rooms_left} left for these dates</p>` : ''}
          </div>
        </div>
        ${room.rate_plans.map(p => {
          const key = `${room.id}:${p.id}`;
          const prices = p.units.filter((u, i) => room.fits[i]).map(u => u.total);
          const low = Math.min(...prices), high = Math.max(...prices);
          const chosenHere = cart.picks.filter(c => c && c.room_type_id === room.id && c.rate_plan_id === p.id).length;
          return `
          <div class="plan">
            <div>
              <div class="plan__name">${p.name}</div>
              ${p.meal_note ? `<div class="plan__note">${p.meal_note}</div>` : ''}
            </div>
            <div class="plan__price">
              ${n > 1
                ? `<b>${low === high ? rupees(low) : `${rupees(low)} – ${rupees(high)}`}</b>
                   <span>per room · ${available.nights} night${available.nights > 1 ? 's' : ''} · incl. taxes</span>`
                : `<b>${rupees(p.total)}</b>
                   <span>${rupees(p.total / Math.max(1, available.nights))} per night · incl. taxes</span>`}
            </div>
            <button class="btn btn--sm ${openPicker === key ? 'btn--ghost' : ''}" type="button"
                    data-select="${room.id}" data-plan="${p.id}" aria-expanded="${openPicker === key}">
              ${n > 1 && chosenHere ? `Selected · ${chosenHere} room${chosenHere > 1 ? 's' : ''}` : 'Select'}</button>
          </div>
          ${n > 1 && openPicker === key ? assignPanel(room, p) : ''}`;
        }).join('')}
      </article>`;
  }

  /* The room numbers for one cottage: tick the rooms it is for. */
  function assignPanel(room, plan) {
    // Rooms of this cottage already given to other rooms count against what is left.
    const used = cart.picks.filter(c => c && c.room_type_id === room.id).length;
    // A room that already has another cottage is not offered here — the guest
    // unticks it under that cottage first. Only free rooms and this cottage's own show.
    const rooms = cart.occupancy.map((g, i) => ({ g, i, pick: cart.picks[i] }))
      .filter(r => !r.pick || (r.pick.room_type_id === room.id && r.pick.rate_plan_id === plan.id));
    if (!rooms.length) {
      return `
        <div class="assign" data-assign="${room.id}" data-plan="${plan.id}">
          <p>Every room already has a cottage. To change one, untick it under the cottage it is on now.</p>
        </div>`;
    }
    return `
      <div class="assign" data-assign="${room.id}" data-plan="${plan.id}">
        <p>Which room${rooms.length > 1 ? 's' : ''} is ${room.name} for?</p>
        <div class="assign__rooms">
          ${rooms.map(({ g, i, pick }) => {
            const here = Boolean(pick);
            const full = !here && used >= room.rooms_left;
            const can = room.fits[i] && !full;
            return `
              <label class="assign__room ${here ? 'is-on' : ''} ${can ? '' : 'is-off'}">
                <input type="checkbox" data-room-no="${i}" ${here ? 'checked' : ''} ${can ? '' : 'disabled'}>
                <span><b>Room ${i + 1} · ${occLabel(g)}</b>
                  <small>${!room.fits[i] ? 'Too many guests for this cottage'
                    : full ? `No more ${room.name} free`
                    : rupees(plan.units[i].total)}</small></span>
              </label>`;
          }).join('')}
        </div>
      </div>`;
  }

  function wireRoomCards() {
    $$('[data-more]').forEach(b => b.addEventListener('click', () => {
      const room = available.results.find(r => r.id === +b.dataset.more);
      const ul = b.closest('.chips');
      ul.innerHTML = room.amenities.map(a => `<li>${a}</li>`).join('');
    }));

    const find = (roomId, planId) => {
      const room = available.results.find(r => r.id === +roomId);
      return [room, room.rate_plans.find(p => p.id === +planId)];
    };

    $$('[data-select]').forEach(b => b.addEventListener('click', () => {
      const [room, plan] = find(b.dataset.select, b.dataset.plan);
      // One room: Select books it straight away, as before.
      if (cart.rooms_wanted === 1) {
        pickFor(0, room, plan);
        syncRooms();
        renderSummary();
        return goToAddons();
      }
      // Several rooms: show (or hide) this cottage's room numbers.
      const key = `${room.id}:${plan.id}`;
      openPicker = openPicker === key ? null : key;
      renderRooms();
      const panel = $(`.assign[data-assign="${room.id}"][data-plan="${plan.id}"]`);
      if (panel) panel.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }));

    // Ticking a room gives it this cottage (taking it from any other); unticking frees it.
    $$('.assign input[data-room-no]').forEach(cb => cb.addEventListener('change', () => {
      const panel = cb.closest('.assign');
      const [room, plan] = find(panel.dataset.assign, panel.dataset.plan);
      const i = +cb.dataset.roomNo;
      if (cb.checked) pickFor(i, room, plan); else cart.picks[i] = null;
      syncRooms();
      renderSummary();
      const y = window.scrollY;
      renderRooms();
      window.scrollTo(0, y);
    }));

    const done = $('#roomsDone');
    if (done) done.addEventListener('click', () => { if (cart.picks.every(Boolean)) goToAddons(); });
  }

  /* =====================================================================
     STEP 2 — extras
     ================================================================== */
  function goToAddons() {
    setStep(2);
    showStepTop();
    quote = null;   // the cart is about to change; the old server total no longer applies
    const list = available.addons || [];
    if (!list.length) return goToDetails();

    // Every room in the booking, numbered the way the server numbers them.
    const roomList = [];
    cart.rooms.forEach(r => { for (let i = 0; i < r.rooms; i++) roomList.push(r.name); });

    // Keep what the guest already picked if they come back to this step,
    // dropping rooms that no longer exist after a change of room count.
    cart.addons = cart.addons.map(a => {
      if (!a.rooms) return a;
      const rooms = a.rooms.filter(n => n <= roomList.length);
      return { ...a, rooms, quantity: rooms.length };
    }).filter(a => a.quantity > 0);
    const chosen = id => cart.addons.find(a => a.addon_id === id);

    // Extras named "Group — Choice" (Airport transfer, one way — Sedan / Ertiga / Innova)
    // share one card, with a row and a counter for each choice.
    const groupOf = a => a.name.includes(' — ') ? a.name.split(' — ')[0] : null;
    const groupSize = {};
    list.forEach(a => { const g = groupOf(a); if (g) groupSize[g] = (groupSize[g] || 0) + 1; });
    const cards = [];
    const groups = {};
    list.forEach(a => {
      const g = groupOf(a);
      if (!g || groupSize[g] < 2) return cards.push(a);
      if (!groups[g]) cards.push(groups[g] = { group: g, items: [] });
      groups[g].items.push(a);
    });
    const counter = id => `
      <div class="qty">
        <button type="button" data-step-addon="-1" aria-label="Fewer">−</button>
        <span data-qty="${id}">${(chosen(id) || {}).quantity || 0}</span>
        <button type="button" data-step-addon="1" aria-label="More">+</button>
      </div>`;
    const groupCard = g => `
      <div class="addon addon--group">
        <div>
          <h3>${g.group}</h3>
          <p>${g.items[0].description || ''}</p>
          <p style="font-size:.76rem;color:var(--muted)">Choose the car and how many you need.</p>
        </div>
        <div class="variants">
          ${g.items.map(v => `
            <div class="variant" data-addon="${v.id}">
              <span class="variant__name">${v.name.split(' — ')[1]}</span>
              <span class="variant__price">${rupees(v.price)}<small> each</small></span>
              ${counter(v.id)}
            </div>`).join('')}
        </div>
      </div>`;

    $('#stage').innerHTML = `
      <h2>Anything to add?</h2>
      <p style="font-size:.86rem;color:var(--muted);margin-bottom:18px">
        All optional, and all can be arranged later too.</p>
      ${cards.map(c => c.group ? groupCard(c) : (a => {
        const unit = { per_person: 'per person', per_night: 'per night',
                       per_room_night: 'per room, per night' }[a.price_type] || 'per booking';
        const price = `<div class="addon__price">${rupees(a.price)}<small>${unit}</small></div>`;

        if (a.per_room) {
          const picked = (chosen(a.id) || {}).rooms || [];
          return `
          <div class="addon addon--rooms" data-addon="${a.id}">
            <div>
              <h3>${a.name}</h3>
              <p>${a.description || ''}</p>
              <p style="font-size:.76rem;color:var(--muted)">${roomList.length > 1
                ? 'One per room. Tick the rooms that need one.' : 'One per room.'}</p>
            </div>
            <div>${price}</div>
            <div class="roompick">
              ${roomList.map((name, i) => `
                <label class="roompick__room ${picked.includes(i + 1) ? 'is-on' : ''}">
                  <input type="checkbox" data-room="${i + 1}" ${picked.includes(i + 1) ? 'checked' : ''}>
                  <span><b>Room ${i + 1}</b><small>${name}</small></span>
                </label>`).join('')}
            </div>
          </div>`;
        }

        const hint = a.price_type === 'per_person' && a.min_quantity > 1 ? `For groups of ${a.min_quantity} or more — choose how many people` :
                     a.price_type === 'per_person' ? 'Choose how many people' :
                     a.price_type === 'per_room_night' ? 'Choose how many rooms' : '';
        return `
        <div class="addon" data-addon="${a.id}">
          <div>
            <h3>${a.name}</h3>
            <p>${a.description || ''}</p>
            ${hint ? `<p style="font-size:.76rem;color:var(--muted)">${hint}</p>` : ''}
          </div>
          <div>
            ${price}
            <div class="qty" style="margin-top:8px">
              <button type="button" data-step-addon="-1">−</button>
              <span data-qty="${a.id}">${(chosen(a.id) || {}).quantity || 0}</span>
              <button type="button" data-step-addon="1">+</button>
            </div>
          </div>
        </div>`;
      })(c)).join('')}
      <div style="display:flex;gap:12px;margin-top:20px">
        <button class="btn btn--plain" type="button" id="backRooms">Back</button>
        <button class="btn" type="button" id="toDetails">Continue</button>
      </div>`;

    $$('[data-step-addon]').forEach(btn => btn.addEventListener('click', () => {
      const wrap = btn.closest('[data-addon]');   // a whole card, or one choice inside a group card
      const id = +wrap.dataset.addon;
      const span = $(`[data-qty="${id}"]`);
      const info = list.find(a => a.id === id);
      // Group extras (gala dinner) start at their minimum and drop straight back to 0.
      const min = info.min_quantity || 1;
      let next = Math.max(0, (+span.textContent) + (+btn.dataset.stepAddon));
      if (next > 0 && next < min) next = +btn.dataset.stepAddon > 0 ? min : 0;
      span.textContent = next;

      cart.addons = cart.addons.filter(a => a.addon_id !== id);
      if (next > 0) cart.addons.push({ addon_id: id, quantity: next, name: info.name });
      renderSummary();
    }));

    // Per-room extras: ticking a room adds one to that room, so it can never exceed one per room.
    $$('.addon--rooms').forEach(wrap => wrap.addEventListener('change', () => {
      const id = +wrap.dataset.addon;
      const rooms = $$('input[data-room]', wrap).filter(i => i.checked).map(i => +i.dataset.room);
      $$('.roompick__room', wrap).forEach(l => l.classList.toggle('is-on', $('input', l).checked));

      const info = list.find(a => a.id === id);
      cart.addons = cart.addons.filter(a => a.addon_id !== id);
      if (rooms.length) cart.addons.push({ addon_id: id, quantity: rooms.length, rooms, name: info.name });
      renderSummary();
    }));

    $('#backRooms').addEventListener('click', () => { setStep(1); renderRooms(); });
    $('#toDetails').addEventListener('click', goToDetails);
  }

  /* =====================================================================
     STEP 3 — guest details, priced by the server
     ================================================================== */
  /* ---- Arrival time: a scroll wheel (hour · minutes · am/pm) --------
     Nothing is filled in until the guest turns a wheel, so an untouched
     wheel sends no arrival time at all. */
  const WHEEL_ROW = 36;
  const WHEEL_COLS = {
    h: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'],
    m: ['00', '15', '30', '45'],
    p: ['AM', 'PM'],
  };
  function arrivalWheel() {
    const col = (k, label) => `<div class="wheel__col" data-k="${k}" tabindex="0" role="listbox" aria-label="${label}">
        <div class="wheel__pad"></div>
        ${WHEEL_COLS[k].map((v, i) => `<div class="wheel__item" data-i="${i}" role="option">${v}</div>`).join('')}
        <div class="wheel__pad"></div></div>`;
    return `<div class="wheelbox">
        <div class="wheel" aria-labelledby="g-arrival-label">
          ${col('h', 'Hour')}<span class="wheel__colon">:</span>${col('m', 'Minutes')}${col('p', 'AM or PM')}
          <div class="wheel__band" aria-hidden="true"></div>
        </div>
        <div class="wheel__side">
          <span class="wheel__value" id="g-arrival-show">Not set — scroll to choose</span>
          <button type="button" class="btn btn--plain btn--sm" id="g-arrival-clear" hidden>Clear</button>
        </div>
        <input type="hidden" id="g-arrival" value="">
      </div>`;
  }
  function bindArrivalWheel() {
    const cols = $$('.wheel__col');
    if (!cols.length) return;
    const start = { h: 1, m: 0, p: 1 };   // where the wheels rest: 2:00 PM (not chosen yet)
    const pick = {};
    let touched = false, settling = true;
    const show = () => {
      const v = touched ? `${WHEEL_COLS.h[pick.h]}:${WHEEL_COLS.m[pick.m]} ${WHEEL_COLS.p[pick.p]}` : '';
      $('#g-arrival').value = v;
      $('#g-arrival-show').textContent = v ? 'Around ' + v : 'Not set — scroll to choose';
      $('#g-arrival-clear').hidden = !v;
      $$('.wheelbox').forEach(b => b.classList.toggle('is-set', !!v));
    };
    const mark = (col, i) => col.querySelectorAll('.wheel__item').forEach(it => it.classList.toggle('is-on', +it.dataset.i === i));
    cols.forEach(col => {
      const k = col.dataset.k, n = WHEEL_COLS[k].length;
      pick[k] = start[k];
      col.scrollTop = start[k] * WHEEL_ROW;
      mark(col, start[k]);
      let t;
      col.addEventListener('scroll', () => {
        clearTimeout(t);
        t = setTimeout(() => {
          const i = Math.max(0, Math.min(n - 1, Math.round(col.scrollTop / WHEEL_ROW)));
          pick[k] = i; mark(col, i);
          if (!settling) touched = true;
          show();
        }, 90);
      });
      col.addEventListener('click', e => {
        const it = e.target.closest('.wheel__item');
        if (it) { touched = true; col.scrollTo({ top: +it.dataset.i * WHEEL_ROW, behavior: 'smooth' }); }
      });
      col.addEventListener('keydown', e => {
        if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') return;
        e.preventDefault(); touched = true;
        const i = Math.max(0, Math.min(n - 1, pick[k] + (e.key === 'ArrowDown' ? 1 : -1)));
        col.scrollTo({ top: i * WHEEL_ROW, behavior: 'smooth' });
      });
      ['wheel', 'touchstart', 'pointerdown'].forEach(ev => col.addEventListener(ev, () => { touched = true; }, { passive: true }));
    });
    $('#g-arrival-clear').addEventListener('click', () => { touched = false; show(); });
    setTimeout(() => { settling = false; }, 250);
    show();
  }

  async function goToDetails() {
    setStep(3);
    showStepTop();
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
      ${GUEST_OPTIONAL ? `<div class="notice notice--sample">Sample mode: these details are optional for now.
        Only what you enter is saved.</div>` : ''}
      <div class="panel">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="field"><label for="g-name">Full name${GUEST_OPTIONAL ? ' (optional)' : ''}</label><input id="g-name" maxlength="120" autocomplete="name" ${GUEST_OPTIONAL ? '' : 'required'}></div>
          <div class="field"><label for="g-phone">Mobile number${GUEST_OPTIONAL ? ' (optional)' : ''}</label><input id="g-phone" type="tel" maxlength="20" inputmode="tel" autocomplete="tel" placeholder="98250 12345" ${GUEST_OPTIONAL ? '' : 'required'}></div>
          <div class="field" style="grid-column:1/-1"><label for="g-email">Email</label>
            <input id="g-email" type="email" maxlength="160" autocomplete="email"><span class="hint">Your confirmation is sent here.</span></div>
          <div class="field"><label for="g-city">City</label><input id="g-city" maxlength="80" autocomplete="address-level2"></div>
          <div class="field"><label id="g-arrival-label">Approximate arrival time</label>
            ${arrivalWheel()}</div>
          <div class="field" style="grid-column:1/-1"><label for="g-notes">Anything we should know</label>
            <textarea id="g-notes" maxlength="2000" placeholder="Dietary preferences, a celebration, an early check-in."></textarea></div>
        </div>
      </div>

      <h2 style="margin-top:26px">How would you like to pay?</h2>
      <div class="panel">${modes}</div>

      <div class="panel" style="border-left-color:var(--muted)">
        <h3 style="font-size:.95rem">If you need to cancel</h3>
        <table style="width:100%;font-size:.84rem">
          ${quote.cancellation.filter(c => !c.past).map(c => `<tr>
            <td style="padding:3px 0;color:var(--muted)">
              ${c.from ? 'From ' + niceDate(c.from > todayISO() ? c.from : todayISO()) : 'Up to ' + niceDate(c.to)}</td>
            <td style="padding:3px 0;text-align:right;color:var(--ink)">
              ${c.charge_percent === 0 ? 'No charge' : c.charge_percent + '% of the total'}</td></tr>`).join('')}
        </table>
        <p style="font-size:.8rem;color:var(--muted);margin:10px 0 0">
          By continuing you agree to our <a href="${termsUrl()}" target="_blank" rel="noopener">terms and conditions</a>
          (opens in a new tab).</p>
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

    bindArrivalWheel();
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
    if (!GUEST_OPTIONAL && (!guest.name || !guest.phone)) { alertBox('Please give your name and mobile number.'); return; }
    // The same checks as the server (lib/booking.php), so a mistake is caught here
    // without leaving this step.
    if (guest.phone && !/^\+?\d{7,15}$/.test(guest.phone.replace(/[\s\-().]/g, ''))) {
      alertBox('That mobile number does not look right. Use digits only, for example 98250 12345 or +91 98250 12345.'); $('#g-phone').focus(); return;
    }
    if (guest.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(guest.email)) {
      alertBox('That email address does not look right.'); $('#g-email').focus(); return;
    }

    alertBox('');
    loading('Holding your room…');

    const res = await api('book.php', Object.assign({}, cart, { guest }));
    if (res._network) { renderFailure(res, () => createBooking()); return; }
    if (!res.ok && res.field === 'guest') {
      // Something in the details: back to this step with everything they typed.
      await goToDetails();
      $('#g-name').value = guest.name; $('#g-phone').value = guest.phone; $('#g-email').value = guest.email;
      $('#g-city').value = guest.city; $('#g-notes').value = guest.special_requests;
      alertBox(res.error); return;
    }
    if (!res.ok) { alertBox(res.error); setStep(1); renderRooms(); return; }

    booking = res;
    setStep(4);

    if (res.next === 'done') return renderConfirmed(res, 'We have your booking.');
    renderPaymentChoice(res);
  }

  function renderPaymentChoice(res) {
    const canCard = res.methods && res.methods.razorpay;
    const canUpi  = res.methods && res.methods.upi_qr;
    const canTest = res.methods && res.methods.test;   // config test_payments.enabled — testing only

    $('#stage').innerHTML = `
      <h2>Payment</h2>
      <div class="notice notice--info">
        Your booking <strong>${res.ref}</strong> is held while you pay.
        Payable now: <strong>${rupees(res.amount_due_now)}</strong>
        ${res.amount_due_now < res.total
          ? ` — the balance of ${rupees(res.total - res.amount_due_now)} is due later.` : ''}
      </div>

      ${(!canCard && !canUpi && !canTest) ? `
        <div class="panel">
          <h3>Online payment is not available right now</h3>
          <p>A booking is confirmed only once 50% or the full amount is paid, so we cannot finish
             <strong>${res.ref}</strong> online. Please call us on ${PHONE_DISPLAY} and we will complete it with you.</p>
        </div>` : `
        <div class="panel">
          <h3>Pay online</h3>
          <div class="paymethod">
            ${canCard ? `<button class="btn" type="button" id="payCard">Pay by card, UPI or netbanking</button>`
              : canTest ? `<button class="btn" type="button" disabled title="Add the Razorpay keys in config.local.php">
                  Card, UPI QR &amp; netbanking (Razorpay) · not connected yet</button>` : ''}
            ${canUpi ? `<button class="btn btn--ghost" type="button" id="payUpi">Pay by UPI QR code</button>`
              : canTest ? `<button class="btn btn--ghost" type="button" disabled title="Add the UPI id in config.local.php">
                  UPI QR to our bank · not connected yet</button>` : ''}
          </div>
          ${canCard ? `<p style="font-size:.82rem;color:var(--muted)">
            Card, UPI (including a scannable QR), netbanking and wallets are handled by Razorpay. Your booking
            confirms the moment the payment succeeds.</p>` : ''}
          ${canUpi ? `<p style="font-size:.82rem;color:var(--muted)">
            The QR code pays our bank account directly. We confirm your booking as soon as the payment
            shows in our account, usually within the hour.</p>` : ''}
          ${canTest && (!canCard || !canUpi) ? `<p style="font-size:.82rem;color:var(--muted)">
            ${!canCard ? 'Razorpay needs its keys (test keys start rzp_test_) in config.local.php. ' : ''}
            ${!canUpi ? 'The direct UPI QR needs the bank account’s UPI id in config.local.php.' : ''}</p>` : ''}
        </div>`}

      ${canTest ? `
        <div class="panel testpay">
          <h3>Test payment</h3>
          <p>For testing only. Press the button to confirm this booking as if
             ${rupees(res.amount_due_now)} had been paid. <strong>No money is taken.</strong> The rooms are
             booked in the system, so you can test availability, the admin panel and the confirmation.</p>
          <button class="btn" type="button" id="payTest">I've paid (test)</button>
        </div>` : ''}

      <div id="payArea" style="margin-top:18px"></div>`;

    if (canCard) $('#payCard').addEventListener('click', payWithRazorpay);
    if (canUpi)  $('#payUpi').addEventListener('click', payWithUpi);
    if (canTest) $('#payTest').addEventListener('click', payWithTest);
  }

  /* Testing only: confirm the booking as paid without taking money. */
  async function payWithTest() {
    $('#payTest').disabled = true;
    const res = await api('payment-test.php', { booking_id: booking.booking_id, manage_token: booking.manage_token });
    if (res._network) { $('#payTest').disabled = false; alertBox('We could not reach the server — please try again.'); return; }
    if (!res.ok) { $('#payTest').disabled = false; alertBox(res.error); return; }
    renderConfirmed(Object.assign({}, booking, res), 'Payment received (test) — your booking is confirmed.');
  }

  async function payWithRazorpay() {
    const order = await api('payment-create.php', { booking_id: booking.booking_id, manage_token: booking.manage_token, method: 'razorpay' });
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
    const upi = await api('payment-create.php', { booking_id: booking.booking_id, manage_token: booking.manage_token, method: 'upi_qr' });
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
          <a class="btn" href="${receiptUrl(res.ref, res.manage_token)}" target="_blank" rel="noopener">Receipt (PDF)</a>
          <a class="btn btn--plain" href="manage.php?${new URLSearchParams({ ref: res.ref, token: res.manage_token })}">Check status</a>
          <a class="btn btn--plain" href="tel:${PHONE_TEL}">Call ${PHONE_DISPLAY}</a>
          <a class="btn btn--plain" href="${document.querySelector('.eng-brand').getAttribute('href')}">Back to the website</a>
        </div>
      </div>`;
    // Kept on this device so a refresh does not lose the booking (see showLastBooking).
    try {
      localStorage.setItem(LAST_KEY, JSON.stringify({
        ref: res.ref, token: res.manage_token, property: cart.property,
        name: available.property.name, check_in: cart.check_in, check_out: cart.check_out,
      }));
    } catch (e) { /* private browsing: nothing to remember */ }
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

    // Once the server has priced the stay, show its figures (before tax, with any
    // extra-guest charges) so the lines add up to the total shown under them.
    // Where the tariff includes GST, lines are shown at the price the guest was quoted.
    const incl = Boolean(q && q.prices_include_tax);
    // Similar rooms together: grouped by cottage (in the order the cottages are listed), then room number.
    const roomNo = r => +((/Room (\d+)/.exec(r.plan_name) || [])[1] || 0);
    const typeOrder = (available && available.results || []).map(x => x.id);
    const rank = r => { const i = typeOrder.indexOf(r.room_type_id); return i < 0 ? 99 : i; };
    (q ? q.rooms.map(r => ({ rooms: r.rooms, name: r.room_type_name, plan_name: r.rate_plan_name, room_type_id: r.room_type_id,
                             price: incl ? r.subtotal + r.tax_amount : r.subtotal }))
       : cart.rooms).slice().sort((a, b) => rank(a) - rank(b) || roomNo(a) - roomNo(b)).forEach(r => lines.push(
      `<div class="sline"><span>${r.rooms} × ${r.name}<small>${r.plan_name}</small></span>
       <span>${rupees(r.price)}</span></div>`));

    if (q) {
      q.addons.forEach(a => lines.push(
        `<div class="sline"><span>${a.rooms ? a.addon_name : `${a.addon_name} × ${a.quantity}`}</span><span>${rupees(incl ? a.subtotal + a.tax_amount : a.subtotal)}</span></div>`));
      if (q.discount > 0) lines.push(
        `<div class="sline" style="color:var(--ok)"><span>Discount</span><span>− ${rupees(q.discount)}</span></div>`);
      if (!incl) lines.push(`<div class="sline"><span>Taxes</span><span>${rupees(q.tax_amount)}</span></div>`);
      lines.push(`<div class="sline sline--total"><span>Total</span><span>${rupees(q.total)}</span></div>`);
      if (incl) lines.push(`<div class="sline"><span style="color:var(--muted)">Includes GST</span>
                  <span style="color:var(--muted)">${rupees(q.tax_amount)}</span></div>`);
      if (q.amount_due_now < q.total) {
        lines.push(`<div class="sline sline--due"><span>Pay now</span><span>${rupees(q.amount_due_now)}</span></div>`);
        lines.push(`<div class="sline"><span style="color:var(--muted)">Balance later</span>
                    <span style="color:var(--muted)">${rupees(q.balance_later)}</span></div>`);
      }
    } else {
      cart.addons.forEach(a => lines.push(
        `<div class="sline"><span>${a.rooms ? `${a.name} — ${a.rooms.map(n => 'Room ' + n).join(', ')}` : `${a.name} × ${a.quantity}`}</span><span style="color:var(--muted)">added</span></div>`));
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
    cart.rooms_wanted = parseInt($('#f-rooms').value, 10);
    cart.occupancy = $$('#occList select').map(sel => parseInt(sel.value, 10));
    cart.adults    = cart.occupancy.reduce((a, b) => a + b, 0);
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

  /* One Single / Double / Triple choice per room. Adding a room starts it at Double. */
  const OCCUPANCY = [[1, 'Single', '1 guest'], [2, 'Double', '2 guests'], [3, 'Triple', '3 guests · extra bed']];
  function renderOccupancy() {
    const n = parseInt($('#f-rooms').value, 10) || 1;
    while (cart.occupancy.length < n) cart.occupancy.push(2);
    cart.occupancy.length = n;
    $('#occList').innerHTML = cart.occupancy.map((g, i) => `
      <label class="occ__room">
        <span>Room ${i + 1}</span>
        <select data-occ="${i}" aria-label="Guests in room ${i + 1}">
          ${OCCUPANCY.map(([v, label, note]) =>
            `<option value="${v}" ${v === g ? 'selected' : ''}>${label} · ${note}</option>`).join('')}
        </select>
      </label>`).join('');
  }
  $('#f-rooms').addEventListener('change', renderOccupancy);
  $('#occList').addEventListener('change', e => {
    const sel = e.target.closest('select[data-occ]');
    if (sel) cart.occupancy[+sel.dataset.occ] = parseInt(sel.value, 10);
  });

  // Refresh the room list as soon as the search changes. Any change before
  // payment starts the room choice again, so the price always matches the search.
  ['#f-in', '#f-out', '#f-rooms', '#occList'].forEach(sel => $(sel).addEventListener('change', () => {
    if (step < 4 && $('#f-in').value && $('#f-out').value > $('#f-in').value) $('#searchForm').requestSubmit();
  }));

  /* A guest who refreshes after booking still sees it: reference, receipt, status. */
  function showLastBooking() {
    let last = null;
    try { last = JSON.parse(localStorage.getItem(LAST_KEY) || 'null'); } catch (e) { last = null; }
    const box = $('#lastBooking');
    if (!box || !last || last.property !== cart.property) return;
    if (last.check_out < todayISO()) {   // the stay is over
      try { localStorage.removeItem(LAST_KEY); } catch (e) {}
      return;
    }
    box.innerHTML = `
      <div class="lastbooking">
        <span>You booked <strong>${last.ref}</strong> · ${niceDate(last.check_in)} to ${niceDate(last.check_out)}</span>
        <span class="lastbooking__actions">
          <a class="btn btn--sm" href="${receiptUrl(last.ref, last.token)}" target="_blank" rel="noopener">Receipt (PDF)</a>
          <a class="btn btn--plain btn--sm" href="manage.php?${new URLSearchParams({ ref: last.ref, token: last.token })}">Check status</a>
          <button class="lastbooking__close" type="button" aria-label="Hide">×</button>
        </span>
      </div>`;
    $('.lastbooking__close', box).addEventListener('click', () => {
      try { localStorage.removeItem(LAST_KEY); } catch (e) {}
      box.innerHTML = '';
    });
  }

  showLastBooking();
  renderOccupancy();
  renderSummary();
  if (cart.check_in && cart.check_out) searchAvailability();
})();
