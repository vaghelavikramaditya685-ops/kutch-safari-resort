<?php
/* ===========================================================================
 *  The debug bar — a strip along the bottom of the booking and admin pages
 *  showing what the engine is actually doing: every API call with its timing,
 *  PHP notices as they happen, the cart the guest has built, and the config
 *  flags that change behaviour (sample mode, test payments, Razorpay, UPI).
 *
 *  It exists because the interesting failures are invisible: a notice logged
 *  to a file nobody opens, an API call that quietly retried, a flag left on.
 *
 *  It NEVER appears on the live site. debug_visible() needs both the debug
 *  flag and a request coming from this machine, so leaving 'debug' => true on
 *  a real server still shows guests nothing.
 * ======================================================================== */

require_once __DIR__ . '/db.php';

/** Both conditions, every time: debug on AND the request is from this machine. */
function debug_visible(): bool {
    if (!cfg('debug')) return false;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return in_array($ip, ['127.0.0.1', '::1', 'localhost'], true);
}

/**
 * Notices, warnings and deprecations raised while this page (or this API call)
 * was built. Collected rather than printed: a stray notice in front of JSON
 * would corrupt every response.
 */
function debug_notes(?array $add = null): array {
    static $notes = [];
    if ($add !== null && count($notes) < 50) $notes[] = $add;
    return $notes;
}

/** Start collecting. Returns false so PHP still logs everything as usual. */
function debug_watch(): void {
    static $on = false;
    if ($on || !debug_visible()) return;
    $on = true;
    $levels = [E_WARNING => 'Warning', E_NOTICE => 'Notice', E_DEPRECATED => 'Deprecated',
               E_USER_WARNING => 'Warning', E_USER_NOTICE => 'Notice', E_STRICT => 'Strict'];
    set_error_handler(function (int $no, string $msg, string $file = '', int $line = 0) use ($levels): bool {
        // Respect the @ operator: the engine silences things it has already handled
        // (@mkdir on a folder that exists), and those are not worth showing.
        if (!(error_reporting() & $no)) return false;
        debug_notes(['level' => $levels[$no] ?? 'Error', 'msg' => $msg, 'at' => debug_where($file, $line)]);
        return false;
    });
}

/** "lib/booking.php:412" — the engine folder stripped off, so the bar stays readable. */
function debug_where(string $file, int $line): string {
    // Both sides to forward slashes first, or a Windows path never matches the root.
    $root = str_replace('\\', '/', dirname(__DIR__)) . '/';
    return str_replace($root, '', str_replace('\\', '/', $file)) . ':' . $line;
}

/** The settings that change how the engine behaves, and whether each is risky to leave on. */
function debug_flags(): array {
    return [
        ['sample mode',   (bool) cfg('rules.guest_details_optional'), true],
        ['test payments', (bool) cfg('test_payments.enabled'),        true],
        ['razorpay',      (bool) cfg('razorpay.enabled'),             false],
        ['upi qr',        (bool) cfg('upi.enabled') && cfg('upi.vpa'), false],
        ['stayflexi',     (bool) cfg('stayflexi.enabled'),            false],
        ['mail',          (bool) cfg('mail.smtp.enabled'),            false],
        ['db ' . cfg('db.driver', '?'), null, false],
    ];
}

