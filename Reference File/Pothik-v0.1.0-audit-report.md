# Pothik v0.1.0 — কোড অডিট ও রোডম্যাপ

তারিখ: ২৮ অক্টোবর ২০২৬ · পরিধি: পুরো ফ্রি প্লাগিন (২২টি ফাইল, ~৪,৫০০ লাইন PHP/JS/CSS), সব PHP ফাইল `php -l` পাস করেছে।

## সারসংক্ষেপ

Pothik এর কোড পরিষ্কার, এবং নিরাপত্তার মূল বিষয়গুলো (nonce, capability check, escaping, honeypot) ঠিক আছে। কিন্তু ১০টি বাগ/ঝুঁকি লাইভ সাইটে লিড হারানো বা ভুল তথ্য দেখানোর কারণ হতে পারে। WP.org-এ জমা দেওয়া বা Freemius-এ বিক্রির আগে এগুলো ঠিক করা দরকার। কোনো ক্রিটিক্যাল সিকিউরিটি ভালনারেবিলিটি (SQLi/XSS/CSRF) পাইনি।

## ১. কী ভালো আছে

- `declare(strict_types=1)`, PSR-4 স্টাইল autoloader, ছোট ছোট ক্লাস। পরিষ্কার গঠন।
- প্রতিটি admin action-এ `manage_options` + nonce। আউটপুটে `esc_html/esc_attr/esc_url` ধারাবাহিকভাবে ব্যবহার হয়েছে।
- পাবলিক ফর্মে honeypot, রেট লিমিট, ইনপুটের দৈর্ঘ্য সীমা, `sanitize_*` ব্যবহার।
- অপশনগুলো `autoload=false`। অ্যাডমিন CSS/JS শুধু Pothik পেজে লোড হয়।
- Pro এর জন্য hook রাখা আছে (`pothik/modules`, `pothik/analytics`, `pothik/seat_line`), Pro কোড ফ্রি-তে নেই।
- বাংলা/ইংরেজি টগল, প্রাইস ক্যালকুলেটর, প্রিন্ট স্লিপ, মোবাইল-ফ্রেন্ডলি UI, `prefers-reduced-motion` সাপোর্ট।

## ২. বাগ ও ঝুঁকি (গুরুত্ব অনুযায়ী)

### 🔴 High — লাইভ সাইটে সরাসরি ক্ষতি

| # | সমস্যা | কোথায় | প্রভাব | সমাধান |
|---|---|---|---|---|
| 1 | রেট লিমিট `REMOTE_ADDR` দিয়ে, আর `> 8` মানে ৯টি সাবমিশন পর্যন্ত চলে | `InquiryStore.php:61-67` | Cloudflare/প্রক্সির পেছনে সব ভিজিটরের IP এক হয়ে যায়। ঘণ্টায় ৯টি ইনকোয়ারির পর **সবার জন্য ফর্ম বন্ধ**। | `CF-Connecting-IP`/`X-Forwarded-For` (শুধু ট্রাস্টেড প্রক্সি থাকলে) পড়ুন, লিমিট কনফিগারেবল করুন (ডিফল্ট ৫/ঘণ্টা/IP, `>=`) |
| 2 | পাবলিক ফর্মে nonce — ক্যাশ করা পেজে ১২-২৪ ঘণ্টা পর মেয়াদ শেষ | `package.php:206`, `InquiryStore.php:55` | LiteSpeed/WP Rocket ক্যাশে পুরনো পেজ থেকে সাবমিট করলে `wp_die("link expired")` — ভিজিটর লিড হারায় | পাবলিক ফর্মে nonce বাদ দিয়ে honeypot + time-trap + রেট লিমিট রাখুন, অথবা nonce AJAX দিয়ে ফ্রেশ আনুন; ফেল করলে wp_die নয়, ফর্মে ফিরিয়ে এরর দেখান |
| 3 | ইনকোয়ারি সর্বোচ্চ ২০০টি, পুরনোগুলো **নিঃশব্দে মুছে যায়**; একই option-এ read-modify-write তাই একসাথে দুটি সাবমিশনে একটি হারাতে পারে | `InquiryStore.php:109-111` | লিড (টাকা) হারানো, কোনো সতর্কতা ছাড়া | ইনকোয়ারি private CPT বা কাস্টম টেবিলে নিন (নিচে আর্কিটেকচার দেখুন) |
| 4 | অ্যাক্টিভেশনে **ফেক ডেটা** ঢোকে: স্যাম্পল প্যাকেজ (ফোন `01711-000000`), স্যাম্পল ইনকোয়ারি, স্যাম্পল রিসিপ্ট। পেজটি অটো-পাবলিশড | `Plugin.php:43-70`, `PackageStore.php:24-` | এজেন্সি ভুলে গেলে ভুয়া ফোন/হোয়াটসঅ্যাপ নম্বর পাবলিক পেজে যায়; অ্যানালিটিক্সে ভুয়া ইনকোয়ারি গোনা হয় | স্যাম্পল ডেটা শুধু "Load demo data" বাটনে; পেজ ডিফল্ট draft; ফোন ফাঁকা থাকলে CTA লুকান |
| 5 | নতুন প্যাকেজ তৈরি করলেই **সাথে সাথে পাবলিক** — ফাঁকা টাইটেল, `৳0` দাম, `0 m` হোটেল দূরত্ব দেখায় | `PackageStore::blank()`, `package.php:107,133`, `catalog.php` | অসম্পূর্ণ প্যাকেজ কাস্টমার দেখে ফেলে | `status` (draft/published) ফিল্ড; দাম `0` হলে সারি/সংখ্যা লুকান বা "অফিসে যোগাযোগ করুন"; `meters = 0` হলে লুকান |
| 6 | ইনকোয়ারি তালিকার "Reply" হোয়াটসঅ্যাপ লিংক ভাঙা। `01819-111111` → `wa.me/01819111111` (আন্তর্জাতিক ফরম্যাটে নয়) | `Format.php:39-47`, `Screens.php:340-358` | ভিজিটর লোকাল নম্বর (০১৮…) দিলে "Reply" ক্লিকে WhatsApp "invalid number" দেখায়। এটাই আপনার মূল ওয়ার্কফ্লো। | `0` দিয়ে শুরু ও ১১ ডিজিট হলে `880` + বাকিটা; `+880`/`880` ঠিক রাখুন; সেটিংসে ডিফল্ট কান্ট্রি কোড |

