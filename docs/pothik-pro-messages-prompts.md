# ধাপ ৩.২ — একসাথে অনেকজনকে মেসেজ: প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **আগে শেষ থাকতে হবে:**
   - ধাপ ১.১ (ডিপার্চার)।
   - হাজির ফাইল (Pilgrim file), কারণ ফোন নম্বর আর পরিবার সেখান থেকে আসে।
   - এই তিনটা থাকলে তালিকাগুলো পুরোপুরি কাজ করবে: ২.২ (কিস্তি বাকি), ৩.১ (কাগজ বাকি), ভিসা বোর্ড (ভিসা হয়ে গেছে)। কোনোটা না থাকলে সেই তালিকাটা নিজে থেকে লুকিয়ে যাবে।
   - ১.২ থাকলে অনুমতি কাজ করবে, ১.৩ থাকলে "কে কাকে কী পাঠাল" activity log-এ উঠবে।
2. `pothik-pro-messages-mockup.html` ফাইলটা Pro প্লাগিনের ফোল্ডারে `docs/messages-mockup.html` নামে রাখুন।
3. ক্রম: **Prompt 1 → Prompt 2 → Prompt 3 → QA**। এতেই হোয়াটসঅ্যাপ দিয়ে পাঠানো পুরোপুরি চলবে। **Prompt 4 (SMS গেটওয়ে) পরে করলেও চলবে।** সবগুলো প্রম্পট **Pro প্লাগিনে (pothik-pro)** চালাবেন।
4. **Prompt 4-এর আগে** আপনার SMS কোম্পানির API কাগজটা (URL, কোন প্যারামিটারে কী যায়, উত্তরে কী আসে) হাতে রাখুন। প্রম্পটে সেটা পেস্ট করার জায়গা রাখা আছে। আমি কোনো কোম্পানির API নিজে থেকে ধরে নিইনি, কারণ এগুলো প্রায়ই বদলায়।
5. প্রতিটা প্রম্পটের পর git commit দিন। সব শেষ হলে `ROADMAP.md`-তে ৩.২-এর পাশে ✅ দিন।

**একটা সৎ কথা:** হোয়াটসঅ্যাপ দিয়ে পাঠালে প্লাগিন শুধু জানতে পারে যে আপনি কারো জন্য হোয়াটসঅ্যাপ "খুলেছেন"। আপনি আসলে Send চেপেছেন কি না, সেটা জানার কোনো উপায় নেই। তাই হিস্ট্রিতে লেখা থাকবে "Opened", "Delivered" নয়। SMS-এর বেলায় গেটওয়ে যা জানায়, সেটাই দেখানো হবে।

---

## RULES (প্রতিটা প্রম্পটের শুরুতে পেস্ট করবেন)

```
RULES — Pothik Pro, bulk messages (roadmap step 3.2)
- Read ROADMAP.md (section 3.2) first.
- Visual reference: docs/messages-mockup.html — the Messages screen (data-screen="msg"): tabs New message / Sent messages / Templates; step 1 "Who gets it" (audience cards with counts + recipient list), step 2 "Message" (template chips, বাংলা/English switch, textarea, variable chips, character count), step 3 "How to send" (WhatsApp one by one / SMS through a gateway), the dark send bar, the WhatsApp-style phone preview with "Preview as", and the sending drawer (#sq) with progress, Open next / Skip. Colours are Pothik's own.
- Pothik never sends anything on its own in WhatsApp mode: it only builds wa.me links that the user opens and sends by hand. No WhatsApp Web automation, no unofficial WhatsApp APIs, no scraping.
- The only external requests allowed are to the SMS gateway in Prompt 4, only after an admin has entered a key and turned it on. Nothing else calls out.
- Phone numbers are personal data: never put them in URLs of admin pages, logs or error messages in full — mask as 01711-***567 in the log and history.
- Access (step 1.2): new area "messages" — view to see history, edit to send and to edit templates. Group limits apply (a user only sees and messages pilgrims of departures they can access). Without 1.2: manage_options.
- Activity (1.3): message batch created, each recipient opened/skipped (WhatsApp) or sent/failed (SMS), template created/edited/deleted, SMS settings changed (never log the API key).
- $wpdb->prepare everywhere. Strings via Pothik\I18n::t() with Bangla translations. Escape everything; the message body is plain text (no HTML) — escape on output, keep line breaks. Vanilla JS. UI via Pothik\Admin\UI. The screen must work without JS for choosing an audience and a template and getting the list of WhatsApp links.
- When done: php -l on touched PHP files, list changed files.
```

