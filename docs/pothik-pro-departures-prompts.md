# ধাপ ১.১ — একাধিক ডিপার্চার: প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **আগে যা চালানো থাকতে হবে:** রোডম্যাপের ধাপ ০ (রিডিজাইনের সব প্রম্পট)। অন্তত Departure ফাইলের Fix 1–2 আর Pro-র পাঁচটা স্ক্রিনের রিডিজাইন।
2. **ডেটাবেসের ব্যাকআপ নিন।** এই ধাপে Pro-র সব ডেটা একটা option থেকে নিজস্ব টেবিলে যাবে। পুরো সাইট না হলেও অন্তত `wp_options` টেবিলের `pothik_pro_desk` সারিটা কপি করে রাখুন।
3. `pothik-pro-departures-mockup.html` ফাইলটা Pro প্লাগিনের ফোল্ডারে `docs/departures-mockup.html` নামে রাখুন।
4. ক্রম: **Prompt 1 (ফ্রি) → Prompt 2 → Prompt 3 → Prompt 4 → Prompt 5 → QA**।
   - Prompt 1 **ফ্রি প্লাগিনে (pothik)**, বাকিগুলো **Pro প্লাগিনে (pothik-pro)**।
   - দুটো ফোল্ডারই Cursor-এ খোলা রাখবেন।
5. প্রতিটা প্রম্পটের পর সাইটে চেক করে git commit দিন। **Prompt 2-এর পর অবশ্যই পরীক্ষা করবেন, পুরনো ডেটা ঠিকমতো এসেছে কিনা।**
6. শেষ হলে `ROADMAP.md`-তে ১.১-এর পাশে ✅ আর "সম্পন্ন কাজের খাতা"-য় তারিখ ও ভার্সন লিখবেন।

---

## RULES (Prompt 2–5-এর শুরুতে পেস্ট করবেন)

```
RULES — Pothik Pro, multiple departures (roadmap step 1.1)
- Read ROADMAP.md (section 1.1) first.
- Plugin: Pothik Pro (namespace PothikPro). Today ALL Pro data lives in one option `pothik_pro_desk` = ['departure' => [...], 'pilgrims' => [...], 'rooms' => ['makkah' => [...], 'madinah' => [...]], 'ledger' => [...]] handled by app/Desk.php. This step moves it to custom tables so an agency can run many departures (groups) at once.
- Visual reference: docs/departures-mockup.html — the "Departures" list (data-screen="deplist"), the group switcher in the header (#grpSw), the sidebar "Active group" box (#sideGroup), the context bar on group screens (.ctx), the "New group" drawer (#nd) and the "Group" card on the pilgrim edit screen (.mv). Colours are Pothik's own.
- Never lose data. Old data must migrate into "the first departure" exactly. Keep `pothik_pro_desk` untouched after migration (renamed to `pothik_pro_desk_legacy`) so it can be restored.
- Existing screens keep their look and their form field names; they only become scoped to the ACTIVE departure. Each form gets one extra hidden field `departure_id`, and every handler checks that the departure exists and the user may edit it.
- Pilgrim ids stay stable across the migration (rooms and ledger rows reference them).
- Use $wpdb with prepare() everywhere, dbDelta for schema, a schema version option `pothik_pro_db_version`. No raw SQL string building with user input.
- Capabilities: keep manage_options for now (step 1.2 adds roles) but route every check through one helper PothikPro\Access::can(string $what) so 1.2 only changes that helper.
- Strings via Pothik\I18n::t() with Bangla translations. Escape everything. Vanilla JS, no external requests. UI via Pothik\Admin\UI helpers.
- When done: php -l on touched PHP files, list changed files.
```

---

## Prompt 1 — ফ্রি প্লাগিনে hook যোগ করা (pothik)

```
Plugin: Pothik (free). Pothik Pro is adding multiple departures and needs a few extension points. Add ONLY hooks — no Pro logic in the free plugin (WP.org rules).

1. Admin header: in Chrome::open(), inside .pk-head .tools and before the search button, add do_action('pothik/admin_header_tools', $screen_id). $screen_id is the current page slug (e.g. 'pothik-pilgrims').
2. Sidebar: in the sidebar nav, right after the "PRO" group label, add do_action('pothik/admin_sidebar_before_pro', $screen_id). Make sure the hook output gets the collapsed (mini) styles: anything with class .pk-side-slot hides its text in mini mode.
3. Content top: right after the header (before the page body) add do_action('pothik/admin_before_content', $screen_id).
4. Seat line: the current filter apply_filters('pothik/seat_line', '') gets the package as context: apply_filters('pothik/seat_line', '', $package). Update templates/package.php and PackagePage so the package being shown is passed. Keep it backwards compatible (Pro may still register with 1 argument).
5. Catalog cards: add apply_filters('pothik/package_card_badges', [], $package) and render the returned short strings as small pills on the public package card (Pro will add "12 seats left").
6. Inquiries: add do_action('pothik/inquiry_actions', $row) inside the inquiry drawer footer (used later in step 2.1).
7. Document these hooks in a "Hooks" section of readme.txt. php -l; list changed files.
```