### 🟠 Medium — ভুল তথ্য / কনফিউশন

| # | সমস্যা | কোথায় | প্রভাব | সমাধান |
|---|---|---|---|---|
| 7 | Desk-এর "Recent inquiries" আসলে **সবচেয়ে পুরনো ৪টি** দেখায় | `Screens.php:38` | ইনকোয়ারি newest-first সেভ হয় (`array_unshift`), তার উপর `array_reverse` দিলে উল্টে যায় | `array_reverse` বাদ দিন, `array_slice($inquiries, 0, 4)` |
| 8 | Analytics-এ টাইমজোন গোলমাল: `created` সেভ হয় লোকাল সময়ে (`wp_date`), পড়া হয় `strtotime` (UTC ধরে), আবার `wp_date` দিয়ে কনভার্ট | `InquiryStore.php:100`, `Analytics.php:57-60, 203-208` | ঢাকার সন্ধ্যা ৬টার পর আসা ইনকোয়ারি "By day" চার্টে **পরের দিনে** গোনা হয়; ৭/৩০ দিনের সীমানা ৬ ঘণ্টা সরে যায় | টাইমস্ট্যাম্প UTC (unix) হিসেবে সেভ করুন, দেখানোর সময় `wp_date` |
| 9 | বাংলা মোডে প্যাকেজ সেভ করলে **ইংরেজি টেক্সট বাংলা ফিল্ডে কপি হয়ে যায়** | `I18n::field` fallback (`I18n.php:357-370`) + `PackageStore::merge_language` (`:366-377`) | বাংলা ফিল্ড ফাঁকা থাকলে ফর্মে ইংরেজি দেখায়; সেভ করলে সেটাই `bn`-এ জমে যায়। পরে ইংরেজি এডিট করলে বাংলা পেজে আর আপডেট হয় না। | অ্যাডমিন ফর্মে fallback বন্ধ (ফাঁকা দেখান, placeholder-এ ইংরেজি); শুধু বদলানো ফিল্ড সেভ করুন |
| 10 | `[pothik_package]` শর্টকোড কোথাও রেজিস্টার হয়নি (`PackagePage::shortcode()` আছে, `add_shortcode` নেই) | `Plugin.php:65`, `PackagePage.php:51` | পেজ কনটেন্টে লেখা শর্টকোড শুধু `template_include` এর কারণে কাজ করে। Elementor/Divi পেজে বসালে `[pothik_package]` লেখাটাই দেখাবে | `add_shortcode('pothik_package', ...)` (এবং `pothik_catalog`), অ্যাসেট শর্টকোড থাকলেই লোড |
| 11 | `ensure_page()` প্রতিটি `init`-এ চলে এবং আবার `activate()` ডাকে | `Plugin.php:37, 105-112` | ইউজার পেজ **পার্মানেন্টলি ডিলিট** করলেও পরের রিকোয়েস্টে ফিরে আসে + welcome নোটিস আবার আসে + প্রতি রিকোয়েস্টে অতিরিক্ত কোয়েরি | শুধু অ্যাক্টিভেশনে + অ্যাডমিনে "Recreate page" বাটন |
| 12 | `?package=ID` URL-এর সবগুলোর canonical ও `<title>` একই (পেজের) | `PackagePage.php`, `front.php` | Google সব প্যাকেজকে ডুপ্লিকেট ধরতে পারে; সার্চে কোনো প্যাকেজ আলাদা র‍্যাঙ্ক করবে না | প্যাকেজ CPT + আলাদা permalink, প্রতি প্যাকেজের title/description/OG |
| 13 | প্লাগিন ডিলিট করলে **সব ডেটা (ইনকোয়ারি, প্যাকেজ) মুছে যায়**, কোনো সেটিং ছাড়া | `uninstall.php` | ভুলে ডিলিট বা রি-ইনস্টলে সব লিড শেষ | "Delete data on uninstall" অপশন (ডিফল্ট off); `pothik_lang` user meta-ও পরিষ্কার করুন |
| 14 | ভাষা কুকিতে সেভ — পেজ ক্যাশ একটি ভাষাই সবাইকে দেখায়। অ্যাডমিন ভাষা বদলালে নিজের পাবলিক ভিউও বদলে যায় | `I18n.php:291-330` | ক্যাশড সাইটে টগল অকার্যকর/বিভ্রান্তিকর; SEO-তে বাংলা পেজ ইনডেক্স হয় না | URL-ভিত্তিক ভাষা (`/bn/`) + `hreflang`; অ্যাডমিন ও ফ্রন্ট ভাষা আলাদা |

