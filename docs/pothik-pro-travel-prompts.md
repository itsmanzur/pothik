# ধাপ ৪.২ — বাসের সিট, লাগেজ ট্যাগ আর যাত্রী তালিকা: প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **আগে শেষ থাকতে হবে:**
   - ধাপ ১.১ (ডিপার্চার)।
   - হাজির ফাইল। পরিবারের সম্পর্ক, লিঙ্গ, বয়স আর পাসপোর্টের তথ্য এখান থেকেই আসে।
   - ৪.১ (আইডি কার্ড)। এর প্রিন্টের নিয়ম, ফন্ট আর গ্রুপের রঙ এখানেও ব্যবহার হবে।
   - এগুলো থাকলে আরও ভালো কাজ করবে, না থাকলে সংশ্লিষ্ট অংশটা বাদ পড়বে:
     - ১.২ (টিম): স্টাফের সিট।
     - ৩.৪ (সফরসূচি): বাসের যাত্রার তারিখ আর সময়।
     - রুমিং: যাত্রী তালিকায় রুম নম্বর।
2. `pothik-pro-travel-mockup.html` ফাইলটা Pro প্লাগিনের ফোল্ডারে `docs/travel-mockup.html` নামে রাখুন।
3. ক্রম: **Prompt 1 → Prompt 2 → Prompt 3 → QA**। সবগুলো প্রম্পট **Pro প্লাগিনে (pothik-pro)** চালাবেন।
4. **যাত্রী তালিকা নিয়ে সাবধান থাকবেন:** এখানে পুরো পাসপোর্ট নম্বর থাকে। তাই CSV নামানোর অনুমতি শুধু যাদের দরকার তাদের দেবেন। কে কবে তালিকা নামাল, তা activity log-এ থাকবে। নামানো ফাইল কাজ শেষে কম্পিউটার থেকে মুছে ফেলবেন।
5. এয়ারলাইন আর সৌদি কোম্পানি একেকজন একেক ফরম্যাটে তালিকা চায়। তাই কলামের নাম আর ক্রম বদলানো যাবে, আর নিজের ফরম্যাট সেভ করে রাখা যাবে (Prompt 3)। আপনার এয়ারলাইনের নমুনা ফাইল থাকলে Prompt 3-এর সাথে পেস্ট করে দিন।
6. প্রতিটা প্রম্পটের পর git commit দিন। সব শেষ হলে `ROADMAP.md`-তে ৪.২-এর পাশে ✅ দিন।

---

## RULES (প্রতিটা প্রম্পটের শুরুতে পেস্ট করবেন)

```
RULES — Pothik Pro, bus seats, luggage tags, passenger list (roadmap step 4.2)
- Read ROADMAP.md (section 4.2) first.
- Visual reference: docs/travel-mockup.html — screen data-screen="tv" with three tabs:
  • Bus seats: bus switcher (Bus 1 6/49, + Add bus, journey line), left "Not seated yet" list grouped by family (drag or click-then-seat), the coach drawing (front, driver, door, 11 rows of 2+2 with row numbers, back row of 5), seat colours (man blue, woman rose, staff gold, empty dashed), family colour line under each seat, right panel (count + meter, "Seat everyone automatically", "Empty this bus", colour legend, print Seat plan / Roll call sheet).
  • Luggage tags: options (bags per pilgrim, hotel on the tag, paper A4 4 tags / sticker 100×70, Arabic hotel name, group colour band) and the tag design (.lgt).
  • Passenger list: format select (Airline group list / Hotel check-in list / Bus roll call), column chips, warning bar for passport expiry, table, Print, CSV.
  Colours are Pothik's own; the group colour comes from step 4.1.
- Print pages follow step 4.1: own template, sizes in mm, @page, local fonts, calibration square, no admin chrome, works without JS.
- Passport numbers and dates of birth appear only in the Passenger list (screen, print, CSV) and only for users with pilgrims edit access. Never on tags, seat plans or roll call sheets. Never in URLs or logs.
- Luggage tags never show the pilgrim's own phone or home address — only the office and guide numbers.
- Access (step 1.2): area "pilgrims" — view to see seats/tags/lists (passport columns hidden), edit to change seats, print, and download the passenger list with passport data. Group limits apply. Without 1.2: manage_options.
- Activity (1.3): bus added/removed, seats auto-filled, seat changes (summarised per save), printed (what, how many), passenger list downloaded or printed (format, columns, count — never the data).
- $wpdb->prepare everywhere. Strings via Pothik\I18n::t() with Bangla translations. Escape everything. Vanilla JS. UI via Pothik\Admin\UI. Seating must also work without drag and drop (click a family, then a seat; and a plain form fallback without JS).
- When done: php -l on touched PHP files, list changed files.
```

---

## Prompt 1 — ডেটা: বাস, সিটের নকশা, পরিবার আর নিজে থেকে বসানো

