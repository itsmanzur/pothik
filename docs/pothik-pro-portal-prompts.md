# ধাপ ৩.৩ — হাজিদের নিজস্ব পাতা: প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **আগে শেষ থাকতে হবে:**
   - ধাপ ১.১ (ডিপার্চার) আর হাজির ফাইল।
   - এই ধাপগুলো থাকলে পাতার সংশ্লিষ্ট অংশ ভরে উঠবে, না থাকলে অংশটা নিজে থেকে লুকিয়ে যাবে: ২.২ (টাকা আর রসিদ), ৩.১ (কাগজ), ভিসা বোর্ড, রুমিং।
   - ৩.২ থাকলে "Send links on WhatsApp" বোতাম Messages পাতা খুলবে।
2. `pothik-pro-portal-mockup.html` ফাইলটা Pro প্লাগিনের ফোল্ডারে `docs/portal-mockup.html` নামে রাখুন।
3. ক্রম: **Prompt 1 → Prompt 2 → Prompt 3 → QA**। সবগুলো প্রম্পট **Pro প্লাগিনে (pothik-pro)** চালাবেন।
4. **Prompt 1-এর পর তিনটা জিনিস নিজে পরীক্ষা করবেন:**
   - লগআউট অবস্থায় (incognito) লিংক খোলে কি না।
   - লিংকের token-এর একটা অক্ষর বদলালে পাতা না খুলে "লিংক পাওয়া যায়নি" দেখায় কি না।
   - পাতার সোর্সে (Ctrl+U) পুরো পাসপোর্ট নম্বর বা অন্য হাজির কোনো তথ্য আছে কি না।

   **কোনো একটায় গোলমাল পেলে থামবেন, পরের প্রম্পটে যাবেন না।**
5. ক্যাশ প্লাগিন (LiteSpeed, WP Rocket, W3TC) বা Cloudflare চালু থাকলে Prompt 1-এর পর দুজন আলাদা হাজির লিংক পরপর খুলে দেখবেন। একজনের পাতায় আরেকজনের তথ্য দেখা গেলে বুঝবেন ক্যাশ পাতাটা জমিয়ে রাখছে। তখনই ঠিক করতে হবে।
6. প্রতিটা প্রম্পটের পর git commit দিন। সব শেষ হলে `ROADMAP.md`-তে ৩.৩-এর পাশে ✅ দিন।

---

## RULES (প্রতিটা প্রম্পটের শুরুতে পেস্ট করবেন)

```
RULES — Pothik Pro, pilgrim page (roadmap step 3.3)
- Read ROADMAP.md (section 3.3) first.
- Visual reference: docs/portal-mockup.html.
  • Admin screen data-screen="portal": four summary cards, the pilgrim list with link states (Active / Sent · not opened / No link yet / Turned off) and actions (copy, WhatsApp, make new link, open as pilgrim, turn off), "Make links for all", "Send links on WhatsApp", and the "What pilgrims see" settings card.
  • The pilgrim page is what is drawn inside the phone (#ppScr): hero with greeting, group, countdown, paid %, papers; the 5-step progress (Booked → Payment → Papers → Visa → Travel); cards for payments, papers, visa, flight, hotels and room, contacts, day-by-day plan; bottom tabs Home / Money / Papers / Trip; বাং/EN switch; PIN gate; "This link has ended" page. CSS classes there start with pg-.
  Colours are Pothik's own.
- This page is public (no login) and shows one real person's money, papers and travel. Rules:
  • Access only by an unguessable token. Never by pilgrim id, phone or name.
  • Show only that pilgrim's data, and only the sections the departure allows. Never print other pilgrims' data, internal notes, staff names other than guide and leader, agent or commission data, or cost and profit.
  • The passport number is masked on the server (first 2 and last 2 characters). The full number never reaches the HTML, JS or any JSON.
  • No caching by WordPress page caches, CDNs or the browser. noindex, nofollow. Referrer-Policy: no-referrer.
  • No external requests from the page (no Google Fonts, no CDNs, no analytics). Fonts are bundled locally.
- Access (step 1.2): new area "portal" — view to see the admin screen and link states, edit to make, send, renew or turn off links and to change settings. Group limits apply. Without 1.2: manage_options.
- Activity (1.3): link made, renewed, turned off, sent, settings changed, first open by the pilgrim, PIN locked. Never log the token itself — log the last 4 characters only.
- $wpdb->prepare everywhere. Strings via Pothik\I18n::t() with Bangla translations (the pilgrim page uses its own language switch, independent of the admin locale). Escape everything. Vanilla JS. Admin UI via Pothik\Admin\UI. The pilgrim page must work without JS (tabs are anchors/sections, language switch is a link).
- When done: php -l on touched PHP files, list changed files.
```

