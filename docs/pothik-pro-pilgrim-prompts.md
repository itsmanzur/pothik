# Pothik Pro — Pilgrim file পেইজ: প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **আগে Departure ফাইলের Fix 1 আর Fix 2 চালানো থাকতে হবে।** Fix 2-তেই ফ্রি প্লাগিনে Pro-র জন্য ডিজাইনের সেট (`Pothik\Admin\UI`), `pothik/admin_enqueue` আর `pothikAdmin.toast()` তৈরি হয়। Departure-এর Prompt 3 চালানো থাকলে Pro-তে `assets/css/pro.css` আর `assets/js/pro.js`-ও তৈরি থাকবে। এই প্রম্পট সেগুলোতেই নতুন অংশ যোগ করবে।
2. `pothik-pro-pilgrim-mockup.html` ফাইলটা Pro প্লাগিনের ফোল্ডারে `docs/pilgrim-mockup.html` নামে রাখুন। ZIP-এ `docs/` বাদ দেবেন।
3. ক্রম: **Prompt 1 → Prompt 2 → QA**। সবগুলো **Pro প্লাগিনে (pothik-pro)** চালাবেন। ফ্রি প্লাগিনের ফোল্ডারও Cursor-এ খোলা রাখুন, যাতে AI `UI` ক্লাস দেখতে পারে।
4. প্রতিটার পর সাইটে চেক করে git commit দিন।

---

## RULES (দুটো প্রম্পটের শুরুতেই পেস্ট করবেন)

```
RULES — Pothik Pro, Pilgrim file
- Plugin: Pothik Pro (namespace PothikPro; app/Screens.php, app/Desk.php, app/Plugin.php). It renders inside the free Pothik admin shell via Pothik\Admin\Chrome and builds markup with Pothik\Admin\UI helpers. If class_exists('Pothik\Admin\UI') is false, fall back to the current markup so nothing fatals.
- Visual reference: docs/pilgrim-mockup.html. Match the "Pilgrim file" list (data-screen="pilgrims") and the edit screen (data-screen="pilgrim") exactly. Ignore the other screens in that file. Colours are Pothik's own (green #1b4336 family, gold #d4a64a, clay #b4532a, paper #f7f3ec).
- Backend contract is frozen:
  • actions/nonces: pothik_pro_pilgrim, pothik_pro_delete_pilgrim
  • URLs: admin.php?page=pothik-pilgrims (list), &pilgrim={id} (edit), &pilgrim=new (create)
  • field names: id, name, gender, dob, passport, expires, share, family, link, visa, biometric, vaccine, insurance
  • allowed values: gender male|female|''; share quad|triple|double|single|child_bed|child_nobed|infant; visa draft|invoice|paid|issued|refused|embassy; biometric not_started|done|failed
  Desk::save_pilgrim() and Desk::delete_pilgrim() must work unchanged with the new forms — list field names before/after and show they match.
- Bangla: family, link, vaccine, insurance are "prose" fields saved through merge_prose (English root + bn block). Read them with I18n::field() and keep that behaviour. Choice labels come from I18n::choice('share'|'visa'|'biometric'|'gender', …).
- Strings through Pothik\I18n::t() with Bangla translations. Escape everything. No inline styles except dynamic values (ring %, bar widths).
- Assets: add to assets/css/pro.css and a new "pilgrims" module in assets/js/pro.js (same structure as the departure module), enqueued via 'pothik/admin_enqueue' only on Pro screens. Vanilla JS, no external requests.
- Works without JS (plain links and form posts). JS adds filters, grouping toggle, live preview, checklist and the save bar.
- Privacy: the list never prints the full passport number (mask: first 2 + ••• + last 3). Full number only on the edit screen. Never put passport numbers in URLs, data attributes used for search are fine only in the masked form plus the full number lower-cased for matching (do not print it visibly).
- When done: php -l on touched PHP files, list changed files.
```

---

## Prompt 1 — Pilgrim file তালিকা

