# ধাপ ৪.১ — হাজির আইডি কার্ড ও ব্যাজ: প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **আগে শেষ থাকতে হবে:**
   - ধাপ ১.১ (ডিপার্চার) আর হাজির ফাইল।
   - ৩.৩ (হাজির পাতা): QR কোড এই পাতার লিংক থেকেই তৈরি হয়। ৩.৩ না থাকলে কার্ডে QR থাকবে না।
   - এগুলো থাকলে কার্ডে আরও তথ্য আসবে, না থাকলে সেই অংশটা ফাঁকা থাকবে:
     - ৩.১ (কাগজপত্র): হাজির ছবি।
     - রুমিং: রুম নম্বর।
     - ১.২ (টিম): স্টাফ ব্যাজ।
2. `pothik-pro-idcards-mockup.html` ফাইলটা Pro প্লাগিনের ফোল্ডারে `docs/idcards-mockup.html` নামে রাখুন।
3. ক্রম: **Prompt 1 → Prompt 2 → Prompt 3 → QA**। সবগুলো প্রম্পট **Pro প্লাগিনে (pothik-pro)** চালাবেন।
4. **Prompt 2-এর পর একটা পরীক্ষা অবশ্যই করবেন:** একটা পাতা ছাপিয়ে স্কেল দিয়ে মাপবেন। পাতার কোণে একটা ৫০ মিমি মাপার বর্গ থাকবে, সেটা ঠিক ৫০ মিমি হতে হবে, আর কার্ড হতে হবে ৮৫.৬ × ৫৪ মিমি। মাপ ছোট বা বড় এলে প্রিন্টারের "Fit to page" বন্ধ করে "Actual size / 100%" দিয়ে আবার ছাপাবেন।
5. দুই পাশে ছাপানোর জন্য প্রিন্টারে "Flip on long edge" বাছবেন। তারপর আলোর সামনে ধরে দেখবেন সামনে আর পেছন ঠিক মিলেছে কি না।
6. **একটা নিরাপত্তার কথা:** কার্ডের QR স্ক্যান করলে হাজির নিজের পাতা খোলে। তাই কার্ড হারিয়ে গেলে যে পাবে, সেও পাতাটা দেখতে পারবে। এজন্য ৩.৩-এর "Ask for a PIN" চালু রাখা ভালো। Prompt 3-এ এ নিয়ে একটা সতর্কবার্তা রাখা আছে।
7. প্রতিটা প্রম্পটের পর git commit দিন। সব শেষ হলে `ROADMAP.md`-তে ৪.১-এর পাশে ✅ দিন।

---

## RULES (প্রতিটা প্রম্পটের শুরুতে পেস্ট করবেন)

```
RULES — Pothik Pro, ID cards and badges (roadmap step 4.1)
- Read ROADMAP.md (section 4.1) first.
- Visual reference: docs/idcards-mockup.html — the ID cards screen (data-screen="idc"): left settings (Who, Size, On the card switches, Group colour, Check before printing), top bar (A4 sheet / One card large, "{n} cards · {p} pages × 2 sides", Front/Back, PDF, Print), the A4 sheet with 8 cards and cut marks, the neck badge layout (4 per A4), and the card design itself (.idk front and .idk.back). Colours are Pothik's own; the group colour is a per-departure choice.
- QR codes are made inside the plugin. No external QR APIs, no Google Charts, no CDN scripts. Bundle a small MIT-licensed library (see Prompt 1). Output SVG.
- Print accuracy matters: all card sizes and positions in mm in the print CSS; @page A4 with no browser scaling assumptions; a 50 mm calibration square on each sheet.
- Photos come from step 3.1's private storage; they are embedded into the print page as resized data URIs by PHP (admin only, never a public URL), and never cached to disk outside the private folder.
- Never print: passport number, NID, phone of the pilgrim, payment data, health data.
- Access (step 1.2): area "pilgrims" — view to preview, edit to print and to mark as printed. Group limits apply. Without 1.2: manage_options.
- Activity (1.3): cards printed (who, which pilgrims, size, sides), marked as printed, group colour changed.
- $wpdb->prepare everywhere. Strings via Pothik\I18n::t() with Bangla translations. Escape everything. Vanilla JS. UI via Pothik\Admin\UI. The print page works without JS.
- When done: php -l on touched PHP files, list changed files.
```

---

## Prompt 1 — QR লাইব্রেরি, ডেটা আর কার্ডের তথ্য