### 🟡 Low — পলিশ

- **অন্যান্য UX:** ৪০ প্যাকেজের লিমিটে চুপচাপ রিডাইরেক্ট (`PackageStore.php:257`); প্যাকেজ না পেলে "No packages yet" বার্তা (`Screens.php:93`) বিভ্রান্তিকর।
- **A11y:** honeypot-এ `aria-hidden="true"` নেই (স্ক্রিন রিডার "Website" পড়ে); `<tr role="button">` টেবিল সেমান্টিক ভাঙে (`package.php:128`); ফর্ম ভ্যালিডেশন বার্তায় `aria-describedby` নেই।
- **JS:** `navigator.clipboard.writeText` এর `.catch` নেই, ফেল করলে "Copied" ফিডব্যাক আসে না (`admin.js:112`)।
- **ফরম্যাট:** `phone_href` `+` ফেলে দেয়; বাংলা মোডে টাকার অঙ্ক ইংরেজি ডিজিটে (`৳1,45,000`) — বাংলা ডিজিট অপশন দিলে ভালো।
- **কোডের অবশেষ:** `PackageStore.php:451` ও `ReceiptStore.php:39`-এ হার্ডকোডেড বাংলা স্যাম্পল স্ট্রিং তুলনা করে লেগেসি মাইগ্রেশন — v0.1.0-তে এর দরকার নেই, সরিয়ে ফেলুন।
- **ভার্সনিং:** `filemtime` দিয়ে ক্যাশ-বাস্টিং প্রতি রিকোয়েস্টে `stat` করে; প্রোডাকশনে `POTHIK_VERSION` ব্যবহার করুন।
- **ফাংশনালিটি:** সব ক্ষেত্রে `manage_options` — স্টাফ/এজেন্টকে ইনকোয়ারি দেখাতে অ্যাডমিন বানাতে হয়, যা নিরাপত্তা ঝুঁকি।

## ৩. ইম্প্রুভমেন্ট

### আর্কিটেকচার (সবচেয়ে বড় লাভ)

