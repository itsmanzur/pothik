# ধাপ ২.৩ — এজেন্ট ও সাব-এজেন্ট কমিশন: প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **আগে শেষ থাকতে হবে:**
   - ধাপ ১.১ (ডিপার্চার), ২.১ (ইনকোয়ারি থেকে হাজি) আর ২.২ (পেমেন্ট ও রসিদ)।
   - কমিশন "পুরো টাকা শোধ হলে" অর্জিত হওয়ার হিসাব ২.২-এর `ledger.paid`-এর উপর দাঁড়ানো।
   - ১.২ আর ১.৩ থাকলে অনুমতি আর লগ কাজ করবে।
2. `pothik-pro-agents-mockup.html` ফাইলটা Pro প্লাগিনের ফোল্ডারে `docs/agents-mockup.html` নামে রাখুন।
3. ক্রম: **Prompt 1 (ফ্রি) → Prompt 2 → Prompt 3 → Prompt 4 → Prompt 5 → QA**।
   - Prompt 1 **ফ্রি প্লাগিনে (pothik)**, বাকিগুলো **Pro প্লাগিনে (pothik-pro)**।
   - দুটো ফোল্ডারই Cursor-এ খোলা রাখবেন।
4. প্রতিটার পর git commit দিন। শেষ হলে `ROADMAP.md`-তে ২.৩ ✅, আর ধাপ ০.৯ অনুযায়ী তুলনার টেবিলের "Coming soon" লেখাটা সরিয়ে দিন (Prompt 5-এ আছে)।

---

## RULES (Prompt 2–5-এর শুরুতে পেস্ট করবেন)

```
RULES — Pothik Pro, agents & commission (roadmap step 2.3)
- Read ROADMAP.md (section 2.3) first.
- Visual reference: docs/agents-mockup.html — the "Agents & commission" screen (data-screen="agents": four summary cards, agent table with sub-agents nested under their agent, the "When is commission earned?" setting) and the agent drawer (#agd) with tabs Pilgrims / Payouts / Rule / Referral link, plus the "New agent" form. Colours are Pothik's own.
- Model:
  • Two levels only: agent → sub-agent. A sub-agent has exactly one parent agent; an agent cannot be a sub-agent of another sub-agent. No deeper chains.
  • A pilgrim has at most one agent (agent_id on the pilgrim). If that agent is a sub-agent, the commission is split between the sub-agent and its parent according to the sub-agent's rule.
  • Rule types: fixed per pilgrim (whole taka) or percent of the pilgrim's contract (ledger.due), 0–30%. Sub-agent rule = total + the sub-agent's own part; the parent gets total − part. Part must be ≤ total.
  • The rule is SNAPSHOT on each commission line when the pilgrim is linked (rule type, value, split, contract at that time). Changing an agent's rule never changes existing lines; an explicit "Recalculate pending lines" action may update lines that are still pending, with confirmation and an activity entry.
  • Earning: global setting (on booking | when fully paid | when visa issued), overridable per agent. A line is "earned" when its condition is met, otherwise "pending". If a pilgrim is later deleted, moved to another departure with a different agent, or their payments are voided so they are no longer fully paid, the line goes back to pending or is cancelled — never silently deleted; already-paid commission stays recorded and shows as "overpaid" on the agent.
  • Payouts are money: never edited or deleted; wrong ones are voided with a reason (same rules as pilgrim payments in step 2.2). Owed = earned − non-voided payouts (can be negative = overpaid, shown as such).
- Access (step 1.2): area "commission" — view to see, edit to add agents/rules/link pilgrims, payouts need commission edit AND the void permission rules from 2.2. Without 1.2: manage_options.
- Activity (step 1.3): agent created/edited, rule changed, pilgrim linked/unlinked, payout added/voided, earning setting changed.
- $wpdb->prepare everywhere. Strings via Pothik\I18n::t() with Bangla translations. Escape everything. Vanilla JS. UI via Pothik\Admin\UI. Works without JS.
- When done: php -l on touched PHP files, list changed files.
```

---

## Prompt 1 — ফ্রি প্লাগিনে রেফারেল কোড ধরে রাখা (pothik)

