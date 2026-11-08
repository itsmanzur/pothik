# ধাপ ২.২ — কিস্তির পূর্ণ ইতিহাস ও রসিদ: প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **আগে শেষ থাকতে হবে:** ধাপ ১.১ (একাধিক ডিপার্চার, লেজার টেবিল)।
   - ১.২ আর ১.৩ থাকলে ভালো, তখন অনুমতি আর activity log কাজ করবে। না থাকলেও এই ধাপ চলবে।
   - ২.১-এ বুকিংয়ের অগ্রিম টাকাও এই ধাপের পর থেকে আলাদা পেমেন্ট হিসেবে লেখা হবে।
2. **ডেটাবেসের ব্যাকআপ নিন।** লেজারের পুরনো "মোট পরিশোধ" টাকা প্রথমবার আলাদা পেমেন্টে ভাগ হবে।
3. `pothik-pro-payments-mockup.html` ফাইলটা Pro প্লাগিনের ফোল্ডারে `docs/payments-mockup.html` নামে রাখুন।
4. Installments ফাইলের **ঐচ্ছিক Prompt 3 আর চালাবেন না।** এই ধাপ সেটার জায়গা নিচ্ছে।
5. ক্রম: **Prompt 1 → Prompt 2 → Prompt 3 → Prompt 4 → QA**, সবগুলো **Pro প্লাগিনে (pothik-pro)**। ফ্রি প্লাগিনের ফোল্ডারও Cursor-এ খোলা রাখবেন।
6. প্রতিটার পর git commit দিন। **Prompt 1-এর পর অবশ্যই মিলিয়ে দেখবেন, প্রতিটা হাজির মোট পরিশোধ আগের মতোই আছে কিনা।** শেষ হলে `ROADMAP.md`-তে ২.২-এর পাশে ✅।

---

## RULES (প্রতিটা প্রম্পটের শুরুতে পেস্ট করবেন)

```
RULES — Pothik Pro, payment history & receipts (roadmap step 2.2)
- Read ROADMAP.md (section 2.2) first. This step REPLACES the optional "Prompt 3 — payment history" of the older installments prompts; if any part of it was built, migrate it into the tables below instead of keeping two systems.
- Visual reference: docs/payments-mockup.html — the Installments screen (data-screen="pay": collection card, "Installments due" card with Overdue/This week/This month, the accounts table), the account drawer (#ac) with tabs Payments / Installment plan / + Add payment, and the receipt dialog (#rcpt). Colours are Pothik's own; channel colours bKash #e2136e, Nagad #f6921e, Rocket #8c3494, Bank #2563eb, Cash #40916c (coloured letters only, never real logos).
- Money rules:
  • A payment is NEVER deleted or edited after saving. Mistakes are VOIDED with a required reason; voided payments stay visible, struck through, and stop counting.
  • ledger.paid always equals the sum of non-voided payments for that row. Recalculate it inside the same request every time a payment is added or voided. Every other screen (visa board, departures, desk, analytics) keeps reading ledger.paid, so nothing else has to change.
  • Contract (ledger.due) is never changed by payments or by installment plans. Plans are reminders.
  • Amounts are whole taka (BIGINT UNSIGNED). Format::money for display; strip commas before submit.
  • Receipt numbers are unique, sequential, never reused (even after a void), generated atomically on the server.
- Access (step 1.2, if present): area "ledger" view to open, edit to add payments and plans; area "receipt" view to print/share receipts; voiding needs ledger edit AND is limited to Manager/Accounts roles (filter 'pothik_pro/can_void'). Without 1.2: manage_options.
- Activity (step 1.3, if present): payment added, payment voided (with reason), plan created/replaced, receipt printed/shared. Skip silently if missing.
- $wpdb->prepare everywhere. Strings via Pothik\I18n::t() with Bangla translations. Escape everything. Vanilla JS. UI via Pothik\Admin\UI. Works without JS.
- When done: php -l on touched PHP files, list changed files.
```