1. **প্যাকেজ → Custom Post Type** (`pothik_package`): সুন্দর URL (`/umrah/7-day-superior/`), Yoast/Rank Math সাপোর্ট, সাইটম্যাপ, ফিচার্ড ইমেজ, রিভিশন, draft/publish ফ্রি-তে পাবেন। বর্তমান একক-option + `?package=` মডেল এটাই বদলায়।
2. **ইনকোয়ারি → কাস্টম টেবিল** (`{prefix}pothik_inquiries`, ইনডেক্স: created_at, status, phone) অথবা private CPT। ফলে: ২০০-র সীমা নেই, race condition নেই, পেজিনেশন/সার্চ/এক্সপোর্ট সহজ, WP Privacy Export/Erase hook যোগ করা যায়।
3. **গ্লোবাল Settings পেজ:** এজেন্সির নাম, ফোন, WhatsApp, লাইসেন্স নম্বর, লোগো, ডিফল্ট কান্ট্রি কোড, নোটিফিকেশন ইমেইল। এখন এগুলো প্রতিটি প্যাকেজে আলাদা, আর প্রথম প্যাকেজই "অফিস" ধরা হয় (সেটা ডিলিট করলে অফিসের তথ্য বদলে যায়)।
4. **ডেটা মডেল ভাগ:** টাইমস্ট্যাম্প UTC-তে, দাম পূর্ণসংখ্যায় (পয়সা নয়, টাকা ঠিক আছে), `status` ও `source` ফিল্ড।

### সিকিউরিটি ও প্রাইভেসি

- ইনকোয়ারিতে নাম/ফোন সংগ্রহ হয়: ফর্মে ছোট privacy নোট + সম্মতি চেকবক্স (ঐচ্ছিক), WP Privacy Tools (export/erase) যোগ করুন।
- Pro-তে পাসপোর্ট/ভিসার তথ্য আসবে: আলাদা ক্যাপাবিলিটি, ফাইল আপলোড `wp-content` এর বাইরে/প্রটেক্টেড ডিরেক্টরিতে, এনক্রিপশন-অ্যাট-রেস্ট বিবেচনা, ডেটা রিটেনশন নীতি। এটা এখনই ডিজাইনে রাখুন।
- স্প্যাম: honeypot-এর সাথে time-trap (ফর্ম রেন্ডার থেকে সাবমিটে ≥ ৩ সেকেন্ড) ও ঐচ্ছিক Cloudflare Turnstile।
- কাস্টম ক্যাপাবিলিটি (`pothik_manage_inquiries`, `pothik_manage_packages`) + "Pothik Agent" রোল।

### পারফরম্যান্স

- Google Fonts (`Menu.php:60`, `PackagePage.php:63`) বাইরের সার্ভার থেকে লোড হয়। বাংলাদেশে ধীর, প্রাইভেসি ইস্যু, **WP.org রিভিউতে প্রায়ই আপত্তি**। Noto Sans Bengali ও Source Sans 3 সাব-সেট করে লোকাল `woff2` + `font-display: swap`।
- `PackageStore::all()` ঠিক আছে (memoized), তবে `ensure_page` ও `get_option('pothik_page_id')` রিকোয়েস্টে বারবার; `ensure_page` সরালেই হবে।
- পাবলিক পেজে JS ছোট ও ঠিক আছে; শুধু প্যাকেজ পেজেই লোড হচ্ছে — ভালো।

### WP.org ও Freemius রিলিজ-প্রস্তুতি

- **`readme.txt` নেই** — WP.org-এর জন্য বাধ্যতামূলক (ট্যাগ, FAQ, চেঞ্জলগ, স্ক্রিনশট)।
- **`.git` ফোল্ডার জিপে আছে** (২২৮ KB)। রিলিজ জিপে থাকা উচিত নয়; `.distignore` + বিল্ড স্ক্রিপ্ট করুন।
- সব ফাইল **CRLF**, কমিটে LF (`git diff --stat` ২২টি ফাইলই modified দেখায়, বাস্তবে শুধু লাইন-এন্ডিং)। `.gitattributes` (`* text=auto eol=lf`) যোগ করুন।
- **Plugin Check সম্ভাব্য ওয়ার্নিং:** `$_SERVER['REMOTE_ADDR']` সানিটাইজ ছাড়া; `$_COOKIE`/`$_GET` নন্স ছাড়া পড়া; `echo I18n::toggle()` (ফাংশন ভেতরে escape করে, তবুও চেকার ধরবে — `wp_kses` বা phpcs ignore + কমেন্ট); `wp_unslash($_POST)` পুরো অ্যারেতে।
- **WordPress i18n নেই:** `Text Domain: pothik` আছে কিন্তু কোথাও `__()` নেই; কাস্টম `I18n::BN` ম্যাপ। translate.wordpress.org-এ অন্য ভাষা (উর্দু, আরবি, মালয়, ইন্দোনেশিয়ান) আসবে না। `__()/_x()` ও `.pot` ফাইলে যান, বাংলা টগল রেখে।
- Plugin header-এর বর্ণনা ("Pro rooms here are a title and one line.") ইউজারকে বিভ্রান্ত করবে; পরিষ্কার একটি বাক্য দিন। `Domain Path`, `Update URI` যোগ করুন।
- **Freemius SDK** যোগ করার সময় `uninstall.php` সরিয়ে `after_uninstall` hook-এ যেতে হয়। এখনই পরিকল্পনা করুন।
- Pro মেনুতে ৫টি লক করা সাবমেনু আছে, প্রতিটিতে শুধু একটি বাক্য, কোনো "Upgrade" লিংক নেই। একটি "Pro" পেজে (তুলনা টেবিল + আপগ্রেড বাটন) জড়ো করুন। WP.org-এ ফ্রি প্লাগিনে এমন নাগিং মেনু কম রাখাই নিরাপদ।

