# Pothik Admin Redesign — Ready-to-paste prompts (Cursor / Claude Code)

**কীভাবে ব্যবহার করবেন**

1. `pothik-admin-redesign-mockup.html` ফাইলটা প্লাগিন রুটে `docs/redesign-mockup.html` হিসেবে রাখুন (এটা শুধু রেফারেন্স, প্লাগিনে লোড হবে না)। প্রতিটা প্রম্পট AI-কে এই ফাইল পড়তে বলবে।
2. প্রম্পটগুলো **ক্রমানুসারে** চালান — Prompt 0 আগে, কারণ বাকিগুলো এর design system-এর উপর দাঁড়ানো।
3. প্রতিটা প্রম্পটের পর সাইটে চেক করে git commit করুন, তারপর পরেরটা।
4. Prompt 0-এর "GLOBAL RULES" ব্লকটা প্রতিটা প্রম্পটে আবার পেস্ট করা আছে যাতে নতুন চ্যাটেও কাজ করে।

> ⚠️ **এখনই ঠিক করার মতো বাগ:** সাইটে Inquiries পেইজ খুললে fatal error আসছে — `Screens.php` লাইন ~327-এ `InquiryRepository::paged()` কল করা হয়েছে, কিন্তু রিপোজিটরিতে মেথডের নাম `paginate()` এবং সেটা `['items','total','pages','page']` অ্যারে রিটার্ন করে। Prompt 4-এ এর ফিক্স যোগ করা আছে; চাইলে আগেই এই এক লাইনের প্রম্পট দিন:
> `In app/Admin/Screens.php inquiries(), replace InquiryRepository::paged($page, $per_page) with InquiryRepository::paginate($page, $per_page); use $result['items'] as $rows and $result['pages'] as $total_pages. Run php -l.`

---

## GLOBAL RULES (প্রতিটা প্রম্পটের শুরুতে থাকে)

```
GLOBAL RULES — Pothik admin redesign
- Plugin: Pothik (WordPress, PHP 8.1, namespace Pothik\, no build step). Files: app/Admin/{Chrome,Screens,Settings,Analytics,Menu}.php, assets/css/admin.css, assets/js/admin.js, app/I18n.php, languages/*.
- Visual reference: docs/redesign-mockup.html. Open it and match its layout, spacing, colours, radii, shadows, motion and interactions. Copy CSS tokens and component styles from it, but scope EVERY selector under `.pk-admin` (the mockup's fake WP sidebar/admin bar `.wp*` must NOT be ported).
- Do NOT change any backend contract: keep every existing `admin-post.php` action name, form field `name`, nonce action, `check_admin_referer`, capability check, redirect and query arg (`updated`, `error=limit`, `demo_loaded`, etc.). Stores (PackageStore, InquiryRepository, InquiryStore, ReceiptStore) stay untouched unless a step says otherwise.
- Progressive enhancement: every action must still work as a normal form POST with JS disabled. JS only enhances (AJAX, live preview, drawers, toasts).
- Vanilla JS only, no jQuery dependency, no React, no CDN, no external fonts/images (WP.org rules). Keep using the self-hosted WOFF2 fonts in assets/fonts. Icons = one inline SVG sprite printed once by Chrome::open() (copy the <symbol>s from the mockup).
- All user-visible strings go through I18n::t() and get a Bangla translation in languages/bn_BD.po (+ regenerate .mo) and an entry in pothik.pot. Never hard-code English in JS: pass strings via wp_localize_script / wp_add_inline_script as `window.pothikAdmin.i18n`.
- Escape everything (esc_html, esc_attr, esc_url, wp_kses for the SVG sprite). No inline `style=""` attributes for layout — move all of them into admin.css classes (the current code has many; remove them as you touch each screen).
- Accessibility: visible focus rings, buttons are <button>, aria-expanded on menus, aria-live for toasts, Esc closes overlays, focus is trapped in drawer/palette and returned on close, colour contrast ≥ 4.5:1, respects prefers-reduced-motion.
- Responsive down to 782px (WP mobile admin breakpoint) and 600px.
- Bangla mode: layouts must not break with longer Bangla labels; use Noto Sans Bengali line-height 1.5.
- After changes: run `php -l` on every touched PHP file and report a short summary of files changed.
```

---

## Prompt 0 — Design system + new app shell (Chrome)

