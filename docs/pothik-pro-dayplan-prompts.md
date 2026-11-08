# ধাপ ৩.৪ — দিনভিত্তিক সফরসূচি: প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **আগে শেষ থাকতে হবে:**
   - ধাপ ১.১ (ডিপার্চার)। যাত্রা আর ফেরার তারিখ থেকেই দিনগুলো তৈরি হবে।
   - এগুলো থাকলে আরও ভালো কাজ করবে:
     - ৩.৩ (হাজির পাতা): হাজিরা নিজের পাতায় সূচি দেখবেন।
     - ৩.২ (মেসেজ): "গ্রুপকে জানান" আর "কালকের সূচি পাঠান" কাজ করবে।
     - ১.৩ (activity log): কে কোন সময় বদলাল, তা লগে থাকবে।
   - এর কোনোটা না থাকলে সংশ্লিষ্ট বোতামটা নিজে থেকে লুকিয়ে যাবে।
2. `pothik-pro-dayplan-mockup.html` ফাইলটা Pro প্লাগিনের ফোল্ডারে `docs/dayplan-mockup.html` নামে রাখুন।
3. ক্রম: **Prompt 1 → Prompt 2 → Prompt 3 → QA**। সবগুলো প্রম্পট **Pro প্লাগিনে (pothik-pro)** চালাবেন।
4. তৈরি সূচির লেখা (যিয়ারতের জায়গা, কোন দিনে কী হবে) আমি একটা সাধারণ নমুনা হিসেবে দিয়েছি। নিজের এজেন্সির নিয়ম অনুযায়ী Prompt 1-এর ৪ নম্বর অংশে বদলে নিতে পারেন। রওজা জিয়ারতের জন্য নুসুক অ্যাপে অনুমতি লাগে, আর এই নিয়ম বদলাতে পারে। তাই নমুনায় শুধু মনে করিয়ে দেওয়ার মতো করে রাখা আছে।
5. প্রতিটা প্রম্পটের পর git commit দিন। সব শেষ হলে `ROADMAP.md`-তে ৩.৪-এর পাশে ✅ দিন।

---

## RULES (প্রতিটা প্রম্পটের শুরুতে পেস্ট করবেন)

```
RULES — Pothik Pro, day plan (roadmap step 3.4)
- Read ROADMAP.md (section 3.4) first.
- Visual reference: docs/dayplan-mockup.html — the Day plan screen (data-screen="plan"): the day strip (Day n, date, weekday, city colour chip, Today badge, faded empty days), the day editor (title, city switch Makkah/Madinah/Travel/Dhaka, event rows with time, type icon, what, where to meet, type tag, men/women only tag, "Changed from" tag, who/delete actions, add buttons per type), the yellow "time changed — tell the group?" bar, the right panel with "Pilgrim page" phone preview (Bangla timeline) and "Print sheet" (A4), and the "Fill from a ready plan" drawer (#sxT). CSS classes there start with sx-. Colours are Pothik's own.
- Every event has text in both languages (EN + BN). If one is empty, the other is shown.
- Times are local Saudi time (Asia/Riyadh) for days in Saudi Arabia and Bangladesh time (Asia/Dhaka) for Dhaka days — stored as plain "HH:MM" plus the day's city; never converted by the server time zone. A time can also be a prayer word (Fajr, Dhuhr, Asr, Maghrib, Isha, "After Isha", "All day") — store as a code, not free text, so it can be translated.
- Access (step 1.2): area "plan" — view to see and print, edit to change. Group limits apply. Without 1.2: manage_options.
- Activity (1.3): day changed, event added/edited/deleted, time changed (old → new), ready plan applied, plan copied, plan sent.
- $wpdb->prepare everywhere. Strings via Pothik\I18n::t() with Bangla translations. Escape everything. Vanilla JS. UI via Pothik\Admin\UI. The editor must work without JS (one form per day, save button).
- When done: php -l on touched PHP files, list changed files.
```

---

## Prompt 1 — ডেটা, দিন তৈরি আর তৈরি সূচি

