# Pothik Pro — Visa board পেইজ: প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **আগে যা চালানো থাকতে হবে:**
   - Departure ফাইলের Fix 1 আর Fix 2 (ফ্রি প্লাগিনে `Pothik\Admin\UI`, `pothik/admin_enqueue`, `pothikAdmin.toast()`)।
   - Pilgrim file ফাইলের Prompt 1 (`PothikPro\Readiness`: পাসপোর্টের মেয়াদ, বায়োমেট্রিক)।
   - Installments ফাইলের Prompt 1 (`PothikPro\Ledger`: কার কত বাকি)। এটা না থাকলেও চলবে, তখন প্রম্পট নিজে ছোট একটা হিসাব করে নেবে।
2. `pothik-pro-visa-mockup.html` ফাইলটা Pro প্লাগিনের ফোল্ডারে `docs/visa-mockup.html` নামে রাখুন। ZIP-এ `docs/` বাদ দেবেন।
3. ক্রম: **Prompt 1 → Prompt 2 → QA**। সবগুলো **Pro প্লাগিনে (pothik-pro)** চালাবেন, ফ্রি প্লাগিনের ফোল্ডারও Cursor-এ খোলা রাখবেন।
4. প্রতিটার পর সাইটে চেক করে git commit দিন।

---

## RULES (প্রতিটা প্রম্পটের শুরুতে পেস্ট করবেন)

```
RULES — Pothik Pro, Visa board
- Plugin: Pothik Pro (namespace PothikPro; app/Screens.php → visa(), warn(); app/Desk.php → save_visa(), season_notes(), get()). Renders inside the free Pothik shell via Pothik\Admin\Chrome; markup with Pothik\Admin\UI helpers; if class_exists('Pothik\Admin\UI') is false, keep the current markup.
- Visual reference: docs/visa-mockup.html — match the "Visa board" screen (data-screen="visa") exactly. Ignore the other screens in that file. Colours are Pothik's own (green #1b4336 family, gold #d4a64a, clay #b4532a, paper #f7f3ec). Status colours: draft #c9c0b2, invoice #d4a64a, paid #3f6b8a, issued #2d6a4f, refused #b4532a, embassy #6b4f8a.
- Data is frozen: the board only changes each pilgrim's 'visa' value in pothik_pro_desk['pilgrims']; allowed values draft|invoice|paid|issued|refused|embassy. Nothing new is stored.
- Form contract is frozen: action + nonce pothik_pro_visa; one field per pilgrim visa[{pilgrim id}] = status. Desk::save_visa() must work unchanged (it ignores unknown ids and invalid values — keep that).
- Read-only context shown on cards comes from existing data: passport validity and biometric (PothikPro\Readiness), ledger balance (PothikPro\Ledger::… if present, otherwise sum due − paid of the ledger row whose pilgrim_id matches; "No ledger row" when none).
- Privacy: passport numbers are masked on the board (first 2 + ••• + last 3) — on screen and in print.
- Strings via Pothik\I18n::t() with Bangla translations; status labels via I18n::choice('visa', …). Escape everything; no inline styles except dynamic widths.
- Assets: a "visa" module in assets/js/pro.js + styles in assets/css/pro.css (same structure as the other Pro modules), enqueued via 'pothik/admin_enqueue' on Pro screens only. Vanilla JS, native HTML5 drag-and-drop, no libraries, no external requests.
- Works without JS: each card shows a small status <select name="visa[{id}]">; the board still groups cards into columns by their saved status; a normal "Save status" submit. JS hides the selects and keeps them in sync while cards move, so the POST is identical.
- When done: php -l on touched PHP files, list changed files.
```

---

## Prompt 1 — Visa board-এর ডিজাইন (কলাম, কার্ড, টেনে সরানো)