```
<<paste GLOBAL RULES>>

TASK: Build the Pothik admin design system and the new shared header ("app bar"). Nothing else on the screens yet.

1. assets/css/admin.css — restructure into sections with comments:
   a) @font-face (keep existing).
   b) Tokens on `.pk-admin`: copy the full :root token list from the mockup (--g950…--g50, --gold, --gold-100, --clay, --clay-100, --clay-50, --ink, --ink-2, --muted, --faint, --line, --line-2, --paper, --sheet, --wa, --wa-ink, --bkash, --nagad, --rocket, --bank, --cash, radii --r-sm/--r/--r-lg/--r-xl, shadows --sh-1/2/3, --ease, --sans).
   c) Make the whole plugin screen use the paper background: `body[class*="pothik"] #wpcontent{background:var(--paper)}` (only on Pothik screens — check the admin body class WordPress adds for our pages, e.g. `toplevel_page_pothik` and `pothik_page_pothik-*`).
   d) Components (port from mockup, scoped): .pk-btn (+ -primary, -ghost, -wa, -gold, -danger, -sm, -lg), .pk-icon-btn (+ .wa), .pk-card, .pk-card-head, .pk-card-pad, .pk-pill (+ .wait with pulsing dot, .done, .draft, .live, .demo, .plain), .pk-chip/.pk-chips (with count badge), .pk-seg (segmented control), .pk-switch (CSS toggle on a real checkbox), .pk-field (label/input/select/textarea + hint <em>), .pk-affix (prefix like ৳ or +), .pk-search (icon input), .pk-meter, .pk-kpi, .pk-empty (icon + title + text), .pk-toast, .pk-drawer, .pk-cmdk, .pk-savebar, .pk-menu-pop.
   e) Keep old class names working only where other screens still use them; mark them `/* legacy — remove after Prompt 7 */`.
   f) @media (prefers-reduced-motion) and print rules from the mockup.

2. app/Admin/Chrome.php — rewrite open():
   - Print the SVG icon sprite once (static flag) — all symbols from the mockup.
   - Sticky app bar `.pk-appbar` (sticky top: 32px for admin bar; 46px under 782px) with: brand mark + "Pothik / Umrah desk"; pill tab nav `.pk-tabs` linking to Desk, Packages, Inquiries, Receipt, Analytics, Settings, Pro (Pro shown with gold sparkle only when any module is locked — reuse Modules::all()/is_open()); active tab = current `page` query var (package editor counts as Packages); Inquiries tab shows a clay badge with the count of WAITING inquiries (add InquiryRepository::count_waiting() — a cheap COUNT query where replied=0, cached in a 60s transient that you clear in InquiryStore on create/reply/delete).
   - Right tools: search button (opens command palette, shows "Ctrl K"), existing language toggle restyled as .pk-seg (keep I18n::toggle() URLs), "Site" ghost button (existing PackagePage::url()).
   - Under the app bar: page head `.pk-page-head` with h1 + lead (existing $title/$lead args) and an optional right-side actions slot: change signature to open(string $title, string $lead = '', string $actions_html = '').
   - Add class `is-stuck` to the app bar on scroll (JS).
   - Chrome::saved() → instead of a <p>, output a hidden `<div data-pk-flash="Saved.">` that JS turns into a toast; without JS it is a visible WP-style notice.
   - Also convert the `demo_loaded/demo_removed/recreated/error=limit` notices to the same flash mechanism.

3. assets/js/admin.js — keep all existing handlers working, then add small modules (IIFE, no globals except window.pothikAdmin):
   - toast(msg, {undo}) with aria-live region, auto-hide 3.6s, optional Undo button.
   - flash reader for [data-pk-flash].
   - appbar stuck state.
   - Command palette (Ctrl/Cmd+K, also the search button): groups "Go to" (all tabs), "Actions" (New package → submits the existing pothik_new_package form, Export CSV → submits export form, Copy [pothik_catalog]), "Pilgrims" (fetched lazily via a new AJAX endpoint `pothik_search_inquiries` returning max 6 {name, phone, city, url}; nonce + manage_options; uses InquiryRepository search on name/phone/city with $wpdb->prepare + esc_like). Arrow keys, Enter, Esc, focus trap.
   - Generic copy-to-clipboard for [data-pk-copy] with toast.
   - Count-up animation for [data-pk-count] and animated meters [data-pk-w] when they enter the viewport.
   - Unsaved-changes save bar helper: any form with [data-pk-dirty] shows `.pk-savebar` after the first input, Ctrl/Cmd+S submits it, and `beforeunload` warns if dirty.

