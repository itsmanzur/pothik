# ধাপ ১.৩ — কে কী বদলাল (Activity log): প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **আগে শেষ থাকতে হবে:**
   - ধাপ ১.১ (একাধিক ডিপার্চার)।
   - ধাপ ১.২ (স্টাফের অনুমতি)। ১.২-এর Prompt 4-এ রাখা অনুমতি বদলের ছোট খাতা (`pothik_pro_access_log`) এই ধাপে আসল লগে চলে যাবে।
2. `pothik-pro-activity-mockup.html` ফাইলটা Pro প্লাগিনের ফোল্ডারে `docs/activity-mockup.html` নামে রাখুন।
3. ক্রম: **Prompt 1 (ফ্রি) → Prompt 2 → Prompt 3 → Prompt 4 → QA**।
   - Prompt 1 **ফ্রি প্লাগিনে (pothik)**, বাকিগুলো **Pro প্লাগিনে (pothik-pro)**।
   - দুটো ফোল্ডারই Cursor-এ খোলা রাখবেন।
4. প্রতিটার পর git commit দিন। শেষ হলে `ROADMAP.md`-তে ১.৩-এর পাশে ✅।

---

## RULES (Prompt 2–4-এর শুরুতে পেস্ট করবেন)

```
RULES — Pothik Pro, Activity log (roadmap step 1.3)
- Read ROADMAP.md (section 1.3) first.
- Visual reference: docs/activity-mockup.html — the "Activity log" screen (data-screen="activity") and the per-pilgrim History drawer (#hist). Colours are Pothik's own.
- The log is APPEND-ONLY. No screen, AJAX or tool may edit or delete a single entry. The only deletion is the automatic retention cleanup (cron) and full uninstall.
- Logging must never break the action being logged: wrap writes in try/catch, never throw, never block a save. If the table is missing, skip silently and show an admin notice to managers.
- Privacy: never store full passport numbers, NID numbers, passwords, nonces or full phone numbers in the log. Mask passport as first 2 + ••• + last 3, phone as last 4 only. Store only the fields that changed, not whole records.
- Access (from step 1.2): area "activity" — Manager edit (settings for retention), Viewer and Accounts view, Staff none by default. Accounts only sees entries in the Money area; anyone limited to some departures only sees entries for those departures (plus entries with no departure only if they are Manager).
- Every query uses $wpdb->prepare. Strings via Pothik\I18n::t() with Bangla translations. Escape everything. Vanilla JS. UI via Pothik\Admin\UI.
- When done: php -l on touched PHP files, list changed files.
```

---

## Prompt 1 — ফ্রি প্লাগিনে লগের hook (pothik)

```
Plugin: Pothik (free). Pothik Pro is adding an activity log. Add ONLY action hooks in the free plugin so Pro can record what happens — no logging code, no table here (WP.org rules).

Fire these actions AFTER the change is saved successfully, passing the old and new data where the change edits something:
- do_action('pothik/package_saved', string $package_id, array $before, array $after)
- do_action('pothik/package_created', string $package_id, array $package)
- do_action('pothik/package_deleted', string $package_id, array $before)
- do_action('pothik/package_status_changed', string $package_id, string $old, string $new)
- do_action('pothik/inquiry_reply_toggled', string $inquiry_id, bool $replied)
- do_action('pothik/inquiry_deleted', string $inquiry_id, array $before)
- do_action('pothik/inquiries_exported', int $count)
- do_action('pothik/receipt_saved', array $before, array $after)
- do_action('pothik/settings_saved', array $before, array $after)
- do_action('pothik/demo_data', string $action)  // 'loaded' | 'removed'
Read the "before" value BEFORE the update in each handler. Do not change any behaviour. Add the hooks to the Hooks section of readme.txt. php -l; list changed files.
```

---

## Prompt 2 — লগের টেবিল আর লেখার ব্যবস্থা (Pro)

```
<<paste RULES>>

TASK: Create the log storage and record every important change.

1. Table {prefix}pothik_activity (dbDelta, bump pothik_pro_db_version):
   id BIGINT PK AI, created_at DATETIME (GMT), user_id BIGINT, user_name VARCHAR(120) (copy of display name at the time — users may be deleted later), departure_id BIGINT NULL, area VARCHAR(20) (money|visa|pilgrims|rooms|groups|team|settings|packages|inquiries), action VARCHAR(40) (e.g. payment_added, visa_changed, pilgrim_deleted), object_type VARCHAR(20) (pilgrim|ledger|room|departure|package|inquiry|user|settings), object_id VARCHAR(40), object_label VARCHAR(160) (e.g. pilgrim name, room label), amount BIGINT NULL (taka, for money entries), is_delete TINYINT, changes LONGTEXT (JSON list of {field, label, before, after}), context LONGTEXT NULL (JSON: channel, trx masked, count for bulk actions). Indexes: created_at, user_id, departure_id+created_at, area+created_at, object_type+object_id.
2. PothikPro\Activity::log(array $entry): void — fills user and time, masks sensitive values (helper Activity::mask($field, $value)), drops fields whose before === after, skips the write when nothing changed, never throws. Activity::diff(array $before, array $after, array $labels): array builds the changes list. Labels are translatable field names ("Paid", "Visa", "Makkah room"…).
3. Record (hook into the existing save handlers in Desk/repositories, AFTER a successful save):
   - Money: ledger row added/edited/removed (amount = change in paid), payment added (step 2.2 later), receipt printed (receipt number if present).
   - Visa: single and bulk status changes (bulk = one entry with context.count and the list of names, max 50).
   - Pilgrims: created, edited (only changed fields), moved between departures, deleted (keep name + masked passport + their ledger totals in the entry).
   - Rooms: pilgrim placed/removed, room added/removed, copied between cities, "fill by family" (one entry with count).
   - Groups: departure created, edited (seats, dates, flight…), archived/restored, deleted.
   - Team: role changed, member added/removed, groups changed, matrix changed. Migrate the old pothik_pro_access_log option entries into the table once, then delete that option.
   - Free plugin hooks from Prompt 1: packages, inquiries (reply/delete/export), receipt, settings, demo data.
   - Bulk actions write ONE entry, not one per row.
4. Retention: option pothik_pro_activity_keep_days (default 365; allowed 90, 365, 730, 0 = forever). Daily WP-Cron event pothik_pro_activity_cleanup deletes rows older than the setting in batches of 1,000, EXCEPT area = money rows younger than 730 days. Unschedule on deactivation.
5. Uninstall: drop the table only when delete_on_uninstall is on.
```