### UX/কনভার্শন (পাবলিক পেজ)

- **ছবি নেই:** হোটেলের ছবি, গ্যালারি, এয়ারলাইন লোগো, হিরো ইমেজ। উমরা প্যাকেজ বিক্রিতে ছবিই ভরসা তৈরি করে।
- **ট্রাস্ট সিগন্যাল:** এজেন্সির হজ/উমরা লাইসেন্স নম্বর, অফিসের ঠিকানা, রিভিউ/টেস্টিমোনিয়াল।
- সাবমিট সফল হলে "Received" এর পাশাপাশি **"এখনই WhatsApp-এ চ্যাট করুন"** বাটন (ইনকোয়ারির সারাংশ প্রিফিল্ড)। এখন ভিজিটরকে অপেক্ষা করতে হয়।
- মোবাইলে স্টিকি বাটন বার: কল + WhatsApp + ইনকোয়ারি।
- **ইমেইল নোটিফিকেশন নেই** — "অফিস রিপ্লাই করবে" বলা হলেও অফিস জানে না নতুন ইনকোয়ারি এসেছে, যতক্ষণ না লগইন করে দেখে। এটি সবচেয়ে জরুরি ফিচার-গ্যাপ।
- ইনকোয়ারি তালিকায় পেজিনেশন, CSV এক্সপোর্ট, স্ট্যাটাস পাইপলাইন (নতুন → যোগাযোগ হয়েছে → আলোচনা → বুকড → বাতিল), নোট, ফলো-আপ তারিখ।
- রিসিপ্ট: রিসিপ্ট নম্বর, এজেন্সির নাম/লোগো, স্বাক্ষর ঘর, কথায় অঙ্ক (বাংলায়), PDF ডাউনলোড।
- SEO/শেয়ার: `meta description`, Open Graph (হোয়াটসঅ্যাপ শেয়ারে ছবি+টাইটেল), JSON-LD স্কিমা (`TouristTrip`/`Offer`)।

## ৪. সামনের ফিচার রোডম্যাপ

### ফেজ ১ — v0.2 "Stabilize" (আগে এটা, ~১-২ সপ্তাহ)

- উপরের ১-১৪ নম্বর বাগ ফিক্স
- Settings পেজ (গ্লোবাল কন্ট্যাক্ট, নোটিফিকেশন ইমেইল, কান্ট্রি কোড)
- নতুন ইনকোয়ারিতে **ইমেইল নোটিফিকেশন**
- প্যাকেজ draft/published স্ট্যাটাস, ডেমো ডেটা অপশনাল
- ইনকোয়ারি CSV এক্সপোর্ট + পেজিনেশন
- opt-in ডেটা ডিলিট, লোকাল ফন্ট, `readme.txt`, `.distignore`, `.gitattributes`

### ফেজ ২ — v0.3 "Convert" (ফ্রি-কে বিক্রির মেশিন বানানো)

- প্যাকেজ CPT + SEO-ফ্রেন্ডলি URL + স্কিমা
- ছবি গ্যালারি, হোটেল ফটো, ভিডিও এমবেড, FAQ সেকশন
- সাবমিটের পর WhatsApp CTA, স্টিকি মোবাইল বার
- ইনকোয়ারি স্ট্যাটাস পাইপলাইন + নোট + ফলো-আপ
- শর্টকোড + Gutenberg ব্লক + **Divi মডিউল** (আপনার Divi ইকোসিস্টেমের সাথে মিলে)
- প্যাকেজ ক্যাটালগে ফিল্টার (মাস, বাজেট, মেয়াদ, গ্রুপ/প্রাইভেট) ও সর্টিং
- প্যাকেজ তুলনা টেবিল (২-৩টি পাশাপাশি)
- Turnstile/reCAPTCHA, UTM/সোর্স ট্র্যাকিং (কোন বিজ্ঞাপন থেকে ইনকোয়ারি এল)