```
<<paste RULES>>

TASK: Redesign Screens::visa() as the kanban board from docs/visa-mockup.html.
Chrome::open('Visa board', 'Staff change the status here. The visa is issued on the Masar portal.'); Chrome::saved() → toast "Status saved".

1. Warnings row (top): keep the existing self::warn(departure) notes (Desk::season_notes) styled as clay notice rows. Add one board-specific note: when at least one pilgrim is not "issued" AND the last visa date (departure visa_last) is within N days (N = apply_filters('pothik_pro/visa_warn_days', 60)) → "{n} visas are not issued yet and the last visa date is {d} days away." If visa_last has passed and some are not issued → "The last visa date {date} has passed. {n} visas are not issued."
2. Top row (stack under 1200px):
   a) "Visa progress" card: big "{issued} of {total} visas issued", pill "{n} need attention" (refused + embassy; clay when > 0, green "All clear" when 0), a stacked bar with one segment per status (title tooltips) and a legend with counts.
   b) Dark green "Last visa date" card: big gold number of days left (or "Closed"), "days left · {visa_last}", a thin gold bar showing how much of the window from visa_open to visa_last has passed, and the two end labels "Visa opens {date}" / "Leave {leave date}". Hide the card when visa_last is empty.
3. Gold note: "Staff change the status here. The visa is issued on the Masar portal — this board does not submit anything."
4. Toolbar: search (name, passport, family — client-side; matching on the full passport is fine but only the masked one is shown), helper text "Drag a card, use → to move it one step, or tick cards to move many.", "Print board" ghost button.
5. Board: four main columns Draft → Invoice → Paid → Issued with one-line meanings ("File being prepared", "Saudi invoice received", "Invoice paid, waiting", "Visa on Masar"; translatable, filter 'pothik_pro/visa_column_hints'), plus a fifth column stacking two side lanes in a warm tint: Refused ("Talk to the family") and Embassy ("Sent to the embassy"). Each column header: colour square, label, hint, count badge. Empty column → dashed "Drop here".
   Layout: 5 equal columns (minmax(0,1fr)) on wide screens; under 1200px fixed 210px columns with horizontal scroll; under 700px 82vw columns with scroll-snap.
6. Card: initials avatar (blue male / rose female), name (ellipsis + title), select checkbox; masked passport + family; three small tags — passport ("Passport ✓" green / "Passport < {N} months" clay, using Readiness::passport_ok with the leave date), biometric ("Biometric ✓" / "Biometric failed" clay / "Biometric –"), money ("Due ৳1,05,000" gold / "Paid in full" green / "No ledger row" gold). Footer: "← {previous step}" (refused/embassy go back to Draft) and "{next step} →" (Issued shows a green "✓ Ready" instead). The name links to that pilgrim's file (?page=pothik-pilgrims&pilgrim={id}).
7. Moving (JS): drag-and-drop between columns (column highlights green on dragover); the → / ← buttons; keyboard ArrowRight / ArrowLeft on a focused card. Every move updates the hidden select, the counts, the progress bar and the attention pill, shows a toast "{name} → {status}" with Undo, and an aria-live announcement. Moving a card does not reorder pilgrims in the saved file.
8. Save bar: shows only when something differs from the saved state, with the count — "{n} status change(s) not saved"; Discard resets every card to its saved status; gold "Save status" + Ctrl/Cmd+S; <noscript> submit button. Leaving the page with unsaved changes asks for confirmation.
```

---

## Prompt 2 — একসাথে অনেকজন সরানো, প্রিন্ট আর ছোট উন্নতি

```
<<paste RULES>>

TASK: Add bulk moves, print and small helpers to the Visa board.

1. Bulk: ticking card checkboxes shows a dark sticky bar "{n} selected · Move to [Draft] [Invoice] [Paid] [Issued] [Refused] [Embassy] · Clear". One click moves all selected cards (one toast with Undo for the whole batch: "{n} pilgrims → Paid"). Shift-click selects a range inside a column. "Select family" in a card's overflow menu ticks everyone with the same family name — families usually move together.
2. Gentle checks, never blocking:
   - Moving to Paid while the ledger shows a balance > 0 → the toast adds "— ledger still shows ৳{balance} due".
   - Moving to Issued while the passport rule fails or biometric is not done → the toast adds "— check passport/biometric".
3. Print board: print stylesheet with agency name, departure (leave date, flight, Program ID from the Departure screen), print date, then one section per status (Issued first, then Paid, Invoice, Draft, Refused, Embassy) listing name, masked passport, family and the three tags as plain text. Hides checkboxes, buttons, bulk bar, notes and the save bar. A4, break-inside: avoid per section.
4. Desk hook: add a small filter 'pothik_pro/visa_summary' that returns ['issued' => n, 'total' => n, 'attention' => n, 'days_left' => n|null] so the free plugin's Desk can show a Pro stat card later. Compute it in one helper (PothikPro\Readiness::visa_summary()) and reuse it for the progress card.
5. Empty state: no pilgrims on file → card "Add pilgrims to the pilgrim file first" + button to pothik-pilgrims&pilgrim=new.
```

---

## QA — শেষে একবার

```
Check the Visa board against docs/visa-mockup.html and fix what you find. Report as a checklist:
- Save with no changes → pothik_pro_desk is identical to before. Changing one card changes only that pilgrim's 'visa' value.
- Works without JS: the per-card selects post visa[{id}] and save correctly.
- With JS: drag-and-drop, → / ← buttons, keyboard arrows, bulk move, Shift range, Select family, Undo (single and batch), Discard, unsaved-changes warning.
- Counts, stacked bar, attention pill and save-bar count stay correct after every move.
- Card tags agree with the Pilgrim file (passport rule, biometric) and the Installment ledger (balance / Paid in full / No ledger row).
- Warnings: season notes + the "not issued and last visa date is near/passed" note with the visa_warn_days filter.
- Passport numbers are masked on screen and in print.
- Status changes here show up on the Pilgrim file list and edit screen (same 'visa' field).
- Bangla mode at 1280px and 390px: no overflow; column hints and statuses translated; horizontal scroll on small screens works with touch.
- No PHP notices, no external requests, Plugin Check passes.
```
