# Pothik Pro — Departure পেইজ: প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. `pothik-pro-departure-mockup.html` ফাইলটা **Pro প্লাগিনের** ফোল্ডারে `docs/departure-mockup.html` নামে রাখুন। ZIP বানানোর সময় `docs/` বাদ দেবেন।
2. ক্রম: **Fix 1 → Fix 2 → Prompt 3 → QA**।
   - Fix 1 আর Fix 2 **ফ্রি প্লাগিনে (pothik)** চালাবেন।
   - Prompt 3 **Pro প্লাগিনে (pothik-pro)** চালাবেন।
   - দুটো প্লাগিন আলাদা ফোল্ডার হলে Cursor-এ দুটো ফোল্ডারই workspace-এ খুলে রাখুন, যাতে AI দুই দিকের কোড দেখতে পারে।
3. প্রতিটার পর সাইটে চেক করে git commit দিন।

---

## Fix 1 — সাইডবার ছোট হয়ে কেটে যাওয়া (ফ্রি প্লাগিনে)

```
Plugin: Pothik (free), the new admin shell built from docs/redesign-mockup.html (.pk-app, .pk-side, .pk-head).

Bug: on screens with little content (e.g. admin.php?page=pothik-departures) the Pothik sidebar is only as tall as the page content. Items at the bottom of the menu get cut off (under PRO only "Departure" is visible; Pilgrim file, Rooming, Installment ledger and Visa board are hidden). When the WordPress admin menu is longer than our content, scrolling down makes our sidebar and header scroll away and leaves an empty area.

Fix:
1. Make .pk-side position:fixed; top:32px (46px under 782px because of the taller admin bar); bottom:0; width 248px (76px in mini mode); z-index above content but below WP modals. Its `left` follows the WP admin menu:
   - normal WP menu: left:160px
   - WP menu folded (body.folded): left:36px
   - auto-fold range 783–960px (body.auto-fold without .folded): left:36px
   - our full-width mode (body.pothik-focus): left:0
   - under 782px: the sidebar becomes our off-canvas drawer (as already planned), not fixed beside the content.
2. Give .pk-body margin-left equal to the sidebar width (248px / 76px in mini mode), with the same .3s transition used for mini/focus modes, and min-height: calc(100vh - 32px).
3. .side-nav fills the space between the brand block and the user card (flex:1; overflow-y:auto; thin scrollbar), so every menu item is reachable on short screens. Active item scrolls into view on load (scrollIntoView({block:'nearest'})).
4. .pk-head stays sticky at top:32px inside .pk-body.
5. Remove the old dark-column background hack on .pk-app if one was added; it is no longer needed.
6. Sidebar labels must match the WP submenu: use each module's 'menu' value (e.g. "Departures") for the sidebar item text and its 'title' value ("Departure") for the page header. Same rule for free screens.
7. Test: pothik-departures, pothik-receipt (short pages) and pothik-inquiries (long page) with WP menu normal, folded, hidden (our full-width toggle) and our sidebar mini mode; at 1440, 1000, 800 and 390px wide. Nothing overlaps, every sidebar item is reachable, nothing scrolls away.
Run php -l on touched PHP files and list the changed files.
```

---

## Fix 2 — Pro-র জন্য স্থায়ী ডিজাইন সেট (ফ্রি প্লাগিনে)

