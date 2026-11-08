# ধাপ ১.২ — স্টাফের অনুমতি (Team & access): প্রম্পট (Cursor / Claude Code)

## শুরু করার আগে

1. **ধাপ ১.১ শেষ থাকতে হবে।** ওখানে সব অনুমতির চেক `PothikPro\Access::can()` দিয়ে করা হয়েছে, এই ধাপ সেটাকেই আসল অনুমতিতে বদলাবে।
2. `pothik-pro-team-mockup.html` ফাইলটা Pro প্লাগিনের ফোল্ডারে `docs/team-mockup.html` নামে রাখুন।
3. ক্রম: **Prompt 1 (ফ্রি) → Prompt 2 → Prompt 3 → Prompt 4 → QA**।
   - Prompt 1 **ফ্রি প্লাগিনে (pothik)**, বাকিগুলো **Pro প্লাগিনে (pothik-pro)**।
   - দুটো ফোল্ডারই Cursor-এ খোলা রাখবেন।
4. **পরীক্ষার জন্য তিনটা আলাদা WordPress ইউজার বানিয়ে রাখুন:** একজন Staff, একজন Accounts, একজন Viewer। প্রতিটা প্রম্পটের পর অন্য ব্রাউজার বা ইনকগনিটো উইন্ডোতে তাদের দিয়ে লগইন করে দেখবেন।
5. প্রতিটার পর git commit দিন। শেষ হলে `ROADMAP.md`-তে ১.২-এর পাশে ✅।

---

## RULES (Prompt 2–4-এর শুরুতে পেস্ট করবেন)

```
RULES — Pothik Pro, Team & access (roadmap step 1.2)
- Read ROADMAP.md (section 1.2) first.
- Visual reference: docs/team-mockup.html — the "Team & access" screen (data-screen="team") with tabs People / What each role can do / Preview as, and the "Add team member" drawer (#inv). Colours are Pothik's own.
- Security model:
  • WordPress administrators (manage_options) ALWAYS have full access to everything in Pothik. Nothing in this step can lock them out.
  • Pothik roles only grant Pothik capabilities. They never grant core WordPress capabilities (no edit_posts, no list_users, no upload_files unless step 3.1 later adds it explicitly), and they don't remove anything a user already has outside Pothik.
  • Every screen render AND every save handler / AJAX endpoint checks the capability on the server. Hiding a menu is never the only protection.
  • Group access: a user limited to certain departures cannot open, switch to, read or save any other departure — server-side, including ?departure= URLs, AJAX, CSV export and print views.
  • Only users who can edit "team" (Manager or WP admin) can change roles or the matrix; nobody can raise their own access.
- Keep every existing form field and action unchanged; only the capability checks change.
- Strings via Pothik\I18n::t() with Bangla translations. Escape everything. Vanilla JS, no external requests. UI via Pothik\Admin\UI.
- When done: php -l on touched PHP files, list changed files.
```

---

## Prompt 1 — ফ্রি প্লাগিনে capability (pothik)

```
Plugin: Pothik (free). Today every screen and handler checks current_user_can('manage_options'). Pothik Pro is adding staff roles. Prepare the free plugin WITHOUT adding any Pro logic (WP.org rules).

1. Add final class Pothik\Access with:
   - const AREAS = ['packages','inquiries','receipt','analytics','settings'] (free areas).
   - can(string $area, string $level = 'view'): bool — $level is 'view' or 'edit'. Default implementation: current_user_can('manage_options'). The result passes through apply_filters('pothik/can', $default, $area, $level, get_current_user_id()) so Pro can extend it. manage_options always returns true (apply the filter only for non-admins).
   - menu_cap(string $area): string — returns 'read' when the user can view the area (so WP lets them open the menu page), otherwise 'manage_options'. Used in add_menu_page/add_submenu_page.
   - deny(): void — wp_die with a friendly translated message "You don't have access to this screen." and a link back to the Pothik Desk, HTTP 403.
2. Replace every manage_options check in the free plugin:
   - Menu registration: top-level "Pothik" menu visible when the user can view ANY area; each submenu uses Access::menu_cap(area).
   - Each screen render: if (!Access::can(area)) Access::deny().
   - Each admin-post / AJAX handler: Access::can(area, 'edit') (export CSV = 'view' on inquiries; reply toggle, delete = 'edit').
   - Settings save, demo data, recreate page: 'settings', 'edit'.
3. View-only mode: when a user can view but not edit an area, Chrome::open() adds body class pothik-readonly and a small "View only" pill next to the page title. CSS: inside .pk-admin.pothik-readonly disable all inputs/buttons that submit (pointer-events none + disabled attribute added by JS on load), hide the save bar. Server still rejects edits.
4. Sidebar: only print items the user can view; add do_action('pothik/sidebar_items') stays as is from 1.1.
5. Desk: cards and quick actions only for areas the user can view (e.g. hide "Last receipt" without receipt access).
6. Add 'pothik/can' to the Hooks section of readme.txt. php -l; list changed files.
```