---

## Prompt 1 — লিংক, token, নিরাপত্তা আর রাউট

```
<<paste RULES>>

TASK: The secure link system and the public route. Render a plain "It works — {first name}" page for now; the real page comes in Prompt 2.

1. Table (dbDelta, bump pothik_pro_db_version) {prefix}pothik_portal_links:
   id AI, pilgrim_id, departure_id, token_hash CHAR(64) UNIQUE (sha256 of the token), token_enc TEXT (the token encrypted with sodium_crypto_secretbox, key derived from wp_salt('auth') — so staff can copy the link again), token_tail CHAR(4), status VARCHAR(10) (active|off), created_by, created_at, sent_at NULL, first_seen_at NULL, last_seen_at NULL, views INT DEFAULT 0, pin_fails TINYINT DEFAULT 0, pin_locked_until DATETIME NULL, expires_at DATETIME NULL, turned_off_by NULL, turned_off_at NULL.
   One active link per pilgrim; making a new one sets the old row to off.
   If sodium is not available, store no token_enc and show "Copy" only right after creating (with a notice).

2. PothikPro\Portal\Links:
   - make(pilgrim_id): token = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=') (32 chars). Store hash, encrypted copy, tail. Return the URL.
   - find(token): look up by hash('sha256', token) only (no LIKE, no partial match). Return null if not found, off, or expired.
   - url(link): home_url('/my-trip/{token}/') with pretty permalinks, else home_url('?pothik_trip={token}').
   - Expiry from the departure setting: 30 days after return (default), 7 days after return, on the return date, never. Recalculate when departure dates change. Daily cron turns expired links off (status off, reason expired).
   - Deleting a pilgrim deletes their links.

3. Route: add_rewrite_rule('^my-trip/([A-Za-z0-9_-]{20,64})/?$', 'index.php?pothik_trip=$matches[1]', 'top'); query var pothik_trip; flush rewrite rules on Pro activation and on db version bump (not on every load). Setting "Link address" (default my-trip) under Settings → Pilgrim page, sanitised to a slug.
   Handle on template_redirect (priority 0) and exit — never load the theme.

4. Response headers on every pilgrim page response (valid, invalid, ended):
   nocache_headers(); define DONOTCACHEPAGE/DONOTCACHEOBJECT/DONOTCACHEDB if not defined; header('Cache-Control: no-store, private, max-age=0'); header('X-LiteSpeed-Cache-Control: no-cache'); header('CDN-Cache-Control: no-store'); header('X-Robots-Tag: noindex, nofollow, noarchive'); header('Referrer-Policy: no-referrer'); header('X-Frame-Options: DENY'); header('X-Content-Type-Options: nosniff'); a Content-Security-Policy allowing only 'self' (plus data: for images) and inline styles/scripts with a nonce.
   Hide the admin bar on this page.

5. Abuse limits:
   - Invalid tokens: count per IP (hashed with wp_salt, transient, 15 min). After 20 misses → 429 page for 15 min. Never reveal whether a pilgrim exists.
   - Invalid / off / expired links all show the same friendly page with the agency phone ("This link has ended" or "Link not found"), HTTP 404 for not found and 410 for ended.

6. PIN (optional, per departure): PIN = last 4 digits of the pilgrim's normalised phone (step 3.2 Phone::normalise). Form POST with nonce bound to the link id. Success → signed HttpOnly, Secure, SameSite=Lax cookie "pothik_trip_{link id}" valid 12 hours (hash_hmac of link id + expiry + token_hash with wp_salt). 5 wrong tries → locked 15 min (pin_locked_until), logged. If the pilgrim has no phone, the PIN step is skipped for that pilgrim and the admin list shows "No PIN — no phone".

7. Visit tracking: on each successful view update last_seen_at and views (once per 10 minutes per link, not on every reload); set first_seen_at and log "opened by pilgrim" once. Do not store IP addresses. Requests with the header "X-Pothik-Preview: 1" coming from a logged-in user with portal view rights (the admin "Open as pilgrim") are not counted.
```

---

## Prompt 2 — হাজির পাতা (ফোনের জন্য)