```
<<paste RULES>>

TASK: Data model, day generation and ready plans. No screen yet.

1. Tables (dbDelta, bump pothik_pro_db_version):
   {prefix}pothik_plan_days: id AI, departure_id, day_no SMALLINT, city VARCHAR(10) (makkah|madinah|travel|dhaka|other), title_en VARCHAR(120), title_bn VARCHAR(120), note_en TEXT NULL, note_bn TEXT NULL, first_seen_at DATETIME NULL (first time any pilgrim saw this day on the pilgrim page — step 3.3), updated_by, updated_at. UNIQUE departure_id+day_no.
   {prefix}pothik_plan_events: id AI, day_id, sort SMALLINT, time_kind VARCHAR(10) (clock|prayer|allday), time_val VARCHAR(10) ("07:30" or fajr|dhuhr|asr|maghrib|isha|after_isha), type VARCHAR(10) (bus|ziyarah|salah|meal|meeting|flight|free|umrah|other), what_en VARCHAR(160), what_bn VARCHAR(160), where_en VARCHAR(160) NULL, where_bn VARCHAR(160) NULL, map_url VARCHAR(255) NULL (only https://maps.google.com, https://goo.gl/maps, https://maps.app.goo.gl links — validate), who VARCHAR(4) (all|m|f), prev_time VARCHAR(10) NULL (set when the time changes after first_seen_at), updated_by, updated_at.
   {prefix}pothik_plan_templates: id AI, name VARCHAR(120), source VARCHAR(10) (system|saved), nights_makkah TINYINT, nights_madinah TINYINT, order_first VARCHAR(10) (makkah|madinah), data LONGTEXT (JSON of days and events), created_by, created_at.

2. PothikPro\Plan\Days::sync(departure_id): number of days = return date − leave date + 1. Create missing day rows; city default: day 1 travel, then Makkah for nights_makkah (from the departure / package hotel nights if present, else half), one travel day, Madinah, last day travel. If dates move and days get fewer, never delete a day with events — mark it "outside the dates" and show a warning in the screen instead. Hook on departure save.

3. Event order: sort by time (prayer words placed at their usual position: fajr 05:00, dhuhr 12:30, asr 15:45, maghrib 18:30, isha 20:00, after_isha 20:30 — only for sorting, never shown as times), "allday" first; manual drag order wins when the user drags (save sort).

4. Ready plans (seed as system templates, BN + EN):
   a) "Standard Umrah · 15–17 days" (Makkah first): day 1 travel (meet at Dhaka airport 3 hours before, flight, bus to Makkah, hotel check-in); day 2 Umrah with the guide (meet in ihram in the lobby), breakfast, rest; day 4 Makkah ziyarah (bus 07:30: Jabal al-Nour, Arafah and Jabal al-Rahmah, Muzdalifah, Mina; back before Dhuhr); other Makkah days: Fajr in Masjid al-Haram, breakfast, group talk after Isha; travel day to Madinah (bags in lobby, bus about 5 hours, check-in); Madinah ziyarah day (Quba, Uhud, Qiblatayn); other Madinah days: Fajr in Masjid an-Nabawi, breakfast, "Rawdah visit — needs a Nusuk permit" after Isha; last day: bags, bus to airport, flight.
   b) "Madinah first · 15 days" (mirror order).
   c) Any earlier departure's plan ("Copy from {departure name}").
   d) Saved plans ("Save this plan as a ready plan" in the screen).
   Apply: Plan::apply(template, departure_id, only_empty = true) maps by position (travel days to travel days, Makkah days in order, Madinah days in order), fills only days with no events, returns how many days were filled. Flight numbers and times come from the departure (step 1.1) when present.

5. PothikPro\Plan\Format: time label in BN/EN (Bangla digits; prayer names ফজর, যোহর, আসর, মাগরিব, এশা, এশার পর, সারা দিন), city names, type icons.
```

---

## Prompt 2 — Day plan পাতা (এডিটর)

```
<<paste RULES>>

TASK: The Day plan screen from the mockup. Menu item under PRO after "Agents & commission", before "Pilgrim page", icon calendar.

1. Context bar (step 1.1). Line "{n} days · Dhaka → Makkah {x} nights → Madinah {y} nights → Dhaka" built from the days. Buttons: Fill from a ready plan, Print, Send tomorrow's plan (WhatsApp green, only with 3.2).
2. Day strip: horizontal, scrollable, keyboard arrows move between days; each chip Day n, date, "Mon · Mar", city chip (Makkah dark green, Madinah green, Travel gold-brown, Dhaka grey); faded when empty; gold "Today" badge on the current Saudi date during the trip; selected chip outlined. ?day= in the URL.
3. Day editor:
   - Header: "Day 4 · Monday 15 March", editable title (EN; a small "বাংলা" toggle shows the Bangla fields for title and every event), city segmented switch, ⋯ menu: Copy this day to… (pick days, replace or add), Clear this day (undo toast).
   - Event rows: drag handle, time (clock input or a small menu with prayer words and "All day"), type icon (colour per type, click to change type), what, where to meet (+ optional map link icon), tags (type, Men only / Women only, "Changed from 07:30"), actions on hover: who (cycles all → men → women), delete (undo toast). Inline editing saves on change (AJAX, nonce) with a small "Saved" tick; Enter moves to the next field.
   - Add buttons per type under the list; a new event gets the time after the last one + 30 min and focuses "what".
   - Empty day: "Nothing planned for this day — add a bus time, a ziyarah or a meeting point, or fill empty days from a ready plan."
4. Time changed after pilgrims have seen the day (first_seen_at set): store prev_time on the event (keep the first old value if changed again; clear it with a small × on the tag), show the yellow bar "{what}: {old} → {new} — Pilgrims already saw Day n. Tell them?" with "Not now" and "Tell the group" (opens 3.2 Messages with audience all and the "Time changed" template — seed it: BN "আস্সালামু আলাইকুম {{name}}।\n{{plan_day}}-এর সময় বদলেছে: {{plan_change}}।\nজায়গা: {{plan_where}}\n— {{agency}}", EN equivalent; add variables plan_day, plan_change, plan_where via 'pothik_pro/msg_vars', filled from the change that opened the screen).
5. Right panel (sticky): segmented "Pilgrim page" / "Print sheet".
   - Pilgrim page: phone frame with the same Bangla timeline that 3.3 will render (reuse one PHP partial templates/portal/plan-day.php for both), updates live after each save.
   - Print sheet: small A4 preview of the whole trip.
6. Ready plan drawer (#sxT): radio list (system plans, "Copy from {earlier departure}" for each departure of the agency, saved plans with who/when); note "Only empty days are filled"; button "Fill empty days" → toast "{n} empty days filled"; second option "Replace all days" behind a confirm. "Save this plan as a ready plan" at the bottom of the screen (name field).
7. Without JS: each day is a normal form with rows and an "Add row" submit; the day strip is links.
```