---

## Prompt 2 — নিজস্ব টেবিল আর পুরনো ডেটা সরানো (migration)

```
<<paste RULES>>

TASK: Create the tables, a data layer, and migrate the old option.

1. Tables (dbDelta in PothikPro\Schema::install(), run on activation AND on plugins_loaded when pothik_pro_db_version is lower):
   {prefix}pothik_departures: id BIGINT PK AI, name VARCHAR(120), name_bn VARCHAR(120), type VARCHAR(20) (group|private|hajj), package_id VARCHAR(40) (Pothik package id, may be ''), status VARCHAR(20) (active|archived), leave_date DATE NULL, return_date DATE NULL, route VARCHAR(20), origin VARCHAR(12), airline VARCHAR(80), flight VARCHAR(80), seats SMALLINT, held SMALLINT, company VARCHAR(80), program VARCHAR(40), leader VARCHAR(80), leader_phone VARCHAR(40), guide VARCHAR(80), guide_phone VARCHAR(40), visa_open DATE NULL, visa_last DATE NULL, entry_last DATE NULL, depart_last DATE NULL, color VARCHAR(7), bn LONGTEXT (JSON of Bangla prose: airline, company, leader, guide), created_at DATETIME, updated_at DATETIME. Index on status, leave_date, package_id.
   {prefix}pothik_pilgrims: id BIGINT PK (NOT auto-increment for migrated rows — keep old ids; use AUTO_INCREMENT starting after the max migrated id), departure_id BIGINT, name, gender, dob DATE NULL, passport VARCHAR(20), expires DATE NULL, share, family, link, visa, biometric, vaccine, insurance, bn LONGTEXT, sort INT, created_at, updated_at. Index departure_id, passport.
   {prefix}pothik_rooms: id VARCHAR(40) PK (keep existing ids like "m1"/"m-1-ab12"), departure_id, city VARCHAR(10) (makkah|madinah), label, number, people LONGTEXT (JSON array of pilgrim ids), bn LONGTEXT, sort INT. Index departure_id+city.
   {prefix}pothik_ledger: id VARCHAR(40) PK, departure_id, pilgrim_id BIGINT, name, due INT UNSIGNED, paid INT UNSIGNED, channel, trx, note, usd_note, sar_note, bn LONGTEXT, sort INT. Index departure_id, pilgrim_id, trx.
   (If the optional ledger "payments" from the installments prompt exists, add {prefix}pothik_payments with ledger_id and the same columns as that prompt defined.)

2. Data layer — one repository per table in app/Repo/: Departures, Pilgrims, Rooms, Ledger. Each returns arrays in EXACTLY the shape the screens use today (same keys, 'bn' as an array), so Screens.php changes stay small. Methods: find, for_departure, save (insert/update), delete, and for Pilgrims::move(int $id, int $to_departure). Cache per request.
   Replace Desk::get()/store() usage: add Desk::for_departure(int $id): array that returns the old ['departure','pilgrims','rooms','ledger'] shape built from the repositories, and Desk::save_part(int $departure_id, string $part, array $data). Keep normalize() and link_file() logic but run it per departure.

3. Migration (PothikPro\Migrate::run(), runs once inside Schema::install when the old option exists and the departures table is empty):
   - Read pothik_pro_desk through the OLD Desk::get() (so sample/normalize rules still apply).
   - Create departure #1 from ['departure'] with name "{Departure} {leave month year}" (e.g. "March group 1448" is not derivable — use "Group · 12 Mar 2027"), package_id = the only published package if there is exactly one, else ''.
   - Insert pilgrims with their ORIGINAL ids, rooms with their ids, ledger rows with their ids, all with departure_id = 1, keeping order in `sort`.
   - Verify counts (pilgrims, rooms per city, ledger rows, total due/paid) match; if not, roll back (delete inserted rows) and show an admin notice with the numbers; never delete the old option.
   - On success rename the option to pothik_pro_desk_legacy and store pothik_pro_migrated_at.
   - Add a hidden tool (Settings → Data, only when the legacy option exists): "Restore pre-1.1 data" which empties the four tables and renames the legacy option back. Ask for confirmation.
4. WP-CLI command (only if WP_CLI is defined): `wp pothik-pro migrate --dry-run` prints what would move.
5. Uninstall: when the free plugin's delete_on_uninstall is on, drop the four tables and the options.
Nothing changes in the UI yet except that every screen now reads departure #1 through the new layer. Test: all five Pro screens look and save exactly as before.
```