```
<<paste RULES>>

TASK: Redesign the list part of Screens::pilgrims() (the branch when no ?pilgrim= is given).
Chrome::open('Pilgrim file', 'The office file for this departure. Mahram is a relationship here. It does not block anyone.')

1. Shared helpers in a new final class PothikPro\Readiness (app/Readiness.php) — used by the list, the edit screen and later by other screens:
   - passport_months(): int — apply_filters('pothik_pro/passport_months', 6).
   - passport_ok(array $p, string $leave): bool — true when expires ≥ leave date + passport_months() months (use DateTimeImmutable in wp_timezone()). If leave is empty, compare with today.
   - vaccine_ok(array $p): bool — not empty and not the "Certificate has not reached the office" sentence (compare in both languages; also allow a filter 'pothik_pro/vaccine_missing_phrases').
   - checks(array $p, string $leave): array of [key, label, bool] — passport valid N months · visa issued · biometric done · vaccine on file · insurance noted.
   - percent(array $p, string $leave): int.
   - age_on(string $dob, string $date): ?int.
   The leave date comes from Desk::get()['departure']['leave'].

2. Four stat cards (UI::stat):
   - Pilgrims on file: count, top-right "{m} ♂ · {f} ♀", sub "of {held} confirmed seats" (departure held).
   - Visa issued: "{issued} / {total}", sub "{n} waiting for payment" (visa = invoice).
   - Biometric done: "{done} / {total}", sub "{n} failed — book again" or "No failures".
   - Passport problems (clay): count of !passport_ok, sub "Less than {N} months left on the leave date".

3. Visa progress card: a stacked bar with one segment per status (widths = share of pilgrims; colours draft #c9c0b2, invoice #d4a64a, paid #3f6b8a, issued #2d6a4f, refused #b4532a, embassy #6b4f8a, each with a title tooltip), and below it filter buttons "All {n}", Draft → Invoice → Paid → Issued (with arrows), then Refused and Embassy, each with its count. They are links with ?visa={status} (server-side filter works without JS); JS filters instantly and updates the URL with history.replaceState. Note on the right: "Click a status to filter the list. Issue happens on Masar."

4. Toolbar: search (name, passport, family — client-side; server-side ?s= fallback), segmented "By family / List" (remembered in localStorage inside try/catch), "Print list" ghost button (window.print with a print stylesheet: only the table, all columns, full passport numbers are NOT printed — masked stays masked), primary "New pilgrim" → &pilgrim=new.

5. Table (UI table styles from the free plugin):
   - Columns: Pilgrim · Passport · Share · Visa · Biometric · Vaccine (hidden under 700px) · Ready.
   - "By family" mode: a header row per family (I18n::field 'family'; empty → "No family") with a small gold icon, "{n} people" and the first two relation lines; families sorted by name, people in saved order. "List" mode: plain rows.
   - Pilgrim cell: initials avatar (blue for male, rose for female, grey if empty), name, small line "♂/♀ {age on leave date} yrs · {relation}".
   - Passport cell: masked number in monospace, under it the expiry as a small chip — green "✓ 11 Apr 2030" when passport_ok, clay "⚠ 30 Jul 2027" when not (title attribute explains the rule).
   - Share: plain pill (I18n::choice). Visa: coloured pill per status (classes v-draft … v-embassy). Biometric: "✓ Done" green, "✕ Failed" clay, "– Not started" grey.
   - Vaccine: first two comma parts of the text, or clay "Missing" when !vaccine_ok.
   - Ready: small conic ring with the percent; full green ring at 100%.
   - The whole row links to the edit screen (the name is a real <a>, the row gets a JS click handler and cursor:pointer; keyboard: Enter on the focused row).
   - Empty states: no pilgrims → "No pilgrims yet" + "Add the first pilgrim" button; no matches → "No pilgrim matches".
6. Footer note: "Mahram is a relationship here. It does not block anyone. Passport numbers stay on this desk."
7. After delete (redirect with updated=1 to the list) show toast "Pilgrim deleted".
```

---

## Prompt 2 — Pilgrim এডিট / নতুন পেইজ