### ফেজ ৩ — v0.4 "Reach"

- WordPress i18n + translate.wordpress.org; উর্দু, আরবি, মালয়, ইন্দোনেশিয়ান, হিন্দি অনুবাদ
- কারেন্সি সেটিংস (BDT, INR, PKR, MYR, IDR, SAR) ও বাংলা ডিজিট
- হজ প্যাকেজ টাইপ (শুধু উমরা নয়), প্যাকেজ ক্যাটাগরি
- PDF ব্রোশিউর (প্যাকেজ থেকে অটো-জেনারেট, শেয়ারযোগ্য)
- সেটআপ উইজার্ড (প্রথমবার ৩ ধাপে এজেন্সির তথ্য)
- রোল/ক্যাপাবিলিটি: "Pothik Agent"

### Pro (Freemius) — বর্তমান ৫টি প্লেসহোল্ডার ছাড়া অতিরিক্ত আইডিয়া

| ফিচার | কেন বিক্রি হবে |
|---|---|
| **Departures** (তারিখ, ফ্লাইট, সিট সংখ্যা, বাকি সিট) + ফ্রন্টে "মাত্র ৫টি সিট বাকি" | সিট ধরে রাখা এজেন্সির সবচেয়ে বড় ব্যথা |
| **Pilgrim file** (পাসপোর্ট স্ক্যান, মাহরাম/ফ্যামিলি লিংক, ডকুমেন্ট চেকলিস্ট) | কাগজ/WhatsApp থেকে মুক্তি |
| **Installment ledger** + অটো রিসিপ্ট PDF + বাকি টাকার রিমাইন্ডার (SMS/WhatsApp) | বকেয়া আদায় |
| **bKash/Nagad/SSLCommerz** অনলাইন ডিপোজিট (সিট হোল্ড) | এখন "ফর্ম সিট ধরে না" — পেমেন্ট হলে ধরবে |
| **Visa board** + কাস্টমারের জন্য পাবলিক স্ট্যাটাস চেক পেজ (ফোন + পাসপোর্টের শেষ ৪ ডিজিট) | ফোন কল কমে |
| **Rooming** (মক্কা/মদিনা বেড লিস্ট), হোটেল ভাউচার/ম্যানিফেস্ট এক্সপোর্ট (Excel/PDF) | অপারেশন সময় বাঁচে |
| **SMS গেটওয়ে** (বাংলাদেশি গেটওয়ে) + **WhatsApp Business API** | অটো নোটিফিকেশন |
| **এজেন্ট/সাব-এজেন্ট কমিশন**, গ্রুপ লিডার ড্যাশবোর্ড | বড় এজেন্সির জন্য |
| **কাস্টমার পোর্টাল** (বুকিং, পেমেন্ট, ডকুমেন্ট আপলোড) | রিটেনশন |
| উন্নত অ্যানালিটিক্স: ফানেল (ভিজিট → ইনকোয়ারি → বুকড), সোর্স অনুযায়ী রূপান্তর | বিজ্ঞাপন ROI |

## ৫. Cursor / Claude Code প্রম্পট — ফেজ ১ (কপি-পেস্ট)

আপনার পছন্দ অনুযায়ী কোড না লিখে প্রম্পট দিলাম। একসাথে একটাই করে চালান, প্রতিটির পর টেস্ট করুন।

### প্রম্পট A — লিড-হারানো ও ক্র্যাশ ফিক্স (বাগ ১, ২, ৩, ৬, ৭, ৮)