---

## Prompt 3 — সক্রিয় গ্রুপ আর গ্রুপ বাছাই

```
<<paste RULES>>

TASK: Make every Pro screen work on the "active departure" and add the switchers from the mockup.

1. PothikPro\Context:
   - active_id(): int — from ?departure= (validated) → user meta pothik_pro_active_departure → the next upcoming active departure → the most recent one → 0.
   - set_active(int $id) stores user meta (per user, so two staff can work on different groups).
   - When ?departure= is present and valid, it also becomes the active one.
   - Every Pro admin link built with Context::url($page, $args) carries departure=…
2. Header switcher (hook pothik/admin_header_tools, only on group screens: pothik-departures, pothik-pilgrims, pothik-rooming, pothik-ledger, pothik-visa): gold button "GROUP · {name}" opening a popover — search box, "Active" list (upcoming + travelling, by leave date), "Completed" list, each row with colour dot, name, "{leave} – {return} · {n} pilgrims" and a ✓ on the current one, footer "All groups →" and "+ New group". Choosing a group = link to the same screen with ?departure= (works without JS); JS adds keyboard (arrows/Enter/Esc), filtering and a toast "Now working on {name}".
3. Sidebar box (hook pothik/admin_sidebar_before_pro): "ACTIVE GROUP / {name}" linking to the Departures list; icon-only in mini mode.
4. Context bar (hook pothik/admin_before_content, group screens only): "Working on {name} · {12–18 Mar 2027}" + "Switch group →".
5. Scope all five screens + their save handlers to Context::active_id(): forms include hidden departure_id; handlers use the posted departure_id (not the user meta) and verify it exists. Redirects keep &departure=.
6. Empty state: if there is no departure at all, every group screen shows a friendly card "Create your first group" with a button to the New group drawer.
7. Rename the old single "Departure" menu item: the module 'departures' now opens the LIST (Prompt 4); the single-group screen becomes ?page=pothik-departures&departure={id}&view=edit. Sidebar label "Departures" with a count badge of non-archived groups.
```

---

## Prompt 4 — Departures তালিকা, নতুন গ্রুপ, কপি, আর্কাইভ

```
<<paste RULES>>

TASK: Build the Departures list screen and the New group drawer exactly as in docs/departures-mockup.html.

1. Status helper: Departures::state($d): archived | done (return < today) | travelling (leave ≤ today ≤ return) | upcoming. Use wp_timezone().
2. Summary cards: "Next to fly — {n} days · {name} · {date}" (dark green), Active groups, Pilgrims in active groups, Seats filled % (held ÷ seats of active groups).
3. "Season at a glance" timeline: SVG/HTML rows, one per non-archived group sorted by leave date; label column 170px with the active group marked ●; bar from leave to return coloured with the group colour (min width so 1-week trips are visible); label "{leave} · {held}/{seats}" right of the bar (left of it when near the right edge); month scale for the range covering all groups (≥ 10 months); red "Today" line; a gold band for Ramadan of the season (Umm al-Qura via IntlDateFormatter, skip if unavailable). Clicking a bar or name opens that group (set active + go to its edit screen). Horizontal scroll under 1100px. Screen-reader table with the same data.
4. Filter chips with counts: Active (upcoming+travelling) · Upcoming · Travelling · Completed · Archived (?state=…, JS instant). Search on name, package title and flight. Primary "New group".
5. Group card (grid auto-fill minmax(330px,1fr)): coloured header (group colour → darker) with name, type pill (Group/Private/Hajj), gold "● Active" pill for the user's active group, DAC ✈ JED/MED (route decides JED/MED), leave date · airline+flight · return date; floating pill "Leaves in {n} days" (clay when ≤ 45), "✈ Travelling now", or "✓ Returned". Body: linked package chip (or grey "No package linked"), seats bar "{held} of {seats} seats confirmed · {n} left/Full", three mini stats — Pilgrims, Visas issued {x}/{n} with bar, Collected % with gold bar (from ledger). Footer: primary "Work on this group" ("Open" when already active), ghost "Pilgrims", ⋯ menu: Copy as new group, Archive/Restore (cannot archive the active group — toast). Last tile: dashed "New group".
6. New group drawer (also a plain form for no-JS at ?page=pothik-departures&view=new): Group name (+ Bangla name in Bangla mode), Type segmented (Group/Private/Hajj), Package select (published + drafts) with hint "shown on the site with this group's seats", Leave / Return dates (return ≥ leave), Total seats stepper, "Copy setup from" select (Start blank or any group) with checkboxes: Flight, airline and origin · Saudi company and season dates · Group leader and guide · Room labels (no pilgrims). Note "Pilgrims, payments and visas are never copied." Colour picked automatically from a palette of 8 (not used by another active group if possible). On create: insert, set as active, redirect to the list with toast "{name} created — it is now the active group". admin-post action pothik_pro_new_departure with nonce.
7. Edit screen of one group (?view=edit) = the redesigned Departure screen, now saving into pothik_departures; add Name, Type, Package and Colour fields at the top; add "Archive this group" in a danger card.
8. Public site: register Pro's 'pothik/seat_line' with the package argument — find the active (non-archived, upcoming) departure linked to that package_id with the nearest leave date; show "{held} seats confirmed · {seats} total" only for type group and seats > 0. Add a 'pothik/package_card_badges' badge "{n} seats left" / "Full" for the same departure.
```