```
Plugin: Pothik (free). Pothik Pro (separate plugin, namespace PothikPro) renders its screens inside our shell with Chrome::open()/close(), but it still prints the OLD classes (.pk-stats, .pk-panel, .pk-grid, .pk-lead, .pk-warn, .pk-btn, label>span fields). The redesign removed their CSS, so every Pro screen looks broken. We need a stable, documented UI layer that add-ons can rely on, so a future CSS change in the free plugin never breaks Pro again.

1. Create app/Admin/UI.php — final class Pothik\Admin\UI with small static helpers that RETURN escaped HTML strings (no echo), using the new design classes from admin.css:
   - card_open(string $title = '', string $sub = '', string $icon = ''): string / card_close(): string → .pk-card with .pk-card-head (icon tile + h3 + sub).
   - grid_open(int $cols = 2) / grid_close() → .pk-fgrid (2 or 3 columns, 1 column under 700px).
   - field(array $a): string — keys: name, label, value, type (text|number|date|email|tel), hint (shown as <em> next to the label), placeholder, span (bool, full row), attrs (array of extra attributes, escaped), class. Markup: <label class="pk-field [pk-span]"><span>Label <em>hint</em></span><input …></label>.
   - select(array $a) with options [value => label]; textarea(array $a).
   - seg(array $a): segmented control (buttons) bound to a hidden input with the given name — works without JS because a visually-hidden <select> with the same name is printed and disabled by JS when it takes over.
   - stepper(array $a): − input + around a type=number input (same name).
   - stat(string $value, string $label, string $icon = '', string $tone = ''): string → the small stat card used on the Desk.
   - notice(string $text, string $tone = 'warn'|'ok'|'info'): string → .pk-notice-row with icon (warn = clay, ok = green).
   - button(string $label, array $a): primary / ghost / wa / gold / danger, sm / lg.
   - person(array $a): avatar initials + name field + phone field + Call / WhatsApp icon buttons (tel: and Format::whatsapp_href).
   - savebar(string $label, string $button): the dark sticky save bar; the form gets data-pk-dirty.
   - icon(string $name): <svg class="pk-i"><use href="#pk-i-{name}"/></svg> — and add the icons Pro needs to the sprite: plane, seat, calendar, building, users, phone, wa, warn, check, globe, passport, bed, wallet, shield.
2. Keep the old class names working as thin aliases in admin.css, in one block marked `/* compat for add-ons — keep */`: .pk-panel → same look as .pk-card with padding; .pk-grid → 2-column field grid; .pk-admin label>span → field label style; .pk-lead → muted helper text; .pk-warn → clay notice row; .pk-stats → stat card row; .pk-btn → primary button; .pk-form-actions → right-aligned actions. Do NOT delete this block in future cleanups (add a comment saying Pothik Pro depends on it).
3. Add `do_action('pothik/admin_enqueue', $hook)` at the end of Menu::assets() so add-ons can enqueue their own CSS/JS after 'pothik-admin' (dependency handle). Expose window.pothikAdmin.toast() publicly for add-on JS.
4. Add a define POTHIK_UI_VERSION = '1' and document the helpers in a docblock at the top of UI.php (one line per helper, with an example).
5. Translations for any new strings; php -l; list changed files.
```

---

## Prompt 3 — Departure পেইজের রিডিজাইন (Pro প্লাগিনে)