---

## Prompt 1 — পেমেন্ট আর কিস্তির টেবিল, পুরনো টাকা ভাগ করা

```
<<paste RULES>>

TASK: Storage, receipt numbering and migration.

1. Tables (dbDelta, bump pothik_pro_db_version):
   {prefix}pothik_payments: id BIGINT AI, departure_id BIGINT, ledger_id VARCHAR(40), pilgrim_id BIGINT NULL, paid_on DATE, amount BIGINT UNSIGNED, channel VARCHAR(10) (bkash|nagad|rocket|bank|cash), trx VARCHAR(40), note VARCHAR(255), receipt_no VARCHAR(30) NULL UNIQUE, is_opening TINYINT DEFAULT 0, received_by BIGINT, received_by_name VARCHAR(120), voided_at DATETIME NULL, voided_by BIGINT NULL, void_reason VARCHAR(255) NULL, created_at DATETIME. Indexes: ledger_id, departure_id+paid_on, trx, receipt_no.
   {prefix}pothik_installments: id BIGINT AI, ledger_id VARCHAR(40), due_on DATE, amount BIGINT UNSIGNED, sort SMALLINT, created_by BIGINT, created_at DATETIME. Index ledger_id+due_on.
2. PothikPro\Payments repository:
   - add(array $p): array — validates (amount > 0, date not in the future by more than 1 day, channel allowed, ledger row exists and belongs to the departure), assigns receipt_no, inserts, recalculates ledger.paid and ledger.channel/trx (= latest non-voided payment), returns the payment.
   - void(int $id, string $reason): bool — reason required (min 5 chars); sets voided_*; recalculates ledger.paid. Cannot void twice. Cannot void an opening balance unless the user is a WP admin.
   - for_ledger(string $ledger_id), find(int $id), by_receipt(string $no), search(string $q) (receipt_no, trx).
   - recalc(string $ledger_id): sum of non-voided amounts → ledger.paid.
3. Receipt numbers: PothikPro\ReceiptNo::next(): string. Format from settings: prefix (default "PTK"), year part (option: Hijri year via IntlDateFormatter umalqura when available, else Gregorian), 4-digit counter → "PTK-1448-0042". Counter reset per year part. Atomic: a single-row counter table {prefix}pothik_counters (name PK, value BIGINT) updated with "UPDATE … SET value = LAST_INSERT_ID(value + 1) WHERE name = %s" and SELECT LAST_INSERT_ID(); insert the row first if missing. Never reuse numbers.
4. Installments repository: replace_plan(ledger_id, array $rows) (deletes old plan rows and inserts new), plan(ledger_id), schedule(ledger_id): applies non-voided paid total to installments oldest first → each item {due_on, amount, got, state: paid|part|overdue|upcoming}, next_due(ledger_id), due_between(departure_id, from, to) and overdue(departure_id) for the "Installments due" card.
5. Migration (once, inside Schema install, guarded by option pothik_pro_payments_migrated):
   - For every ledger row with paid > 0 and no payments yet: insert ONE payment with is_opening = 1, amount = paid, channel/trx from the row, paid_on = the row's created date or the migration date, note "Opening balance (before payment history)", receipt_no NULL.
   - Verify per departure that SUM(non-voided payments) = SUM(ledger.paid); on mismatch roll back the inserted rows and show an admin notice with the numbers.
   - If step 2.1 or the older optional prompt already wrote payments somewhere, move them here first and skip the opening balance for those rows.
6. Make ledger.paid read-only everywhere in the UI from now on (the old inline "Paid" input on the ledger table becomes a number + link to the drawer). Server: the old pothik_pro_ledger save handler ignores posted paid values for rows that have payments.
```

---

## Prompt 2 — Installments পেইজ আর হিসাবের প্যানেল