```
You are working on the WordPress plugin "Pothik" (PHP 8.1, namespace Pothik, files in /app, templates in /templates). Make the following fixes WITHOUT changing the public hook names (pothik/modules, pothik/analytics, pothik/seat_line, pothik/loaded). Keep strict_types, escaping, nonces and capability checks. Follow WordPress Coding Standards. Do not add dependencies.

1) Rate limit (app/InquiryStore.php::handle_public)
- Client IP must come from a helper Pothik\Request::ip(): prefer CF-Connecting-IP only if the request really comes through Cloudflare (REMOTE_ADDR in Cloudflare ranges is optional; otherwise accept a filter 'pothik/trusted_proxy_headers' defaulting to []), else REMOTE_ADDR. Sanitize with sanitize_text_field(wp_unslash()) and validate with filter_var(FILTER_VALIDATE_IP).
- Limit becomes configurable via option pothik_settings['rate_limit'] (default 5 per hour per IP) and uses >= comparison. Use a transient with the hashed IP.

2) Public form nonce (templates/package.php, InquiryStore::handle_public)
- Page-cache plugins serve stale nonces, causing wp_die("link expired"). Remove check_admin_referer from the PUBLIC handler. Replace with: honeypot (already there) + a signed time-trap: a hidden field with an HMAC (wp_hash) of a timestamp issued at render; reject (silently treat as spam) if the submit happens in under 3 seconds or older than 24 hours — but if the timestamp is missing/invalid because of caching, do NOT reject; just rely on honeypot + rate limit. Never call wp_die on the public handler; on any validation failure redirect back with ?pothik=missing|limit and keep the visitor's typed values (pass via a short-lived transient keyed by a random token in the redirect URL).

3) Inquiry storage (new class app/InquiryRepository.php)
- Create a custom table {$wpdb->prefix}pothik_inquiries with columns: id BIGINT UNSIGNED AUTO_INCREMENT PK, uuid CHAR(36), created_gmt DATETIME, name, phone, whatsapp, city, share, adults, children, infants, trip, note, package_id, package_title, status VARCHAR(20) default 'new', replied TINYINT. Index on created_gmt, status, phone. Create via dbDelta on activation and on version change (store pothik_db_version).
- Write a one-time migrator that moves rows from option pothik_inquiries into the table (convert the local-time 'created' string with wp_date/ DateTimeImmutable in wp_timezone() to GMT), then deletes the option. Run it on plugins_loaded when pothik_db_version is lower.
- Replace InquiryStore::all()/handle_* to use the repository (prepared statements only). Remove the 200-row cap. Add pagination (20 per page) and a CSV export (admin_post_pothik_export_inquiries, nonce + capability, stream with proper headers, UTF-8 BOM so Excel shows Bangla).

4) WhatsApp number normalisation (app/Format.php)
- Add Format::normalize_msisdn(string $raw, string $default_cc = '880'): digits only; if it starts with '00' strip it; if it starts with the default country code keep it; if it starts with a single '0' replace that 0 with the country code; return '' if the result has fewer than 10 or more than 15 digits.
- Use it in whatsapp_href() and in phone_href() (phone_href should output tel:+<digits>). Default country code comes from pothik_settings['country_code'] (default 880).

5) Recent inquiries order (app/Admin/Screens.php::overview)
- Remove array_reverse; show the newest 4 using the repository (ORDER BY created_gmt DESC LIMIT 4).

6) Timezones (app/Admin/Analytics.php, repository)
- All timestamps stored in GMT. Analytics ranges (7/30 days) and the by-day grouping must compute in the site timezone (wp_timezone()). Add a unit-test-style checklist in a comment: an inquiry created at 2026-10-24 22:00 Asia/Dhaka must be counted on 2026-10-24, not 25.

After the changes, list every file touched and give me a manual test checklist (cached page submit, Cloudflare IP, 250 inquiries, local number 01819-111111 reply link, timezone edge case).
```

### প্রম্পট B — ফেক ডেটা, প্যাকেজ স্ট্যাটাস, শর্টকোড, পেজ রিক্রিয়েশন (বাগ ৪, ৫, ৯, ১০, ১১, ১৩)

