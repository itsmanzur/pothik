# Pothik Admin Redesign — ফাইনাল প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. `pothik-dashboard-v2-mockup.html` ফাইলটা প্লাগিনের ভেতরে `docs/redesign-mockup.html` নামে রাখুন। এটা শুধু রেফারেন্স, প্লাগিন এটা লোড করবে না। ZIP বানানোর সময় `docs/` বাদ দেবেন।
2. প্রম্পটগুলো **এই ক্রমে একটা একটা করে** দিন: Fix → 0 → 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8।
3. প্রতিটা প্রম্পটের পর সাইটে চেক করে git commit দিন, তারপর পরেরটা।
4. প্রতিটা প্রম্পটের শুরুতে `<<paste GLOBAL RULES>>` লেখা জায়গায় নিচের GLOBAL RULES ব্লকটা পুরোটা পেস্ট করবেন।

---

## Fix — আগে এই বাগটা (Inquiries পেইজে fatal error)

```
In app/Admin/Screens.php, method inquiries(): it calls InquiryRepository::paged($page, $per_page), which does not exist and causes "Call to undefined method Pothik\InquiryRepository::paged()". The real method is InquiryRepository::paginate($page, $per_page) and it returns ['items' => [...], 'total' => int, 'pages' => int, 'page' => int]. Replace the call, use $result['items'] as $rows and $result['pages'] as $total_pages (drop the separate count_all() call for pages). Change nothing else. Run php -l on the file.
```

---

## GLOBAL RULES

```
GLOBAL RULES — Pothik admin redesign
- Plugin: Pothik (WordPress 6.5+, PHP 8.1, namespace Pothik\, no build step). Main files: app/Admin/{Chrome,Screens,Settings,Analytics,Menu}.php, app/Modules.php, assets/css/admin.css, assets/js/admin.js, app/I18n.php, languages/*.
- Visual reference: docs/redesign-mockup.html. Open it in full and match its layout, spacing, colours, radii, shadows, motion and interactions exactly. Copy CSS tokens/components from it, but scope EVERY selector under `.pk-admin` or the `pothik-screen` body class. Do NOT port the mockup's fake WordPress parts (`.wp-bar`, `.wp-side`, `.wp`, `.wp-main`) — those only simulate wp-admin.
- Colours stay Pothik's own: deep green (--g800 #1b4336 family), gold (--gold #d4a64a), clay (--clay #b4532a), paper (#f7f3ec). Fonts: the self-hosted Source Sans 3 + Noto Sans Bengali in assets/fonts.
- Backend contract is frozen: keep every existing admin-post action name, form field `name`, nonce action, check_admin_referer, capability check, redirect and query arg (updated, error=limit, demo_loaded, demo_removed, recreated …). Stores (PackageStore, InquiryRepository, InquiryStore, ReceiptStore) stay as they are unless a step explicitly says to add a method.
- Progressive enhancement: every action must still work as a plain form POST with JS disabled. JS only enhances (AJAX, live preview, drawer, toasts, countdown).
- Every new AJAX endpoint: wp_ajax_ only (never nopriv), check_ajax_referer('pothik_admin'), current_user_can('manage_options'), sanitise input, wp_send_json_success / wp_send_json_error.
- Vanilla JS only (no jQuery dependency, no React, no CDN, no external fonts or images — WP.org rules). Icons = one inline SVG sprite printed once by the shell (copy the <symbol>s from the mockup).
- All visible strings go through I18n::t() with a natural Bangla translation in languages/bn_BD.po (+ regenerate .mo) and an entry in pothik.pot. JS strings come from window.pothikAdmin.i18n (wp_localize_script) — never hard-code English in JS.
- Escape everything (esc_html, esc_attr, esc_url; wp_kses with an SVG allow-list for the sprite). No inline style="" for layout — use classes in admin.css (dynamic widths/heights of meters and chart bars are the only exception).
- Accessibility: visible focus rings, real <button>/<a>, aria-expanded on toggles, aria-current="page" on the active menu item, aria-live for toasts, Esc closes overlays, focus trap in drawer/command palette, contrast ≥ 4.5:1, prefers-reduced-motion respected.
- Responsive at 1400 / 1100 / 960 / 782 / 600px as in the mockup. Bangla mode must not overflow (longer labels, line-height 1.5).
- When done: run php -l on every touched PHP file and give me a short list of changed files.
```