/** What an API call sends back to the bar. Added to JSON responses only when visible. */
function debug_payload(): array {
    return ['notes' => debug_notes(), 'ms' => round((microtime(true) - (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 1)];
}

/**
 * Print the bar. Call it early in the page (right after <body>) so the fetch
 * wrapper is installed before engine.js makes its first call.
 */
function debug_bar(): void {
    if (!debug_visible()) return;

    $boot = json_encode([
        'flags' => debug_flags(),
        'notes' => debug_notes(),
        'page'  => basename($_SERVER['PHP_SELF'] ?? 'page'),
        'ms'    => round((microtime(true) - (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 1),
        'mem'   => round(memory_get_peak_usage(true) / 1048576, 1),
    ], JSON_UNESCAPED_SLASHES);
    ?>
<div id="ksrdbg" data-boot='<?= str_replace("'", '&#39;', $boot) ?>'></div>
<style>
#ksrdbg, #ksrdbg * { box-sizing: border-box; font-family: ui-monospace, Menlo, Consolas, monospace; }
#ksrdbg { position: fixed; left: 0; right: 0; bottom: 0; z-index: 2147483000; font-size: 11.5px; line-height: 1.5; }
#ksrdbg .d-tab { display: inline-flex; gap: 10px; align-items: center; margin: 0 0 0 10px; padding: 4px 12px;
  background: #241F1B; color: #F3EDE4; border-radius: 6px 6px 0 0; cursor: pointer; border: 0; font-size: 11.5px; }
#ksrdbg .d-tab b { color: #E9A23B; font-weight: 600; }
#ksrdbg .d-tab .d-warn { color: #F0866B; }
#ksrdbg .d-panel { display: none; max-height: 42vh; overflow: auto; background: #241F1B; color: #D9D2C7;
  border-top: 2px solid #B85C2E; padding: 10px 14px 14px; }
#ksrdbg.is-open .d-panel { display: block; }
#ksrdbg h4 { margin: 10px 0 4px; font-size: 10.5px; letter-spacing: .09em; text-transform: uppercase; color: #9C958A; font-weight: 600; }
#ksrdbg table { width: 100%; border-collapse: collapse; }
#ksrdbg td { padding: 2px 8px 2px 0; vertical-align: top; border-bottom: 1px solid #332C26; }
#ksrdbg .d-ok { color: #8FBF7A; } #ksrdbg .d-bad { color: #F0866B; } #ksrdbg .d-dim { color: #8C857B; }
#ksrdbg .d-on { color: #E9A23B; } #ksrdbg .d-off { color: #6E675E; }
#ksrdbg .d-chip { display: inline-block; margin: 0 6px 4px 0; padding: 1px 7px; border-radius: 10px; background: #332C26; }
#ksrdbg .d-chip.d-risk { background: #5A2A1C; color: #FFC9B8; }
#ksrdbg pre { margin: 0; white-space: pre-wrap; word-break: break-word; color: #D9D2C7; font-size: 11px; }
#ksrdbg .d-x { float: right; background: none; border: 0; color: #9C958A; cursor: pointer; font-size: 11.5px; }
@media print { #ksrdbg { display: none; } }
</style>
<script>
window.KSR_DEBUG = window.KSR_DEBUG || {};
(function () {
  var root = document.getElementById('ksrdbg');
  var boot = JSON.parse(root.getAttribute('data-boot'));
  var calls = [], notes = boot.notes.slice();

  /* Wrap fetch before engine.js loads, so every API call is recorded with its
     timing and whatever notices the server raised while answering it. */
  var fetch0 = window.fetch;
  window.fetch = function (input, init) {
    var url = (typeof input === 'string' ? input : (input && input.url) || '');
    var method = ((init && init.method) || (input && input.method) || 'GET').toUpperCase();
    var t0 = performance.now();
    return fetch0.apply(this, arguments).then(function (res) {
      var ms = Math.round(performance.now() - t0);
      var row = { method: method, url: url, status: res.status, ms: ms };
      calls.push(row); draw();
      // Read a clone so the engine still gets an unread body.
      res.clone().json().then(function (j) {
        if (j && j._debug) {
          if (j._debug.ms) { row.srv = j._debug.ms; }
          (j._debug.notes || []).forEach(function (n) { n.from = url; notes.push(n); });
        }
        if (j && j.ok === false && j.error) { row.err = j.error; }
        draw();
      }).catch(function () {});
      return res;
    }).catch(function (e) {
      calls.push({ method: method, url: url, status: 0, ms: Math.round(performance.now() - t0), err: String(e && e.message || e) });
      draw(); throw e;
    });
  };

  var esc = function (s) { return String(s === undefined || s === null ? '' : s).replace(/[<>&]/g, function (c) { return { '<': '&lt;', '>': '&gt;', '&': '&amp;' }[c]; }); };
  var open = localStorage.getItem('ksrdbg_open') === '1';

  function cartState() {
    try { return window.KSR_DEBUG && window.KSR_DEBUG.cart ? window.KSR_DEBUG.cart() : null; } catch (e) { return null; }
  }

  function draw() {
    var warn = notes.length + calls.filter(function (c) { return c.status >= 400 || c.status === 0 || c.err; }).length;
    var flags = boot.flags.map(function (f) {
      var mark = f[1] === null ? '' : (f[1] ? ' <span class="d-on">on</span>' : ' <span class="d-off">off</span>');
      return '<span class="d-chip' + (f[1] && f[2] ? ' d-risk' : '') + '">' + esc(f[0]) + mark + '</span>';
    }).join('');

    var shortUrl = function (u) {
      u = String(u).replace(/^.*?api\//, '');
      var q = u.indexOf('?');
      return q < 0 || u.length < 70 ? u : u.slice(0, q + 1) + u.slice(q + 1, 60) + '…';
    };
    var callRows = calls.length ? calls.slice(-25).map(function (c) {
      var cls = c.status >= 400 || c.status === 0 || c.err ? 'd-bad' : 'd-ok';
      return '<tr><td class="d-dim">' + esc(c.method) + '</td><td>' + esc(shortUrl(c.url)) +
        '</td><td class="' + cls + '">' + (c.status || 'failed') + '</td><td class="d-dim">' + c.ms + 'ms' +
        (c.srv ? ' <span class="d-dim">(php ' + c.srv + 'ms)</span>' : '') + '</td><td class="d-bad">' + esc(c.err || '') + '</td></tr>';
    }).join('') : '<tr><td class="d-dim" colspan="5">no API calls yet</td></tr>';

    var noteRows = notes.length ? notes.slice(-25).map(function (n) {
      return '<tr><td class="d-bad">' + esc(n.level) + '</td><td><pre>' + esc(n.msg) + '</pre></td><td class="d-dim">' + esc(n.at) + '</td></tr>';
    }).join('') : '<tr><td class="d-ok" colspan="3">no PHP notices</td></tr>';

    var st = cartState(), cartHtml = '';
    if (st && st.cart) {
      var c = st.cart;
      cartHtml = '<h4>Cart · step ' + esc(st.step) + '</h4><table>' +
        '<tr><td class="d-dim">dates</td><td>' + esc(c.check_in) + ' → ' + esc(c.check_out) +
          ' <span class="d-dim">(' + esc((c.occupancy || []).join('/')) + ' per room)</span></td></tr>' +
        '<tr><td class="d-dim">rooms</td><td>' + (c.rooms || []).map(function (r) {
            return esc(r.name) + ' ×' + r.rooms; }).join(', ') + '</td></tr>' +
        '<tr><td class="d-dim">extras</td><td>' + ((c.addons || []).map(function (a) {
            return esc(a.name) + ' ×' + a.quantity; }).join(', ') || '—') + '</td></tr>' +
        '<tr><td class="d-dim">pay</td><td>' + esc(c.payment_mode) +
          (st.quote ? ' · total ₹' + esc(st.quote.total) + ' · now ₹' + esc(st.quote.amount_due_now) : '') + '</td></tr>' +
        (st.booking ? '<tr><td class="d-dim">booking</td><td>' + esc(st.booking.ref) + '</td></tr>' : '') +
      '</table>';
    }

    root.innerHTML =
      '<button class="d-tab" type="button">⚙ <b>DEBUG</b> ' + esc(boot.page) +
        ' · ' + calls.length + (calls.length === 1 ? ' call' : ' calls') +
        (warn ? ' · <span class="d-warn">' + warn + ' to look at</span>' : '') + '</button>' +
      '<div class="d-panel">' +
        '<button class="d-x" type="button" data-clear>clear</button>' +
        '<h4>Flags</h4>' + flags +
        '<div class="d-dim" style="margin-top:4px">page built in ' + boot.ms + 'ms · ' + boot.mem + ' MB peak</div>' +
        cartHtml +
        '<h4>API calls</h4><table>' + callRows + '</table>' +
        '<h4>PHP notices</h4><table>' + noteRows + '</table>' +
      '</div>';
    root.classList.toggle('is-open', open);
    // The bar is fixed to the bottom, so without this it sits on top of whatever
    // the page puts there — which on the booking page is the Continue button.
    var strip = root.querySelector(open ? '.d-panel' : '.d-tab');
    document.body.style.paddingBottom = (strip ? Math.ceil(strip.getBoundingClientRect().height) + 6 : 0) + 'px';
    root.querySelector('.d-tab').addEventListener('click', function () {
      open = !open; localStorage.setItem('ksrdbg_open', open ? '1' : '0'); draw();
    });
    var clear = root.querySelector('[data-clear]');
    if (clear) clear.addEventListener('click', function (e) { e.stopPropagation(); calls = []; notes = []; draw(); });
  }

  // Anything the page logs as an error is worth seeing here too.
  window.addEventListener('error', function (e) {
    notes.push({ level: 'JS', msg: e.message, at: (e.filename || '').split('/').pop() + ':' + e.lineno }); draw();
  });
  window.addEventListener('unhandledrejection', function (e) {
    notes.push({ level: 'JS', msg: 'unhandled: ' + (e.reason && e.reason.message || e.reason), at: '' }); draw();
  });
  // The cart changes without a network call (ticking a room), so refresh now and then.
  setInterval(function () { if (open) draw(); }, 1200);
  draw();
})();
</script>
    <?php
}

debug_watch();
