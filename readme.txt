=== Pothik ===
Contributors: itsmanzur
Donate link: https://thereadscope.com/pothik
Tags: umrah, hajj, travel agency, package, inquiry
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create Umrah and Hajj package landing pages, collect pilgrim inquiries with WhatsApp integration, and print payment receipts.

== Description ==

**Pothik** is a lightweight, purpose-built WordPress plugin crafted specifically for Umrah and Hajj travel agencies in Bangladesh and South Asia.

Publish beautiful, high-converting package landing pages in minutes, collect pilgrim inquiries directly into your WordPress dashboard, receive instant email notifications, and connect with prospective pilgrims on WhatsApp with one click.

### Key Features

* **Package Landing Pages**: Showcase days, Makkah & Madinah hotels, walking distance in meters to the Haram and the Prophet's Mosque, star ratings, flight routes, and detailed Quad/Triple/Double/Single pricing in BDT.
* **Instant WhatsApp Integration**: Visitors can inquire and immediately tap "Chat with the office on WhatsApp" with pre-filled details (name, package, room share, and number of pilgrims).
* **Reliable Lead Capture**: No lost leads. Uses custom database tables (`{$wpdb->prefix}pothik_inquiries`) with pagination and one-click UTF-8 CSV export for Excel.
* **Smart Spam Protection**: Cache-compatible signed HMAC time-trap and configurable IP rate limiting — zero reliance on fragile page nonces that break on cached pages.
* **Instant Email Notifications**: Get notified via `wp_mail` the moment a pilgrim sends an inquiry, complete with a direct one-tap WhatsApp link to reply immediately.
* **Payment Receipt Slip**: Generate, print, or copy branded payment proof slips with bKash, Nagad, Rocket, Bank, and Cash channels.
* **Bilingual English & Bengali (বাংলা)**: Seamless native language toggle with full translation coverage.
* **Self-Hosted Typography**: Includes subsetted WOFF2 fonts (Noto Sans Bengali and Source Sans 3) for lightning-fast, privacy-friendly page loads without external Google Fonts dependencies.
* **Shortcodes Included**: Easily embed packages anywhere with `[pothik_package id="..."]` or show your entire package offering with `[pothik_catalog]`.

== Installation ==

1. Upload the `pothik` directory to your `/wp-content/plugins/` directory, or install it directly through the WordPress plugin directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Pothik → Settings** to set your agency name, office phone, WhatsApp number, and notification email.
4. Go to **Pothik → Packages** to create and publish your first Umrah package.

== Frequently Asked Questions ==

= Does Pothik work with full-page caching plugins? =
Yes. Pothik does not rely on fragile WordPress nonces for public lead submission forms. It uses honeypot fields, signed HMAC timestamps, and IP-based rate limiting that work smoothly alongside WP Rocket, LiteSpeed Cache, Super Cache, Cloudflare, and server-side Nginx caching.

= How do inquiries reach our office? =
Inquiries are saved directly to your WordPress database table with pagination and CSV export. Simultaneously, an email notification is dispatched to your configured notification email(s) with an instant WhatsApp link to reply to the pilgrim.

= Can I customize the office phone and WhatsApp for individual packages? =
Yes. By default, packages inherit the agency phone and WhatsApp numbers defined in **Pothik → Settings**. However, each package can optionally specify its own contact details to override global settings.

= Can I use shortcodes? =
Yes. Use `[pothik_package id="package_id"]` to embed a single package or `[pothik_catalog]` to display your package catalog on any post, page, or block layout.

== Screenshots ==

1. Public Umrah package landing page with pricing, hotel distances, and WhatsApp CTA.
2. Admin Desk overview showing inquiries count, last payment receipt, and quick actions.
3. Inquiries management table with status filters, phone/WhatsApp quick links, and CSV export.
4. Package editor with hotels, distances, pricing tiers, inclusions, exclusions, and itinerary.
5. One-payment receipt generator with print and WhatsApp copy buttons.
6. Settings screen with agency details, logo picker, and notification setup.

== Credits ==

