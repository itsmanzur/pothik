# Pothik Pro — Installment ledger পেইজ: প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **আগে যা চালানো থাকতে হবে:** Departure ফাইলের Fix 1 আর Fix 2 (ফ্রি প্লাগিনে `Pothik\Admin\UI`, `pothik/admin_enqueue`, `pothikAdmin.toast()`)।
2. `pothik-pro-installments-mockup.html` ফাইলটা Pro প্লাগিনের ফোল্ডারে `docs/installments-mockup.html` নামে রাখুন। ZIP-এ `docs/` বাদ দেবেন।
3. ক্রম: **Prompt 1 → Prompt 2 → QA**। **Prompt 3 ঐচ্ছিক।** প্রতিটা কিস্তি তারিখসহ আলাদা রাখতে চাইলে সেটা দেবেন, আর দিলে QA-র আগে দেবেন।
4. সবগুলো **Pro প্লাগিনে (pothik-pro)** চালাবেন। ফ্রি প্লাগিনের ফোল্ডারও Cursor-এ খোলা রাখবেন।
5. প্রতিটার পর সাইটে চেক করে git commit দিন।

---

## RULES (প্রতিটা প্রম্পটের শুরুতে পেস্ট করবেন)

```
RULES — Pothik Pro, Installment ledger
- Plugin: Pothik Pro (namespace PothikPro; app/Screens.php → ledger(), app/Desk.php → save_ledger(), link_file(), merge_prose()). Renders inside the free Pothik shell via Pothik\Admin\Chrome; markup with Pothik\Admin\UI helpers; if class_exists('Pothik\Admin\UI') is false, keep the current markup.
- Visual reference: docs/installments-mockup.html — match the "Installment ledger" screen (data-screen="ledger") exactly. Ignore the other screens in that file. Colours are Pothik's own (green #1b4336 family, gold #d4a64a, clay #b4532a, paper #f7f3ec). Channel colours: bKash #e2136e, Nagad #f6921e, Rocket #8c3494, Bank #2563eb, Cash #40916c — coloured letter tiles only, never the real brand logos.
- Data shape is frozen (Prompt 3 may ADD a key, nothing else): pothik_pro_desk['ledger'] = list of rows ['id','pilgrim_id','name','due','paid','channel','trx','note','usd_note','sar_note','bn' => ['note','usd_note','sar_note']]. note/usd_note/sar_note are prose fields (merge_prose: English root + bn block). link_file() keeps pilgrim_id/name in sync with the pilgrim file — do not change it.
- Form contract is frozen: action + nonce pothik_pro_ledger; fields ledger[{index}][id|name|pilgrim_id|due|paid|channel|trx|note|usd_note|sar_note|remove]. A NEW row posts an EMPTY id (the server generates it). due/paid are plain integers — strip the comma grouping before submit. channel ∈ bkash|nagad|rocket|bank|cash|''. Server limit stays 200 rows.
- Money: Format::money() in PHP (৳ with South-Asian grouping); the same grouping in JS. USD and SAR are NOTES only — never convert or add them up.
- Strings via Pothik\I18n::t() with Bangla translations; channel labels via I18n::choice('channel', …). Escape everything; no inline styles except dynamic widths and the donut percentage variable.
- Assets: a "ledger" module in assets/js/pro.js + styles in assets/css/pro.css (same structure as the other Pro modules), enqueued via 'pothik/admin_enqueue' on Pro screens only. Vanilla JS, no libraries, no external requests.
- Works without JS: the server renders every row with real inputs (selects/number inputs/radio tiles) and a "Remove this row" checkbox, plus one blank "New row". JS enhances (grouping, live totals, popover, filters, save bar) but the POST stays identical.
- When done: php -l on touched PHP files, list changed files.
```

---

## Prompt 1 — লেজার পেইজের ডিজাইন