```
Plugin: Pothik (free). Pothik Pro is adding agents with referral links like /umrah-package/?ref=MAR. Add ONLY a generic, privacy-friendly referral capture; no Pro logic.

1. On the public package page and catalog (and any page with a Pothik shortcode): if ?ref= is present and matches /^[A-Z0-9]{2,10}$/i, keep it for the visitor:
   - print a hidden input name="ref" in the inquiry form with the value (upper-cased),
   - store it in a first-party cookie pothik_ref (30 days, path /, SameSite=Lax, not HttpOnly-required, no personal data) ONLY if apply_filters('pothik/referral_cookie', true) — so sites with a cookie banner can turn it off; without the cookie the hidden field still works on the same visit.
   - The form must keep working on cached pages: read the cookie with a tiny inline JS that fills the hidden field, instead of relying on PHP for cached HTML.
2. Inquiry save: accept 'ref' (same validation), store it in a new nullable column ref VARCHAR(10) on {prefix}pothik_inquiries (dbDelta, schema version bump), show it nowhere in the free UI except as a small "Ref: MAR" chip in the inquiry drawer, include it in CSV export.
3. Fire do_action('pothik/inquiry_created', string $inquiry_id, array $row) after a public inquiry is saved (if not already present).
4. Privacy note in readme.txt: what the cookie stores and how to disable it. Add the new hooks to the Hooks section. php -l; list changed files.
```

---

## Prompt 2 — এজেন্ট, নিয়ম আর কমিশনের হিসাব (Pro)

```
<<paste RULES>>

TASK: Data model and the commission engine.

1. Tables (dbDelta, bump pothik_pro_db_version):
   {prefix}pothik_agents: id BIGINT AI, name VARCHAR(120), name_bn VARCHAR(120), code VARCHAR(10) UNIQUE (upper-case letters/digits, auto-suggested from the name's initials, editable), type VARCHAR(10) (agent|sub), parent_id BIGINT NULL, phone VARCHAR(40), area VARCHAR(80), pay_channel VARCHAR(10), pay_account VARCHAR(80), rule_type VARCHAR(10) (fixed|percent), rule_value DECIMAL(10,2), split_value DECIMAL(10,2) NULL (sub-agents only), earn_on VARCHAR(10) NULL (booked|paid|visa; NULL = global), status VARCHAR(10) (active|archived), notes VARCHAR(255), created_at, updated_at.
   {prefix}pothik_commissions: id BIGINT AI, agent_id BIGINT (who receives this line), source_agent_id BIGINT (the agent the pilgrim is linked to), pilgrim_id BIGINT, departure_id BIGINT, ledger_id VARCHAR(40) NULL, role VARCHAR(10) (own|override — override = parent's share of a sub-agent's pilgrim), amount BIGINT, rule_snapshot LONGTEXT (JSON), status VARCHAR(10) (pending|earned|cancelled), earned_at DATETIME NULL, cancelled_reason VARCHAR(160) NULL, created_at, updated_at. Index agent_id+status, pilgrim_id.
   {prefix}pothik_agent_payouts: id BIGINT AI, agent_id, paid_on DATE, amount BIGINT UNSIGNED, channel, trx, note, paid_by, paid_by_name, voided_at, voided_by, void_reason, created_at. Index agent_id.
   Add agent_id BIGINT NULL to {prefix}pothik_pilgrims (index).
   Option pothik_pro_commission_earn_on (default 'paid').
2. PothikPro\Agents repository: CRUD with validation (code unique and matches /^[A-Z0-9]{2,10}$/; sub-agent needs an active parent of type agent; split ≤ total; percent ≤ 30), archive instead of delete when lines or payouts exist, children(), by_code().
3. PothikPro\Commission engine:
   - link(int $pilgrim_id, ?int $agent_id): sets pilgrim.agent_id; cancels the pilgrim's existing pending lines (reason "Agent changed"); creates new lines: for an agent → one 'own' line; for a sub-agent → 'own' line for the sub-agent (split part) + 'override' line for the parent (total − split). Amount = fixed value, or round(contract × percent / 100). Snapshot the rule and contract. Earned lines that are already paid out are NOT cancelled — they stay, and the new agent's lines are created as well; show a warning on screen.
   - evaluate(int $pilgrim_id): recompute status of that pilgrim's non-cancelled lines from the earning condition (agent override or global): booked → earned immediately; paid → ledger.paid ≥ ledger.due and due > 0; visa → pilgrim.visa = 'issued'. Earned → pending transitions are allowed (e.g. a voided payment) and logged.
   - Hooks that call evaluate(): payment added/voided (2.2), visa status changed (visa board and pilgrim file), contract changed, pilgrim moved between departures (lines move with the pilgrim; departure_id updated), pilgrim deleted (lines cancelled, reason "Pilgrim deleted").
   - Contract change: pending lines with percent rules are re-amounted; earned lines are not.
   - totals(int $agent_id, ?int $departure_id): earned, pending, paid (non-voided payouts), owed (earned − paid), counts of own pilgrims and pilgrims via sub-agents.
4. Payouts repository: add (validates amount > 0, records who), void (reason ≥ 5 chars, permissions as 2.2), for_agent().
5. A one-time "season summary" query used by the cards: pilgrims through agents / all pilgrims (in departures the user may open), total earned, paid, owed and number of agents owed.
```