---

## Prompt 1 — ডেটা, ফোন নম্বর, তালিকা আর লেখার ঘর

```
<<paste RULES>>

TASK: Data model and the engine (no screen yet beyond what is needed to test).

1. Tables (dbDelta, bump pothik_pro_db_version):
   {prefix}pothik_msg_templates: id AI, code VARCHAR(30) UNIQUE NULL (for seeded ones), label VARCHAR(80), icon VARCHAR(8), body_bn TEXT, body_en TEXT, audience_hint VARCHAR(20) NULL, is_system TINYINT, sort SMALLINT, updated_by, updated_at.
   {prefix}pothik_msg_batches: id AI, departure_id, channel VARCHAR(10) (whatsapp|sms), audience VARCHAR(20), template_id NULL, lang CHAR(2), body TEXT (the raw text with {{variables}}), status VARCHAR(12) (draft|sending|done|cancelled), total INT, created_by, created_at, finished_at NULL.
   {prefix}pothik_msg_recipients: id AI, batch_id, pilgrim_id, phone VARCHAR(20) (normalised), rendered TEXT (the final text this person got), status VARCHAR(12) (queued|opened|skipped|sent|failed|delivered), provider_ref VARCHAR(64) NULL, error VARCHAR(190) NULL, updated_at. Index batch_id, pilgrim_id.
   Seed 5 system templates (BN + EN), exact texts from the mockup TPL object: flight (✈ Flight time), papers (📄 Papers deadline), inst (৳ Installment reminder), visa (🛂 Visa issued), back (🤲 After return). System templates can be edited but not deleted; "Reset to original" restores the seeded text.

2. PothikPro\Messages\Phone::normalise(string $raw): ?string
   - Strip spaces, dashes, brackets. "+880…" / "880…" / "01…" → "8801XXXXXXXXX"; valid Bangladeshi mobile = /^8801[3-9]\d{8}$/.
   - Other countries: keep if it starts with + and has 8–15 digits (store without +).
   - Return null for anything else. Phone::mask() → "01711-***567".
   - Use the pilgrim's own phone; if empty, fall back to the family head's phone (from the pilgrim file family link) and mark it "via {head name}".

3. PothikPro\Messages\Audience — each audience returns pilgrim ids for one departure, and tells whether it is available:
   all — every active pilgrim of the departure.
   due — ledger balance > 0 (step 2.2; unavailable if 2.2 is missing).
   overdue — at least one installment past its date and unpaid (2.2).
   docs — at least one required paper missing or "needs a new copy" (step 3.1).
   visa — visa status Issued (visa board).
   family — one person per family: the family head, or the first adult if no head is set; children (under 18 on the leave date) never.
   pick — ids given by the user.
   Filter by group access (1.2). Add filter hook 'pothik_pro/msg_audiences' so later steps can add more.

4. PothikPro\Messages\Vars — fills {{variables}} for one pilgrim:
   name, group, leave_date, return_date, flight, report_time, leader, leader_phone, balance, next_amount, next_date, missing_papers, room, hotel_makkah, hotel_madinah, agency, agency_phone, deadline.
   - Dates and money in the chosen language: Bangla digits and Bangla month names when lang = bn (১২ মার্চ ২০২৭, ৳১,০৫,০০০ with Bangladeshi grouping); English otherwise (12 Mar 2027, ৳105,000).
   - missing_papers = labels of missing/redo papers in that language, comma-separated.
   - deadline comes from a field on the send form (date picker), not from data.
   - Unknown variable → left as is and reported by Vars::unknown($body) so the UI can warn. Empty value → "—" and reported by Vars::empty_for($pilgrim, $body) (e.g. "3 people have no next installment").
   - Filter hook 'pothik_pro/msg_vars' to add variables.

5. Duplicate phones: Messages::dedupe($recipients) groups by normalised phone. Default "one message per phone" (first person's text, family members' names joined: "রফিকুল ইসলাম ও ফারহানা আক্তার") — with a setting on the form to send one per person instead.
6. Pilgrim file: add a "No messages" checkbox (opt-out) on the pilgrim record; such pilgrims are always skipped and shown as "Asked not to be messaged".
```

---

## Prompt 2 — Messages পেইজ: কাকে, কী, কীভাবে