* Noto Sans Bengali - SIL Open Font License 1.1 (https://scripts.sil.org/OFL)
* Source Sans 3 - SIL Open Font License 1.1 (https://scripts.sil.org/OFL)
* Amiri Bold (subset) - SIL Open Font License 1.1 (https://scripts.sil.org/OFL)

== Hooks ==

Add-ons extend Pothik through these hooks. The free plugin does not ship add-on behaviour.

* `pothik/admin_header_tools` — actions inside the admin header, before search. Argument: the current page slug.
* `pothik/admin_sidebar_before_pro` — markup directly under the Pro label in the sidebar. Argument: the current page slug. Give the wrapper the class `pk-side-slot` so mini mode hides its text.
* `pothik/admin_before_content` — markup after the header, before the page body. Argument: the current page slug.
* `pothik/seat_line` — the seat sentence on a package page. Arguments: the current line, then the package array. A callback registered with one argument still receives only the line.
* `pothik/package_card_badges` — extra pills on a catalog card. Arguments: an array of pills, then the package. Each pill is `['label' => string, 'class' => string]`.
* `pothik/inquiry_actions` — inside the inquiry drawer footer. Argument: the inquiry row when the drawer was built for one row. The shared drawer passes an empty array because the open row is chosen in the browser.
* `pothik/inquiries_before_table` — above the inquiries toolbar. Argument: the status counts (`all`, `waiting`, `replied`).
* `pothik/inquiries_show_status_chips` — whether the Waiting and Replied chips show. Default true. Return false when the toolbar is replaced.
* `pothik/inquiry_columns` — the inquiries table columns. Argument: an array of column key to label. The free keys are `cb`, `pilgrim`, `phone`, `city`, `share`, `people`, `status`, `received` and `actions`.
* `pothik/inquiry_cell` — HTML for a column key the free plugin does not draw. Arguments: an empty string, the column key, the inquiry row. Return escaped HTML.
* `pothik/inquiry_row_actions` — extra buttons in the actions cell. Arguments: the call and WhatsApp HTML, then the inquiry row.
* `pothik/inquiries_query_args` — the list query. Argument: `page`, `per_page`, `status`, `search` and `share`. Add `include_ids` or `exclude_ids` (arrays of inquiry ids). An empty `include_ids` list matches no rows.
* `pothik/inquiry_drawer_top` — after the inquiry drawer header. Argument: the inquiry row when the drawer was built for one row. The shared drawer passes an empty array.
* `pothik/inquiries_bulk_actions` — extra controls inside the bulk bar. No arguments. The form posts `ids[]` with nonce `pothik_bulk_inquiries`.
* `pothik/desk_cards` — extra cards on the Desk. Argument: an array of HTML strings. Callbacks return escaped HTML.
* `pothik/can` — whether a user may view or edit an area. Arguments: the default (false for anyone who is not a WordPress administrator), the area name, the level (`view` or `edit`), and the user id. WordPress administrators are always allowed before this filter runs. Return true only when that user may do that level of that area.
* `pothik/package_saved` — after a package is saved. Arguments: package id, the row before the save, the row after the save.
* `pothik/package_created` — after a package is created or copied. Arguments: package id, the new row.
* `pothik/package_deleted` — after a package is deleted. Arguments: package id, the row before it was deleted.
* `pothik/package_status_changed` — after a package is published or unpublished. Arguments: package id, the old status, the new status.
* `pothik/inquiry_reply_toggled` — after one inquiry is marked replied or waiting. Arguments: inquiry id, true when it is now replied.
* `pothik/inquiry_deleted` — after one inquiry is deleted. Arguments: inquiry id, the row before it was deleted.
* `pothik/inquiries_bulk` — after a bulk reply or delete. Arguments: `reply` or `delete`, then the number of rows. One action for the whole bulk, not one per row.
* `pothik/inquiries_exported` — after an inquiry CSV is built. Argument: the number of rows.
* `pothik/receipt_saved` — after the receipt is saved. Arguments: the option before, the option after.
* `pothik/settings_saved` — after settings are saved. Arguments: the option before, the option after.
* `pothik/settings_data` — inside the Data pane of the settings form. No arguments. Printed fields are saved with the rest of the form.
* `pothik/inquiry_created` — after a public inquiry is saved. Arguments: the inquiry id, then the saved row.
* `pothik/referral_cookie` — whether to store a referral code in the `pothik_ref` cookie. Default true. Return false to leave the cookie unset. The form still keeps a `ref` field for the same visit.
* `pothik/demo_data` — after demo data is loaded or removed. Argument: `loaded` or `removed`.
* `pothik/notify_email` — the new-inquiry email before it is sent. Argument: an array with `to`, `subject`, `message`, `headers` and `inquiry`. Return false to send nothing.
* `pothik/trusted_proxy_headers` — server headers to trust for the visitor address used by the rate limit, for example `HTTP_X_FORWARDED_FOR`. Argument: an array of header names. Default: empty.

== External services ==

Pothik makes no request to any outside server by itself. It does build links to **WhatsApp** (wa.me, operated by WhatsApp LLC / Meta), and the visitor or the office staff opens them by clicking:

* The "Chat with the office on WhatsApp" and share buttons on package and catalog pages open `https://wa.me/` with a message that holds the package name and room share, and the visitor's name and pilgrim counts after they send an inquiry. The data goes to WhatsApp only when the visitor taps the button.
* The new-inquiry email and the inquiries screen contain a `https://wa.me/<number>` reply link for the office. The pilgrim's number and a greeting are in that link. It is sent only when staff click it.

WhatsApp terms: https://www.whatsapp.com/legal/terms-of-service. Privacy policy: https://www.whatsapp.com/legal/privacy-policy

== Privacy ==

Pothik stores the following. Its text is also added to the site's privacy policy guide (Settings → Privacy).

* **Inquiries.** The name, phone, WhatsApp number, city, room share, pilgrim counts, note and referral code that a visitor sends are saved in the `pothik_inquiries` table and emailed to the office. A counter per visitor address (the address is hashed) lives for one hour to limit spam.
* **Cookie `pothik_ref`.** A package or catalog page that is opened with `?ref=` followed by 2–10 letters or digits stores that code in a first-party cookie for 30 days (path `/`, SameSite=Lax). The cookie holds only that code. It is not HttpOnly, so the inquiry form can fill itself on a cached page. No name, phone, or other personal data is stored in it. Return false from `pothik/referral_cookie` to disable the cookie. The inquiry row stores the same code and shows it as “Ref: …” in the inquiry drawer and in the CSV export.
* **Cookie `pothik_lang`.** When a visitor picks English or Bengali, the choice (`en` or `bn`) is stored in a first-party HttpOnly cookie for one year. For a signed-in user it is also saved in their profile as the user meta `pothik_lang`.

== Changelog ==

= 0.3.0 =
* Removed the 40-package limit. You can create as many packages as you need.
* The referral code is now filled in by the plugin's script file instead of an inline script.
* Added the cookies and inquiry data to the site's privacy policy guide, and documented WhatsApp links under External services.
* Redesigned admin:
  - Haramain skyline hero on Desk with Makkah/Madinah crossfade, live Makkah local time, Hijri date, and countdowns.
  - Interactive reply queue with one-tap status toggling and undo toast notification.
  - Packages grid with deterministic color gradient covers, hotel distance meters, and AJAX publish toggling.
  - 3-column Package editor with sticky progress nav, live smartphone preview, star rating widget, and range distance sliders.
  - Server-side inquiries table with live search, room share filter, bulk actions, and slide-over detail drawer with quick WhatsApp templates.
  - Branded payment receipt screen with English & Bengali words generator, channel radio tiles, and ticket slip preview.
  - Analytics dashboard with segmented 7d/30d/All range, trend %, SVG column chart, room share donut, and reply rate semicircle gauge.
  - 2-column Settings tabs with media library logo dropzone, email tagger, and rate limit slider.
  - Unified Pro comparison page.
  - Removed all legacy CSS and optimized vanilla JavaScript bundle.

= 0.2.0 =
* Major release: Custom database repository for inquiries with UTF-8 CSV export.
* Added signed time-traps and configurable IP rate limiting for spam protection.
* Added package status workflow (Draft / Published) and 40-package free tier.
* Added frontend shortcodes `[pothik_package]` and `[pothik_catalog]`.
* Added Settings screen with agency branding, logo media picker, and contact fallback.
* Added instant admin email notifications on new inquiries with one-tap wa.me reply link.
* Added post-submission WhatsApp CTA button with prefilled inquiry details.
* Added self-hosted Noto Sans Bengali and Source Sans 3 WOFF2 fonts.
* Added gettext i18n support with `bn_BD` translations and standard text domain.
* Collapsed locked submenus into a single unified Pothik Pro comparison page.

= 0.1.0 =
* Initial internal release.