```
<<paste RULES>>

TASK: Data model and seating logic. No screen yet.

1. Tables (dbDelta, bump pothik_pro_db_version):
   {prefix}pothik_buses: id AI, departure_id, name VARCHAR(40) ("Bus 1"), layout VARCHAR(20), journey VARCHAR(120) NULL ("Makkah → Madinah"), leaves_at DATETIME NULL, meet_point VARCHAR(160) NULL, plate VARCHAR(30) NULL, driver_phone VARCHAR(30) NULL, sort SMALLINT, created_by, created_at.
   {prefix}pothik_bus_seats: bus_id, seat VARCHAR(4) ("7C"), person_type VARCHAR(6) (pilgrim|staff), person_id BIGINT, updated_by, updated_at. PRIMARY KEY (bus_id, seat); UNIQUE (bus_id, person_type, person_id).
   A person can sit in only one seat per bus, but can be in several buses (different journeys).
   If step 3.4 exists, a bus can be linked to a day plan event of type "bus" (event_id NULL) — then journey, time and meet point come from there.

2. Layouts — PothikPro\Travel\Layouts (filter 'pothik_pro/bus_layouts'):
   coach49: rows 1–11 seats A B | aisle | C D, row 12 back A–E (49).
   coach45: rows 1–10 2+2, row 11 back A–E (45).
   coach53: rows 1–12 2+2, row 13 back A–E (53).
   mini29: rows 1–8 2+1 (A B | C), row 9 back A–E (29).
   van14: rows 1–4 1+2 … (14) — keep it simple and listed.
   Each layout returns rows with seat ids and which seats are window/aisle and which pairs are "side by side" (A–B, C–D; back row A–B, C–D–E).
   Seats can be blocked per bus (broken seat, luggage) — store as person_type 'block'.

3. Families — PothikPro\Travel\Families::for_departure(): use the pilgrim file's family/mahram links to group pilgrims; a pilgrim with no link is a family of one. Each family gets a stable colour from a 10-colour palette (by family id) used under seats and in the list. Children = under 18 on the leave date; older = 65+.

4. Auto seating — PothikPro\Travel\Seater::fill(bus, options) (only empty seats, only unseated people of this departure that are not in another bus for the same journey):
   a) Staff from the departure (leader, guide; step 1.2 team members) → row 1 (A, B; then C, D).
   b) Sort families: families with someone 65+ or a child under 12 first, then by size (bigger first), then by family name.
   c) Family of 1: fill the next free single seat, preferring a seat next to the same gender.
   d) Family of 2: the next free side-by-side pair (A–B or C–D in the same row); never across the aisle.
   e) Family of 3–4: a whole row (A–B–C–D) if free; otherwise two side-by-side pairs in consecutive rows (same side).
   f) Family of 5+: the back row or consecutive rows, keeping pairs together.
   g) Options: "Women together on one side" (off by default), "Keep row 1 for staff" (on).
   h) Return a list of what was placed and who could not be seated (bus full).
   i) Deterministic: same input → same result (tests).
   Unit tests for d) and e) with a nearly full bus.

5. Data for print/CSV — PothikPro\Travel\Passengers::rows(departure, format, columns): passport surname and given names exactly as stored (uppercase), sex, date of birth, nationality (ISO-3 code, default BGD), passport number, issue and expiry dates, PNR/ticket number (from step 1.1 flight data or the pilgrim record), bus and seat, room (only when rooming is final), phone (only for the Hotel check-in format if the agency turns it on). Passport warning: expiry < (return date + N months), N from the pilgrim-file passport rule (default 6).
```

---

## Prompt 2 — Bus seats ট্যাব আর সিটের প্রিন্ট

```
<<paste RULES>>

TASK: The screen "Bus, bags & lists" (menu under PRO after "Agents & commission", before "ID cards", icon seat), tab Bus seats, plus the two seat prints.

1. Context bar (step 1.1). Tabs Bus seats / Luggage tags / Passenger list (?tab=).
2. Bus switcher: one button per bus "Bus 1 · 6/49"; "+ Add bus" (drawer: name, layout select with a small preview, journey, date/time, meet point, plate, driver phone — or "Link to a day plan bus event"); edit/delete in a ⋯ menu (delete only after confirm; seats are removed with it). Journey line on the right ("Makkah → Madinah · 20 Mar · 09:00 from the hotel front gate"). "Copy seats from {other bus}" in the ⋯ menu (same layout only).
3. Left panel "Not seated yet": families (colour square + "{Family name} family · {n}" or "Alone"; members with avatar, name and "child 9"/"age 71" hints), staff group on top. Draggable (HTML5 drag) and clickable (select family → gold outline → "Now click the first seat" toast). When empty: "Everyone has a seat. Alhamdulillah."
4. Coach: drawn from the layout; seat buttons 46px with seat id in the corner, initials (staff: LDR / GDE / STF), gender colour, family colour line, title "{name} · seat {id}"; drop target highlight; aria-labels "Seat 7C, Farhana Islam" / "Seat 7C, empty"; keyboard: arrow keys move focus between seats, Enter places the selected family, Delete empties the seat.
   Interactions: drop a family on a seat → members fill free seats from that seat onward in layout order, keeping pairs (use the same pair logic as the Seater); drag a seat onto another → move or swap; click a filled seat → back to the list with Undo; blocked seats shown hatched, toggled from a seat's context menu (right-click / long-press) "Block this seat".
   Save: each change saves via AJAX (nonce) with a small "Saved" tick; activity log gets one summary entry per 30 seconds of changes.
5. Right panel: count "{n} of {total} seats" + meter; "Seat everyone automatically" (Seater::fill, toast "{n} people seated — families together", lists anyone not seated); "Empty this bus" (confirm + undo); options (Women together on one side, Keep row 1 for staff); legend; Print buttons.
6. Prints (step 4.1 print rules):
   - Seat plan: A4 portrait, header (group, bus name, journey, date/time, meet point, plate, driver phone, leader phone), the coach drawing with names (EN + BN, small) in each seat, family colour line, legend. Language toggle on the toolbar.
   - Roll call sheet: A4 portrait table ordered by seat: Seat | Name (EN) | নাম | ✓ boarding | ✓ return, with totals and a signature line for the leader.
7. No-JS fallback: a table "Name | Seat (select of free seats)" with Save.
```