```
<<paste RULES>>

TASK: Build the pilgrim page exactly as drawn inside the phone in the mockup. Mobile first (360–430px wide), still fine on desktop (centered, max-width 480px).

1. Template: templates/portal/page.php (own <!doctype html>, no wp_head/wp_footer, no theme). CSS in assets/portal.css, JS in assets/portal.js (small, optional), fonts bundled in assets/fonts (Hind Siliguri 400/600/700 woff2 + a Latin fallback). <html lang="bn"> or "en". Agency name, logo and colours from Pothik settings. <meta name="robots" content="noindex,nofollow">, theme-color #1b4336.

2. Data — PothikPro\Portal\View::for_link($link) builds one array with only what is allowed (the departure's "What pilgrims see" toggles) and already masked/formatted for the chosen language. The template only prints this array. Sections:
   - hero: greeting "আস্সালামু আলাইকুম, {name}" (name in Bangla if the pilgrim file has a Bangla name, else the English name), agency, group name, dates, countdown (days to leave; "আজ যাত্রা" on the day; "সফর চলছে" during the trip; "আল্লাহ কবুল করুন" after return), paid %, papers "{checked}/{required}".
   - steps: Booked ✓ always; Payment ✓ when balance 0, current when partly paid; Papers ✓ when all required checked; Visa ✓ when issued; Travel ✓ after leave date. The first not-done step is "current" (gold ring).
   - payments (2.2): total, paid, left, ring; next installment with date (clay "Date has passed" when overdue); bKash/Nagad number from settings with a Copy button; on the Money tab the receipts list (amount, date, receipt number) with "Receipt ↓" — the download goes through /my-trip/{token}/receipt/{payment id}, which checks that the payment belongs to this pilgrim and is not voided.
   - papers (3.1): each required paper with Received / Being checked / Needed / New copy needed (+ the reason in simple words: blurry, cut off, expired, wrong paper). Button "ছবি তুলে হোয়াটসঅ্যাপে পাঠান" → wa.me/{agency WhatsApp}?text="{name}, {group} — {missing papers}". Never show the uploaded files themselves on this page. All received → "সব কাগজ পাওয়া গেছে, আলহামদুলিল্লাহ".
   - visa: four simple steps from the visa board status (Papers submitted → Sent on Masar → Waiting for approval → Visa issued) + masked passport "A0•••••67".
   - flight: airline + number, DAC → JED, dates and times, "এয়ারপোর্টে পৌঁছাবেন {time}". Return flight too when set.
   - hotels: Makkah and Madinah hotel names; room number only when rooming is marked final, else "রুম নম্বর পরে জানানো হবে".
   - contacts: group leader, guide, office — tel: and wa.me buttons.
   - plan: from step 3.4 when it exists (first 3 days on Home, all on Trip); otherwise "সফরসূচি শিগগিরই আসছে" — or hidden if the toggle is off.
   - Home shows: hero, steps, payments, papers (only if something is missing), visa, flight, hotels, contacts, plan. Money tab: payments with receipts. Papers tab: papers + visa. Trip tab: flight, hotels, plan, contacts.
   - Footer: "এই লিংক শুধু আপনার জন্য। কাউকে ফরোয়ার্ড করবেন না।"

3. Language: Bangla by default (departure setting), switch "বাং | EN" in the hero → ?lang=en / ?lang=bn, remembered in a cookie for this link. Bangla uses Bangla digits, Bangla month names and Bangladeshi number grouping (৳১,০৫,০০০) — reuse Vars formatting from step 3.2 if present, else add PothikPro\Format::bn_money / bn_date.

4. Tabs: bottom nav as links to #home, #money, #papers, #trip; without JS each tab is a section on one long page; with JS only the active section shows and the hash is kept on reload.

5. Accessibility: tap targets ≥ 44px, real buttons/links, aria-current on the active tab, colour is never the only signal (icons ✓ ! …), works with the phone's large text setting.

6. "Add to home screen": a small manifest (name "{agency} — আমার সফর", start_url = this link, display standalone, icons from the agency logo) linked only on this page. No service worker, no offline caching (personal data must not be stored on the device).

7. Admin preview: "Open as pilgrim" opens the same page in a new tab with a top strip "Preview — the pilgrim does not see this strip" and does not count as a visit.
```

---

## Prompt 3 — অ্যাডমিন পাতা, সেটিংস আর পাঠানো

