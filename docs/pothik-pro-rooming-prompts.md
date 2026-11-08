# Pothik Pro — Rooming পেইজ: প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **আগে যা চালানো থাকতে হবে:**
   - Departure ফাইলের Fix 1 আর Fix 2 (ফ্রি প্লাগিনে Pro-র জন্য ডিজাইনের সেট `Pothik\Admin\UI`, `pothik/admin_enqueue`, `pothikAdmin.toast()`)।
   - Pilgrim file ফাইলের Prompt 1, কারণ সেখানে `PothikPro\Readiness` ক্লাস তৈরি হয়।
2. `pothik-pro-rooming-mockup.html` ফাইলটা Pro প্লাগিনের ফোল্ডারে `docs/rooming-mockup.html` নামে রাখুন। ZIP-এ `docs/` বাদ দেবেন।
3. ক্রম: **Prompt 1 → Prompt 2 → QA**। সবগুলো **Pro প্লাগিনে (pothik-pro)** চালাবেন, ফ্রি প্লাগিনের ফোল্ডারও Cursor-এ খোলা রাখবেন।
4. প্রতিটার পর সাইটে চেক করে git commit দিন।

---

## RULES (দুটো প্রম্পটের শুরুতেই পেস্ট করবেন)

```
RULES — Pothik Pro, Rooming
- Plugin: Pothik Pro (namespace PothikPro; app/Screens.php → rooming(), app/Desk.php → save_rooms(), posted_ids(), link_file(), merge_prose()). Renders inside the free Pothik shell via Pothik\Admin\Chrome; markup with Pothik\Admin\UI helpers; if class_exists('Pothik\Admin\UI') is false, keep the current markup.
- Visual reference: docs/rooming-mockup.html — match the "Rooming" screen (data-screen="rooming") exactly. Ignore the other screens in that file. Colours are Pothik's own (green #1b4336 family, gold #d4a64a, clay #b4532a, paper #f7f3ec).
- Data shape is frozen: pothik_pro_desk['rooms'] = ['makkah' => [room…], 'madinah' => [room…]]; room = ['id', 'label', 'number', 'people' => [pilgrim ids], 'bn' => ['label']]. label is a prose field (merge_prose: English root + bn block). No new keys.
- Form contract is frozen: action + nonce pothik_pro_rooms; fields rooms[{city}][{index}][id|label|number|remove] and rooms[{city}][{index}][people][] (pilgrim ids). A NEW room posts an EMPTY id (the server generates it) — never post client-made ids like "n10". Server limits stay: 40 rooms per city, 8 people per room.
- Strings via Pothik\I18n::t() with Bangla translations; choice labels via I18n::choice('share', …). Escape everything; no inline styles except dynamic values.
- Assets: a "rooming" module in assets/js/pro.js + styles in assets/css/pro.css (same structure as departure/pilgrims), enqueued via 'pothik/admin_enqueue' on Pro screens only. Vanilla JS, native HTML5 drag-and-drop, no libraries, no external requests.
- Works without JS: the server renders the current-style form (room cards with label, number, a checkbox list of pilgrims and "Remove this room", plus one blank "New room" card per city) inside the new card design. JS then hides the checkbox lists and takes over with the board, but always keeps the real form inputs in sync so the POST is identical.
- When done: php -l on touched PHP files, list changed files.
```

---

## Prompt 1 — Rooming বোর্ড (ডিজাইন + টেনে বসানো)