---

## Prompt 3 — লাগেজ ট্যাগ আর যাত্রী তালিকা

```
<<paste RULES>>

TASK: Tabs Luggage tags and Passenger list, with their prints and the CSV export.

AIRLINE SAMPLE (optional — paste the header row / sample file your airline or Saudi company wants):
<<paste here>>

1. Luggage tags tab:
   - Options (saved per departure): Bags per pilgrim 1 / 2 (/ 3 in a select), Hotel on the tag Makkah / Madinah, Paper "A4 · 4 tags" / "Sticker 100×70", Arabic hotel name (Arabic name field on the departure's hotel; if empty, hidden), Group colour band; who: everyone / pick / one pilgrim.
   - Preview of the first page (real print HTML in an iframe, like 4.1); "Print {n} tags" with n = pilgrims × bags.
   - Tag design (.lgt): hole mark at the top, name (EN uppercase, auto-shrink) + Bangla name, hotel line (EN + Arabic), guide phone, right stub "BAG 1 OF 2" + pilgrim no, bottom band in the group colour "{AGENCY} · {GROUP}" + office phone.
   - Print: A4 → 2×2 tags of 95 × 135 mm with cut marks (calibration square as in 4.1); Sticker → @page { size: 100mm 70mm; margin: 0 } one tag per page, compact layout for label printers.
2. Passenger list tab:
   - Format select with built-in formats:
     Airline group list: No, Surname, Given name, Sex, Date of birth, Passport no, Expiry, Nationality, PNR.
     Hotel check-in list: No, Name, Sex, Passport no, Nationality, Room, Family (no date of birth).
     Bus roll call: Seat, Name, নাম, Bus (no passport data).
     + "Save as my format" (name, chosen columns, column order by drag, custom header names, date format DD MMM YYYY / DD/MM/YYYY / YYYY-MM-DD, name order).
   - Column chips to show/hide; table with sticky header; expiry cells in clay with ⚠ when the passport rule fails; warning bar "{n} passports expire less than 6 months after the return date ({names}). Open their files".
   - Users without pilgrims edit: passport number, date of birth and expiry columns are not offered at all.
   - CSV: admin-post, nonce, streamed; UTF-8 with BOM (so Excel shows Bangla/Arabic correctly); comma separator, all fields quoted; protect against CSV/formula injection — any cell starting with = + - @ or a tab/CR is prefixed with a single quote; filename passenger-list-{group-slug}-{format}-{date}.csv; Cache-Control no-store; log the download (format, columns, count).
   - Print: A4 landscape table with header (group, flight, dates, agency) and page numbers; repeat the table header on each page.
3. Free-plugin note: none of this goes into the free plugin.
```

---

## QA — শেষে একবার

```
Check roadmap step 4.2 against docs/travel-mockup.html and ROADMAP.md. Report as a checklist:
- Layouts: coach49/45/53/mini29 draw correctly; blocked seats skipped by auto seating and drop.
- Auto seating: staff in row 1; families with 65+ or under-12 first; couples never split across the aisle; families of 3–4 get a row or stacked pairs on the same side; nobody seated twice; people already in another bus of the same journey are skipped; same input gives the same result; message when the bus is full.
- Manual: drag family, click-then-seat, swap, move, click to unseat with undo, keyboard navigation, no-JS form; changes saved and summarised in the activity log.
- Multiple buses, copy seats between buses with the same layout, journey from the day plan (3.4) when linked.
- Prints: seat plan and roll call — names EN/BN, no passport data, 100% scale correct; tags A4 2×2 at 95×135 mm and sticker 100×70 mm; Arabic hotel name renders with the bundled font; no pilgrim phone or address on tags.
- Passenger list: formats, column choice/order/rename saved; passport columns hidden for users without edit; expiry warning matches the pilgrim-file rule; CSV opens in Excel with Bangla intact; a name like "=HYPERLINK(...)" is exported as text ('=…); download logged without data.
- Access and group limits; nothing in URLs; no external requests.
- Mobile 390px: tabs usable, coach scrolls, click-to-seat works on touch.
- No PHP notices, all queries prepared, Plugin Check passes.
```