---

## Prompt 2 — রোল, capability আর সার্ভারের পাহারা (Pro)

```
<<paste RULES>>

TASK: Implement roles and the permission engine.

1. Areas and levels. PothikPro\Access (replacing the 1.1 stub):
   - AREAS (grouped exactly like the mockup matrix): Sales: packages, inquiries · Pilgrims: groups, pilgrims, rooming, visa · Money: ledger, receipt, commission · System: analytics, settings, team.
   - Levels: 'none' | 'view' | 'edit'.
   - Default matrix (option pothik_pro_role_matrix; manager is fixed and never stored):
     manager: all edit.
     staff: packages view, inquiries edit, groups view, pilgrims edit, rooming edit, visa edit, ledger none, receipt none, commission none, analytics view, settings none, team none.
     accounts: packages view, inquiries view, groups view, pilgrims view, rooming none, visa view, ledger edit, receipt edit, commission edit, analytics view, settings none, team none.
     viewer: everything view except settings none, team none.
     "team" can only ever be edit for manager and none for others (enforce in sanitising).
   - level_for(int $user_id, string $area): WP admin → edit; else the highest level among the user's Pothik roles; else none.
   - can(string $area, string $level='view', ?int $departure_id = null): checks level AND, for group areas (groups, pilgrims, rooming, visa, ledger, receipt, commission), departure access.
   - Hook into the free plugin with add_filter('pothik/can', …) so free screens follow the same matrix.
2. WordPress roles: on activation (and on version bump) add roles pothik_manager, pothik_staff, pothik_accounts, pothik_viewer with only ['read' => true] plus one marker capability each (pothik_role_manager, …). Do NOT give them any other core capability. Users keep their existing WP role; Pothik roles are ADDED to the user (WP supports multiple roles), so a shop manager can also be Pothik Staff.
   Remove the roles on uninstall (only when delete_on_uninstall is on) and strip them from users.
3. Group access: user meta pothik_pro_departures = 'all' or JSON list of departure ids. Context::active_id() (from 1.1) must only return a departure the user may open; the header switcher, Departures list, timeline, Desk card, search palette and every query only include allowed departures. New groups: users with 'all' see them; limited users don't until added.
4. Go through every Pro screen, admin-post handler, AJAX endpoint, CSV export and print view and replace the 1.1 checks with Access::can(area, level, $departure_id). Make a table in the PR description: endpoint → area → level.
5. View-only: same behaviour as the free plugin (body class, "View only" pill, disabled controls, hidden save bar); drag-and-drop boards (rooming, visa) become non-draggable.
6. Unit-style checks: add a small WP-CLI command `wp pothik-pro access-check <user>` that prints the user's level per area and allowed departures (only when WP_CLI is defined).
```

---

## Prompt 3 — Team & access পেইজ (Pro)