```
<<paste RULES>>

TASK: Redesign Screens::rooming() as the drag-and-drop rooming board from docs/rooming-mockup.html.
Chrome::open('Rooming', 'Makkah list and Madinah list are separate. Choose pilgrims from the file.'); Chrome::saved() → toast "Rooming saved".

1. Room type helper — add to PothikPro\Readiness (or a new PothikPro\Rooms class):
   - room_type(string $label): string — detects quad|triple|double|single from the label in English AND Bangla (Quad/কোয়াড, Triple/ট্রিপল, Double/ডাবল, Single/সিঙ্গেল; case-insensitive; filter 'pothik_pro/room_type_words'). '' when unknown.
   - capacity(string $type): int — quad 4, triple 3, double 2, single 1, unknown 0 (filter 'pothik_pro/room_capacity').
   - flags(array $room, array $pilgrims_by_id): list of [code, text, tone]:
     over_capacity (clay) when capacity > 0 and people > capacity;
     mixed (clay) when the room has both genders AND more than one family name;
     share_mismatch (gold, info) per adult pilgrim whose booked share (quad/triple/double/single) differs from the room type — children and infants are ignored. Text: "{first name} booked {Share}".
   Flags are warnings only — saving is never blocked.

2. Top bar:
   - City tabs "🕋 Makkah {n rooms}" / "🕌 Madinah {n rooms}" (segmented, real links ?city=makkah|madinah for no-JS; JS switches instantly and remembers the last city in localStorage inside try/catch). Both cities stay in ONE form so one save posts both lists.
   - Stat pills on the right for the active city: "{placed} / {total} placed", "{rooms} room(s) · {beds} beds" (sum of capacities), clay "{n} not in a room" when > 0, clay "⚠ {n} to check" when there are clay flags.
   - Gold hint bar: "Drag a pilgrim onto a room — or click a pilgrim, then click a room. Each city has its own list."

3. Left panel "Not in a room" (sticky, 290px; stacks above the rooms under 1100px): count pill (clay > 0, green 0), sub "{City} list · from the pilgrim file", pilgrims of the active city not yet in any room, grouped by family name (I18n::field 'family'). Each pilgrim chip: grip icon, initials avatar (blue male / rose female / grey unknown), name (ellipsis + title), share pill. When empty: "✓ Everyone has a bed in {City}". The panel is also a drop target (drop = take the pilgrim out of their room).

4. Room cards (grid auto-fill minmax(260px,1fr)):
   - Header: dark green bed-icon tile, inline-editable label input (looks like text until hover/focus) bound to rooms[city][i][label], small "Room no." input bound to [number].
   - Type buttons Quad / Triple / Double / Single: clicking replaces the type word at the end of the label (keep the rest, e.g. "Room 2 · Double"; default base "Room {n}"); in Bangla mode write the Bangla word. Active button follows room_type(label) live.
   - Beds: pilgrim chips (with ✕ to remove) + dashed "Empty bed" placeholders up to the capacity (1 placeholder if unknown capacity).
   - Flags row (pills), footer "{n} / {cap} people" (green "· full" when equal, clay when over; just "{n} people" when unknown) and a "Remove room" text button.
   - Removing a room: the card disappears, its pilgrims return to the left panel, toast with Undo. For an existing room this sets the hidden rooms[city][i][remove]=1 input (so the server drops it); for a new room it simply removes its inputs.
   - Last tile: dashed "Add a room — Pick the room type" with Quad / Triple / Double / Single buttons → appends a new card "Room {n} · {Type}" with an empty id.

5. Interaction (JS):
   - Native drag-and-drop between the panel and room cards (dragover highlight with green ring + slight lift).
   - Click-to-place for touch/keyboard: click (or Enter/Space on a focused chip) selects it (gold ring); all room cards show a "copy" cursor; clicking a room places the selected pilgrim; Esc or clicking elsewhere cancels.
   - A pilgrim can be in only ONE room per city — placing them moves them out of any other room in that city.
   - Respect the 8-people server limit: refuse a 9th with toast "A room can hold up to 8 people."
   - Every change rewrites the hidden people[] inputs for the affected rooms and re-renders counts, flags and stat pills; the shared save bar shows ("Unsaved rooming", Discard = reload, gold "Save rooming" + Ctrl/Cmd+S); <noscript> keeps a normal submit button.
   - Accessibility: chips are focusable buttons with aria-label "{name}, {share}, in {room or 'no room'}"; an aria-live region announces moves ("Abdul Karim moved to Room 2 · Double").
   - prefers-reduced-motion: no lift/rise animations.
```

---

## Prompt 2 — দ্রুত সাজানোর টুল, প্রিন্ট আর সার্ভারের পাহারা

```
<<paste RULES>>

TASK: Add the helper actions from docs/rooming-mockup.html and harden the save.

1. "Fill by family" (ghost button, wand icon): for the active city, take everyone not in a room, group by family; for each family find an existing room whose free beds ≥ family size and whose current people are all from that same family, else create a new room with the type that fits the family size (4+ → Quad, 3 → Triple, 2 → Double, 1 → Single; families larger than 4 are split into Quad rooms). Never moves people who are already placed. Toast "{n} pilgrims placed by family — check and save". JS only, then the user saves.
2. "Copy Makkah rooms to Madinah" / "Copy Madinah rooms to Makkah" (label follows the active tab): replaces the OTHER city's rooms with copies of this city's rooms (same labels incl. Bangla, same people, EMPTY room numbers, empty ids so the server makes new ones). If the other city already has rooms, ask inline first ("Replace the 2 Madinah rooms? Yes / Cancel"). Toast with Undo.
3. "Print for hotel": print stylesheet that shows only the active city — agency name, departure (leave–return dates, flight from the Departure screen), then each room as a block: label, room number, numbered list of names with gender and share. Hides the panel, buttons, type chips, flags and save bar; no passport numbers. Page breaks between rooms when needed (break-inside: avoid).
4. Server hardening in Desk::save_rooms() — keep the same output shape:
   - A pilgrim id may appear in only one room per city: keep the first occurrence, drop later duplicates (JS already prevents it; this is the safety net).
   - Ignore pilgrim ids that no longer exist (already done by posted_ids — keep it).
   - No other behaviour change (limits, id generation, merge_prose for label, redirect).
5. Empty states: no pilgrims on file → card "Add pilgrims to the pilgrim file first" + button to pothik-pilgrims&pilgrim=new; pilgrims but no rooms in this city → the "Add a room" tile plus a hint "Start with Fill by family".
```

---

## QA — শেষে একবার

```
Check the Rooming screen against docs/rooming-mockup.html and fix what you find. Report as a checklist:
- Save with no changes → the pothik_pro_desk['rooms'] option is identical to before (ids, labels, bn labels, numbers, people order).
- Works without JS: add a room, tick pilgrims, remove a room, save — same result as the old screen.
- With JS: drag-and-drop, click-to-place, keyboard (Tab, Enter, Esc), remove from room, remove room + Undo, add room, type buttons (EN and Bangla labels), both cities saved in one POST.
- New rooms post empty ids; the server generates ids; nothing like "n10" is stored.
- One pilgrim per room per city (UI and server); 8-per-room limit message.
- Flags: over capacity, mixed men & women from different families, share mismatch (children/infants ignored) — warnings only, save never blocked.
- Fill by family and Copy to other city behave as described; Undo works.
- Deleting a pilgrim on the Pilgrim file screen removes them from both cities here.
- Print shows only the active city, no passport numbers, readable on A4.
- Bangla mode at 1280px and 390px: no overflow; screen reader announces moves.
- No PHP notices, no external requests, Plugin Check passes.
```
