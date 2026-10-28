=== Pothik — Umrah Agency Packages & Inquiries ===
Contributors: itsmanzur
Donate link: https://thereadscope.com/pothik
Tags: umrah, hajj, travel agency, package, inquiry, bangladesh
Requires at least: 6.5
Tested up to: 6.7
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

== Changelog ==

= 0.3.0 =
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