```
<<paste RULES>>

TASK: QR generation, data and the card data builder. No screen yet.

1. QR library:
   - Use chillerlan/php-qrcode (MIT; current stable major version that supports PHP 8.1) via Composer, with Strauss (or PHP-Scoper) to prefix its namespaces to PothikPro\Vendor\ so it never clashes with other plugins that ship the same library. Commit the prefixed vendor folder (vendor-prefixed/) — the plugin must not need Composer on the server.
   - If adding Composer is not acceptable in this repo, use instead the single-file PHP port of Kazuhiko Arase's "qrcode-generator" (MIT) placed in includes/vendor/qrcode/, wrapped in a class_exists guard.
   - Keep the license file next to the library and mention it in readme.txt.
   - Wrapper PothikPro\Print\Qr::svg(string $text, int $module_px = 0): string — error correction M, quiet zone 2 modules, black on white, shape-rendering crispEdges, returns a sanitised inline <svg> with viewBox (no width/height so CSS sizes it).
   - Unit test (or a WP-CLI command `wp pothik qr-test`) that encodes a sample URL and decodes nothing externally — just checks the SVG has the three finder patterns and the expected module count.

2. Data:
   - Departure meta: pothik_card_colour (one of green #1b4336, maroon #7a2e3b, blue #24476b, gold #8c6a2a; default green).
   - Pilgrim: pilgrim_no (if not already present from earlier steps: format PTK-{season}-{2-digit sequence within the departure}, assigned once, never reused), card_printed_at DATETIME NULL, card_printed_by.
   - Staff badge data from step 1.2 team members assigned to the departure (role leader / guide / staff) or from the departure's leader and guide fields.

3. PothikPro\Print\CardData::for_pilgrim($pilgrim, $options): array with only what the card needs:
   - name_en (uppercase as in the passport if present, else the file name), name_bn (if present), pilgrim_no, photo (data URI from step 3.1 "Photo (white background)" if checked or uploaded, resized server-side to 300×360 px JPEG q80, EXIF stripped; else null → initials), gender (only for the photo placeholder colour), group name, season label ("UMRAH 1448 · 2027"), hotels Makkah/Madinah (names from the departure), room numbers only when rooming is final, guide name + phone, office name + phone, leader phone, QR (Qr::svg of the 3.3 link URL — create a link if the pilgrim has none and 3.3 is active; otherwise null).
   - The data for the back: Arabic + English + Bangla "if lost, please call" texts (below), leader and guide phones in international format (+966 …), hotel names, the emergency line "Emergency in Saudi Arabia · 911".
     Arabic: "إذا ضاع صاحب هذه البطاقة\nيرجى الاتصال على الأرقام التالية"
     English: "If the holder of this card is lost, please call:"
     Bangla: "এই কার্ডের মালিক পথ হারালে নিচের নম্বরে ফোন করুন"
     Labels: "Group leader · قائد المجموعة", "Guide · المرشد", "Makkah hotel · فندق مكة", "Madinah hotel · فندق المدينة".
     Ask the agency to have an Arabic speaker check these texts once; make them editable in Settings → Cards.
   - Warnings: CardData::warnings($pilgrims): count without photo, without room (when hotels are shown), without QR, names longer than 26 characters (will be shrunk).

4. Fonts for print, bundled locally in assets/fonts (OFL): Source Sans 3 (400/700/800), Hind Siliguri (500/600) for Bangla, Amiri (700) for Arabic. Only woff2. Licences in assets/fonts/OFL.txt.
```

---

## Prompt 2 — প্রিন্ট পাতা (A4, মিলিমিটার মাপে)

```
<<paste RULES>>

TASK: The print page that produces the A4 sheets. This is what goes to the printer.

1. Endpoint: admin-post pothik_pro_cards_print (GET with nonce) params: departure, who=all|new|ids|staff, ids[], size=card|badge, sides=front|both, opts (photo,bn,hotel,qr,back,cut). Own template templates/print/cards.php — no admin chrome, no admin bar, local fonts, a toolbar at the top (hidden in print) with "Print" and "Back".
2. Page CSS:
   @page { size: A4 portrait; margin: 0 } ; each sheet is a .sheet of 210mm × 297mm, page-break-after: always; print-color-adjust: exact.
   Card size: 85.6mm × 54mm (CR80). Grid 2 × 4, centred: left = (210 − 2×85.6)/2 = 19.4mm, top = (297 − 4×54)/2 = 40.5mm, no gap (cut lines are shared).
   Neck badge: 95mm × 136mm, grid 2 × 2: left = 10mm, top = 12.5mm.
   Cut marks: thin 0.2mm lines, 4mm long, 1.5mm outside each card corner (only outside the card area), toggle by option.
   Calibration: a 50mm × 50mm outlined square with the label "This square must measure 50 mm" in the bottom margin of every sheet, and a small header line "March group 1448 · ID cards · front · Print at 100% (Actual size)" in the top margin.
3. Card front (match the mockup .idk): top band in the group colour with the agency logo (or first letter on gold), agency name, season label, "GROUP {name}" in gold; photo 18×22mm (or initials with a blue/rose gradient and "No photo" when missing); name in uppercase, auto-shrinking font for long names (min 7pt, CSS clamp via a class chosen in PHP by length); Bangla name; pilgrim no chip; hotels and rooms; QR 20×20mm with "My trip page" under it; footer with Guide and Office phones; a thin colour stripe on the right edge.
   Badge front: same content, vertical, photo 30×36mm, QR 25mm, bigger type.
4. Card back (match .idk.back): band with name + pilgrim no, Arabic text (Amiri, dir=rtl), English and Bangla lines, 2×2 boxes for leader, guide, Makkah hotel, Madinah hotel, bottom band "Emergency in Saudi Arabia · 911".
5. Double-sided: sides=both prints front sheet then back sheet for each page. On back sheets the two columns are swapped (card in column 1 of the front is in column 2 of the back) so that "flip on long edge" lines them up. Empty slots stay empty on both sides.
6. Staff badges: same design, the word "STAFF" (or the role: Group leader / Guide) instead of the pilgrim no, no QR, no hotels on the front.
7. Without JS everything prints; with JS the toolbar calls window.print() on load if ?autoprint=1.
8. "PDF": no PDF library — the PDF button opens the same page with a note "Choose ‘Save as PDF’ as the printer". (Keep the plugin light.)
```