```
<<paste RULES>>

TASK: The Installments screen and the account drawer from the mockup. This replaces the body of the current ledger screen; contract editing, adding/removing ledger rows and the USD/SAR notes stay available (move them into the drawer: a small "Contract & notes" section at the top of the Payments tab for users with ledger edit).

1. Header: "Installments" / "Every payment with its date and receipt, each pilgrim's installment plan, and who is due this week." Scoped to the active departure (step 1.1 context bar).
2. Collection card (dark green): donut % collected (paid capped at due ÷ due), Collected, Contract, Balance.
3. "Installments due" card: segmented Overdue {n} / This week {n} / This month {n}; rows with avatar, name, "Installment {i} of {n} · {date}", amount still due for that installment, pill "{d}d overdue" (clay) / "Today" (gold) / "in {d}d". Clicking a row opens the drawer on "+ Add payment" with that amount prefilled. Empty: "Nothing here, alhamdulillah."
4. Accounts table: Pilgrim (avatar, name, "{n} payments") · Contract · Paid · Balance (clay amount or green "✓ Paid", "+৳… extra" when overpaid) · Plan (small bars per installment: green paid, half-green part, clay overdue, grey upcoming; "No plan") · Next installment ("৳35,000 · 18d overdue" in clay or "৳35,000 · 15 Nov 2026") · Last payment (date · channel, receipt number in monospace) · "+ Payment" for rows with balance. Search matches name, receipt number and transaction ID. Row click opens the drawer on Payments.
5. Account drawer (560px): header avatar, name, group; three summary boxes Contract / Paid / Balance; tabs:
   - Payments: vertical timeline newest first — coloured channel tile, amount, "{date} · {channel} · {trx} · by {name}", receipt number, note; opening balance shown with a grey "Opening balance" tag and no receipt; voided entries struck through with a "VOID" tag and "Voided: {reason} · {who} · {when}". Actions per payment: Receipt (open dialog), Void (only for allowed users; asks for the reason in an inline field, not window.prompt). Hint: "Payments are never deleted. A wrong entry is voided with a reason — it stays visible and stops counting."
   - Installment plan: list of installments with number/tick, date, amount, state ("Paid", "৳x paid", "{d}d overdue", "In {d}d"). Gold "Make a plan" box: "Split the balance ৳… into [2/3/4/6] monthly installments from [date]" → Create plan (rounds to the nearest 1,000, last installment takes the remainder; already-paid installments are kept, only the unpaid part is replaced). Warn (not block) when the last date is after the departure's leave date. Also allow editing rows manually (date + amount, add/remove) with the rule "plan total must equal the balance" shown live. Hint: "Payments fill installments from the oldest. Plans are reminders — they never change the contract."
   - + Add payment: amount (৳ grouping) with quick chips "Next installment ৳…" and "Full balance ৳…", date (default today, site timezone), channel tiles, transaction ID (upper-case; warn if the same trx already exists on another payment — shared family payments are allowed), note, hint "Receipt number {next} is given on save." Gold "Save & make receipt". Overpayment allowed with a confirm ("This is ৳x more than the balance").
   Footer: Statement (print) and "+ Add payment".
6. All actions: admin-post handlers (no-JS) + AJAX returning the re-rendered drawer and table row. Nonces and access checks on every handler.
```

---

## Prompt 3 — রসিদ, স্টেটমেন্ট আর হোয়াটসঅ্যাপ