```
<<paste RULES>>

TASK: Redesign Screens::ledger() as the spreadsheet-style ledger from docs/installments-mockup.html.
Chrome::open('Installment ledger', "This ledger is the pilgrim's BDT. The ticket dollar and the Saudi riyal stay as notes. This version does not recalculate them."); Chrome::saved() → toast "Ledger saved".

1. Helper class PothikPro\Ledger (app/Ledger.php):
   - totals(array $rows): ['due','paid','balance' (max 0, due − paid), 'percent' (paid capped at due ÷ due, rounded; 0 when due = 0)].
   - state(array $row): 'full' (due > 0 and paid ≥ due) | 'part' (paid > 0) | 'none'.
   - by_channel(array $rows): [channel => paid sum] for rows with paid > 0 and a channel.
   - shared_trx(array $rows): [TRX (upper-cased, trimmed) => list of names] where the same transaction ID appears on 2+ rows.

2. Top row (stack under 1200px):
   a) Dark green "collection" card: conic donut with "{percent}% collected", Collected (big), Contract and Balance (balance in light clay).
   b) Right: three filter cards — Paid in full (green number), Partly paid (gold), Nothing paid (clay) — each a toggle filter (links with ?state=full|part|none for no-JS; JS filters instantly; clicking again clears). Under them a "By channel" card: stacked bar by paid amount with legend "bKash ৳80,000 · Bank ৳1,45,000" ("No payments yet" when empty).
3. Gold note bar: "This ledger is the pilgrim's BDT. The ticket dollar and the Saudi riyal stay as notes — open a row (▸) to see them."
4. Toolbar: search (pilgrim name or transaction ID), sort select (File order · Highest balance · Name — display order only; the saved order is never changed), "Print statement" ghost button.
5. Table (card, horizontal scroll under 1100px), one row per ledger row:
   - ▸ expand button (aria-expanded) that opens a detail row with Note, "Ticket, USD note" and "Ground, SAR note" inputs (hint "not recalculated").
   - Pilgrim: initials avatar (blue male / rose female from the pilgrim file) + a borderless select of pilgrims from the file (ledger[i][pilgrim_id]); keep the hidden ledger[i][name]. If the row's pilgrim_id is 0 (old free-text row), show the stored name as an extra selected option so nothing is lost (same logic as today's pilgrim_select()).
   - Contract and Paid: ৳-prefixed inputs, right-aligned, live grouping (1,45,000).
   - Progress: thin bar + "{n}%".
   - Balance: clay "৳1,05,000" when due, green "✓ Paid" when 0, "+৳5,000 extra" when paid > due.
   - Channel · Transaction ID (one cell): five small coloured letter tiles (radio buttons named ledger[i][channel]; clicking the active one clears it — no-JS: a hidden "none" radio) and under them the transaction ID input (monospace, upper-cased while typing). If the ID is shared with other rows, a small blue chip "🔗 Shared · {n} pilgrims" with the names in its title.
   - Actions: "+ Payment" (only when balance > 0) and a trash icon. Trash marks the row for removal (sets the hidden remove=1, row fades with strike-through, button becomes "Undo") — the row is dropped on save.
   - Footer row: "Total · {n} rows", total contract, total paid, total balance (clay).
6. "Add to ledger" row under the table: a select of pilgrims from the file who don't have a row yet ("Everyone in the file has a row" when none) + primary button → appends a new row (empty id, due 0) and focuses its Contract input. Helper text "Pick a pilgrim from the file. Each pilgrim gets one row." (One row per pilgrim is a UI rule; the server keeps accepting what it accepts today.)
7. Empty states: no pilgrims on file → "Add pilgrims to the pilgrim file first" + button; no rows yet → friendly card with the Add row; no filter match → "No row matches".
8. Every edit updates the donut, the three counts, the channel bar, the shared-ID chips and the footer totals live; the shared save bar ("Unsaved ledger", Discard = reload, gold "Save ledger" + Ctrl/Cmd+S; <noscript> submit button).
```

---

## Prompt 2 — পেমেন্ট যোগ করার বক্স আর প্রিন্ট