```
Plugin: Pothik Pro (namespace PothikPro, files app/Screens.php, app/Desk.php, app/Plugin.php, app/Analytics.php). It needs the free Pothik plugin and renders inside its admin shell via Pothik\Admin\Chrome. After Fix 2 the free plugin provides Pothik\Admin\UI helpers, the 'pothik/admin_enqueue' action and window.pothikAdmin.toast().

Visual reference: docs/departure-mockup.html — open it and match the "Departure" screen (section data-screen="departures") exactly: layout, spacing, colours, motion. Ignore everything else in that file (it also contains the free plugin's screens as context). Colours are Pothik's own (deep green #1b4336 family, gold #d4a64a, clay #b4532a, paper #f7f3ec).

RULES
- Backend contract is frozen: keep admin-post action `pothik_pro_departure`, nonce `pothik_pro_departure`, and EVERY field name: leave, return, route, from, airline, flight, seats, held, type, company, program, leader, leader_phone, guide, guide_phone, visa_open, visa_last, entry_last, depart_last. Desk::save_departure() must work unchanged with the new form (list the field names before/after and show they match).
- Build markup with Pothik\Admin\UI helpers. If class_exists('Pothik\Admin\UI') is false (old free version), fall back to the current markup so nothing fatals.
- Strings through Pothik\I18n::t() with Bangla translations (same translation mechanism Pro already uses). Escape everything. No inline styles except dynamic widths.
- New assets: assets/css/pro.css and assets/js/pro.js, enqueued only on Pro screens via add_action('pothik/admin_enqueue', …) with 'pothik-admin' as dependency. Vanilla JS, no external requests.
- Works without JS (plain form post); JS adds live preview, warnings, timeline and the save bar.

SCREEN (Screens::departures())
Chrome::open('Departure', 'One group file. This screen does not submit to Masar.'); then:

1. Top row, two columns (stack under 1200px):
   a) Boarding-pass card (dark green gradient, faint star pattern, perforated divider with two paper-coloured notches):
      - Top line: "Group file · {program}" + gold pill Group/Private + glass pill Jeddah first/Madinah first.
      - Route: big origin code (from, uppercased) — dashed line with plane icon and "{airline} · {flight}" under it — big destination code: JED when route = jeddah_first, MED when madinah_first, with the city name under each code (origin city: a small map DAC→Dhaka, CGP→Chattogram, ZYL→Sylhet, else empty; filter 'pothik_pro/airport_names').
      - Facts row: Leave · Return (wp_date 'j M Y') · Trip length ("{n} days", inclusive; "—" if return < leave) · Saudi company (I18n::field for Bangla).
      - Stub on the right: "Departs in {N} days" in big gold (Today / Departed when ≤ 0), a gold progress bar = held/seats and "{held} of {seats} seats confirmed".
   b) Seats card: "{held} / {seats}", pill "{n} seats left" (clay) or "Full" (green); a seat grid of small squares — one per seat up to 60 (scale when more), confirmed ones green; legend; and a dashed "site line" box that previews Desk::seat_line(): "Shown on the package page: “28 seats confirmed · 40 total” — only when the site has one package", or "Private file — no seat line is shown on the site." when type = private or seats = 0.
2. Warnings row: one notice per Desk::season_notes() item (clay, warn icon). If there are none, one green notice "All dates fit inside the Saudi season." Add a new check to Desk::season_notes(): return earlier than leave → "Return date is before the leave date." (translated).
3. Season timeline card: the six dates (visa_open, visa_last, leave, entry_last, return, depart_last — skip empty ones) sorted by date and shown as EVENLY spaced steps (not a scaled axis — the dates are months apart and would overlap). Each step: round icon dot, label, formatted date; "+N days" above the connector between steps; a dark "Today" flag placed before the first future step. Season dates = gold dots, trip dates (leave/return) = green dots, a trip date outside the season (leave > entry_last or return > depart_last) = clay dot + clay label; connectors between leave and return are green. Horizontal scroll on small screens. Legend line: "Gold = Saudi season dates · Green = this trip · Red = outside the season. Warnings only — saving is never blocked." Print it in PHP; JS re-renders it live.
4. Form in a two-column card grid (one column under 1200px):
   - Flight: Leave date, Return date, Direction (segmented Jeddah first / Madinah first → field route), Origin (hint "airport code", uppercase, maxlength 12), Airline, Flight (full row, placeholder "Outbound / return").
   - Seats & type: Type segmented Group / Private (field type), Total seats and Confirmed seats as steppers (hint "max = total"; JS clamps held ≤ seats; server already clamps), then "Saudi partner": Saudi company, Program ID (uppercase).
   - People on this trip: two person rows (Group leader + Phone, Guide + Phone) with initials avatar and Call / WhatsApp buttons (hidden when phone empty).
   - Season dates: Visa opens, Last visa, Last entry, Last departure + helper "These dates warn the desk. They do not stop a save."
5. Replace the bottom "Save departure" button with the shared save bar ("Unsaved departure", Discard = reload, gold "Save departure" + Ctrl/Cmd+S). Keep a normal submit button inside <noscript>.
6. After save (?updated=1 — whatever Desk::store() redirects with) show toast "Departure saved".

JS (assets/js/pro.js, only the departure module for now, structured so the other Pro screens can add modules later):
- On any input: update the pass (codes, pills, dates, trip days, countdown), seat grid (small pop animation on the newly filled seat), seats-left pill, site-line preview, warnings (same rules as PHP season_notes + return<leave), timeline, person initials.
- Respect prefers-reduced-motion. All strings from wp_localize_script (window.pothikPro.i18n).

Do not change the other Pro screens in this step, but make sure the compat CSS from Fix 2 keeps them readable. php -l on touched files; list changed files.
```

---

## QA — শেষে একবার

```
Check the Departure screen against docs/departure-mockup.html and fix what you find. Report as a checklist:
- Saving with JS on and off stores exactly the same values as before (compare the pothik_pro_desk option before/after a save with no changes — it must be identical).
- Confirmed seats can never exceed total seats (UI and server).
- Warnings match between PHP (after save) and JS (while typing), including the new "return before leave" check. Saving is never blocked.
- Timeline: correct order, gaps, Today flag, colours; no overlapping labels at 1440px, 1000px and 390px.
- Pass card: JED/MED follows Direction; trip length inclusive; "Departs in" correct with the site timezone (wp_timezone()).
- Seat line preview equals what Desk::seat_line() prints on the public package page (group only, seats > 0).
- Bangla mode: Bangla airline/company/leader/guide fields (merge_prose) still save and display; no overflow.
- Sidebar: every item reachable on this short page; WP menu normal / folded / hidden all fine (Fix 1).
- Other Pro screens (Pilgrim file, Rooming, Installments, Visa board) are still readable thanks to the compat CSS.
- Pro without the updated free plugin: no fatal error (UI class check).
- No external requests; no PHP notices; Plugin Check passes for both plugins.
```
