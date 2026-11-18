# ধাপ ০.৯ — Pro পেইজের তুলনার টেবিল: প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **এই কাজটা ফ্রি প্লাগিনে (pothik)**, `app/Admin/Screens.php`-এর `pro()` ফাংশনে। Pro প্লাগিনে কিছু বদলাবে না।
2. `pothik-pro-comparison-mockup.html` ফাইলটা ফ্রি প্লাগিনের ফোল্ডারে `docs/pro-comparison-mockup.html` নামে রাখুন।
3. ক্রম: **Prompt 1 → QA**। ছোট কাজ, তাই একটা প্রম্পটেই হয়ে যাবে।
4. **এরপর থেকে একটা স্থায়ী নিয়ম:** রোডম্যাপে কোনো ধাপ ✅ হলে, সেই রিলিজেই টেবিলের সংশ্লিষ্ট লাইন "Coming soon" থেকে "In Pro now" করবেন। কোন ধাপের সাথে কোন লাইন, তা কোডে প্রতিটা লাইনের পাশে `'step' => '2.3'` লেখা থাকবে। তাই খুঁজে পেতে সমস্যা হবে না।
5. ফ্রি প্লাগিন WordPress.org-এ থাকে, আর সেখানে নিয়ম হলো: যে ফিচার নেই তার দাবি করা যাবে না, আর ফ্রি প্লাগিনের ভেতরে Pro-র কোনো কাজ চলবে না। এই টেবিল শুধু বিজ্ঞাপনের লেখা, এতে কোনো লক বা ট্রায়াল নেই। তাই নিয়মের মধ্যেই থাকে।
6. কাজ শেষে git commit দিন। তারপর `ROADMAP.md`-তে ০.৯-এর পাশে ✅ দিন।

---

## Prompt 1 — সৎ তুলনার টেবিল