```
<<paste RULES>>

TASK: Add the payment popover and the printable statement from docs/installments-mockup.html.

1. "+ Payment" popover (anchored under the button, closes on Esc / outside click, focus trapped, returns focus):
   - Title "Add a payment · {name}".
   - Amount input with ৳ prefix and grouping; quick chips "Full balance ৳{balance}", "10,000", "25,000" (quick amounts filterable via 'pothik_pro/ledger_quick_amounts').
   - Channel tiles (preselected with the row's channel) and a Transaction ID input.
   - Buttons Cancel / "Add to paid". Helper: "Adds to the paid total. The channel and ID replace the row's last payment."
   - On confirm: paid += amount; channel and (if filled) trx overwrite the row's values; the row re-renders; toast "৳{amount} added for {name}" or "… — paid in full 🎉" when balance reaches 0. Amount 0 or empty → keep the popover open and focus the amount.
   - The popover only changes form inputs in the page; nothing is saved until the user saves (save bar appears).
2. "Print statement": print stylesheet showing agency name + logo, departure (leave–return, flight from the Departure screen), print date, then a clean table: Pilgrim · Contract · Paid · Balance · Channel · Transaction ID, plus totals; notes are printed under each row in small text. Hides the toolbar, filters, buttons, tiles (print the channel as text) and the save bar. A4, break-inside: avoid per row.
3. Per-row "Print" in the ⋯/detail area: prints a single pilgrim's statement (same layout, one row) — useful to hand to the family.
4. Keyboard: Enter in the Paid input of a row moves focus to the next row's Paid input; "/" focuses search.
```

---

## Prompt 3 (ঐচ্ছিক) — প্রতিটা কিস্তির আলাদা ইতিহাস

> এখনকার ডেটায় প্রতিটা হাজির শুধু মোট পরিশোধ আর শেষ পেমেন্টের মাধ্যম ও আইডি থাকে। প্রতিটা কিস্তি তারিখসহ আলাদা দেখতে চাইলে এই প্রম্পট দিন। এতে ডেটায় নতুন একটা অংশ (`payments`) যোগ হবে, কিন্তু আগের ঘরগুলো একই থাকবে।

```
<<paste RULES>>

TASK: Add an optional per-row payment history without breaking the frozen fields.

1. Data: each ledger row may get 'payments' => list of ['id','date' (Y-m-d),'amount' (int),'channel','trx','note'] (max 50 per row). Rules:
   - 'paid' stays the source of truth that every screen reads; when a row has payments, save_ledger() sets paid = sum(payments.amount) and channel/trx = the latest payment's values.
   - Rows without payments behave exactly as today.
   - Migration: none needed; the first time a payment is added to an old row that already has paid > 0, create an "Opening balance" payment with that amount, today's date and the row's channel/trx, so the sum stays correct.
2. Form: ledger[i][payments][j][id|date|amount|channel|trx|note] (+ [remove]). Server sanitises like the other fields (absint, iso date, enum channel, clip 40/160) and generates ids for new payments.
3. UI: the "+ Payment" popover gets a Date field (default today) and appends a payment instead of only adding to paid. The ▸ detail row shows a small timeline of payments (date · amount · channel tile · trx · note · remove) above the three notes, newest first, with a running total. Paid becomes read-only (computed) for rows that have payments, with a hint "Sum of payments".
4. Shared transaction detection also looks inside payments.
5. Print statement lists each pilgrim's payments under their row.
6. php -l; list changed files; show a before/after example of one row in the option.
```

---

## QA — শেষে একবার

```
Check the Installment ledger against docs/installments-mockup.html and fix what you find. Report as a checklist:
- Save with no changes → pothik_pro_desk['ledger'] is identical to before (ids, order, names, numbers, bn notes). With Prompt 3: rows without payments are still identical.
- Works without JS: edit amounts, choose channel, remove a row, add the blank row, save — same result as the old screen.
- Money inputs post plain integers (no commas); paid > due shows "+extra"; totals, percent and donut are right; USD/SAR are never added up.
- Add-payment popover: quick chips, channel/trx overwrite, toast, Esc/outside close, focus return; nothing saved until Save.
- Shared transaction chip appears for the same ID on 2+ rows (case/space-insensitive).
- One row per pilgrim in the UI; old free-text rows (pilgrim_id 0) keep their names.
- Filters (full/part/none), search, sort (display only) work; saved order unchanged.
- Deleting a pilgrim in the Pilgrim file unlinks their row here (pilgrim_id 0, name kept) — as link_file() does today.
- Print statement (all + single pilgrim) is clean on A4, Bangla names render.
- Bangla mode at 1280px and 390px: no overflow; channel labels translated.
- No PHP notices, no external requests, Plugin Check passes.
```
