# ধাপ ২.১ — ইনকোয়ারি থেকে হাজি, এক ক্লিকে: প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **আগে শেষ থাকতে হবে:**
   - ধাপ ১.১ (একাধিক ডিপার্চার)।
   - ধাপ ১.২ (অনুমতি): কে বুক করতে পারবেন সেটা এখান থেকে আসে।
   - ধাপ ১.৩ (Activity log): বুকিং লগে ওঠে।
   - ১.২ বা ১.৩ বাকি থাকলেও কাজ চলবে, শুধু সেই অংশটুকু বাদ পড়বে। প্রম্পটেই সেটা বলা আছে।
2. `pothik-pro-inquiry-booking-mockup.html` ফাইলটা Pro প্লাগিনের ফোল্ডারে `docs/inquiry-booking-mockup.html` নামে রাখুন।
3. ক্রম: **Prompt 1 (ফ্রি) → Prompt 2 → Prompt 3 → Prompt 4 → QA**।
   - Prompt 1 **ফ্রি প্লাগিনে (pothik)**, বাকিগুলো **Pro প্লাগিনে (pothik-pro)**।
   - দুটো ফোল্ডারই Cursor-এ খোলা রাখবেন।
4. প্রতিটার পর git commit দিন। শেষ হলে `ROADMAP.md`-তে ২.১-এর পাশে ✅।

---

## RULES (Prompt 2–4-এর শুরুতে পেস্ট করবেন)

```
RULES — Pothik Pro, inquiry → pilgrim (roadmap step 2.1)
- Read ROADMAP.md (section 2.1) first.
- Visual reference: docs/inquiry-booking-mockup.html — the Inquiries screen (data-screen="inquiries": five funnel cards, conversion bar, table with Status and Follow up columns, "Book" button) and the inquiry drawer (#ld) with the status buttons, follow-up box, office notes and the 3-step booking wizard + success card. Colours are Pothik's own.
- Ownership: inquiries belong to the FREE plugin (table {prefix}pothik_inquiries, InquiryRepository, the `replied` flag). Pro must NOT alter that table. Pro keeps its extra data (pipeline status, follow-up date, lost reason, notes, booking link) in its own tables and plugs into the free screen through hooks.
- Status mapping with the free plugin: Pro status new ⇔ replied = 0; replied/booked/lost ⇔ replied = 1. When Pro changes status it also calls the free plugin's existing reply toggle so the free screens and Desk stay correct. When the free toggle is used (e.g. Pro deactivated, then reactivated), Pro reads replied = 1 with no Pro row as "replied".
- Booking writes ONLY through the 1.1 repositories (Departures, Pilgrims, Ledger) and inside one guarded sequence: check seats → create pilgrims → create ledger rows → record deposit → mark inquiry booked. If any step fails, undo the rows already created and show the error; never leave half a booking.
- Access (step 1.2, if present): view inquiries to open the screen; edit inquiries to change status/follow-up/notes; booking also needs edit pilgrims on the chosen departure; deposit also needs edit ledger. Groups the user can't open are not offered. If step 1.2 is not installed, fall back to manage_options.
- Activity (step 1.3, if present): log status changes, follow-up set/cleared, notes added, booking (one entry: departure, number of pilgrims, contract total, deposit). Skip silently if 1.3 is missing.
- Strings via Pothik\I18n::t() with Bangla translations. Escape everything. $wpdb->prepare everywhere. Vanilla JS. UI via Pothik\Admin\UI. Works without JS (plain forms; the wizard becomes one page with three sections).
- When done: php -l on touched PHP files, list changed files.
```

---

## Prompt 1 — ফ্রি প্লাগিনে hook (pothik)

```
Plugin: Pothik (free). Pothik Pro is adding a sales pipeline and "book into a group" on top of inquiries. Add ONLY extension points; no Pro logic (WP.org rules). Keep the free Inquiries screen working exactly as now when Pro is not active.

1. Inquiries screen:
   - do_action('pothik/inquiries_before_table', array $counts) right above the toolbar (Pro prints its funnel there). When something is hooked, hide the free status chips (filter 'pothik/inquiries_show_status_chips', default true).
   - apply_filters('pothik/inquiry_columns', array $columns) — the column list (key => label) used for <thead>; and apply_filters('pothik/inquiry_cell', string $html, string $column_key, array $row) for each cell of an unknown column key.
   - apply_filters('pothik/inquiry_row_actions', string $html, array $row) for the actions cell (Pro adds "Book").
   - apply_filters('pothik/inquiries_query_args', array $args) passed into InquiryRepository::paginate() and count methods, plus a way for Pro to supply a list of ids to include/exclude: support $args['include_ids'] and $args['exclude_ids'] (prepared IN lists) in the repository.
2. Inquiry drawer: do_action('pothik/inquiry_drawer_top', array $row) after the header, and keep the existing do_action('pothik/inquiry_actions', $row) in the footer.
3. Repository: InquiryRepository::find(string $id): ?array (by id/uuid) and set_replied(string $id, bool $replied) (used by the existing toggle handler; fires the existing 'pothik/inquiry_reply_toggled' action).
4. Desk: apply_filters('pothik/desk_cards', array $cards) if not added yet.
5. Add the new hooks to the Hooks section of readme.txt. php -l; list changed files.
```