---

## Prompt 3 — Agents & commission পেইজ আর এজেন্টের প্যানেল (Pro)

```
<<paste RULES>>

TASK: Build the screen and drawer from docs/agents-mockup.html. Menu item under PRO "Agents & commission" with a count badge of active agents.

1. Summary cards: dark green "Pilgrims through agents {x}%" with "{n} of {m} pilgrims this season" and a gold bar; Commission earned; Paid to agents; gold "Owed now ৳… · {n} agents". Scope: all departures the user may open; a select at the top right "All groups / {group}" narrows everything.
2. Toolbar: search (name, area, code), chips All / Owed / Agents / Sub-agents, primary "New agent".
3. Table: each agent followed by its sub-agents (indented with a curved connector). Columns: Agent (initials avatar in a stable colour, name, "{area} · {CODE} · Agent/Sub-agent" pills) · Rule (plain sentence: "৳5,000 per pilgrim" / "3% of contract"; for sub-agents a second line "৳3,000 to sub-agent · ৳2,000 to {parent}"; for agents "Earned when fully paid" or their override) · Pilgrims ({n}; for agents with sub-agents "+ {n} via sub-agents") · Earned ({amount}, "+ ৳… pending") · Paid · Owed (gold amount, green "✓ 0", or clay "Overpaid ৳…") · actions Pay (gold, only when owed > 0) and Statement. Row click opens the drawer.
4. "When is commission earned?" card: segmented On booking / When fully paid / When visa is issued; saving re-evaluates all non-cancelled lines in batches (progress toast) and logs the change.
5. Agent drawer (580px): header avatar, name, "Agent · {area} · {phone}" or "Sub-agent of {parent} · …"; three boxes Earned / Paid / Owed; tabs:
   - Pilgrims: one row per commission line — avatar, pilgrim name, "{group} · contract ৳… · via {sub-agent}" for override lines, status pill Earned (gold) / "Pending · ৳… due" or "Pending · visa {status}" / Cancelled (struck, with reason), amount ("agent share" under override amounts). Hint "Earned commission is paid out together; there is no per-pilgrim payout." Pilgrim names link to the pilgrim file.
   - Payouts: "Record a payout" form (amount prefilled with owed, channel, transaction ID, note) + timeline of payouts newest first with void action and the never-delete hint.
   - Rule: radio cards Fixed per pilgrim / % of contract, amount field with ৳ or % prefix; for sub-agents "Sub-agent keeps" field + coloured split bar (sub-agent blue / agent green); Earned select (Use the default / On booking / When fully paid / When visa is issued); gold example box ("a Quad pilgrim at ৳1,45,000 → ৳5,000 commission, of which ৳3,000 to the sub-agent. Changing the rule only affects pilgrims booked from now on."); Save rule; secondary "Recalculate pending lines" with confirmation.
   - Referral link: text, the link {package page URL}?ref={CODE} with copy button, a QR code (generate with a small bundled PHP or JS QR library under assets/vendor — no external API), "Download QR (PNG)", stats Inquiries from link / Booked.
   Footer: Statement (print) and WhatsApp (wa.me to the agent's phone with a statement summary text, filter 'pothik_pro/agent_whatsapp_text').
6. New agent / edit agent form (drawer): Name (+ Bangla name in Bangla mode), Phone, Area, Type segmented Agent / Sub-agent, "Works under" (only for sub-agents; active agents), Code (auto-suggested, editable, live uniqueness check), Pay commission by + Account/number, then the Rule section as above. Archive agent in a danger card (only when it has no owed amount; children must be moved or archived first).
7. All actions have admin-post handlers (no-JS) and AJAX enhancement; nonces and access checks everywhere.
```