```
RULES — Pothik (free), Pro comparison page (roadmap step 0.9)
- Read ROADMAP.md (section 0.9) first.
- Visual reference: docs/pro-comparison-mockup.html — data-screen="pro": the dark hero (pill, headline, text, Upgrade button), the legend row (✓ In Pro now / Coming soon / Free) with "Show coming soon" checkbox and the count "{n} in Pro now · {m} coming soon", the grouped table (Sell, Pilgrims & papers, Money, Travel, Visa, Team, then Support) with a one-line explanation under each feature, Free column and Pothik Pro column with status chips, and the footer line with versions. Colours are Pothik's own (use the existing Pothik\Admin\UI components and design tokens).
- Free plugin only. No Pro logic, no feature checks, no locks, no remote calls — this page is plain information plus the upgrade link (existing POTHIK_UPGRADE_URL / 'pothik/upgrade_url' filter).
- Never claim a feature Pro does not have today: anything not built yet is "Coming soon".
- Strings via Pothik\I18n::t() with Bangla translations (add every new string to languages/pothik.pot and bn_BD.po, rebuild bn_BD.mo). Escape everything. Vanilla JS only for the checkbox (works without JS: all rows visible).
- When done: php -l on touched PHP files, list changed files.

TASK:
1. Move the feature list out of Screens::pro() into a new class Pothik\Admin\ProFeatures with a static all(): array returning groups:
   [ 'group' => 'Sell', 'rows' => [ [ 'title' => …, 'desc' => …, 'free' => …, 'pro' => …, 'status' => 'available'|'soon', 'step' => '2.1' ], … ] ]
   'free' is a string or '—' or '✓'; 'pro' is a string, '✓', or '' (for soon). Add filter 'pothik/pro_features' on the array (so a later release can adjust without editing the screen).
   A PHP comment at the top: "When a roadmap step is marked ✅, switch its row to 'available' in the same release."

2. Content (exact texts; status as of today — 1.1 done, the redesigned Pro screens exist):
   Sell
   - Package landing pages · "Days, hotels, prices and a WhatsApp button on each page." · Free "Up to 40 packages" · Pro "Unlimited packages" · available
   - Inquiry form, WhatsApp reply, email alerts · "Anti-spam time trap included." · ✓ · ✓ · available
   - Inquiry to pilgrim in one click · "Turn a won inquiry into a booked pilgrim without typing again." · — · soon · 2.1
   - Agent & sub-agent commission · "Who brought which pilgrim, earned and owed commission." · — · soon · 2.3
   - Season report · "Best packages, districts, inquiry conversion, agents." · — · soon · 5.2
   Pilgrims & papers
   - Departures board · "Several groups per season, dates and seats." · — · ✓ · available · 1.1
   - Pilgrim file · "Passport details, mahram and family links, readiness." · — · ✓ · available
   - Paper upload & checklist · "Passport, photo, NID and vaccine card in a private folder." · — · soon · 3.1
   - Pilgrim’s own page · "A private link with payments, papers, flight and hotel." · — · soon · 3.3
   - Message many pilgrims · "WhatsApp one by one, SMS gateway later." · — · soon · 3.2
   Money
   - Payment receipts · "Printable receipt with your agency details." · "1 latest receipt" · "Full receipt history" · available
   - Installment ledger · "Partial payments, balance due, bKash / Nagad transaction IDs." · — · ✓ · available
   - Payment history with receipt numbers · "Payments are never edited — voided with a reason." · — · soon · 2.2
   - Cost & profit per group · "Tickets in USD, Saudi ground in SAR, home costs in BDT." · — · soon · 5.1
   Travel
   - Makkah & Madinah rooming lists · "Separate bed lists for each city." · — · ✓ · available
   - Day-by-day plan · "Ziyarah, bus times, meeting points — print and share." · — · soon · 3.4
   - ID cards & badges · "8 per A4 with a QR to the pilgrim’s page." · — · soon · 4.1
   - Bus seats, luggage tags, passenger list · "Families sit together; CSV for the airline." · — · soon · 4.2
   Visa
   - Visa board · "Staff move each file through statuses. The visa is issued on Masar." · — · ✓ · available
   - Masar-ready export & import · "Download in your Saudi company’s format; bring visa numbers back." · — · soon · 6A
   Team
   - Staff roles & group access · "Who can open which screens and groups." · — · soon · 1.2
   - Activity log · "Who changed what, and when." · — · soon · 1.3
   Support (no group header row, last row)
   - Support · "" · "Community forum" · "1-on-1 agency onboarding" · available
   Remove the old lines "Visa Board with Masar Portal Integration", "Agent & Sub-Agent Commission Tracking", "Automated quad/triple/double room allocation", "bKash & Nagad reconciliation" and "Flight schedules, group & private quotas" completely.
   Before finishing, open the Pro plugin code and confirm every 'available' row exists there; if one does not, switch it to 'soon' and tell me.

3. Screens::pro() renders the mockup:
   - Hero: pill "Pothik Pro", headline "Run the whole Umrah season from one desk" (the last three words in gold), text "Departures, pilgrim files, rooming, installments and the visa board — built for Bangladeshi agencies. More is on the way; everything marked “Coming soon” is in our plan and arrives as a Pro update — at no extra cost for Pro customers.", Upgrade button.
   - Legend row with the three chips, "Show coming soon" checkbox (default on), and the count line computed from the data.
   - Table: group header rows; each feature row: title (bold) + desc (muted, small); Free cell ('—' muted, '✓' as a grey "✓ Free" chip, or the text); Pro cell (available: optional green text + green "✓ In Pro now" chip; soon: gold "Coming soon" chip). Rows carry data-status so the checkbox hides soon rows.
   - Footer line: "Matches Pothik {POTHIK_VERSION}" (and "and Pothik Pro {POTHIK_PRO_VERSION}" if that constant exists) · "Free never limits your inquiries, WhatsApp buttons or email alerts."
   - Bottom Upgrade button as before.
   - Under 900px the Free column hides.
4. Screens::locked() (used for Pro menu items when Pro is not active) keeps calling pro(), but if the requested module maps to a 'soon' row, show a small note above the hero: "{feature} is coming soon to Pothik Pro."
5. readme.txt: update the Pro section bullet list to the same honest wording (available items only, then "Coming soon:" with the rest).
```

---

## QA — শেষে একবার

```
Check roadmap step 0.9 against docs/pro-comparison-mockup.html and ROADMAP.md. Report as a checklist:
- Every 'available' row exists in Pothik Pro's code today (list where); every not-yet-built feature is 'soon'; the five old over-claims are gone from the screen, readme.txt and translation files.
- Count line matches the rows; "Show coming soon" hides/shows soon rows; without JS all rows show.
- Hero text includes "at no extra cost for Pro customers"; footer shows the real plugin versions.
- Bangla: every new string translated; the page reads naturally in bn_BD.
- No Pro logic, locks or remote calls in the free plugin; upgrade link still filterable; Plugin Check passes; php -l clean.
- Mobile 390px: table readable, Free column hidden.
```