```
Continue on the Pothik plugin. Do these changes:

1) No fake data on activation (app/Plugin.php::activate, PackageStore::defaults, InquiryStore::sample, ReceiptStore::sample)
- Activation must NOT insert sample package/inquiry/receipt. Instead add a "Load demo data" button on the Desk screen (admin_post action, nonce + capability) that inserts them, flagged with 'demo' => true. Demo rows show a small "Demo" badge in admin and are excluded from Analytics counts. Add a "Remove demo data" button.
- The auto-created page must be created as 'draft'. Show an admin notice with a "Publish page" action. ReceiptStore::get() must return an empty receipt (not the sample) when nothing is saved. Remove the legacy hard-coded Bangla comparisons in PackageStore::from_legacy and ReceiptStore::get.

2) Package status
- Add 'status' => 'draft'|'published' to packages (default 'draft' for new/blank/copied packages). Public catalog and ?package= resolution only show published packages (admins can preview drafts with a logged-in preview notice). Add a status select and a Publish/Unpublish action in the package list. When a price is 0, hide that price row on the public page and in the catalog; if quad is 0 show a "Contact the office" label instead of ৳0. If *_meters is 0 hide that distance line. If a hotel name is empty hide that hotel card.
- When the 40-package limit is hit show an admin notice instead of a silent redirect. When ?package=ID is not found in admin show "Package not found", not "No packages yet".

3) Bangla save bug (app/Admin/Screens.php::package_form, PackageStore::merge_language)
- In admin forms never fall back to English when the Bangla field is empty: show the field empty with the English text as the placeholder. On save in 'bn' mode, only write a bn value if the submitted value is non-empty and differs from the English root value; otherwise unset that bn key. Same for the *_lines (inclusions/exclusions/itinerary).

4) Shortcodes
- Register [pothik_package id="..."] and [pothik_catalog] with add_shortcode on init. Enqueue assets only when the shortcode renders or the Pothik page template is used. The standalone template front.php keeps working as now.

5) Page recreation
- Remove ensure_page() from the init hook. Keep page creation only in activation and in an admin "Recreate package page" button on Settings. Never call activate() from anywhere except the activation hook.

6) Uninstall (uninstall.php)
- Delete data only if pothik_settings['delete_on_uninstall'] is true (add this checkbox to Settings, default off). When deleting: drop the inquiries table, delete all pothik_* options, the pothik_lang user meta and any pothik_inq_* transients; handle multisite via get_sites() loop.

Give me the list of touched files and a test checklist.
```

### প্রম্পট C — Settings পেজ + ইমেইল নোটিফিকেশন

```
Add a Settings submenu to Pothik (app/Admin/Settings.php, option pothik_settings, sanitize_callback with whitelisted keys): agency name (EN/BN), phone, WhatsApp, license number, logo (media picker), notification email(s), default country code, rate limit, delete_on_uninstall. Move the "office" contact fields out of per-package storage: packages fall back to Settings values when their own phone/whatsapp is empty (keep per-package override). Update templates to use the resolved values via PackageStore::contact($package).

On every new public inquiry send wp_mail to the notification email(s): subject "New Umrah inquiry — {name} ({share})", plain text body with all fields, the package title, and a one-tap wa.me link built with Format::whatsapp_href(). Use wp_mail headers with Reply-To only when the visitor supplied an email (do not add an email field to the form yet). Wrap in a filter 'pothik/notify_email' so Pro can replace it. Failure must never break the visitor flow. Also show the WhatsApp CTA button on the success state of the public form (templates/package.php, $result === 'sent'): "Chat with the office on WhatsApp" with a prefilled message containing name, package, share type, number of adults/children/infants.
```

### প্রম্পট D — রিলিজ-প্রস্তুতি (WP.org/Freemius)

```
Prepare Pothik for release: (1) create readme.txt following the WordPress.org standard (short description, tags: umrah, hajj, travel agency, package, inquiry, bangladesh; Requires 6.5, PHP 8.1, FAQ, Changelog, Screenshots list) and fix the plugin header description to one clear user-facing sentence; add Domain Path and /languages. (2) Replace the custom I18n::t() BN map with WordPress i18n: wrap all strings in __()/_x()/esc_html__() with the 'pothik' text domain, load_plugin_textdomain on init, generate pothik.pot, and add bn_BD.po. Keep the EN/BN toggle by switching the locale with switch_to_locale()/determine_locale filter based on the user's choice. (3) Self-host Noto Sans Bengali and Source Sans 3 as subsetted woff2 in assets/fonts with @font-face + font-display: swap, remove the Google Fonts enqueue. (4) Add .distignore (.git, .github, node_modules, tests, *.md except readme), .gitattributes (* text=auto eol=lf) and phpcs.xml.dist with WordPress-Extra + WordPress-Docs; run phpcs and fix all errors/warnings, then run the WP Plugin Check plugin and fix findings. (5) Collapse the five locked Pro submenus into one "Pro" admin page with a feature comparison table and an Upgrade button (URL via a constant/filter) — keep Modules::all() filter contract so the Pro plugin still works. Report what remains.
```

## ৬. পরবর্তী ধাপ

প্রম্পট A → B → C → D ক্রমে চালিয়ে প্রতিটির পর টেস্ট করুন। ফেজ ১ শেষ হলে রিপোর্ট আবার দিন, আমি ফেজ ২ (CPT মাইগ্রেশন + SEO + ছবি) এর বিস্তারিত প্রম্পট ও ডেটা-মাইগ্রেশন পরিকল্পনা তৈরি করে দেব।