```
<<paste RULES>>

TASK: The admin screen "Pilgrim page" from the mockup. Menu item under PRO after "Agents & commission", before "Messages", icon link.

1. Context bar (step 1.1). Four cards: dark green "links active" (active + sent), "opened by the pilgrim", "not sent yet" (no link or never sent), "turned off".
2. List (search, pagination 50): avatar, name, sub-line ("Opened 2 days ago" / "Sent on WhatsApp 3 Nov" / "Turned off 1 Nov · Rahim" / "Not sent" / "No phone — copy the link"), state pill, actions:
   - No link → "Make link".
   - Active/sent → Copy link (clipboard, toast "Link copied"), WhatsApp (wa.me to the pilgrim's phone with the "Your trip page" template from 3.2, or a built-in text if 3.2 is missing; sets sent_at), ⋯ menu: Make a new link (confirm: "The old link will stop working"), Open as pilgrim, Turn off link (confirm).
   - Off → "New link".
   - Undo toast (10 s) for Turn off and Make a new link (restores the previous row).
   Clicking a row selects it and shows the preview on the right (phone frame) — the preview is an iframe of the real page in preview mode, not a JS copy.
3. Toolbar: "Make links for all" (only for pilgrims without one; result toast "{n} links made"), "Send links on WhatsApp" → opens Messages (3.2) with audience "all" (or "only not opened yet" — add this audience to 3.2 via the 'pothik_pro/msg_audiences' hook) and the template "Your trip page".
4. 3.2 integration: add variable {{trip_link}} (via 'pothik_pro/msg_vars'; makes a link if the pilgrim has none) and seed a template "🔗 Your trip page" in BN and EN:
   BN: "আস্সালামু আলাইকুম {{name}}।\nআপনার সফরের সব তথ্য — টাকা, কাগজ, ভিসা, ফ্লাইট আর হোটেল — এই লিংকে দেখতে পাবেন:\n{{trip_link}}\nলিংকটি শুধু আপনার জন্য, কাউকে পাঠাবেন না।\n— {{agency}}"
   EN: "Assalamu alaikum {{name}}.\nEverything about your trip — payments, papers, visa, flight and hotel — is on this page:\n{{trip_link}}\nThis link is only for you, please do not forward it.\n— {{agency}}"
   Sending a batch with {{trip_link}} sets sent_at for each opened/sent recipient.
5. "What pilgrims see" card (per departure, with "Save as agency default"): toggles Payments, Papers, Visa, Flight, Hotels and room, Guide and group leader, Day-by-day plan; Link stops working (select); Ask for a PIN (switch); Language when opened (বাংলা/English). Changes apply immediately to every link of the departure; the preview reloads.
6. Pilgrim file: a small "Pilgrim page" box on each pilgrim — state, last opened, Copy / WhatsApp / New link / Turn off — reusing the same handlers.
7. Settings → Pilgrim page (agency level): link address slug, bKash/Nagad number shown to pilgrims, agency WhatsApp number for papers, default toggles.
8. All actions admin-post with nonces + AJAX enhancement; activity log entries as in RULES.
```

---

## QA — শেষে একবার

```
Check roadmap step 3.3 against docs/portal-mockup.html and ROADMAP.md. Report as a checklist:
- Security: link opens logged out; one changed character → not found; tokens are 32 chars from random_bytes; only sha256 in the lookup column; no pilgrim id/phone in any URL; 20 bad tokens from one IP → 429; turned-off and expired links show "This link has ended" (410); renewing kills the old link at once.
- Data: page source and any JSON contain no full passport number, no other pilgrim, no notes, no agent/commission, no cost/profit; hidden sections are not in the HTML at all (not just CSS-hidden); receipt download refuses another pilgrim's payment id and voided payments.
- Caching: with LiteSpeed Cache / WP Rocket / W3TC on and with Cloudflare "cache everything", two pilgrims' links never show each other's data; response headers include no-store, noindex, no-referrer, DENY.
- PIN: correct PIN → 12h cookie; 5 wrong → 15 min lock (logged); no phone → no PIN and noted in the list.
- No external requests from the pilgrim page (Network tab empty except own domain); no admin bar; no theme CSS/JS loaded.
- Content: steps logic, countdown states (before/day of/during/after), overdue installment in clay, room number hidden until rooming final, plan "coming soon" without 3.4; Bangla digits, months and ৳১,০৫,০০০ grouping; EN switch remembered.
- Works without JS (all sections on one page, language links); tabs keep the hash with JS.
- Mobile 360px and large text: nothing cut off, tap targets ≥ 44px; "Add to home screen" opens the right link; no service worker registered.
- Admin: counts match the list; make/copy/WhatsApp/new/turn off/undo; Make links for all; preview iframe matches the real page and is not counted as a visit; first open logged once; Pilgrim file box works; settings per departure + agency default.
- 3.2: {{trip_link}} fills and creates missing links; "Your trip page" template in BN/EN; sent_at set.
- Expiry cron turns links off on the right day; deleting a pilgrim deletes the link.
- No PHP notices, all queries prepared, Plugin Check passes.
```