---

## Prompt 2 — পাইপলাইনের ডেটা আর ইনকোয়ারি পেইজ (Pro)

```
<<paste RULES>>

TASK: Pipeline data and the Inquiries screen.

1. Tables (dbDelta, bump pothik_pro_db_version):
   {prefix}pothik_leads: inquiry_id VARCHAR(40) PK, status VARCHAR(10) (new|replied|booked|lost), follow_up DATE NULL, lost_reason VARCHAR(40) NULL (price|other_agency|dates|no_reply|postponed|other), lost_note VARCHAR(160) NULL, departure_id BIGINT NULL, booked_at DATETIME NULL, booked_by BIGINT NULL, updated_at DATETIME. Index status, follow_up.
   {prefix}pothik_lead_notes: id BIGINT AI, inquiry_id, user_id, note VARCHAR(500), created_at. Index inquiry_id.
   PothikPro\Leads repository: get(id) (returns defaults derived from the free row when no Pro row exists: replied 0 → new, 1 → replied), set_status, set_follow_up, add_note, notes(id), mark_booked(id, departure_id), counts(), due_count(). Deleting an inquiry in the free plugin (hook 'pothik/inquiry_deleted') deletes its lead row and notes.
2. Funnel (hook pothik/inquiries_before_table): five cards — All (last 30 days) · New "Not answered yet" · Follow up due "Today or overdue" (clay card; follow_up ≤ today and status new/replied) · Replied "Waiting on them" · Booked "In a group" (green). Each is a link with ?stage= (new|due|replied|booked; also lost reachable from the conversion bar legend). Below: conversion bar "{x}% booked" with segments new / replied / booked / lost and counts. Uses 'pothik/inquiries_query_args' to filter the table by the Pro status (include_ids).
3. Table columns (filters from Prompt 1): add "Status" (pill New clay / Replied blue / Booked green + "✓ {group name}" / Lost grey + reason), "Follow up" ("Overdue 2d" clay, "Today" clay, "Tomorrow", "In 3 days" gold, "—"). Package · share and People stay from the free plugin. Row action "Book" (primary small button) for new/replied when the user may book.
4. Drawer top (hook pothik/inquiry_drawer_top):
   - Status buttons New / Replied / Booked / Lost (Booked opens the wizard instead of just switching). Lost shows a reason select (+ free text for Other). Changing status updates the free `replied` flag via InquiryRepository::set_replied.
   - When booked: green note "✓ Booked into {group}. Open pilgrims →" (link to that departure's pilgrim list).
   - For new/replied: the dark green "Book into a group" button ("Creates {n} pilgrim files and their ledger rows in one step") and the gold Follow up box: quick buttons Tomorrow / In 3 days / Next week, a date input, Clear; text "Shows on the Desk and in “Follow up due” on that day."
   - Office notes: list (note, "{user} · {time ago}") + input + Add. Adding the first note to a "new" lead moves it to "replied".
   All via admin-post handlers (no-JS) with AJAX enhancement returning the re-rendered drawer section; nonces + access checks.
5. Desk card (filter pothik/desk_cards): "Follow up today" — up to 5 due leads with name, package, how overdue, WhatsApp button and "Open"; count badge; hidden when 0.
```

---

## Prompt 3 — বুকিংয়ের ৩ ধাপ (Pro)