---

## Prompt 0 — App shell: নিজস্ব সাইডবার, হেডার, ফুল-স্ক্রিন টগল, ডিজাইন সিস্টেম

```
<<paste GLOBAL RULES>>

TASK: Build the new Pothik admin shell (left sidebar + top header) and the full-width toggle, exactly like docs/redesign-mockup.html (`.pk-app`, `.pk-side`, `.pk-head`, `#focusBtn`, `#sideToggle`). Screen bodies stay as they are in this step — only wrap them in the new shell.

A. Body class + base
1. Add `admin_body_class` filter: on every Pothik screen (hook suffix contains "pothik") add `pothik-screen`. If the current user has user meta `pothik_focus` = '1' also add `pothik-focus`; if `pothik_side_mini` = '1' add `pothik-side-mini`. Doing this in PHP means there is no flash when the page loads.
2. On pothik-screen: #wpcontent background var(--paper), remove WP's default left padding of #wpcontent and the .wrap margin so the shell touches the WP menu edge; hide #wpfooter (it overlaps the sidebar user card in our other plugin — we don't want that here) and move "Version x" into our sidebar instead.

B. Full-width toggle (VERY IMPORTANT — the owner asked for this specifically)
- A header button "Full screen" / "Show WP menu" (icon + label, mockup `#focusBtn`). One click hides the WordPress admin menu (#adminmenumain = #adminmenuback + #adminmenuwrap) and sets #wpcontent and #wpfooter margin-left:0 so Pothik fills the whole display width. Another click shows the WP menu again and restores the margins.
- Animate smoothly (margin-left / transform .3s), respecting prefers-reduced-motion.
- Remember the choice per user: JS toggles the `pothik-focus` body class instantly, then saves via AJAX `pothik_ui_pref` (key=focus, value=0|1) into user meta `pothik_focus`. Next time any Pothik page opens, PHP adds the class so it is already full width. It only affects Pothik screens; other wp-admin pages always show the WP menu.
- Keyboard shortcut: Ctrl/Cmd + Shift + F toggles it too. Button has aria-pressed and a title that explains it.
- Without JS: the button is a small form that posts to admin-post `pothik_ui_pref` (nonce) and redirects back.
- Under 782px WordPress uses its own off-canvas menu: hide our button there and ignore the class.
- The WP top admin bar (#wpadminbar) stays visible.

C. Pothik sidebar (`.pk-side`, 248px, sticky top:32px, height calc(100vh - 32px); 46px admin-bar offset under 782px)
- Top: gold brand mark + "Pothik" + small "Umrah desk", and a collapse button (mockup `#sideToggle`). Collapsed = 76px, icons only with title tooltips; remembered in user meta `pothik_side_mini` through the same `pothik_ui_pref` endpoint (key=side_mini).
- Grouped nav built from data (one PHP array, filterable with `pothik/admin_nav` so Pro can add items):
  OVERVIEW: Desk (home), Analytics (chart)
  PACKAGES: All packages (box, grey count badge), New package (plus — submits the existing pothik_new_package form)
  PILGRIMS: Inquiries (inbox, clay badge = WAITING count), Receipt (receipt)
  PRO: one item per locked module from Modules::all() (departures→plane, pilgrims→passport, rooming→bed, ledger→wallet, visa→shield, fallback spark), muted text + gold lock icon, all linking to pothik-pro. When a module is unlocked (Pro active) it shows as a normal item linking to its own page.
  SYSTEM: Settings (gear)
- Active item: green gradient background + 3px gold bar on the left + aria-current="page". The package editor marks "All packages" active.
- Bottom: user card (initial avatar, display name, role) + plugin version (POTHIK_VERSION). Nothing may overlap it.
- Dark green gradient background with a very faint 8-point star pattern at the top (copy from mockup).
- Under 960px the sidebar becomes an off-canvas drawer opened by a menu button in the header.
- Keep the normal WP submenu registration in Menu::register() as it is (so the WP menu still works when shown).

D. Header (`.pk-head`, white, sticky, bottom border)
- Left: h1 page title + one-line lead (existing Chrome::open($title, $lead) args).
- Right: search button (opens command palette, shows "Ctrl K"), language select (EN / বাংলা — options are links built from the existing I18n::toggle() URLs; selecting navigates), the full-width toggle from B, primary "New package" button.
- Directly after the header print `<hr class="wp-header-end">` so WordPress places other plugins' admin notices below our header instead of above the shell.
- Chrome::open() signature becomes open(string $title, string $lead = '', string $actions_html = ''); $actions_html renders a right-aligned actions row at the top of the content. Chrome::close() closes all wrappers.
- Chrome::saved() and the demo_loaded / demo_removed / recreated / error=limit notices become `<div data-pk-flash="…" data-pk-flash-type="success|info|warning">` — JS shows them as toasts; without JS they render as visible notices.

E. Waiting count
- Add InquiryRepository::count_waiting(): COUNT(*) WHERE replied = 0 (use the real column name), cached in a 60-second transient `pothik_waiting_count`; delete the transient in InquiryStore after create / reply toggle / delete. Used by the sidebar badge, Desk and later screens.

F. CSS (assets/css/admin.css) — reorganise into commented sections:
  1 @font-face (keep) · 2 tokens on .pk-admin (copy the full :root list from the mockup) · 3 shell (.pk-app, .pk-side, .pk-head, focus mode, mini mode) · 4 components (.pk-btn + -primary/-ghost/-wa/-gold/-danger/-sm/-lg, .pk-icon-btn, .pk-card/.pk-card-head, .pk-pill + wait/done/draft/live/demo/plain, .pk-chip/.pk-chips, .pk-seg, .pk-switch, .pk-field, .pk-affix, .pk-search, .pk-meter, .pk-empty, .pk-toast, .pk-drawer, .pk-cmdk, .pk-savebar, .pk-menu-pop) · 5 screens (filled in later prompts) · 6 responsive · 7 reduced-motion + print.
  Keep old class names only where screens still use them, marked `/* legacy — remove in Prompt 7 */`.

G. JS (assets/js/admin.js) — keep all current handlers working, add small modules inside one IIFE (only global: window.pothikAdmin):
  toast(msg,{type,undo}) · flash reader · ui prefs (focus, side_mini) · command palette (Ctrl/Cmd+K: "Go to" all screens, "Actions" New package / Export CSV / Copy [pothik_catalog], "Pilgrims" from AJAX `pothik_search_inquiries` → max 6 {name, phone, city, url}, prepared LIKE with esc_like; arrows, Enter, Esc, focus trap) · copy-to-clipboard for [data-pk-copy] · count-up for [data-pk-count] + meters [data-pk-w] when visible · dirty-form save bar for forms with [data-pk-dirty] (shows .pk-savebar, Ctrl/Cmd+S submits, beforeunload warning).
  Menu::assets: wp_localize_script('pothik-admin','pothikAdmin',{ajax:admin_url('admin-ajax.php'), nonce:wp_create_nonce('pothik_admin'), focus:bool, i18n:{…}}).

Test: every Pothik page shows the new sidebar + header; the full-width button hides/shows the WP menu, survives a page reload and a different Pothik page; other wp-admin pages are unaffected; no PHP notices.
```

---

## Prompt 1 — Desk (ড্যাশবোর্ড)

```
<<paste GLOBAL RULES>>

TASK: Rebuild Screens::overview() as the "Desk" screen in docs/redesign-mockup.html (section data-screen="desk"). Header title "Desk", lead "Today's inquiries, packages and the next Umrah season at a glance." Order top to bottom:

1. Greeting row (`.greet`): "Assalamu alaikum, {display_name}" and under it "{Hijri date} · {Gregorian date}". Right side: the Talbiyah لَبَّيْكَ اللَّهُمَّ لَبَّيْكَ in gold (lang="ar" dir="rtl").
   - Hijri: if class_exists('IntlDateFormatter') use new IntlDateFormatter(($bn?'bn_BD':'en_GB').'@calendar=islamic-umalqura', IntlDateFormatter::LONG, IntlDateFormatter::NONE, wp_timezone_string(), IntlDateFormatter::TRADITIONAL, 'd MMMM y') + " AH"/" হিজরি"; otherwise print an empty span that JS fills with Intl.DateTimeFormat('en-GB-u-ca-islamic-umalqura' | 'bn-u-ca-islamic-umalqura'). Never hard-code Hijri dates.
   - Arabic font: self-host a SUBSET of Amiri Bold (SIL OFL) at assets/fonts/amiri-700-talbiyah.woff2 containing only this line's glyphs (`pyftsubset Amiri-Bold.ttf --text="لَبَّيْكَ اللَّهُمَّ لَبَّيْكَ" --flavor=woff2 --layout-features='*'`), serif fallback; add the OFL credit to readme.txt.
2. Notice strip (`.notice`, green gradient): gold "Notice" badge, "{N} guests of Allah are waiting for your reply." + a soft rotating line (fade every 7 s; lines translatable and filterable with `pothik/desk_lines`: "Every reply could be someone's first step towards the Haram." / "May Allah accept the Umrah of every pilgrim you serve." / "Serve the guests of Ar-Rahman with ihsan."), right "Reply now →" link to pothik-inquiries&status=waiting. When N = 0: "All guests of Allah have been answered. Alhamdulillah." and the link becomes "View inquiries".
3. Season countdown card (`.count-card`, light green→sand gradient):
   - Left: eyebrow "Next season", title "Ramadan 1448" (or "Hajj 1448"), "Starts around {date} · {tip}", a segmented control Ramadan / Hajj, and two chips "🕋 Makkah {live Makkah time}" / "🕌 Madinah" that switch the background scene.
   - Right: big ticking timer DAYS : HOURS : MINS : SECS in a frosted white box (updates every second — must never sit at 00:00:00).
   - Target dates: find the next 1 Ramadan and 8 Dhul Hijjah by walking forward day by day (max 400) with the Umm al-Qura calendar (PHP IntlDateFormatter or JS Intl formatToParts). Count down to local midnight of that day. During Ramadan show "Ramadan Mubarak 🌙", during 8–13 Dhul Hijjah "Hajj days 🤍" instead of a timer. Remember the chosen tab (Ramadan/Hajj) in localStorage inside try/catch.
   - Background art: the two inline SVG skylines from the mockup (#scMakkah: hills, clock tower, Haram minarets, Kaaba with dotted tawaf rings whose dots move via stroke-dashoffset; #scMadinah: white minarets, Green Dome with crescent, umbrellas) at ~17% opacity on the right, fading out to the left with a mask; they crossfade every 9 s, a chip click stops auto-switch. Put them in templates/admin/skyline.php. aria-hidden, no human figures, nothing placed on top of the Kaaba / Green Dome except the frosted timer box.
   - Times: Makkah time via JS toLocaleTimeString(…,{timeZone:'Asia/Riyadh'}) every 30 s (PHP prints an initial value with wp_date and DateTimeZone('Asia/Riyadh')).
4. Quick actions row (`.qbar`, ghost buttons with green icons): New package (existing form), Inquiries, Payment slip (receipt), Export CSV (existing export form), Copy shortcode [pothik_catalog], Settings.
5. Six stat cards (`.stats6`, repeat(6, minmax(0,1fr)) — must always fill the full width: 3 columns under 1400px, 2 under 600px). Each: coloured icon tile, big number (data-pk-count, real value printed for no-JS), bold label, small grey line, whole card is a link:
   Inquiries (last 7 days, ▲/▼ % vs previous 7 days, small sparkline of 7 daily counts drawn as an SVG path in PHP — reuse Analytics' timezone-safe day bucketing by moving it into one shared helper) · Waiting reply (clay; "Oldest X ago" via human_time_diff) · Packages ("N of 40 free" + meter; hide the limit text when Pro removes the limit — use the same check PackageStore uses for 40) · Published ("Live on your site") · Last receipt (Format::money, "{channel} · {date}") · Reply rate (% replied, last 30 days).
6. Two columns (`.desk2`: 1.7fr / 1fr, stacked under 1100px):
   - Left card "Reply queue": column header row, then up to 5 WAITING inquiries (add InquiryRepository::recent_waiting(int $limit)). Row: initials avatar (colour derived from the name, mb_substr for Bangla), name, "package · share · city · N pilgrims", relative time, Call (tel:), WhatsApp (Format::whatsapp_href) and a dashed check button = mark replied.
     The check button lives inside the existing pothik_reply_inquiry form (works without JS). With JS: AJAX `pothik_toggle_reply` (returns {replied, waiting_count}) → row slides out, toast "{name} marked replied" with Undo, every [data-pk-wait-count] on the page updates (notice, stat card, sidebar badge).
     Empty state: check icon, "All caught up, alhamdulillah", small text.
   - Right: "Packages" card (colour thumb with days, title, "from ৳…", Live/Draft pill, "Manage →") and a "Finish setting up" card: progress meter + 5 items computed from real data (agency name & phone, WhatsApp, first package, logo, a published package); done items green tick + strikethrough, others link to the right screen. Hide this card when all 5 are done or after "Hide" (user meta `pothik_hide_setup`, via pothik_ui_pref).
7. Pro card (`.pro-card`): eyebrow "Pro" + "Docs" link; inner cream/gold box "Pothik Pro [PRO]", one line, primary "Learn about Pro", and on the right small chips for each locked module (same icons as the sidebar). Hidden when nothing is locked.
8. Footer text: "Trying things out? Load demo data / Remove demo data" as links that submit the existing demo forms. Demo rows get a "Demo" pill.

Remove the old Desk markup (.pk-stats, module grid, recent list) and all inline styles from overview(). prefers-reduced-motion: no tawaf motion, no crossfade, no rotating lines, timer still ticks.
```

---

## Prompt 2 — Packages list

```
<<paste GLOBAL RULES>>

TASK: Redesign Screens::package_list() + package_row() + package_menu() as the "Packages" screen in docs/redesign-mockup.html. Header: "Packages" / "Each package becomes a landing page with prices, hotels and a WhatsApp button."

1. Toolbar: search (client-side filter on title + hotel names), chips All / Published / Draft with counts, right side usage meter "3 of 40 packages" (hidden when unlimited).
2. Card grid (auto-fill minmax(300px,1fr)). Each `.pk-pkg` card:
   - Cover band: gradient chosen deterministically from the package id (5 pairs from the mockup: green, gold-brown, blue, clay, plum) + star pattern; big "7 days"; Draft/Published pill; frosted chips "🕋 4 nights Makkah" / "🕌 2 Madinah".
   - Body: title; Makkah and Madinah rows: city label, hotel name (ellipsis), gold stars from *_stars (parse safely to 0–5), distance meter (width = max(6, 100 − metres/20)%) + "150 m".
   - Footer: "from, quad" + lowest non-zero price; publish switch; edit icon; kebab menu (Edit, View on site, Copy shortcode [pothik_package id="…"], Duplicate, —, Delete in clay).
   - Publish switch sits in the existing pothik_toggle_package_status form (no-JS = submit). With JS: AJAX `pothik_package_status` → pill + switch update, toast "Published — live on your site" / "Moved to draft", sidebar count updates. Duplicate: AJAX returns the new card HTML to insert (respect the 40 limit → toast + banner). Delete: inline confirm inside the menu ("Delete? Yes / No"), then fade out + toast.
   - Menu: aria-expanded, Esc, arrow keys, outside click closes, opens upward if near the viewport bottom.
3. Last tile: dashed "New package" card with a + that rotates on hover (submits pothik_new_package).
4. Empty state: big card "Create your first Umrah package" + primary button + "Load demo data".
5. ?error=limit → warning toast + gold banner above the grid with an "Upgrade" link.
Delete the old .pk-pkg-row CSS when nothing uses it.
```

---

## Prompt 3 — Package editor

```
<<paste GLOBAL RULES>>

TASK: Redesign Screens::package_form() as the editor screen in docs/redesign-mockup.html (data-screen="editor"). The POST payload must stay IDENTICAL (same field names, newline-separated inclusions/exclusions/itinerary textareas, same price_* names, same status values) so PackageStore::handle_save needs no change — list field names before and after and show me they match.

Header: "Edit package" / "Days, hotels and prices. The site shows this package as you save it."
Layout: left sticky section nav (210px) · form · right sticky live preview (320px). Under 1180px the preview moves into a drawer opened by "Preview"; under 960px the nav becomes a horizontal scroll strip.

1. Top row: "← All packages", h1 = package title (updates live), Draft/Published segmented control (two radios named `status`), ghost "Preview" (public URL, new tab).
2. Gold language note: "Editing English / Editing বাংলা — empty Bangla fields fall back to English" (I18n::is_bn()); keep the existing placeholder fallback logic.
3. Numbered section cards:
   1 Basics — Package name, Eyebrow, Flight direction, Days / Makkah nights / Madinah nights as − [n] + steppers (inputs stay type=number with the same names), live check "✓ 6 nights fit inside 7 days" or clay warning, Group note.
   2 Hotels — Makkah and Madinah cards: hotel, "or similar", 5-star widget (click + hover preview + arrow keys, writes the hidden *_stars input), distance range slider 0–2000 (step 10) synced with the *_meters number input, walking hint ("≈ 1 min walk" ≤100 m, "≈ N min walk" at 80 m/min, "shuttle bus" >1000 m).
   3 Prices — tier cards: Quad (highlighted "shown as from"), Triple, Double, Single with small bed icons; Child + bed, Child no bed, Infant with age hints. Big ৳ inputs with live South-Asian grouping (1,45,000); strip commas before submit. Food note.
   4 Journey & terms — Included / Not included tag inputs (Enter or comma adds, Backspace removes last, × removes, drag to reorder, pasted multi-line text splits) each serialised into its hidden textarea (`inclusions`, `exclusions`). Day-by-day timeline (D1, D2… bubbles, one input per day, remove, "Add day", drag to reorder) → hidden `itinerary`. Documents, Cancellation.
   5 Contact — agency / phone / WhatsApp overrides with Settings values as placeholders + "Leave empty to use Settings".
4. Section nav: tick per section when required fields are filled (Basics: title + days; Hotels: both hotel names; Prices: quad > 0; Journey: ≥1 inclusion; Contact always ticked), "60% complete" meter, scroll-spy (IntersectionObserver), smooth scroll.
5. Live preview phone: hero (eyebrow, title, day/night chips), two hotel boxes with distance text, Quad/Triple prices, route, WhatsApp CTA — all update on input (data-pk-pv / data-pk-pvo pairs as in the mockup).
6. Dark sticky save bar (form has data-pk-dirty): "Unsaved changes", Discard, gold "Save package" + Ctrl/Cmd+S. Remove the old bottom actions. After save (?updated=1) toast "Package saved".
```

---

## Prompt 4 — Inquiries

```
<<paste GLOBAL RULES>>

TASK: Redesign Screens::inquiries() as the "Inquiries" screen + detail drawer in docs/redesign-mockup.html. Header: "Inquiries" / "Every name from the site form. Click a row to see details and reply." Actions row: ghost "Export CSV" (existing form).

1. Toolbar: chips All / Waiting / Replied with real counts (add InquiryRepository::count_by_status()), search ("/" focuses it), "Any share" select.
   Filtering becomes server-side: status, s, share as GET params; extend InquiryRepository::paginate() and the counts with optional filters ($wpdb->prepare + esc_like). JS: 300 ms debounce, history.replaceState, AJAX `pothik_inquiries_table` returns {tbody, counts, pagination} so the page doesn't reload. No-JS: a visible "Filter" submit. Support ?status=waiting from the Desk.
2. Table card: sticky header; columns: checkbox · Pilgrim (avatar, name, package, Demo pill) · Phone · City · Share (pill) · Pilgrims (👤 adults, 🧒 children, 👶 infants — only non-zero) · Status (pill that toggles Waiting ⇄ Replied via AJAX pothik_toggle_reply + toast) · Received (relative, full date in title) · Call / WhatsApp (fade in on row hover; always visible on touch). Clicking the row (not a control) opens the drawer.
3. Bulk: select-all + row checkboxes → dark sticky bar "N selected · Mark replied · Export · Delete". New admin-post `pothik_bulk_inquiries` (op = reply|delete|export, ids[]); export reuses the CSV writer with an id filter; delete confirms inside the bar.
4. Drawer (right, 440px, blurred overlay, Esc/overlay closes, focus trap): avatar + name + package + time, status pill, fact grid (Phone, City, Room share, Pilgrims, Trip, Received), note in a gold quote box, "Quick WhatsApp replies" (3 translatable templates, filter `pothik/reply_templates`, opens wa.me with name + package filled in), footer Call / WhatsApp / Mark replied and a small Delete. Data comes from data-* on the row.
5. Pagination as pill buttons + "Showing 21–40 of 132".
6. Empty states: no inquiries ("No inquiries yet" + button that copies the package page URL) vs no matches ("Nothing matches").
```

---

## Prompt 5 — Receipt

```
<<paste GLOBAL RULES>>

TASK: Redesign Screens::receipt() as the "Receipt" screen in docs/redesign-mockup.html. Field names (name, amount, channel, trx, date, note) and pothik_save_receipt stay the same. Header: "Payment receipt" / "Fill the form — the slip updates as you type. Print it or paste it on WhatsApp."

1. Two columns (form / 400px sticky slip; stacked under 960px).
2. Amount card: big ৳ input with live grouping (strip commas on submit); amount in words under it — English lakh/crore ("Forty thousand taka only") or Bangla ("চল্লিশ হাজার টাকা মাত্র") by I18n::is_bn(); JS for live, PHP Format::words() for the printed slip without JS. Chips +10,000 / +25,000 / +50,000 / Clear.
3. Channel card: 5 radio tiles bKash, Nagad, Rocket, Bank, Cash with coloured letter badges (no real brand logos); selected tile gets its colour border + soft ring.
4. Details card: Paid by (+ <datalist> of the last 20 inquiry names), Date (type=date), Transaction ID (hint "from the bKash / Nagad SMS"), Note. Form has data-pk-dirty → save bar.
5. Slip: green header with agency logo (fallback pin), name, licence + phone; big amount with a small bump animation on change; amount in words; rotated "PAID" stamp; dashed rows Paid by / Channel / Transaction ID / Date (localised) / Note; zig-zag bottom edge; live updates.
6. Under the slip: green "Copy for WhatsApp" (multi-line text, toast "Copied — paste it in WhatsApp") and "Print". Print CSS: only the slip, centred, A5-friendly. Hint "Free version keeps one receipt. Full history is in Pro." (hidden when Pro active).
```

---

## Prompt 6 — Analytics

```
<<paste GLOBAL RULES>>

TASK: Redesign app/Admin/Analytics.php render() as the "Analytics" screen in docs/redesign-mockup.html. Keep the timezone logic and the demo-row exclusion exactly as they are, keep do_action('pothik/analytics'), and keep the public helpers columns()/ring()/bars() with the same signatures (Pro may call them) — only their markup changes. Header: "Analytics" / "Where your pilgrims come from and what they ask for." Actions row: segmented 7 days / 30 days / All (?range= links).

1. Four stat cards: Inquiries (+ trend vs previous equal period, hidden for All) · Still waiting · Pilgrims asked (sub: adults · children · infants) · Reply rate %.
2. "Inquiries by day": SVG column chart rendered in PHP (gridlines, rounded green-gradient bars, light bars for zero days, x labels every Nth day), bars grow in with a staggered transition, JS tooltip "24 Oct · 3 inquiries" that dims other bars, plus a .screen-reader-text table of the same data.
3. Three cards: By room share (donut with small gaps + legend; hovering a segment or legend row enlarges it, dims the rest and swaps the centre number) · Top cities (7 animated horizontal bars) · Umrah vs Hajj bars + semicircle reply-rate gauge.
4. Empty state for a range with no rows.
Replace the old .pk-cols / .pk-ring / .pk-bars styles.
```

---

## Prompt 7 — Settings, Pro page, cleanup

```
<<paste GLOBAL RULES>>

TASK A — Settings::render() as the "Settings" screen in docs/redesign-mockup.html. Same option, field names and actions (pothik_save_settings, pothik_recreate_page). Header: "Settings" / "Agency details, notifications, the public page and plugin data."
1. Left vertical tabs (Agency, Notifications, Public page, Data — icon + one-line description), right panes; client-side switching, active tab kept in the URL hash. No-JS: all panes visible one after another. ONE form wraps Agency + Notifications + Data so one save posts everything.
2. Agency: name EN + BN, phone, WhatsApp with "+" prefix, licence; logo drop-zone card (click/drag opens wp.media; keep hidden logo_id/logo_url and the data-pk-media-* hooks), preview, Remove.
3. Notifications: notification emails as a tag input (invalid emails shake and are refused) serialised to the comma-separated notification_emails field; "Send test" → AJAX `pothik_test_email` (wp_mail to those addresses, toast result). Country code with "+" prefix. Spam guard range slider 1–30 synced with rate_limit + live value.
4. Public page (outside the form): status row with dot (published / draft / missing) + URL; copyable [pothik_catalog]; a package select that builds [pothik_package id="…"] with copy; "Recreate page" (existing form).
5. Data: Load / Remove demo data row (existing forms); clay danger card with delete_on_uninstall as .pk-switch + clear warning.
6. Sticky save bar instead of the bottom button. No inline styles left in Settings.php.

TASK B — Screens::pro() as the "Pothik Pro" screen: dark hero with gold gradient headline, 6 feature cards, comparison table (✓ / —, Pro column emphasised), gold CTA top and bottom. Keep POTHIK_UPGRADE_URL and the 'pothik/upgrade_url' filter.

TASK C — Cleanup: remove every `/* legacy */` CSS rule no longer used (grep each class), remove dead PHP helpers (old module_grid, inquiry_line if unused), no duplicate selectors, regenerate pothik.pot + bn_BD.mo, bump POTHIK_VERSION to 0.3.0, add readme changelog "Redesigned admin: own sidebar, full-width mode, new Desk with season countdown". List any string still missing a Bangla translation.
```

---

## Prompt 8 — QA (সব শেষে একবার)

```
Review the Pothik admin redesign against docs/redesign-mockup.html and fix what you find. Report as a checklist:
- Full-width toggle: hides/shows the WP admin menu with one click each, is remembered after reload and on every Pothik page, never affects other wp-admin pages, works with Ctrl/Cmd+Shift+F, hidden under 782px.
- Sidebar collapse remembered; nothing overlaps the sidebar user card; WP footer hidden on Pothik screens; other plugins' notices appear below our header.
- Every admin-post action still works with JS disabled (compare form field names with the handlers in PackageStore / InquiryStore / ReceiptStore / Settings / Plugin).
- Every AJAX endpoint: nonce, manage_options, sanitised input, JSON responses, no PHP notices or warnings.
- Countdown ticks every second and never shows 00:00:00 outside the season; Hijri date correct in English and Bangla; Makkah time correct.
- No inline style="" left in app/Admin/*.php except meter/chart sizes. No string without I18n::t().
- Bangla mode at 1280px and 782px: no overflow on Desk, Editor, Inquiries.
- Keyboard-only run through sidebar → Desk → Inquiries drawer → Editor stars/tags → Settings tabs; Esc closes overlays; focus returns.
- prefers-reduced-motion stops tawaf motion, crossfades, shimmer, count-up and slide animations.
- WP.org Plugin Check passes: no external requests, escaping OK, docs/ excluded from the release ZIP.
```