---

## Prompt 4 — হাজির সাথে এজেন্ট যুক্ত করা (Pro)

```
<<paste RULES>>

TASK: Connect agents to pilgrims everywhere a pilgrim is created or edited.

1. Inquiry → booking (step 2.1): if the inquiry has a ref code matching an active agent, show "Came through {agent} ({CODE})" in the inquiry drawer and preselect that agent in the booking wizard step 3 ("Agent" select: None + active agents/sub-agents, searchable). On confirm, link every created pilgrim to that agent (Commission::link).
2. Pilgrim edit screen: "Came through" select in the Room share & family card (None / agents / sub-agents, grouped), with the commission line(s) preview under it ("৳5,000 to Mufti Abdur Rahman — pending until fully paid"). Changing it calls Commission::link and shows the warning if earned lines already paid out exist.
3. Pilgrim file list: optional column "Agent" (toggle in the toolbar), filter by agent; bulk action "Set agent…".
4. Departures list card and Desk: small line "{n} pilgrims through agents".
5. Inquiries screen (2.1): "Source" column chip — "Website", "Ref: MAR · Mufti Abdur Rahman", or "Ref: XYZ (unknown code)". Unknown codes are kept on the inquiry and can be turned into a new agent with one click ("Create agent with code XYZ").
```

---

## Prompt 5 — স্টেটমেন্ট, রিপোর্ট আর তুলনার টেবিল (Pro + ফ্রি)

```
<<paste RULES>>

TASK: Statements, reports and the honest comparison table.

1. Agent statement (print view, A4): agency header, agent name/code/area/phone, period (season or chosen dates), table of lines (pilgrim, group, contract, rule, amount, status, earned date), sub-agent section for agents, payouts list (date, channel, trx, amount, void marked), totals Earned / Paid / Owed (or Overpaid). Signature lines "Received by" / "Paid by". Two-copies option.
2. CSV exports: commission lines and payouts (filters: agent, group, status, date range).
3. Analytics (Pro panels): pilgrims by source (website vs each top agent), commission earned per month, owed by agent (bars), conversion of referral links (inquiries → booked).
4. Desk card for users with commission view: "Owed to agents" total + top 3 agents with Pay buttons.
5. Comparison table on the free plugin's Pro page (Screens::pro() in the FREE plugin): change "Agent & Sub-Agent Commission Tracking" from "Coming soon" to "Partner commission rules, referral links, payouts & statements". Keep the wording honest — list only what exists.
6. Update ROADMAP.md: mark 2.3 as 🧪, and mark 0.9 as partly done (commission row fixed; Masar row still waits for step 6). List the files changed.
```

---

## QA — শেষে একবার

```
Check roadmap step 2.3 against docs/agents-mockup.html and ROADMAP.md. Report as a checklist:
- Agents and sub-agents: two levels only; sub-agent needs an active parent; split ≤ total; percent ≤ 30; code unique and editable; archive rules.
- Commission lines: fixed and percent amounts correct; sub-agent split creates own + override lines; rule snapshot kept; changing a rule doesn't touch existing lines; "Recalculate pending lines" only updates pending ones.
- Earning: each global setting and per-agent override; earned ↔ pending transitions on payment add/void, visa change, contract change; cancel on pilgrim delete / agent change; moving a pilgrim moves lines.
- Payouts: add, void with reason, never edit/delete; owed = earned − paid; overpaid shown.
- Referral: ?ref=CODE fills the inquiry form (also on a cached page), cookie can be disabled by filter, inquiry stores ref, booking wizard preselects the agent, unknown codes can become an agent; QR generated locally (no external request).
- Pilgrim screens: Came through select, list column/filter, bulk set agent.
- Statements print cleanly (A4, Bangla names); CSV exports; analytics and desk cards.
- Access (Staff without commission sees nothing; Accounts can pay out) and activity log entries.
- Free plugin's Pro comparison table matches what exists.
- No PHP notices, all queries prepared, Plugin Check passes for both plugins.
```