```
<<paste RULES>>

TASK: Receipts and statements.

1. Receipt dialog (after saving a payment and from any payment's Receipt button): green header with agency logo/name, licence and phone, receipt number chip; "Payment received" + big amount + amount in words (English lakh/crore or Bangla, reuse Format::words from the free plugin if present); PAID stamp; rows Pilgrim · Group · Date · Channel (+ trx) · Received by; a green box "Paid so far ৳…" (sum of non-voided payments up to and including this one, by paid_on then id) and "Balance after this ৳… / ✓ Fully paid". Buttons Close / Print / WhatsApp.
   A voided payment's receipt shows a large diagonal "VOID" watermark and the reason; it can still be printed for records.
2. Print: dedicated print view admin.php?page=pothik-receipt-print&payment={id}&_wpnonce=… (no admin chrome, A5/A4-friendly, two copies option "Office copy / Pilgrim copy" on one A4). Log "receipt printed".
3. WhatsApp: wa.me link to the pilgrim's phone (from the pilgrim file or the original inquiry) with a translatable text (filter 'pothik_pro/receipt_whatsapp_text'): "Assalamu alaikum {name}. We received ৳{amount} on {date} by {channel}. Receipt {no}. Paid so far ৳{paid}, balance ৳{balance}. JazakAllahu khairan — {agency}". Never send automatically.
4. Statement: per pilgrim (drawer footer) and per departure (toolbar "Print statement"): header, departure, print date; table of payments (date, receipt, channel, trx, amount, void marked) and totals contract/paid/balance; installment plan with states. Per-departure statement lists every pilgrim with totals and a grand total.
5. Settings → Data (Pro section): receipt prefix (2–6 letters/digits), year style (Hijri / Gregorian), "Next number" read-only preview. Changing the prefix never renumbers old receipts.
```

---

## Prompt 4 — Desk, মনে করানো আর সংযোগ

```
<<paste RULES>>

TASK: Connect payments to the rest of Pothik.

1. Desk card "Installments due" (filter pothik/desk_cards; users with ledger view): overdue count (clay) + this week's list (max 5) across the departures the user may open, each with amount, group, WhatsApp reminder button.
2. WhatsApp reminder text (filter 'pothik_pro/installment_reminder_text'): "Assalamu alaikum {name}. A gentle reminder: installment {i} of ৳{amount} for {group} was due on {date}. Balance ৳{balance}. You can pay by bKash/Nagad to {agency phone}. — {agency}". Opening the link is logged as "reminder sent" (step 1.3).
3. Step 2.1 booking deposit: write it with Payments::add() (gets a receipt number; the success card shows "Receipt PTK-…" with Print/WhatsApp).
4. Visa board tags and departure cards keep reading ledger.paid — verify they show the same numbers.
5. Analytics (Pro panels): collected per week (bars), by channel, overdue amount trend; voided payments count for the period.
6. CSV export of payments for the active departure (date, receipt, pilgrim, channel, trx, amount, voided, reason, received by).
7. Update ROADMAP.md: mark 2.2 as 🧪 and list the files changed.
```

---

## QA — শেষে একবার

```
Check roadmap step 2.2 against docs/payments-mockup.html and ROADMAP.md. Report as a checklist:
- Migration on a copy of real data: one opening-balance payment per row with paid > 0; per-departure totals unchanged; re-running does nothing.
- ledger.paid equals the sum of non-voided payments after every add and void (check in the database).
- Payments can't be edited or deleted (screens, AJAX, forged POSTs); void needs a reason and the right role; voided payments stay visible and stop counting; opening balance voiding restricted.
- Receipt numbers: unique, sequential, no gaps reused after voids; two browsers saving at the same second get different numbers; prefix/year settings work; Hijri year correct.
- Receipt "paid so far / balance after" correct for older receipts too (not just the latest).
- Installment plans: split helper rounding, remainder on last, paid installments kept, manual edit total check, leave-date warning; schedule states correct (paid/part/overdue/upcoming); due card tabs and counts.
- Overpayment confirm; duplicate trx warning; date in the future rejected.
- Print views (receipt single/two copies, statements) clean on A4; VOID watermark; Bangla names and amount in words.
- WhatsApp receipt and reminder texts in English and Bangla; nothing sent automatically.
- Booking deposit from 2.1 gets a receipt; visa board / departures / desk show the same paid totals.
- Access and activity log (if 1.2/1.3 installed).
- No PHP notices, all queries prepared, Plugin Check passes.
```