```
<<paste RULES>>

TASK: Build the Team & access screen from docs/team-mockup.html. Menu item under SYSTEM "Team & access" with a count badge of team members; visible only to users who can edit 'team'.

1. Tab "People":
   - Four role cards (Manager, Staff, Accounts, Viewer) with icon colour (#1b4336, #3f6b8a, #8c6a2a, #6b645a), "{n} people", one-line description, and chips for the first 8 areas (struck through when 'none'). Clicking a card opens the matrix tab.
   - Search (name or email) + primary "Add team member".
   - Table: avatar initials (role colour), name (+ "You" pill for the current user), email; Role select (Manager/Staff/Accounts/Viewer) — WordPress admins show a dark "WordPress admin" pill instead and cannot be changed here; Groups ("All groups" gold chip or the group names); Last active (store user meta pothik_pro_last_seen on each Pothik admin page load, at most once per 5 minutes; human_time_diff); "Remove" button.
   - Remove = take away the Pothik roles and group meta only; the WordPress account stays. Toast "{name} removed from the team — their WordPress account stays" with Undo. A user cannot remove or downgrade themselves, and the last Manager cannot be removed or downgraded if there is no WP admin besides them (there always is, but keep the guard).
   - Note under the table: "WordPress administrators always have full access. Pothik roles only change what people see inside Pothik — not the rest of WordPress."
2. "Add team member" drawer:
   - Segmented "Existing user" / "Invite by email".
   - Existing: searchable select of users who are not WP admins and not yet on the team (AJAX search on display_name/email, 20 results, needs edit 'team').
   - Invite: name + email; create the user with wp_insert_user (role = subscriber + the Pothik role), random password never shown, then wp_send_new_user_notifications($id, 'user') so WordPress emails the set-password link. If the email already exists, offer to add that user instead.
   - Role radio cards with descriptions. "Groups they can open": checkbox "All groups, including new ones" (default on) or a checklist of departures.
   - Validation messages inline; success toast "{name} added as {role}" or "Invitation sent to {email}".
3. Tab "What each role can do": the matrix table — rows grouped Sales / Pilgrims / Money / System with screen name + one-line hint, columns per role with a three-state control No access / View / Edit (colours: none white, view blue #e4eef8/#2f5f86, edit green). Manager column and the Team row are locked. Changes show the save bar ("Unsaved access changes", Discard, "Save access"); saved via admin-post pothik_pro_save_matrix (nonce, edit 'team'), sanitised to the allowed values. "Reset to defaults" link.
4. Tab "Preview as": segmented role picker; a mini dark sidebar showing what that role sees (hidden items struck through, "VIEW" tag on read-only ones) and a card listing "Can change / Read-only / Hidden" plus the two rules about read-only screens and server blocking. Pure preview — never logs in as anyone.
5. Edit a member: clicking a row's Groups cell opens the same drawer in edit mode (role + groups).
```

---

## Prompt 4 — ছোট কিন্তু জরুরি জিনিস (Pro)

```
<<paste RULES>>

TASK: Polish and safety for team access.

1. "No access" page: friendly card with the Pothik shell, text "You don't have access to this screen. Ask your manager if you need it.", button back to Desk. HTTP 403 for direct URL hits.
2. Admin bar & WP dashboard: Pothik-only staff (users whose only meaningful capability is a Pothik role) land on the Pothik Desk after login (login_redirect filter) instead of the WP dashboard; the WP admin menu shows only Pothik and Profile for them (remove_menu_page for items they can't use anyway — cosmetic only).
3. Activity hint: when a role, the matrix or someone's groups change, store a simple log line (option pothik_pro_access_log, last 100 entries: who, when, what) — step 1.3 will move this into the real activity log.
4. Desk card for managers: "Team" — number of members per role and who was active today.
5. Free plugin compatibility: if Pro is deactivated, Pothik roles simply have no access (free plugin falls back to manage_options); nothing breaks.
6. Update ROADMAP.md: mark 1.2 as 🧪 and list the files changed.
```

---

## QA — শেষে একবার

```
Check roadmap step 1.2 against docs/team-mockup.html and ROADMAP.md. Log in as each test user and report as a checklist:
- WP admin: sees everything; cannot be locked out by any matrix setting.
- Staff: sees Inquiries, Pilgrim file, Rooming, Visa board (edit), Packages, Departures, Analytics (view only); Installments, Receipts, Commission, Settings, Team hidden; direct URLs to hidden screens → 403 page; admin-post/AJAX for money areas rejected.
- Accounts: Installments and Receipts editable; Rooming hidden; Pilgrim file read-only (all inputs disabled, no save bar, server rejects a forged POST).
- Viewer: can open screens but every save is rejected; drag-and-drop boards disabled.
- Group limits: a Staff limited to "March group" cannot switch to, list, export, print or save "December group" (try ?departure=, AJAX, CSV).
- Team screen: add existing user, invite by email (WordPress set-password email arrives; no password shown anywhere), change role, change groups, remove + Undo, cannot change own role, matrix save/reset, Manager and Team row locked.
- Pothik roles grant no core WP capabilities (check with `wp user list-caps`); removing a member keeps their WP account.
- Pro deactivated: Pothik roles have no access, no errors.
- Bangla mode: no overflow; all new strings translated.
- No PHP notices, all queries prepared, Plugin Check passes for both plugins.
```