4. Menu::assets — enqueue as before; add wp_localize_script('pothik-admin','pothikAdmin',{ajax, nonce, i18n:{…all JS strings…}}).

Show me the diff summary when done. Do not touch screen bodies yet except for what Chrome::open's new signature requires.
```

---

## Prompt 1 — Desk (dashboard)

```
<<paste GLOBAL RULES>>

TASK: Redesign Screens::overview() to match the "Desk" screen in docs/redesign-mockup.html.

Layout, top to bottom:
1. HERO card `.pk-hero` (deep green gradient + faint 8-point star pattern from the mockup as an inline SVG data-URI, gold radial glow):
   - Left: eyebrow with today's date via wp_date('l, j F'); greeting "Assalamu alaikum, {display_name}." and second line "{N} pilgrims are waiting for a reply." (N in gold) — if N = 0 show "All caught up. New inquiries will show up here."; one helper sentence; buttons: gold "Open reply queue" → pothik-inquiries&status=waiting, ghost-on-dark "New package" (existing pothik_new_package form).
   - Right: "Finish setting up" checklist with a conic progress ring (x/5). Items computed from real data: agency name+phone set (Settings), WhatsApp set, at least one package, at least one published package, logo set. Done items get gold tick + strikethrough; undone items link to the right screen. Hide the whole checklist (and widen the left side) once all 5 are done, or after the user clicks a small "Hide" link (store in user meta `pothik_hide_setup`, via admin-post with nonce).