```
<<paste RULES>>

TASK: The "Book into a group" wizard from the mockup, and the server-side booking.

1. Wizard inside the drawer (no-JS: admin.php?page=pothik-inquiries&book={id} renders the same three sections on one page). Progress bar of 3 segments + "Step n of 3 · {title}".
   Step 1 — Choose a group: list of departures the user may edit that are upcoming (not archived, return date in the future), sorted with the inquiry's package first ("Same package" gold tag) then by leave date. Each option: colour bar, name, "{dates} · {package}", seats left (green; clay when ≤ 5). Options with fewer free seats than the party size are disabled with "Only {n} left". Private groups are offered only if empty. Preselect the nearest same-package group that fits.
   Step 2 — Who is travelling: Family name (default "{first word of name}'s family", hint "groups them on the pilgrim file and rooming"). Rows prefilled from the inquiry: adults (share = inquiry share), children (child_bed), infants (infant); first row's name = inquiry name. Each row: number badge, name input (placeholder "Name as in passport" / "Name (can add later)"), share select, price for that share from the chosen group's package (Format::money; "—" when the package has no price for it), remove ✕. "+ Add person". Hint about empty names becoming "{family} 2". The first name is required.
   Step 3 — Check and confirm: summary (group, number of files + family, one line per person with share and price, contract total), checkbox "Create a ledger row for each person with the package price as contract" (default on; hidden when the user can't edit ledger), "Deposit received now (optional)" amount with ৳ grouping + channel select (bKash/Nagad/Rocket/Bank/Cash) + optional transaction ID, note "Recorded on the first person's ledger row.", checkbox "Send a WhatsApp confirmation to {phone}". Gold "Confirm booking".
   Success card: green tick, "Booked, alhamdulillah", "{name} and family are now in {group}.", checklist (files created, ledger rows + total, deposit, inquiry marked booked · logged), buttons "Back to inquiries" / "Open pilgrim files".
2. Server: admin-post pothik_pro_book_inquiry (nonce, access). PothikPro\Booking::book(array $input): array|WP_Error
   - Re-check seats at save time (held + n ≤ seats) to avoid double booking by two staff at once; if not enough, return an error that sends the user back to step 1 with "Someone just took seats — only {n} left."
   - Refuse if the inquiry is already booked (show where).
   - Create pilgrims (name or "{family} {i}", share, family, departure_id, gender empty, visa draft, biometric not_started), ledger rows (pilgrim_id, name, due = price, paid = 0), then deposit on row 1 (paid += amount, channel, trx). If step 2.2's payments table exists, write the deposit as a payment instead.
   - departure held += n (respect seats max).
   - Leads::mark_booked(), set the free replied flag, clear follow-up.
   - On any failure: delete the rows created in this call and restore held; return WP_Error.
   - Return ids for the success card.
3. WhatsApp confirmation: build a wa.me link (Format::whatsapp_href) with a translatable template, filter 'pothik_pro/booking_whatsapp_text': "Assalamu alaikum {name}. Alhamdulillah, your Umrah booking is confirmed in {group} ({dates}). {n} pilgrims · contract ৳{total}{deposit line}. We will send the paper list soon. — {agency}". The success card opens it in a new tab when the box was ticked (never sends anything automatically).
```

---

## Prompt 4 — শেষ ছোঁয়া (Pro)

```
<<paste RULES>>

TASK: Polish and connections.

1. Pilgrim file: pilgrims created from an inquiry show a small "From inquiry · {date}" line and a link back to it; the inquiry drawer of a booked lead lists the created pilgrims with links.
2. Duplicate guard: when booking, if a pilgrim with the same name AND phone already exists in that departure, show a warning on step 3 ("{name} may already be in this group") — warning only.
3. Analytics (Pro panels): "Inquiries → bookings" — conversion % for the period, booked by package, and top lost reasons (bars).
4. Lost reasons and follow-up quick choices are filterable: 'pothik_pro/lost_reasons', 'pothik_pro/follow_up_choices'.
5. Bulk on the inquiries table: select rows → "Set follow-up…", "Mark lost…" (with reason). One activity entry per bulk action.
6. Update ROADMAP.md: mark 2.1 as 🧪 and list the files changed.
```

---

## QA — শেষে একবার

```
Check roadmap step 2.1 against docs/inquiry-booking-mockup.html and ROADMAP.md. Report as a checklist:
- Free plugin alone (Pro deactivated): Inquiries screen works exactly as before; reactivating Pro shows correct statuses.
- Funnel counts, ?stage= filters, conversion bar; Follow up due includes overdue and today only for new/replied.
- Status buttons keep the free `replied` flag in sync both ways; Lost requires a reason.
- Follow-up quick buttons / date / clear; Desk "Follow up today" card.
- Notes: add, order, author, time; first note moves New → Replied.
- Wizard: same-package group preselected; groups without enough seats disabled; private groups only when empty; prices per share; add/remove people; first name required; no-JS one-page version works.
- Booking creates exactly n pilgrims and n ledger rows with the right contract, deposit on row 1, held += n, inquiry booked; an already-booked inquiry can't be booked twice.
- Race test: two browsers booking the last seats → second gets "only n left", nothing half-created.
- Forced failure in the middle (e.g. make ledger insert fail) → no pilgrims left behind, held unchanged.
- Access: a Staff limited to March group sees only March in step 1; a user without ledger edit doesn't see the ledger/deposit options.
- Activity log entries for status, follow-up, notes, booking (if 1.3 installed).
- WhatsApp confirmation link text correct in English and Bangla; nothing sent automatically.
- Bangla mode at 1280px and 390px; no PHP notices; queries prepared; Plugin Check passes for both plugins.
```