---

## Prompt 5 — হাজিকে অন্য গ্রুপে সরানো আর Desk-এ সংক্ষেপ

```
<<paste RULES>>

TASK: Moving pilgrims between groups, and small integrations.

1. Pilgrim edit screen: a "Group" card in the right column (above "Ready to travel"): text "Move this pilgrim to another departure. Their room is cleared; the ledger row moves with them.", a select of other non-archived, not-completed groups ("{name} · {leave}"), and "Move pilgrim". admin-post pothik_pro_move_pilgrim (nonce, departure check). Server: in ONE transaction-like sequence — update pilgrim.departure_id, remove the id from every room of the old departure, move their ledger row(s) to the new departure_id. Redirect to the pilgrim list of the OLD group with a toast "Moved to {group} — room cleared, ledger row moved" and an Undo link (moves back; rooms are not restored — say so in the toast).
2. Bulk move on the Pilgrim file list: select pilgrims → "Move to group…" using the same handler.
3. Departure deletion is NOT offered (only archive). If a group has 0 pilgrims, 0 rooms and 0 ledger rows, show "Delete empty group" instead of archive.
4. Free Desk: if Pro is active, add a small "Groups" card via the existing Desk hooks (or 'pothik/desk_cards' — add it to the free plugin if missing, only as a filter) showing the next 3 upcoming groups with leave date, seats and a link.
5. Analytics: Pro's panels (do_action pothik/analytics) get a group filter (All groups / one group).
6. Update ROADMAP.md: mark 1.1 as 🧪 and list the files changed.
```

---

## QA — শেষে একবার

```
Check roadmap step 1.1 against docs/departures-mockup.html and ROADMAP.md. Report as a checklist:
- Migration on a copy of a real site: counts and totals match; pilgrim/room/ledger ids unchanged; Bangla prose kept; old option renamed to pothik_pro_desk_legacy; "Restore pre-1.1 data" works and re-migration works again.
- Fresh install with no old data: no errors; empty states guide to "Create your first group".
- Two users can work on two different groups at the same time (active group is per user); forms always save into the departure_id they were opened with.
- Switching groups via header popover, sidebar box, list cards, timeline bars, and ?departure=; keyboard works; no-JS links work.
- Each of the five Pro screens shows and saves only the active group's data.
- New group: copy options copy only what is ticked; pilgrims, payments, visas never copied; colour picked.
- Archive/restore; active group cannot be archived; empty group can be deleted.
- Move pilgrim (single and bulk): room cleared, ledger moves, Undo moves back.
- Public package page shows the seat line and "seats left" badge from the right departure; private/hajj groups don't show the seat line.
- Timeline readable at 1440, 1000 and 390px; screen-reader table present.
- Bangla mode: no overflow; dates and numbers localised.
- No PHP notices, all queries prepared, Plugin Check passes for both plugins.
```