```
<<paste RULES>>

TASK: Redesign the edit/create branch of Screens::pilgrims() (?pilgrim={id} or ?pilgrim=new).
Chrome::open($creating ? 'New pilgrim' : $name, 'Passport number stays on this desk. The photo file comes in a later version.'). Under the header a "← All pilgrims" link (left-aligned). Layout: form (fluid) + right sticky column 340px (stacks under 1200px).

FORM (one <form>, action pothik_pro_pilgrim, hidden id, nonce — unchanged):
1. Identity card: Name (full row, hint "as in passport"); Gender as segmented Male / Female bound to `gender` (no-JS: a visible <select> with an empty option, hidden by JS); Birth (type=date, name dob) with hint "{age} yrs on leave date" updated live.
2. Passport card (sub "Saudi entry needs at least {N} months left on the leave date."): Passport number (monospace, uppercase while typing, spaces removed, maxlength 20); Expiry (type=date, name expires) with a live hint: green "✓ valid on leave date" or clay "⚠ needs {N} months after {leave date}". Uses the same rule as Readiness::passport_ok (pass leave date + months to JS via wp_localize_script).
3. Room share & family card:
   - Share as chip buttons (Quad, Triple, Double, Single, Child with bed, Child without bed, Infant) bound to `share` (no-JS select fallback).
   - Family (hint "group people who travel together") with a <datalist> of existing family names; Relation (name link, hint "mahram link", placeholder "e.g. Farhana's husband").
   - Gold note under them: if other pilgrims share the same family → "Travels with {name} ({relation}), … Mahram is a relationship here — it does not block anyone." else just the second sentence. Rendered in PHP, updated live in JS.
4. Visa & health card (sub "Staff update the status here. Issue happens on Masar."):
   - Visa as arrow-shaped step buttons Draft → Invoice → Paid → Issued (earlier steps tinted green, current solid green) plus two separate dashed buttons Refused (clay when active) and Embassy (purple when active), bound to `visa` (no-JS select fallback).
   - Biometric as segmented Not started / Done / Failed bound to `biometric`.
   - Vaccine and Insurance text fields, each with small "quick fill" chips under it (Vaccine: "Meningitis, conjugate", "Meningitis, polysaccharide", "Certificate has not reached the office"; Insurance: "With the visa", "Separate policy") — translated, filterable via 'pothik_pro/vaccine_quick' and 'pothik_pro/insurance_quick'. Clicking a chip fills the field.
5. Danger zone card (only for existing pilgrims) OUTSIDE the main form: clay card "Delete this pilgrim — Removes them from rooming lists and unlinks ledger rows." with the existing pothik_pro_delete_pilgrim form; confirm inline ("Delete {name}? Yes / Cancel") instead of window.confirm. (Desk::normalize()/link_file() already drops deleted ids from rooms and unlinks ledger rows on read — do not change that.)
6. Shared save bar: "Unsaved pilgrim", Discard (reload), gold "Save pilgrim" + Ctrl/Cmd+S; <noscript> submit button. Name is required: block the JS save with a toast "Name is required" and focus the field (server already redirects back when empty — keep that). After save (updated=1) toast "Pilgrim saved".

RIGHT COLUMN:
7. Pilgrim card styled like a passport data page (light green→sand gradient, fine diagonal guilloche lines, green top strip "Pilgrim file" + "#{id}" or "New"): photo box with the first letter (blue for male, rose for female), Name, Sex (M/F), Born, Passport, Expires, "Share · Family". Live updates while typing. No MRZ line (names are often in Bangla).
8. "Ready to travel" card: ring with percent + the five checks from Readiness::checks() (green tick / clay "!"), and the hint "Photo file comes in a later version." Live updates.

Responsive: under 1200px the right column moves above the form as two cards side by side; under 700px everything is one column and segmented controls stay full width.
```

---

## QA — শেষে একবার

```
Check the Pilgrim file list and edit screens against docs/pilgrim-mockup.html and fix what you find. Report as a checklist:
- Create, edit and delete a pilgrim with JS on and off; the stored pothik_pro_desk option has exactly the same keys/values as with the old screens (save an unchanged pilgrim → option identical).
- Bangla mode: family/link/vaccine/insurance save into the bn block and show correctly; choice labels are Bangla; no overflow at 1280px and 390px.
- Passport rule: Readiness::passport_ok uses leave date + N months (filter works); stat card, list chip, edit hint and checklist all agree.
- Full passport numbers never appear on the list, in print, in URLs or in JS-visible text on the list page.
- Visa filter works via ?visa= without JS and instantly with JS; counts and bar widths are right; By family / List remembered.
- Deleting a pilgrim removes them from Rooming and unlinks their Installment rows (check those screens).
- Name required: empty name never creates a record.
- Sidebar badge / counts match the number of pilgrims.
- No PHP notices, no external requests, Plugin Check passes.
```