---

## Prompt 3 — প্রিন্ট, হাজির পাতা আর মেসেজ

```
<<paste RULES>>

TASK: Print sheet, pilgrim page integration and sending the plan.

1. Print: admin-post pothik_pro_plan_print&departure=…&lang=bn|en&mode=trip|day&day=n opens a clean print page (own template, no admin chrome, @page A4 portrait, 14mm margins, local fonts):
   - Header: group name, dates, agency name/logo/phone, group leader and guide phones, hotel names.
   - Trip mode: a table day by day (Day n + date + city | title in bold — events "07:30 Bus leaves for ziyarah (Hotel front gate, bus no. 3)"); keep each day on one page (break-inside: avoid); "Men only/Women only" shown as small tags.
   - Day mode: one day large (for the bus or the lobby notice board), big times, no QR code (no external libraries); instead a line "Your own page: ask the office for your link".
   - Footer: "Printed {date}" and page numbers (CSS counters).
   - The Print button in the screen opens this in a new tab and calls window.print().
2. Pilgrim page (step 3.3): replace the "plan" placeholder with the real data via the shared partial:
   - Home: today's day during the trip (or the first 3 days before the trip), with "See all →".
   - Trip tab: all days, today expanded and the others collapsible (details/summary — works without JS); today's next event highlighted.
   - Respect who=m/f: a pilgrim only sees events for everyone and for their own gender.
   - Show "সময় বদলেছে: আগে ০৭:৩০" when prev_time is set.
   - Set first_seen_at on a day the first time any pilgrim's page renders that day (Home or Trip, expanded).
   - Respect the departure toggle "Day-by-day plan".
3. Send the plan on WhatsApp (3.2): "Send tomorrow's plan" opens Messages with audience all and a seeded template "📅 Tomorrow's plan": BN "আস্সালামু আলাইকুম {{name}}।\nআগামীকাল ({{plan_date}}) {{plan_title}}:\n{{plan_list}}\n— {{agency}}". Variables: plan_date, plan_title, plan_list (one line per event "০৭:৩০ — যিয়ারতের বাস ছাড়বে (হোটেলের সামনের গেট)", filtered by the pilgrim's gender), and {{trip_link}} if 3.3 exists. A day picker on the Messages screen lets them choose another day.
4. Optional daily reminder (setting, off by default): at 20:00 Saudi time during the trip, show an admin notice and a dashboard card "Tomorrow's plan not sent yet — Send" for each running departure. No automatic sending.
5. Activity log entries for print (who printed what) and send.
```

---

## QA — শেষে একবার

```
Check roadmap step 3.4 against docs/dayplan-mockup.html and ROADMAP.md. Report as a checklist:
- Days: count matches leave/return dates; moving dates adds days and never deletes days with events (warning shown); city defaults sensible; Today badge on the right Saudi date.
- Editor: inline edit, prayer-word times, sorting (prayer words placed correctly), drag order kept, who men/women, delete with undo, copy day to others, clear with undo, Bangla fields; works without JS.
- Time changed after first_seen_at: prev_time stored once, tag shown in admin and on the pilgrim page, bar appears, "Tell the group" opens Messages pre-filled; no bar for days nobody has seen.
- Ready plans: only empty days filled by default; replace-all asks first; copy from an earlier departure maps Makkah/Madinah days correctly; save as ready plan works; flight numbers taken from the departure.
- Map links: only Google Maps URLs accepted; others rejected with a message.
- Print: A4 trip and day mode in BN and EN; no admin bar; days do not split across pages; page numbers; local fonts only (no external requests).
- Pilgrim page: today expanded, gender filter respected (a woman never sees a men-only event and vice versa), toggle off hides it completely (not in HTML), first_seen_at set once.
- Messages: tomorrow's plan text per pilgrim (gender-filtered list), Bangla digits and prayer names, day picker.
- Access and group limits; activity log entries for every change with old → new time.
- Mobile 390px: strip scrolls, rows stack, preview below.
- No PHP notices, all queries prepared, Plugin Check passes.
```