2. KPI row (4 cards, clickable, hover lift):
   - Inquiries last 7 days + trend % vs previous 7 days (green ▲ / clay ▼) + tiny SVG sparkline of 7 daily counts (render the path in PHP; reuse Analytics' timezone-safe day bucketing — move the helper into a small shared method instead of duplicating it).
   - Waiting count (clay icon, pulsing "Needs reply" pill) + "oldest X ago" via human_time_diff.
   - Packages count with "of 40 free" and a meter (hide the cap text when Pro defines unlimited — check the same constant/filter PackageStore uses for the 40 limit), sub-text "N published".
   - Last receipt amount (Format::money) + channel pill coloured by channel (bKash pink, Nagad orange, Rocket purple, Bank blue, Cash green) + date.
   Numbers use data-pk-count for the JS count-up; print the real final value as text too (no-JS safe).
3. Two columns (1.55fr / 1fr; stack under 960px):
   - Left "Reply queue" card: up to 5 WAITING inquiries (add InquiryRepository::recent_waiting(5)). Each row: coloured initials avatar (colour chosen deterministically from the name; support Bangla names — use mb_substr), name, "package · share · city · N pilgrims", relative time, icon buttons Call (tel:), WhatsApp (existing Format::whatsapp_href) and a dashed "check" button that marks replied.
     * The check button is inside the existing pothik_reply_inquiry form (works without JS). With JS: intercept, POST via a new AJAX action `pothik_toggle_reply` (nonce, manage_options, returns {replied, waiting_count}), animate the row out (slide right + fade), show toast "{name} marked replied" with Undo (calls the same endpoint again), update every [data-pk-wait-count] on the page (hero, KPI, tab badge).
     * Empty state: icon + "All caught up, alhamdulillah" + sub text.
   - Right column: "Quick actions" 2×2 tiles (New package, Payment slip → receipt, Export CSV → existing export form, Copy shortcode [pothik_catalog]) and a compact "Packages" list (colour thumb showing days, title, "from ৳…", Live/Draft pill, link to editor) with a "Manage →" link.
4. "Grow with Pothik Pro" strip: one small card per LOCKED module from Modules::all() (icon map: departures→plane, pilgrims→passport, rooming→bed, ledger→wallet, visa→shield, fallback spark), lock badge, gold shimmer sweep on hover, links to pothik-pro. Hide the section if Pro is active / nothing is locked. Unlocked modules are NOT shown here (they are in the tab bar now) — remove the old module_grid() from the Desk.
5. Footer line: "Trying things out? Load demo data / Remove demo data" as a text-link that submits the existing demo forms (keep nonces). Rows from demo data show a `.pk-pill.demo` "Demo" chip.

Remove the old .pk-stats/.pk-module-grid markup from Desk. Remove all inline styles from overview(). Add every new string to I18n + bn_BD.po.
```

---

## Prompt 1B — Desk hero: Makkah & Madinah feel (Prompt 1-এর পরে চালান)

```
<<paste GLOBAL RULES>>

TASK: Turn the Desk hero into the "Haramain night" hero from docs/redesign-mockup.html (section `.hero`, `#scMakkah`, `#scMadinah`, `.talbiyah`, `.place`, `.season`, and the "Haramain hero" JS block). Goal: the moment the agency owner opens the Desk it should feel like evening over the Haramain — calm, warm, full of love — without hurting usability.

1. Background: a deep night gradient (#071812 → #0f2a21 → #173d30) with a warm gold glow at the bottom, the faint 8-point star pattern fading out downward, a thin gold geometric border along the top edge (repeating-linear-gradient from the mockup), ~45 twinkling stars (positions generated in JS; render nothing without JS) and a gold crescent moon made with CSS box-shadow.
2. Skyline (bottom 190px, full width, `aria-hidden="true"`, pointer-events none, z-index under the content): two inline SVG scenes copied from the mockup, nothing loaded from outside:
   - Makkah: hills, the clock-tower silhouette with a softly glowing gold clock face and lit windows, Haram minarets and arcade, the Kaaba (black with gold band and door) with a soft gold halo and two dotted tawaf rings whose dots move (animate stroke-dashoffset, NOT rotation).
   - Madinah: white minarets with gold lights, white arcade, the Green Dome with gold crescent finial, and the courtyard umbrellas.
   The scenes crossfade every 9 s (opacity + 10px rise, 1.6 s). Two chips at bottom-left switch scene manually ("🕋 Makkah al-Mukarramah 08:15" with the live local Makkah time, "🕌 Madinah al-Munawwarah"); a manual click stops the auto-rotation. Save the last chosen scene in localStorage (wrapped in try/catch).
   Print both SVGs from a new template partial templates/admin/hero-skyline.php so Screens.php stays readable.
3. Text column:
   - Talbiyah line in Arabic, gold with soft glow, lang="ar" dir="rtl" but left-aligned: لَبَّيْكَ اللَّهُمَّ لَبَّيْكَ. Font: self-host Amiri Bold (SIL OFL) as a SUBSET WOFF2 in assets/fonts/amiri-700-talbiyah.woff2 containing only the glyphs of this line (use `pyftsubset Amiri-Bold.ttf --text="لَبَّيْكَ اللَّهُمَّ لَبَّيْكَ" --flavor=woff2 --layout-features='*'`), with a serif fallback stack. Add Amiri's OFL licence to readme credits.
   - Eyebrow: Hijri date (Umm al-Qura) · Gregorian date. PHP: if class_exists('IntlDateFormatter') use `new IntlDateFormatter($locale.'@calendar=islamic-umalqura', IntlDateFormatter::LONG, IntlDateFormatter::NONE, wp_timezone_string(), IntlDateFormatter::TRADITIONAL, 'd MMMM y')` (locale bn_BD in Bangla mode, en_GB otherwise) and append "AH"/"হিজরি"; else output an empty span that JS fills with Intl.DateTimeFormat('en-GB-u-ca-islamic-umalqura' / 'bn-u-ca-islamic-umalqura'). Never hard-code Hijri dates.
   - Headline: "Assalamu alaikum, {name}." + "{N} guests of Allah are waiting for your reply." (N gold). Zero state: "All guests of Allah have been answered. Alhamdulillah."
   - A gentle rotating line (fade every 7 s, 4 lines, all translatable, filter `pothik/desk_lines` so agencies can add their own): "Every reply you send could be someone's first step towards the Haram." / "May Allah accept the Umrah of every pilgrim you serve." / "Serve the guests of Ar-Rahman with ihsan — they remember the agency that cared." / "A pilgrim's dua at the Multazam may include your name."
   - Buttons as before (gold "Open reply queue", glass "New package").
4. Season chips at bottom-right: "🌙 Ramadan in N days" and "🐪 Hajj in N days" (to 8 Dhul Hijjah). Compute by walking forward day by day with the Umm al-Qura calendar (PHP IntlDateFormatter or JS Intl formatToParts) until month 9 day 1 / month 12 day 8 — max 400 iterations. Hide a chip during the month itself and show "Ramadan Mubarak 🌙" / "Hajj days 🤍" instead. These help agencies push Ramadan/Hajj packages on time.
5. The setup checklist card sits on the right in a dark glass panel with gold border (rgba(7,24,18,.45) / rgba(212,166,74,.25)).
6. Responsive: under 960px the content stacks, chips become static rows above the skyline and the hero gets padding-bottom:200px; the moon moves to the right edge. Under 600px hide the Madinah umbrellas and side buildings (class .hide-sm) so the Kaaba/Dome stay centred.
7. prefers-reduced-motion: no twinkle, no tawaf dot motion, no auto crossfade, no rotating lines (show the first line only).
8. Respectful details: no human figures or faces in the art, keep the Kaaba and the Green Dome simple and dignified, never place buttons/text over them (keep them in the centre of the skyline, chips on the sides).
9. Add every new string to I18n + bn_BD.po (e.g. "অতিথিরা আপনার উত্তরের অপেক্ষায়" style Bangla — natural, warm, not literal).
```

---

## Prompt 2 — Packages list

```
<<paste GLOBAL RULES>>

TASK: Redesign Screens::package_list() + package_row() + package_menu() to match the "Packages" screen in docs/redesign-mockup.html.

1. Page head actions: primary large "New package" button (existing pothik_new_package form).
2. Toolbar: search input (client-side filter on title + hotel names), filter chips All / Published / Draft with counts, right side usage meter "3 of 40 packages" (hide limit when unlimited).
3. Responsive card grid (auto-fill minmax(300px,1fr)). Each card `.pk-pkg`:
   - Cover band with gradient + star pattern. Pick the gradient pair deterministically from the package id (4–5 tasteful pairs from the mockup: green, gold-brown, blue, clay, plum). Big "7 days", Draft/Published pill, frosted chips "4 nights Makkah" / "2 Madinah".
   - Body: title; two hotel rows (city label, hotel name ellipsis, gold stars from makkah_stars/madinah_stars — parse the stored value safely to 0–5), and a distance meter (closer = fuller: width = max(6, 100 - metres/20)%) with "150 m".
   - Footer: "from, quad" + lowest non-zero price (Format::money); publish toggle `.pk-switch`; edit icon button; kebab menu (Edit, View on site, Copy shortcode `[pothik_package id="…"]`, Duplicate, divider, Delete in clay).
   - The publish switch is wrapped in the existing pothik_toggle_package_status form; without JS it is a submit button styled as a switch. With JS: AJAX `pothik_toggle_package_status_ajax` (nonce, cap) → update pill + toast "Published — live on your site" / "Moved to draft". Do the same for Duplicate (AJAX returns rendered card HTML to insert) and Delete (confirm via a small inline confirm in the menu, not window.confirm; on success fade the card out + toast with no undo).
   - Keep the menu keyboard-accessible: aria-expanded, Esc closes, arrow keys move between items, clicking outside closes. Open the popover upward if it would overflow the viewport.
4. Last grid item: dashed "New package" tile with + icon that rotates on hover (submits pothik_new_package).
5. Empty state (no packages): big friendly card with icon, "Create your first Umrah package", short line, primary button + secondary "Load demo data".
6. Keep `?error=limit` → flash toast + a gold banner above the grid explaining the 40 limit with an "Upgrade" link.
Delete the old .pk-pkg-row CSS once nothing uses it.
```

---

## Prompt 3 — Package editor

```
<<paste GLOBAL RULES>>

TASK: Redesign Screens::package_form() to match the "Editor" screen in docs/redesign-mockup.html. The POST payload must stay IDENTICAL (same field names, same textarea newline format for inclusions/exclusions/itinerary, same price_* names, same status values) so PackageStore::handle_save needs no change. Verify by comparing field names before/after.

Layout: 3 columns — left sticky section nav (210px), form (fluid), right sticky live preview (320px). Under 1180px hide preview behind a "Preview" button that opens it in the drawer; under 960px the nav becomes a horizontal scroll strip.

1. Page head: "← All packages" back link, h1 = package title (updates live as the title field changes), right side: Draft/Published segmented control (bound to the existing `status` select — keep the select in the DOM visually hidden for no-JS, or render two radio buttons named `status`), "Preview" ghost button (opens public URL in new tab).
2. Language note bar (gold): "Editing English / Editing বাংলা — empty Bangla fields fall back to English" depending on I18n::is_bn(). Keep the existing placeholder fallback logic.
3. Section cards with numbered badges and a one-line helper each:
   1 Basics: Package name (full width), Eyebrow, Flight direction, Days / Makkah nights / Madinah nights as steppers (− input +, inputs remain type=number with the same names), live check line "✓ 6 nights fit inside 7 days" / clay warning if nights ≥ days, Group note.
   2 Hotels: two side-by-side hotel cards (Makkah, Madinah) with hotel, "or similar", star rating widget (5 clickable stars + hover preview, writes into the existing *_stars input which becomes hidden; keyboard: arrow keys), distance = range slider 0–2000 step 10 synced with the existing *_meters number input, plus a walking hint ("≈ 1 min walk", "≈ N min walk" at 80 m/min, "shuttle bus" above 1000 m).
   3 Prices: tier cards grid (Quad highlighted as "shown as from", Triple, Double, Single with little bed icons; Child + bed, Child no bed, Infant with age hints) — each has a big number input with ৳ prefix and live South-Asian grouping (1,45,000) while typing; on submit strip commas so the server still receives plain integers. Food note field.
   4 Journey & terms: Included / Not included as tag inputs (Enter or comma adds, Backspace removes last, × removes, drag to reorder, paste of multi-line text splits into tags). Each tag input keeps a hidden <textarea name="inclusions"> / "exclusions" updated with one item per line. Day-by-day as a vertical timeline (D1, D2… bubbles, one input per day, remove button, "Add day" button, drag to reorder) serialised to the hidden `itinerary` textarea. Documents + Cancellation fields.
   5 Contact: agency, phone, WhatsApp overrides with the Settings values as placeholders and helper "Leave empty to use Settings".
4. Left nav: each item shows a completion tick when its required fields are filled (Basics: title+days; Hotels: both hotel names; Prices: quad>0; Journey: ≥1 inclusion; Contact: always optional → tick). Progress meter "60% complete". Scroll-spy highlights the section in view (IntersectionObserver). Smooth scroll on click.
5. Right live preview: a phone frame showing a mini landing page — hero (eyebrow, title, days/nights chips), two hotel boxes with distance text, Quad/Triple prices, flight route, WhatsApp CTA. Everything updates on input (data-pk-pv / data-pk-pvo attribute pairs like the mockup).
6. Sticky dark save bar (bottom centre) appears after the first change: pulsing gold dot, "Unsaved changes", Discard (reload) and gold "Save package" with Ctrl/Cmd+S hint. Warn on leaving with unsaved changes. Remove the old bottom .pk-form-actions.
7. After save (`updated=1`) show toast "Package saved" and briefly flash the title.
```

---

## Prompt 4 — Inquiries

```
<<paste GLOBAL RULES>>

TASK: Redesign Screens::inquiries() to match the "Inquiries" screen + detail drawer in docs/redesign-mockup.html.

1. Page head actions: ghost "Export CSV" (existing export form).
2. Toolbar: status chips All / Waiting / Replied with real counts (add InquiryRepository::count_by_status()), search input ("/" focuses it), "Any share" select.
   BUG TO FIX FIRST: Screens::inquiries() calls InquiryRepository::paged($page, $per_page), which does not exist (fatal error "Call to undefined method Pothik\InquiryRepository::paged()" on line ~327). The repository method is paginate(), and it returns ['items','total','pages','page'] — use $result['items'] for rows and $result['pages'] for pagination.
   IMPORTANT: today's filter is client-side and only filters the current page of 20. Make filtering server-side: chips/search/share become GET params (status, s, share) on the same page URL; InquiryRepository::paginate() and count gain optional filters (use $wpdb->prepare, esc_like). JS enhances with debounce (300ms) + history.replaceState and fetches the table body via AJAX `pothik_inquiries_table` (returns rendered <tbody> + counts + pagination HTML) so the page doesn't reload. Without JS a "Filter" submit button is visible.
   Support ?status=waiting coming from the Desk hero button.
3. Table card: sticky header, columns: checkbox, Pilgrim (initials avatar + name + package title + Demo chip), Phone, City, Share (plain pill), Pilgrims (small chips 👤 adults, 🧒 children, 👶 infants — only non-zero), Status (pill that is itself a toggle button: Waiting ⇄ Replied, AJAX `pothik_toggle_reply`, toast), Received (human_time_diff + full date in title attribute), row actions Call / WhatsApp (fade in on hover, always visible on touch).
   Row click (not on a control) opens the drawer.
4. Bulk actions: select-all checkbox + row checkboxes. When ≥1 selected show a dark sticky bulk bar: "N selected", Mark replied, Export selected, Delete. Add admin-post handlers `pothik_bulk_inquiries` (op = reply|delete|export, ids[]; nonce + cap; export reuses the CSV writer with an id filter). Delete asks for confirmation inside the bar ("Delete 3? Yes / Cancel").
5. Detail drawer (right slide-over, 440px, overlay with blur, Esc/overlay click closes, focus trap): header avatar + name + package + time; status pill; fact grid (Phone, City, Room share, Pilgrims, Trip type, Received); note in a gold quote box; "Quick WhatsApp replies" — 3 template buttons that open wa.me with the template + pilgrim name + package pre-filled (templates are translatable strings; add a filter `pothik/reply_templates` so Pro can add more); footer: Call, WhatsApp, Mark replied/Mark waiting, and a small Delete text button. Drawer data comes from data-* attributes already on the row (no extra request).
6. Pagination: restyle paginate_links output as pill buttons; show "Showing 21–40 of 132".
7. Empty states: no inquiries at all (icon + "No inquiries yet" + "Share your package page" button copying the page URL) vs. no matches (icon + "Nothing matches").
```

---

## Prompt 5 — Receipt

```
<<paste GLOBAL RULES>>

TASK: Redesign Screens::receipt() to match the "Receipt" screen in docs/redesign-mockup.html. Field names (name, amount, channel, trx, date, note) and the pothik_save_receipt action stay the same.

1. Two columns (fluid form / 400px sticky slip; stack under 960px).
2. Form cards:
   - Amount: very large input with ৳ prefix, live grouping (1,45,000) — strip commas on submit; under it the amount in words, English ("Forty thousand taka only", lakh/crore system) or Bangla ("চল্লিশ হাজার টাকা মাত্র") depending on I18n::is_bn() — implement both in JS (and a PHP version in Format::words() for the printed slip without JS). Quick-add chips +10,000 / +25,000 / +50,000 / Clear.
   - Channel: 5 radio tiles (bKash, Nagad, Rocket, Bank, Cash) each with a coloured letter badge (NOT the real brand logos — just coloured initials), selected tile gets its colour border + soft ring. Real <input type=radio name="channel">.
   - Paid by (with a <datalist> of the last 20 inquiry names), Date (type=date), Transaction ID with hint "from the bKash / Nagad SMS", Note. Save button bottom-right.
3. Slip (right): ticket-style card — green header with agency logo (Settings logo_url, fallback pin mark), agency name, license + phone; big amount that does a small "bump" animation when it changes; amount in words; rotated "PAID" stamp; dashed rows Paid by / Channel / Transaction ID / Date (formatted with wp_date / toLocaleDateString in the right locale) / Note; zig-zag bottom edge. Updates live on every input.
4. Actions under the slip: green "Copy for WhatsApp" (multi-line text with agency name, amount + words, payer, channel, TrxID, date — toast "Copied — paste it in WhatsApp"), ghost "Print". Print CSS: only the slip, centred, no shadows, A5-friendly.
5. Small hint "Free version keeps one receipt. Full history is in Pro." (hide when Pro active).
6. Save bar behaviour from Prompt 0 applies (form gets data-pk-dirty).
```

---

## Prompt 6 — Analytics

```
<<paste GLOBAL RULES>>

TASK: Redesign app/Admin/Analytics.php render() to match the "Analytics" screen in docs/redesign-mockup.html. Keep the timezone logic and the demo-row exclusion exactly as is (see the docblock checklist) and keep do_action('pothik/analytics').

1. Page head right: segmented 7 days / 30 days / All (links with ?range=, JS can swap without reload but links must work).
2. KPI row: Inquiries (+ trend vs previous equal period; hide trend for "All"), Still waiting, Pilgrims asked (sum, sub-text adults · children · infants), Reply rate % (replied / total).
3. "Inquiries by day" card: SVG column chart rendered in PHP (viewBox, preserveAspectRatio none, gridlines, rounded bars with green gradient, zero days as light bars, x labels every Nth day), bars grow in with staggered CSS transition, JS hover tooltip "24 Oct · 3 inquiries" and dims other bars. Provide an accessible <table class="screen-reader-text"> with the same data.
4. Grid of 3 cards:
   - By room share: donut (SVG circles with stroke-dasharray, 2px gaps) + legend; hovering a legend row or segment enlarges that segment, dims others and swaps the centre number/label.
   - Top cities: horizontal bars (top 7) that animate their width on load.
   - Umrah vs Hajj bars + a semicircle gauge for reply rate.
5. Empty state when no rows in range: illustration-style icon + "No inquiries in this period" + link to share the package page.
Replace the old .pk-cols/.pk-ring/.pk-bars styles with the new ones; keep the public static helpers (columns/ring/bars) because Pro may call them — update their markup, keep their signatures.
```

---

## Prompt 7 — Settings + Pro page + cleanup

```
<<paste GLOBAL RULES>>

TASK A — Settings::render() to match the "Settings" screen in docs/redesign-mockup.html. Same option name, same field names, same actions (pothik_save_settings, pothik_recreate_page).
1. Two columns: left vertical nav (Agency / Notifications / Public page / Data, each with icon + one-line description); right panes. Tabs switch client-side and remember the last tab in the URL hash (#notify). Without JS all panes are visible one under another. ONE <form> wraps the Agency + Notifications + Data panes so a single save still posts everything.
2. Agency: EN + BN name, phone, WhatsApp with "+" prefix, license; logo as a drop-zone card (click or drag opens wp.media — keep the existing hidden logo_id/logo_url inputs and data-pk-media-* hooks), preview inside the rounded square, "Remove" link.
3. Notifications: notification emails as a tag input (each email validated with a simple regex, invalid ones shake and are refused) serialised to the existing comma-separated `notification_emails` field; "Send test" button → new AJAX `pothik_test_email` (nonce, cap, wp_mail to those addresses, toast result). Default country code with + prefix. Spam guard = range slider 1–30 synced with the rate_limit number input + live value.
4. Public page (outside the form): status row with green/clay dot (published/draft/missing), page URL, copyable shortcode rows ([pothik_catalog] and a select of packages that builds [pothik_package id="…"]), "Recreate page" (existing form).
5. Data: "Load / Remove demo data" row (existing forms), and a clay "danger" card with the delete_on_uninstall toggle as .pk-switch plus a clear warning sentence.
6. Sticky save bar (Prompt 0 helper) instead of the bottom save button.
Remove every inline style from Settings.php.

TASK B — Screens::pro() to match the "Pro" screen: dark hero with gold gradient headline, 6 feature cards (icons from sprite), comparison table with ✓ / — and Pro column emphasised, gold CTA top and bottom. Keep POTHIK_UPGRADE_URL + 'pothik/upgrade_url' filter. Remove inline styles.

TASK C — Cleanup: delete all CSS marked `/* legacy */` that is no longer referenced (grep every class), remove unused PHP helpers (old module_grid, inquiry_line if replaced), make sure admin.css has no duplicate selectors, regenerate languages/pothik.pot and bn_BD.mo, bump POTHIK_VERSION to 0.3.0 and add a readme changelog entry "Redesigned admin". Finally list any string that has no Bangla translation.
```

---

## Prompt 8 — QA pass (শেষে একবার চালান)

```
Review the Pothik admin redesign against docs/redesign-mockup.html and fix what you find. Check, and report as a checklist:
- Every admin-post action still works with JS disabled (test each form's field names vs. the handler in PackageStore/InquiryStore/ReceiptStore/Settings/Plugin).
- All new AJAX endpoints: check_ajax_referer, current_user_can('manage_options'), sanitised input, wp_send_json_* responses, no PHP notices.
- No inline style="" left in app/Admin/*.php (except dynamic widths/heights of chart bars and meters).
- No string without I18n::t(); Bangla mode renders without overflow on Desk, Editor and Inquiries at 1280px and 782px.
- Keyboard-only run: Tab through Desk → Inquiries drawer → Editor stars/tags → Settings tabs; Esc closes every overlay; focus returns.
- Lighthouse/axe on one admin page: no contrast or label errors.
- prefers-reduced-motion disables count-up, shimmer, pulsing and slide animations.
- Plugin Check (WP.org) passes: no external requests, escaping OK.
```