---

## Prompt 3 — Activity log পেইজ (Pro)

```
<<paste RULES>>

TASK: Build the Activity log screen from docs/activity-mockup.html. Menu item under SYSTEM "Activity log" (clock icon), visible to roles with view on "activity".

1. Summary cards (respecting the viewer's access): Changes today · Money recorded this week (sum of amount where amount > 0, + "{n} entries") · Visa status changes this week · Deletions this week (clay card, "check them"; clicking it filters to deletions).
2. Filters (GET params, work without JS; JS applies them without reload via AJAX pothik_pro_activity_list returning rendered HTML + next-page cursor): search (object_label, context trx, change values — LIKE with esc_like), person (users who appear in the log), area (Money · Visa · Pilgrims & rooms · Groups, team & settings · Deletions only), group (only departures the viewer may open), period (7 days default · 30 days · All time). Export CSV of the current filter (respects access; masked values stay masked; filename pothik-activity-YYYY-MM-DD.csv).
3. List grouped by day (site timezone; "Today · Saturday 31 October", "Yesterday · …", then the date):
   - Each entry: initials avatar in a colour per user, one readable sentence built from action + data (e.g. "Rahim recorded a payment of ৳1,05,000 for রফিকুল ইসলাম · bKash · TrxID BKX9…7Q"; "Salma moved the visa of ফারহানা আক্তার from Invoice to Paid"; "Kamrul moved 6 visas in December group from Paid to Issued"). Sentences are translatable templates per action, filterable via 'pothik_pro/activity_sentence'.
   - Pills: area (Money gold, Visa blue, Pilgrims green, Groups & team purple, Deleted clay) and the group name. Time on the right.
   - Money entries get a gold left border, deletions a clay left border.
   - "Show what changed ▾" expands a Field / Before / After table (before in clay with strike-through, after in green) and the note "The log is read-only. To reverse this, edit the record itself."
   - Names of pilgrims link to the History drawer.
   - Infinite "Load more" (50 per page, cursor on id).
4. Right column: "Who changed most · 7 days" bars; "Keep the log for" select + Save (Manager only; others see the current value as text) with the note "Older entries are deleted automatically. Money entries are kept at least 2 years."; "What is logged" list and "Passport numbers are never written in full here. The log cannot be edited."
5. Empty state: "No activity matches — Try another person, area or date range."
6. Under 1100px the right column moves below the list.
```

---

## Prompt 4 — হাজির ইতিহাস, Desk আর অন্য স্ক্রিনে যোগ (Pro)

```
<<paste RULES>>

TASK: Show history where people need it.

1. History drawer (right side, 440px, from the mockup): opens from pilgrim names in the log AND from a new "History" button on the pilgrim edit screen. Shows a vertical timeline of all entries for that pilgrim (object_type pilgrim, plus ledger/room/visa entries that mention their id), newest first, with a small icon per area (৳, 🛂, ✎, ✕), sentence, "{day} · {time} · {person}", and the file-created entry at the bottom. Footer button "Open pilgrim file". AJAX pothik_pro_activity_object (access-checked).
2. Small "Last changed by {name} · {time ago}" line under the page title on: pilgrim edit, departure edit, ledger row detail (only when the viewer can see that entry).
3. Ledger rows: a tiny clock icon opens the same drawer filtered to that row's money entries.
4. Desk card for Managers: "Today at the desk" — 5 latest entries in short form + "Open activity log".
5. Activity sentence for actions from step 2.1+ (inquiry → pilgrim, payments with receipt numbers, commissions) must be easy to add: document in a docblock how to call Activity::log() and register a sentence template.
6. Update ROADMAP.md: mark 1.3 as 🧪 and list the files changed.
```

---

## QA — শেষে একবার

```
Check roadmap step 1.3 against docs/activity-mockup.html and ROADMAP.md. Report as a checklist:
- Every action in Prompt 2's list writes exactly one entry with the right area, group, sentence and before/after; unchanged saves write nothing; bulk actions write one entry.
- A failing log write (rename the table temporarily) never blocks the save; managers see a notice.
- No full passport numbers, phone numbers, passwords or nonces anywhere in the table, the screen, the CSV or the drawer.
- No way to edit or delete an entry (check screens, AJAX, REST, forged POSTs).
- Access: Staff can't open the log; Accounts sees only Money; a user limited to "March group" sees only March entries; Viewer sees but can't change retention.
- Filters, search (Bangla names, transaction IDs), period, Load more, CSV export with the same filter.
- History drawer from the log, the pilgrim screen and ledger rows; "Last changed by" lines correct.
- Retention cron deletes old rows in batches and keeps money rows for 2 years; deactivation unschedules it.
- Old pothik_pro_access_log entries migrated once.
- Day grouping uses the site timezone; Bangla mode readable at 1280px and 390px.
- No PHP notices, all queries prepared, Plugin Check passes for both plugins.
```