```
<<paste RULES>>

TASK: The Messages screen, "New message" tab, from the mockup. Menu item under PRO after "Agents & commission", icon WhatsApp, no badge.

1. Context bar (step 1.1) and the three tabs (New message / Sent messages / Templates) as ?tab= links, enhanced with JS.
2. Step 1 "Who gets it": a card per available audience (label, one-line explanation, count pill; active card green). Recipient list below (checkbox, initials avatar blue/rose, name, masked phone, and per audience: "balance ৳…" for due, the missing paper names for docs). Checkboxes are locked except for "Pick people". Rows without a phone show clay "No phone"; opted-out rows show "Asked not to be messaged". Note line under the list: "{n} without a phone will be skipped. {m} share a phone with family — one message per phone." Choosing an audience pre-selects its matching template (due → inst, docs → papers, visa → visa) but never overwrites text the user has typed (ask "Replace your text with the template?" if edited).
3. Step 2 "Message": template chips + "Write your own"; বাংলা/English switch (switches the template text and the language of dates/money); textarea; variable chips that insert at the cursor; "Deadline" date field appears only when the text contains {{deadline}}; footer "Words in {{ }} are filled for each person" + character count. Warnings under the textarea in clay: unknown variables, and empty values ("3 people have no next installment — they will see —").
4. Phone preview on the right (sticky): WhatsApp-style bubble with filled values highlighted in gold, "Preview as" select of the recipients. Rendering through an AJAX endpoint (nonce, edit cap) that calls Vars for that pilgrim — never fill values in JS from data embedded in the page. Debounce 300 ms.
5. Step 3 "How to send": WhatsApp one by one (default) / SMS through a gateway (disabled with "Set up" link to Settings → Messages until Prompt 4 is done and turned on). With SMS selected, the count line shows "{segments} SMS each · about {total} SMS" (Unicode: 70 chars single, 67 per part; GSM-7: 160 / 153).
6. Dark send bar: "{n} people" + channel line, "Save draft" (stores a draft batch, listed at the top of Sent messages with "Continue"), and "Start sending" (Prompt 3).
7. No-JS: the form posts to admin-post; the result page is a plain list of recipients, each with an "Open WhatsApp" link — enough to work.
8. Hooks for other screens: "Remind missing" on Documents (3.1) and the reminder buttons on Installments (2.2) should now open this screen with ?audience=docs&template=papers (or due/inst) and the departure preselected, replacing any older single-purpose reminder code.
```

---

## Prompt 3 — হোয়াটসঅ্যাপে একজন একজন করে পাঠানো, হিস্ট্রি আর টেমপ্লেট

```
<<paste RULES>>

TASK: The sending queue, Sent messages tab and Templates tab.

1. Start sending (WhatsApp): server creates the batch + recipient rows (rendered text stored per person, status queued), returns the queue. Opens the drawer #sq: green progress bar, "{x} of {n} opened", list of people (done rows faded with "✓ Opened"/"Skipped", current row highlighted green "Next"), help line "WhatsApp opens in a new tab with the message filled in. Press Send there, come back, and tap Next. Pothik never sends on its own."
2. "Open {first name}" button: window.open('https://wa.me/{phone}?text=' + encodeURIComponent(rendered), '_blank', 'noopener') inside the click handler (so popup blockers allow it), then POST status opened for that row and move to the next. "Skip" marks skipped. Setting "Open in": WhatsApp app (wa.me) / WhatsApp Web (https://web.whatsapp.com/send?phone=…&text=…) — default wa.me.
3. The queue survives a page reload: closing the drawer keeps the batch "sending"; Sent messages shows it with "Continue ({left} left)". After the last person the button becomes "Finish" → batch done, toast "Done — {n} messages opened".
4. Sent messages tab: list of batches (coloured icon, template label or "Custom message", audience · date · who, "{n} opened / {m} skipped" or for SMS "{n} sent / {f} failed"). "Details" opens a drawer with the exact raw text and the list of people (masked phone, status, time) and "Send again to skipped/failed". Filter by departure and channel; pagination.
5. Templates tab: list of templates (label, first two lines of the Bangla text). Edit drawer: label, icon (emoji), Bangla text, English text (both with variable chips), "Suggested for" audience; live preview using the first pilgrim of the active departure. "+ New template". System templates: no delete, "Reset to original".
6. Activity log (1.3): one entry per batch ("Sent installment reminder on WhatsApp to 5 people in March group 1448") and per recipient status change, linked to the pilgrim's file timeline so the pilgrim file shows "Installment reminder opened on WhatsApp · 2 Nov · Rahim".
7. Retention: setting "Delete sent message texts after" (Never / 6 months / 1 year after the departure returns) — removes rendered texts and phones from recipients, keeps counts.
```

---

## Prompt 4 (পরে) — বাংলাদেশি SMS গেটওয়ে