---

## Prompt 3 — ID cards পাতা (সেটিংস আর প্রিভিউ)

```
<<paste RULES>>

TASK: The admin screen from the mockup. Menu item under PRO after "Agents & commission", before "Day plan", icon passport.

1. Context bar (step 1.1).
2. Left settings card (sticky):
   - Who: Everyone ({n}), Not printed yet ({n}, card_printed_at empty), Pick people (opens a checklist drawer with search), Staff badges.
   - Size: Card (8 on one A4 · 86×54 mm) / Neck badge (4 on one A4 · 95×136 mm).
   - On the card: Photo, Bangla name, Hotels and room, QR code to their page (disabled with a hint when step 3.3 is off), Back side, Cut marks. Saved per departure (user can change any time).
   - Group colour: four swatches; saved to the departure; tooltip "Different colours help when two groups are in the same hotel".
   - Check before printing: warnings from CardData::warnings with links (Add photos → Documents, Rooming → Rooming). When QR is on and the departure's pilgrim page PIN is off, an extra note: "Anyone who finds a lost card can open the pilgrim’s page. Turn on ‘Ask for a PIN’ on the Pilgrim page screen." with a link.
3. Preview (right):
   - Segmented "A4 sheet" / "One card, large".
   - A4 sheet: the real print template rendered in an iframe (same HTML, ?preview=1 hides the toolbar), scaled to fit the column with CSS zoom; page arrows when more than one page; Front/Back switch when the back side is on. Line "{n} cards · {p} pages × 2 sides".
   - Click a card in the iframe (postMessage with the pilgrim id) → "One card, large" view showing front and back of that pilgrim at 1.5× size.
4. Buttons: PDF (opens print page with the Save-as-PDF note), Print (opens the print page in a new tab with ?autoprint=1).
5. After printing: when the user returns to the tab (visibilitychange) show a bar "Did the cards print well?" with "Yes, mark {n} as printed" (sets card_printed_at/by, logs) and "No". Never mark automatically.
6. Pilgrim file (each pilgrim): small "ID card" box — printed on {date} by {who} / Not printed yet, "Print this card" (single card on a sheet, at the top-left slot) and "Mark as not printed".
7. Settings → Cards (agency): editable back-side texts (Arabic, English, Bangla), emergency line, default size and options.
```

---

## QA — শেষে একবার

```
Check roadmap step 4.1 against docs/idcards-mockup.html and ROADMAP.md. Report as a checklist:
- Print accuracy: printed at 100% the calibration square is 50 mm and a card is 85.6 × 54 mm (±0.5 mm); badge 95 × 136 mm; cut marks sit outside the cards; nothing is cut off by the printer margin on a normal office printer.
- Double-sided: with "flip on long edge" the back of each card lines up with its front (test with 3, 7 and 8 cards — odd counts too).
- QR: generated locally (no external requests in the Network tab); scanning with a phone opens the right pilgrim's page; the link is created if missing; QR hidden when 3.3 is off.
- Content: long names shrink and stay on one or two lines; Bangla and Arabic render with the bundled fonts (also when the system has no Bangla/Arabic fonts); no passport number, NID, pilgrim phone, payments or health data anywhere; room numbers only when rooming is final.
- Photos: come from 3.1, embedded as data URIs, EXIF stripped, never a public URL; missing photo → initials and counted in the warnings.
- Who filters: Everyone / Not printed yet / Pick / Staff; "mark as printed" only after confirming; per-pilgrim print and reset in the Pilgrim file.
- Group colour saved per departure and used on front, back and stripe.
- PIN warning appears when QR is on and the pilgrim page has no PIN.
- Library: prefixed namespace (no clash when another plugin loads the same QR library), license files present, Composer not needed on the server.
- Access and group limits; activity log entries.
- Print page without JS; preview iframe matches the printed output.
- No PHP notices, all queries prepared, Plugin Check passes.
```