> নিচের `PROVIDER API` অংশে আপনার SMS কোম্পানির API কাগজ থেকে URL, প্যারামিটার আর উত্তরের নমুনা পেস্ট করবেন। না দিলে প্রম্পটটা একটা সাধারণ "Custom HTTP" গেটওয়ে বানাবে, যেটা সেটিংস থেকেই যেকোনো কোম্পানির সাথে মেলানো যায়।

```
<<paste RULES>>

TASK: Send SMS through a Bangladeshi SMS gateway, turned on in Settings with an API key.

PROVIDER API (paste from your provider's documentation; leave empty to build only the Custom HTTP gateway):
<<paste here>>

1. Interface PothikPro\Messages\Gateway { id(): string; label(): string; fields(): array; send(string $phone, string $text): GatewayResult; balance(): ?float; parse_delivery(array $request): ?array }.
   GatewayResult: ok bool, ref ?string, error ?string, retry bool.
   Implementations: CustomHttp (always) + one class per provider pasted above. Filter 'pothik_pro/msg_gateways' for more.
2. CustomHttp settings: Method (GET/POST), URL, parameter names for api key / sender id / number / message, extra fixed params (key=value lines), number format (8801… / 01… / +8801…), "Unicode" param name+value, success check (HTTP 200 + response contains text X / JSON path equals value), reference JSON path.
3. Settings → Messages (Pro): provider select, fields from the provider, API key stored encrypted (sodium_crypto_secretbox with a key derived from wp_salt('auth'); shown as •••• last 4; never output in HTML or logs), Sender ID, "Turn on SMS sending" switch, "Send a test SMS" to a number, balance line if the provider supports it. External-request notice: "When on, message texts and phone numbers are sent to {provider}."
4. Sending: Start sending (SMS) creates the batch and returns at once; a WP-Cron / Action Scheduler (if present) job sends in batches of 20 with a pause (setting: messages per minute, default 30), retries a failed message up to 2 times when retry = true, and stops the whole batch on an auth or balance error with a clear message on the batch. The drawer shows live progress (poll every 3 s): sent / failed / left, "Pause" and "Cancel".
5. Before sending: confirm dialog "{n} SMS × {parts} parts = about {total} SMS. Continue?". Block if the gateway reports a balance lower than the total.
6. Delivery reports: if the provider supports a callback, register a REST route /pothik-pro/v1/sms-dlr/{secret} (random secret per site, shown in Settings) that updates status delivered/failed by provider_ref. No callback → status stays "sent".
7. Logging: per recipient sent/failed with error (no full phone, no API key). php -l; list changed files.
```

---

## QA — শেষে একবার

```
Check roadmap step 3.2 against docs/messages-mockup.html and ROADMAP.md. Report as a checklist:
- Audiences: counts match Installments (balance > 0), Documents (missing + redo, required only), visa board (Issued); one-per-family skips children and picks the head; unavailable audiences hidden when 2.2 / 3.1 / visa board are missing; group limits respected for a limited user.
- Phones: 01711-234567, +880 1711 234567, 8801711234567 all become 8801711234567; invalid numbers skipped with a reason; family-head fallback shown; opted-out pilgrims never get messages; shared phones get one message by default.
- Variables: every variable fills in BN (Bangla digits, Bangladeshi grouping ৳১,০৫,০০০) and EN; unknown/empty warnings appear; {{deadline}} shows the date field; HTML typed into the message is shown as text, never rendered.
- Preview matches exactly what the wa.me link contains (compare encodeURIComponent output) for a Bangla text with line breaks and emoji.
- WhatsApp queue: popups not blocked; Skip; reload mid-way and Continue; Finish; history shows "Opened", never "Delivered"; Send again to skipped.
- Templates: seeded 5 present in both languages; edit, new, reset to original; system templates cannot be deleted.
- No-JS path gives a working list of links.
- SMS (if Prompt 4 done): off by default; no external request until turned on (check Network/Query Monitor); key encrypted and never printed; test SMS; segment count for Bangla (70/67) and English (160/153); batch sending with rate limit, pause/cancel, auth/balance errors stop the batch; delivery callback with wrong secret refused.
- Activity log entries for batches and each recipient, shown on the pilgrim file timeline; phones masked everywhere in logs and history; retention clears texts and phones.
- "Remind missing" (Documents) and Installments reminders open this screen pre-filled.
- Mobile (390px): wa.me opens the WhatsApp app; drawer usable; preview below the form.
- No PHP notices, all queries prepared, Plugin Check passes.
```
