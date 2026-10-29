<?php
/**
 * Free admin screens.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik\Admin;

use Pothik\Format;
use Pothik\Front\PackagePage;
use Pothik\I18n;
use Pothik\InquiryRepository;
use Pothik\InquiryStore;
use Pothik\Modules;
use Pothik\PackageStore;
use Pothik\ReceiptStore;

if (!defined('ABSPATH')) {
    exit;
}

final class Screens
{
    public static function avatar_color(string $name): string
    {
        return Format::avatar_color($name);
    }

    public static function avatar_initial(string $name): string
    {
        return Format::avatar_initials($name);
    }

    public static function overview(): void
    {
        $current_user = wp_get_current_user();
        $display_name = $current_user && $current_user->display_name ? $current_user->display_name : 'admin';

        $inquiries_count = InquiryRepository::count_all();
        $waiting_count = InquiryRepository::count_waiting();
        $packages = PackageStore::all();
        $packages_count = count($packages);
        $receipt = ReceiptStore::get();
        $is_pro = defined('POTHIK_PRO_FILE');

        // Oldest waiting calculation
        global $wpdb;
        $inq_table = InquiryRepository::table_name();
        $oldest_row = $wpdb->get_row("SELECT created_gmt FROM {$inq_table} WHERE replied = 0 ORDER BY id ASC LIMIT 1");
        $oldest_diff = '';
        if ($oldest_row && !empty($oldest_row->created_gmt)) {
            $t = strtotime((string) $oldest_row->created_gmt);
            if ($t) {
                $oldest_diff = human_time_diff($t, time());
            }
        }

        // Shared analytics metrics (7-day sparkline, trend, 30-day reply rate)
        $desk_m = Analytics::desk_metrics();
        $curr_7_count = $desk_m['curr_7_count'];
        $trend_label = $desk_m['trend_label'];
        $trend_down = $desk_m['trend_down'];
        $line_d = $desk_m['line_d'];
        $fill_d = $desk_m['fill_d'];
        $reply_rate_30 = $desk_m['reply_rate_30'];

        // Packages published count
        $published_count = 0;
        foreach ($packages as $pkg) {
            if (!empty($pkg['published']) || (!empty($pkg['status']) && $pkg['status'] === 'published')) {
                $published_count++;
            }
        }
        $pkg_meter_pct = min(100, (int) round(($packages_count / 40) * 100));
        $pkg_limit_text = $is_pro ? sprintf(I18n::t('%d packages'), $packages_count) : sprintf(I18n::t('%d of 40 free'), $packages_count);

        // Receipt data
        $rc_amt = (int) ($receipt['amount'] ?? 0);
        $rc_ch = (string) ($receipt['channel'] ?? 'bkash');
        $rc_date = !empty($receipt['date']) ? wp_date('j M', strtotime((string) $receipt['date'])) : '';
        $rc_summary = $rc_amt > 0 ? (I18n::choice('channel', $rc_ch) . ($rc_date !== '' ? ' · ' . $rc_date : '')) : I18n::t('No receipts yet');

        // Setup checklist (5 real steps)
        $opt = Settings::get_all();
        $s1 = (!empty($opt['agency']) || !empty($opt['name'])) && !empty($opt['phone']);
        $s2 = $packages_count > 0;
        $s3 = !empty($opt['whatsapp']);
        $s4 = !empty($opt['logo_url']) || !empty($opt['logo_id']);
        $s5 = $published_count > 0;
        $completed_steps = ($s1 ? 1 : 0) + ($s2 ? 1 : 0) + ($s3 ? 1 : 0) + ($s4 ? 1 : 0) + ($s5 ? 1 : 0);
        $progress_pct = (int) round(($completed_steps / 5) * 100);
        $hide_setup = (bool) get_user_meta(get_current_user_id(), 'pothik_hide_setup', true);
        $show_setup = ($completed_steps < 5) && !$hide_setup;

        // Hijri date string
        $hijri_text = '';
        if (class_exists('IntlDateFormatter')) {
            try {
                $locale = I18n::is_bn() ? 'bn_BD' : 'en_GB';
                $fmt = new \IntlDateFormatter(
                    $locale . '@calendar=islamic-umalqura',
                    \IntlDateFormatter::LONG,
                    \IntlDateFormatter::NONE,
                    wp_timezone_string(),
                    \IntlDateFormatter::TRADITIONAL,
                    'd MMMM y'
                );
                $formatted = $fmt->format(time());
                if ($formatted) {
                    $hijri_text = $formatted . ' ' . (I18n::is_bn() ? 'হিজরি' : 'AH');
                }
            } catch (\Throwable $e) {
                $hijri_text = '';
            }
        }
        $gregorian_date = wp_date(I18n::is_bn() ? 'l, j F Y' : 'l, j F Y');

        $desk_lines = [
            I18n::t("Every reply could be someone's first step towards the Haram."),
            I18n::t("May Allah accept the Umrah of every pilgrim you serve."),
            I18n::t("Serve the guests of Ar-Rahman with ihsan."),
        ];
        $desk_lines = apply_filters('pothik/desk_lines', $desk_lines);

        $waiting_inquiries = InquiryRepository::recent_waiting(5);

        $locked_modules = [];
        $free_slugs = ['package', 'inquiries', 'receipt', 'analytics'];
        if (!$is_pro) {
            foreach (Modules::all() as $mid => $m) {
                if (in_array($mid, $free_slugs, true)) {
                    continue;
                }
                if (!Modules::is_open($m)) {
                    $locked_modules[$mid] = $m;
                }
            }
        }

        $has_demo = PackageStore::has_demo() || InquiryRepository::has_demo() || ReceiptStore::has_demo();
        $makkah_initial = wp_date('g:i A', null, new \DateTimeZone('Asia/Riyadh'));

        Chrome::open('Desk', I18n::t("Today's inquiries, packages and the next Umrah season at a glance."));

        echo '<section class="screen" data-screen="desk">';

        // 1. GREETING ROW
        echo '<div class="greet">';
        echo '<div><h2>' . esc_html(sprintf(I18n::t('Assalamu alaikum, %s'), $display_name)) . '</h2>';
        echo '<div class="sub"><span id="hijri">' . esc_html($hijri_text) . '</span> · <span>' . esc_html($gregorian_date) . '</span></div></div>';
        echo '<p class="talbiyah" lang="ar" dir="rtl">لَبَّيْكَ اللَّهُمَّ لَبَّيْكَ</p>';
        echo '</div>';

        // 2. NOTICE STRIP
        echo '<div class="notice" role="status">';
        echo '<span class="badge-n">' . esc_html(I18n::t('Notice')) . '</span>';
        if ($waiting_count === 1) {
            echo '<p><b data-wait-count data-pk-wait-count>1</b> ' . esc_html(I18n::t('guest of Allah is waiting for your reply.')) . ' <em data-wait-pill>' . esc_html($desk_lines[0] ?? '') . '</em></p>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=pothik-inquiries&status=waiting')) . '">' . esc_html(I18n::t('Reply now')) . ' <svg class="i sm"><use href="#i-arrow"/></svg></a>';
        } elseif ($waiting_count > 1) {
            echo '<p><b data-wait-count data-pk-wait-count>' . (int) $waiting_count . '</b> ' . esc_html(I18n::t('guests of Allah are waiting for your reply.')) . ' <em data-wait-pill>' . esc_html($desk_lines[0] ?? '') . '</em></p>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=pothik-inquiries&status=waiting')) . '">' . esc_html(I18n::t('Reply now')) . ' <svg class="i sm"><use href="#i-arrow"/></svg></a>';
        } else {
            echo '<p><b data-wait-count data-pk-wait-count>0</b> ' . esc_html(I18n::t('guests of Allah waiting.')) . ' <em data-wait-pill>' . esc_html(I18n::t('All guests of Allah have been answered. Alhamdulillah.')) . '</em></p>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=pothik-inquiries')) . '">' . esc_html(I18n::t('View inquiries')) . ' <svg class="i sm"><use href="#i-arrow"/></svg></a>';
        }
        echo '</div>';

        // 3. SEASON COUNTDOWN CARD
        echo '<section class="count-card">';
        echo '<div class="art" aria-hidden="true">';
        $skyline_file = dirname(__DIR__, 2) . '/templates/admin/skyline.php';
        if (file_exists($skyline_file)) {
            require $skyline_file;
        }
        echo '</div>';
        echo '<div>';
        echo '<div class="eyebrow">' . esc_html(I18n::t('Next season')) . '</div>';
        echo '<h3 id="cName">' . esc_html(I18n::t('Ramadan 1448')) . '</h3>';
        echo '<div class="sub">' . esc_html(I18n::t('Starts around')) . ' <b id="cDate">—</b> · <span id="cTip">' . esc_html(I18n::t('Publish your Ramadan Umrah packages early.')) . '</span></div>';
        echo '<div class="place-row">';
        echo '<div class="seg pk-seg" id="cSeg"><button type="button" class="on" data-k="ram">🌙 ' . esc_html(I18n::t('Ramadan')) . '</button><button type="button" data-k="hajj">🐪 ' . esc_html(I18n::t('Hajj')) . '</button></div>';
        echo '<button type="button" class="on" data-scene="Makkah">🕋 ' . esc_html(I18n::t('Makkah')) . ' <b data-tz-time>' . esc_html($makkah_initial) . '</b></button>';
        echo '<button type="button" data-scene="Madinah">🕌 ' . esc_html(I18n::t('Madinah')) . '</button>';
        echo '</div>';
        echo '</div>';
        echo '<div class="timer" id="timer" aria-live="off">';
        echo '<div><b data-t="d">000</b><span>' . esc_html(I18n::t('days')) . '</span></div><i>:</i>';
        echo '<div><b data-t="h">00</b><span>' . esc_html(I18n::t('hours')) . '</span></div><i>:</i>';
        echo '<div><b data-t="m">00</b><span>' . esc_html(I18n::t('mins')) . '</span></div><i>:</i>';
        echo '<div><b data-t="s">00</b><span>' . esc_html(I18n::t('secs')) . '</span></div>';
        echo '</div>';
        echo '</section>';

        // 4. QUICK ACTIONS ROW
        echo '<div class="qbar">';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="inline-form">';
        wp_nonce_field('pothik_new_package');
        echo '<input type="hidden" name="action" value="pothik_new_package">';
        echo '<button type="submit" class="btn pk-btn btn-ghost pk-btn-ghost"><svg class="i sm"><use href="#i-plus"/></svg>' . esc_html(I18n::t('New package')) . '</button>';
        echo '</form>';
        echo '<a class="btn pk-btn btn-ghost pk-btn-ghost" href="' . esc_url(admin_url('admin.php?page=pothik-inquiries')) . '"><svg class="i sm"><use href="#i-inbox"/></svg>' . esc_html(I18n::t('Inquiries')) . '</a>';
        echo '<a class="btn pk-btn btn-ghost pk-btn-ghost" href="' . esc_url(admin_url('admin.php?page=pothik-receipt')) . '"><svg class="i sm"><use href="#i-receipt"/></svg>' . esc_html(I18n::t('Payment slip')) . '</a>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="inline-form">';
        wp_nonce_field('pothik_export_inquiries');
        echo '<input type="hidden" name="action" value="pothik_export_inquiries">';
        echo '<button type="submit" class="btn pk-btn btn-ghost pk-btn-ghost"><svg class="i sm"><use href="#i-down"/></svg>' . esc_html(I18n::t('Export CSV')) . '</button>';
        echo '</form>';
        echo '<button type="button" class="btn pk-btn btn-ghost pk-btn-ghost" data-pk-copy="[pothik_catalog]"><svg class="i sm"><use href="#i-copy"/></svg>' . esc_html(I18n::t('Copy shortcode')) . '</button>';
        echo '<a class="btn pk-btn btn-ghost pk-btn-ghost" href="' . esc_url(admin_url('admin.php?page=pothik-settings')) . '"><svg class="i sm"><use href="#i-gear"/></svg>' . esc_html(I18n::t('Settings')) . '</a>';
        echo '</div>';

        // 5. SIX STAT CARDS
        echo '<div class="stats6">';
        // Stat 1: Inquiries
        echo '<a class="stat" href="' . esc_url(admin_url('admin.php?page=pothik-analytics')) . '">';
        echo '<span class="ic"><svg class="i"><use href="#i-inbox"/></svg></span>';
        echo '<span class="trend ' . ($trend_down ? 'neg' : 'pos') . '">' . esc_html($trend_label) . '</span>';
        echo '<strong data-pk-count="' . $curr_7_count . '" data-count="' . $curr_7_count . '">' . $curr_7_count . '</strong>';
        echo '<span class="lb">' . esc_html(I18n::t('Inquiries')) . '</span>';
        echo '<span class="ln">' . esc_html(I18n::t('Last 7 days')) . '</span>';
        echo '<svg class="spark" viewBox="0 0 100 40" preserveAspectRatio="none"><path d="' . esc_attr($fill_d) . '" fill="#40916c" opacity=".12"/><path d="' . esc_attr($line_d) . '" fill="none" stroke="#40916c" stroke-width="2"/></svg>';
        echo '</a>';

        // Stat 2: Waiting
        echo '<a class="stat c2" href="' . esc_url(admin_url('admin.php?page=pothik-inquiries&status=waiting')) . '">';
        echo '<span class="ic"><svg class="i"><use href="#i-clock"/></svg></span>';
        echo '<strong data-pk-count="' . $waiting_count . '" data-count="' . $waiting_count . '" data-pk-wait-count data-wait-count>' . $waiting_count . '</strong>';
        echo '<span class="lb">' . esc_html(I18n::t('Waiting reply')) . '</span>';
        echo '<span class="ln">' . esc_html($oldest_diff !== '' ? sprintf(I18n::t('Oldest %s ago'), $oldest_diff) : I18n::t('All answered')) . '</span>';
        echo '</a>';

        // Stat 3: Packages
        echo '<a class="stat c3" href="' . esc_url(admin_url('admin.php?page=pothik-package')) . '">';
        echo '<span class="ic"><svg class="i"><use href="#i-box"/></svg></span>';
        echo '<strong data-pk-count="' . $packages_count . '" data-count="' . $packages_count . '">' . $packages_count . '</strong>';
        echo '<span class="lb">' . esc_html(I18n::t('Packages')) . '</span>';
        echo '<span class="ln">' . esc_html($pkg_limit_text) . '</span>';
        echo '<div class="meter"><i data-pk-w="' . $pkg_meter_pct . '" data-w="' . $pkg_meter_pct . '" style="width:' . $pkg_meter_pct . '%"></i></div>';
        echo '</a>';

        // Stat 4: Published
        echo '<a class="stat c4" href="' . esc_url(admin_url('admin.php?page=pothik-package')) . '">';
        echo '<span class="ic"><svg class="i"><use href="#i-globe"/></svg></span>';
        echo '<strong data-pk-count="' . $published_count . '" data-count="' . $published_count . '">' . $published_count . '</strong>';
        echo '<span class="lb">' . esc_html(I18n::t('Published')) . '</span>';
        echo '<span class="ln">' . esc_html(I18n::t('Live on your site')) . '</span>';
        echo '</a>';

        // Stat 5: Last receipt
        echo '<a class="stat c5" href="' . esc_url(admin_url('admin.php?page=pothik-receipt')) . '">';
        echo '<span class="ic"><svg class="i"><use href="#i-receipt"/></svg></span>';
        echo '<strong data-pk-count="' . $rc_amt . '" data-count="' . $rc_amt . '" data-pk-money data-money>' . Format::money($rc_amt) . '</strong>';
        echo '<span class="lb">' . esc_html(I18n::t('Last receipt')) . '</span>';
        echo '<span class="ln">' . esc_html($rc_summary) . '</span>';
        echo '</a>';

        // Stat 6: Reply rate
        echo '<a class="stat c6" href="' . esc_url(admin_url('admin.php?page=pothik-analytics')) . '">';
        echo '<span class="ic"><svg class="i"><use href="#i-check"/></svg></span>';
        echo '<strong data-pk-count="' . $reply_rate_30 . '" data-count="' . $reply_rate_30 . '" data-suffix="%">' . $reply_rate_30 . '%</strong>';
        echo '<span class="lb">' . esc_html(I18n::t('Reply rate')) . '</span>';
        echo '<span class="ln">' . esc_html(I18n::t('Last 30 days')) . '</span>';
        echo '</a>';

        echo '</div>'; // .stats6

        // 6. TWO COLUMNS (Desk2: Reply queue & Packages / Setup)
        echo '<div class="desk2">';

        // Left card: Reply queue
        echo '<div class="card pk-card">';
        echo '<div class="card-head pk-card-head"><h3><svg class="i"><use href="#i-inbox"/></svg>' . esc_html(I18n::t('Reply queue')) . '</h3><a class="link" href="' . esc_url(admin_url('admin.php?page=pothik-inquiries')) . '">' . esc_html(I18n::t('All inquiries')) . ' <svg class="i sm"><use href="#i-arrow"/></svg></a></div>';
        echo '<div class="queue-head"><span></span><span>' . esc_html(I18n::t('Pilgrim · package · share')) . '</span><span>' . esc_html(I18n::t('Received · actions')) . '</span></div>';
        echo '<ul class="queue" id="queue">';
        if (empty($waiting_inquiries)) {
            echo '<li class="pk-empty empty"><div class="em-ico"><svg class="i lg"><use href="#i-check"/></svg></div><b>' . esc_html(I18n::t('All caught up, alhamdulillah')) . '</b><div class="sub">' . esc_html(I18n::t('New inquiries will appear here.')) . '</div></li>';
        } else {
            foreach ($waiting_inquiries as $r) {
                $name = (string) $r->name;
                $phone = (string) $r->phone;
                $bg = Format::avatar_color($name);
                $initial = Format::avatar_initials($name);
                $pkg_name = (string) ($r->package_title ?: I18n::t('Umrah package'));
                $share_txt = I18n::choice('share', (string) $r->share);
                $city_txt = (string) $r->city;
                $pax_num = (int) $r->adults + (int) $r->children + (int) $r->infants;
                $time_raw = !empty($r->created_gmt) ? (string) $r->created_gmt : (string) ($r->created ?? '');
                $time_str = $time_raw !== '' ? human_time_diff(strtotime($time_raw), time()) . ' ' . I18n::t('ago') : '';
                $inq_id = (string) ($r->uuid ?: $r->id);
                $wa_href = Format::whatsapp_href($phone, I18n::t('Office reply about your Umrah inquiry.'));
                $tel_href = Format::phone_href($phone);

                $meta_parts = array_filter([$pkg_name, $share_txt, $city_txt, $pax_num . ' ' . I18n::t('pilgrims')]);
                $meta_line = implode(' · ', $meta_parts);

                echo '<li class="q-row" data-pk-inq-id="' . esc_attr($inq_id) . '" data-pk-inq-name="' . esc_attr($name) . '">';
                echo '<span class="ava" style="background:' . esc_attr($bg) . '">' . esc_html($initial) . '</span>';
                echo '<div class="q-main"><b>' . esc_html($name) . '</b><div class="sub">' . esc_html($meta_line) . '</div></div>';
                echo '<div class="q-act">';
                echo '<span class="q-time">' . esc_html($time_str) . '</span>';
                if ($tel_href !== '') {
                    echo '<a class="pk-icon-btn icon-btn" href="' . esc_url($tel_href) . '" title="' . sprintf(esc_attr(I18n::t('Call %s')), $phone) . '"><svg class="i"><use href="#i-phone"/></svg></a>';
                }
                if ($wa_href !== '') {
                    echo '<a class="pk-icon-btn icon-btn wa" href="' . esc_url($wa_href) . '" target="_blank" rel="noopener" title="' . esc_attr(I18n::t('Reply on WhatsApp')) . '"><svg class="i"><use href="#i-wa"/></svg></a>';
                }
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="pk-reply-form inline-form">';
                wp_nonce_field('pothik_reply_inquiry');
                echo '<input type="hidden" name="action" value="pothik_reply_inquiry">';
                echo '<input type="hidden" name="id" value="' . esc_attr($inq_id) . '">';
                echo '<input type="hidden" name="back" value="pothik">';
                echo '<button type="submit" class="check-btn" title="' . esc_attr(I18n::t('Mark replied')) . '"><svg class="i"><use href="#i-check"/></svg></button>';
                echo '</form>';
                echo '</div>'; // .q-act
                echo '</li>';
            }
        }
        echo '</ul>';
        echo '</div>'; // .card

        // Right column: Packages + Setup checklist
        echo '<div class="grid">';

        // Mini Packages card
        echo '<div class="card pk-card">';
        echo '<div class="card-head pk-card-head"><h3>' . esc_html(I18n::t('Packages')) . '</h3><a class="link" href="' . esc_url(admin_url('admin.php?page=pothik-package')) . '">' . esc_html(I18n::t('Manage')) . ' <svg class="i sm"><use href="#i-arrow"/></svg></a></div>';
        echo '<div id="miniPkgs">';
        if (empty($packages)) {
            echo '<div class="pk-empty empty"><p class="sub">' . esc_html(I18n::t('No packages created yet.')) . '</p></div>';
        } else {
            $mini_sample = array_slice($packages, 0, 3);
            $color_pairs = [
                ['#173d30', '#2d6a4f'],
                ['#2b2110', '#c4a574'],
                ['#14273e', '#2563eb'],
                ['#3a1d14', '#b4532a'],
            ];
            foreach ($mini_sample as $idx => $p) {
                $pair = $color_pairs[$idx % count($color_pairs)];
                $days = max(1, (int) ($p['days'] ?? 7));
                $title = trim((string) ($p['title'] ?? ''));
                if ($title === '') {
                    $title = trim((string) ($p['bn']['title'] ?? ''));
                }
                if ($title === '') {
                    $title = trim((string) ($p['name'] ?? ''));
                }
                if ($title === '') {
                    $title = I18n::t('Untitled package');
                }
                $is_live = !empty($p['published']) || (!empty($p['status']) && $p['status'] === 'published');
                $lowest_price = 0;
                foreach (['quad', 'triple', 'double', 'single'] as $k) {
                    $v = (int) ($p['prices'][$k] ?? ($p['price_' . $k] ?? 0));
                    if ($v > 0 && ($lowest_price === 0 || $v < $lowest_price)) {
                        $lowest_price = $v;
                    }
                }
                $edit_url = admin_url('admin.php?page=pothik-package&package=' . urlencode((string) ($p['id'] ?? '')));

                echo '<div class="mini-pkg">';
                echo '<span class="thumb" style="background:linear-gradient(135deg,' . $pair[0] . ',' . $pair[1] . ')">' . $days . '<small>' . esc_html(I18n::t('DAYS')) . '</small></span>';
                echo '<div class="m"><b><a href="' . esc_url($edit_url) . '" style="color:inherit;text-decoration:none">' . esc_html($title) . '</a></b>';
                if ($lowest_price > 0) {
                    echo '<span class="sub">' . esc_html(I18n::t('from')) . ' ' . Format::money($lowest_price) . '</span>';
                } else {
                    echo '<span class="sub">—</span>';
                }
                echo '</div>';
                echo '<span class="pill pk-pill ' . ($is_live ? 'live' : 'draft') . '">' . esc_html(I18n::t($is_live ? 'Live' : 'Draft')) . '</span>';
                echo '</div>';
            }
        }
        echo '</div>'; // #miniPkgs
        echo '</div>'; // .card

        // Setup checklist card
        if ($show_setup) {
            echo '<div class="card pk-card setup-light">';
            echo '<div class="card-head pk-card-head"><h3>' . esc_html(I18n::t('Finish setting up')) . '</h3><span class="sub"><b>' . $completed_steps . '</b> ' . esc_html(I18n::t('of 5')) . '</span></div>';
            echo '<div class="meter"><i data-pk-w="' . $progress_pct . '" style="width:' . $progress_pct . '%"></i></div>';
            echo '<ul>';
            echo '<li class="' . ($s1 ? 'ok' : '') . '"><a href="' . esc_url(admin_url('admin.php?page=pothik-settings')) . '"><span class="tick">' . ($s1 ? '<svg class="i sm"><use href="#i-check"/></svg>' : '') . '</span><span>' . esc_html(I18n::t('Add agency name & phone')) . '</span></a></li>';
            echo '<li class="' . ($s3 ? 'ok' : '') . '"><a href="' . esc_url(admin_url('admin.php?page=pothik-settings')) . '"><span class="tick">' . ($s3 ? '<svg class="i sm"><use href="#i-check"/></svg>' : '') . '</span><span>' . esc_html(I18n::t('Set WhatsApp number')) . '</span></a></li>';
            echo '<li class="' . ($s2 ? 'ok' : '') . '"><a href="' . esc_url(admin_url('admin.php?page=pothik-package')) . '"><span class="tick">' . ($s2 ? '<svg class="i sm"><use href="#i-check"/></svg>' : '') . '</span><span>' . esc_html(I18n::t('Create your first package')) . '</span></a></li>';
            echo '<li class="' . ($s4 ? 'ok' : '') . '"><a href="' . esc_url(admin_url('admin.php?page=pothik-settings')) . '"><span class="tick">' . ($s4 ? '<svg class="i sm"><use href="#i-check"/></svg>' : '') . '</span><span>' . esc_html(I18n::t('Upload agency logo')) . '</span></a></li>';
            echo '<li class="' . ($s5 ? 'ok' : '') . '"><a href="' . esc_url(admin_url('admin.php?page=pothik-package')) . '"><span class="tick">' . ($s5 ? '<svg class="i sm"><use href="#i-check"/></svg>' : '') . '</span><span>' . esc_html(I18n::t('Publish a package')) . '</span></a></li>';
            echo '</ul>';
            echo '</div>'; // .setup-light
        }

        echo '</div>'; // .grid
        echo '</div>'; // .desk2

        // 7. PRO CARD
        if (!empty($locked_modules)) {
            echo '<div class="card pk-card pro-card">';
            echo '<div class="ph"><span class="eyebrow">' . esc_html(I18n::t('Pro')) . '</span><a class="link" href="' . esc_url(admin_url('admin.php?page=pothik-pro')) . '">' . esc_html(I18n::t('Docs')) . '</a></div>';
            echo '<div class="pro-inner">';
            echo '<div><h4>' . esc_html(I18n::t('Pothik Pro')) . ' <span class="pill pk-pill draft plain" style="background:var(--gold-100);color:#8a6414">PRO</span></h4>';
            echo '<p>' . esc_html(I18n::t('Run the whole season: departures, pilgrim files, rooming, installments and the visa board.')) . '</p>';
            echo '<a class="btn pk-btn btn-primary pk-btn-primary btn-sm" href="' . esc_url(admin_url('admin.php?page=pothik-pro')) . '">' . esc_html(I18n::t('Learn about Pro')) . '</a></div>';
            echo '<div class="pro-chips">';
            echo '<span><svg class="i sm"><use href="#i-plane"/></svg>' . esc_html(I18n::t('Departures')) . '</span>';
            echo '<span><svg class="i sm"><use href="#i-users"/></svg>' . esc_html(I18n::t('Pilgrim file')) . '</span>';
            echo '<span><svg class="i sm"><use href="#i-bed"/></svg>' . esc_html(I18n::t('Rooming')) . '</span>';
            echo '<span><svg class="i sm"><use href="#i-wallet"/></svg>' . esc_html(I18n::t('Installments')) . '</span>';
            echo '<span><svg class="i sm"><use href="#i-shield"/></svg>' . esc_html(I18n::t('Visa board')) . '</span>';
            echo '</div>'; // .pro-chips
            echo '</div>'; // .pro-inner
            echo '</div>'; // .pro-card
        }

        // 8. DEMO FOOTER
        echo '<div class="pk-demo-footer">';
        echo '<span>' . esc_html(I18n::t('Trying things out?')) . '</span>';
        if ($has_demo) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('pothik_demo_data');
            echo '<input type="hidden" name="action" value="pothik_remove_demo">';
            echo '<button type="submit">' . esc_html(I18n::t('Remove demo data')) . '</button>';
            echo '</form>';
        } else {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('pothik_demo_data');
            echo '<input type="hidden" name="action" value="pothik_load_demo">';
            echo '<button type="submit">' . esc_html(I18n::t('Load demo data')) . '</button>';
            echo '</form>';
        }
        echo '</div>'; // .pk-demo-footer

        echo '</section>'; // .screen

        Chrome::close();
    }

    public static function pro(): void
    {
        $upgrade_url = defined('POTHIK_UPGRADE_URL') ? POTHIK_UPGRADE_URL : 'https://thereadscope.com/pothik';
        $upgrade_url = apply_filters('pothik/upgrade_url', $upgrade_url);

        Chrome::open('Pothik Pro', 'Advanced Umrah & Hajj agency operations management.');

        echo '<section class="screen" data-screen="pro">';

        // 1. Hero
        echo '<div class="pro-hero pk-pro-hero">';
        echo '<span class="pill pk-pill pro-badge">Pothik Pro</span>';
        echo '<h1>' . sprintf(esc_html(I18n::t('Run the whole Umrah season %s')), '<span>' . esc_html(I18n::t('from one desk')) . '</span>') . '</h1>';
        echo '<p>' . esc_html(I18n::t('Departures, pilgrim files, rooming lists, installments and the visa board — built for Bangladeshi agencies.')) . '</p>';
        echo '<a class="btn pk-btn pk-btn-gold btn-gold btn-lg" href="' . esc_url($upgrade_url) . '" target="_blank" rel="noopener">' . esc_html(I18n::t('Upgrade to Pro')) . ' <svg class="i"><use href="#i-arrow"/></svg></a>';
        echo '</div>'; // .pro-hero

        // 2. Feature Cards (6 features)
        echo '<div class="pro-feats pk-pro-feats">';
        echo '<div class="pf pk-pf"><span class="kpi-ico"><svg class="i"><use href="#i-plane"/></svg></span><h3>' . esc_html(I18n::t('Departures board')) . '</h3><p>' . esc_html(I18n::t('Flight dates, seats left and group or private quotas at a glance.')) . '</p></div>';
        echo '<div class="pf pk-pf"><span class="kpi-ico"><svg class="i"><use href="#i-passport"/></svg></span><h3>' . esc_html(I18n::t('Pilgrim file')) . '</h3><p>' . esc_html(I18n::t('Passport scans, mahram and family links, visa column per pilgrim.')) . '</p></div>';
        echo '<div class="pf pk-pf"><span class="kpi-ico"><svg class="i"><use href="#i-bed"/></svg></span><h3>' . esc_html(I18n::t('Rooming lists')) . '</h3><p>' . esc_html(I18n::t('Separate Makkah and Madinah bed lists, auto-filled by share.')) . '</p></div>';
        echo '<div class="pf pk-pf"><span class="kpi-ico"><svg class="i"><use href="#i-wallet"/></svg></span><h3>' . esc_html(I18n::t('Installment ledger')) . '</h3><p>' . esc_html(I18n::t('Partial payments, balance due, bKash & Nagad matching.')) . '</p></div>';
        echo '<div class="pf pk-pf"><span class="kpi-ico"><svg class="i"><use href="#i-shield"/></svg></span><h3>' . esc_html(I18n::t('Visa board')) . '</h3><p>' . esc_html(I18n::t('Staff move each file through statuses. Issue happens on Masar.')) . '</p></div>';
        echo '<div class="pf pk-pf"><span class="kpi-ico"><svg class="i"><use href="#i-users"/></svg></span><h3>' . esc_html(I18n::t('Agent commissions')) . '</h3><p>' . esc_html(I18n::t('Sub-agent leads and commission reports.')) . '</p></div>';
        echo '</div>'; // .pro-feats

        // 3. Comparison Table
        $comparison = [
            [I18n::t('Package landing pages'), '40', 'Unlimited'],
            [I18n::t('Inquiry form, WhatsApp, email alerts'), '✓', '✓'],
            [I18n::t('Payment receipts'), '1 latest', 'Full history'],
            [I18n::t('Flight & group departures board'), '—', '✓'],
            [I18n::t('Pilgrim passport & visa tracking'), '—', '✓'],
            [I18n::t('Makkah & Madinah bed / rooming lists'), '—', '✓'],
            [I18n::t('Installment ledger & bKash matching'), '—', '✓'],
            [I18n::t('Visa board with Masar status tracking'), '—', '✓'],
            [I18n::t('Sub-agent commission reports'), '—', '✓'],
            [I18n::t('Direct 1-on-1 agency support'), 'Community', 'Priority VIP'],
        ];

        echo '<div class="card pk-card">';
        echo '<div class="tbl-wrap pk-tbl-wrap">';
        echo '<table class="tbl pk-tbl cmp pk-cmp">';
        echo '<thead><tr><th>' . esc_html(I18n::t('Feature')) . '</th><th>' . esc_html(I18n::t('Free Version')) . '</th><th class="th-pro">' . esc_html(I18n::t('Pothik Pro')) . '</th></tr></thead>';
        echo '<tbody>';
        foreach ($comparison as $row) {
            echo '<tr>';
            echo '<td><b>' . esc_html($row[0]) . '</b></td>';
            $free_class = $row[1] === '✓' ? 'yes pk-yes' : ($row[1] === '—' ? 'no' : '');
            echo '<td class="' . esc_attr($free_class) . '">' . esc_html($row[1]) . '</td>';
            $pro_class = $row[2] === '✓' ? 'yes pk-yes' : '';
            echo '<td class="' . esc_attr($pro_class) . '"><strong class="pro-val">' . esc_html($row[2]) . '</strong></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>'; // .tbl-wrap
        echo '</div>'; // .card

        // Bottom CTA
        echo '<div class="pro-cta-bottom">';
        echo '<a class="btn pk-btn pk-btn-gold btn-gold btn-lg" href="' . esc_url($upgrade_url) . '" target="_blank" rel="noopener">' . esc_html(I18n::t('Upgrade to Pothik Pro Now')) . ' <svg class="i"><use href="#i-arrow"/></svg></a>';
        echo '</div>';

        echo '</section>'; // .screen

        Chrome::close();
    }

    /**
     * @param array<string, mixed> $module
     */
    public static function locked(array $module): void
    {
        self::pro();
    }

    public static function package(): void
    {
        $id = isset($_GET['package']) ? sanitize_key(wp_unslash((string) $_GET['package'])) : '';
        if ($id !== '') {
            $package = PackageStore::find($id);
            if ($package === null) {
                Chrome::open('Packages', 'Each package keeps its own days, hotels and prices.');
                echo '<p class="pk-empty">' . esc_html(I18n::t('Package not found.')) . '</p>';
                Chrome::close();
                return;
            }
            self::package_form($package);
            return;
        }

        self::package_list();
    }

    private static function package_list(): void
    {
        $packages = PackageStore::all();
        $total_count = count($packages);
        $is_pro = defined('POTHIK_PRO_FILE');
        $live_count = 0;
        $draft_count = 0;
        foreach ($packages as $pkg) {
            $is_live = !empty($pkg['published']) || (($pkg['status'] ?? 'draft') === 'published');
            if ($is_live) {
                $live_count++;
            } else {
                $draft_count++;
            }
        }
        $usage_pct = min(100, round(($total_count / 40) * 100, 1));

        $actions_html = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="inline-form">'
            . wp_nonce_field('pothik_new_package', '_wpnonce', true, false)
            . '<input type="hidden" name="action" value="pothik_new_package">'
            . '<button type="submit" class="pk-btn pk-btn-primary pk-btn-lg btn btn-primary btn-lg"><svg class="i"><use href="#i-plus"/></svg>' . esc_html(I18n::t('New package')) . '</button>'
            . '</form>';

        Chrome::open('Packages', 'Each package becomes a landing page with prices, hotels and a WhatsApp button.', $actions_html);
        Chrome::saved();

        if (isset($_GET['error']) && $_GET['error'] === 'limit') {
            echo '<div class="pk-card limit-banner">';
            echo '<div class="flex-center">';
            echo '<svg class="i" style="color:var(--gold)"><use href="#i-spark"/></svg>';
            echo '<span>' . esc_html(I18n::t('Free version is limited to 40 packages. Upgrade to Pothik Pro for unlimited packages.')) . '</span>';
            echo '</div>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=pothik-pro')) . '" class="pk-btn pk-btn-gold pk-btn-sm">' . esc_html(I18n::t('Upgrade to Pro')) . ' &rarr;</a>';
            echo '</div>';
        }

        echo '<section class="screen" data-screen="packages">';

        // Toolbar
        echo '<div class="toolbar">';
        echo '<label class="search pk-search"><svg class="i sm"><use href="#i-search"/></svg><input id="pkgSearch" placeholder="' . esc_attr(I18n::t('Search packages or hotels…')) . '"></label>';
        echo '<div class="chips pk-chips" id="pkgChips">';
        echo '<button class="chip pk-chip on" type="button" data-f="">' . esc_html(I18n::t('All')) . ' <b>' . $total_count . '</b></button>';
        echo '<button class="chip pk-chip" type="button" data-f="live">' . esc_html(I18n::t('Published')) . ' <b>' . $live_count . '</b></button>';
        echo '<button class="chip pk-chip" type="button" data-f="draft">' . esc_html(I18n::t('Draft')) . ' <b>' . $draft_count . '</b></button>';
        echo '</div>';
        echo '<span class="spacer"></span>';
        if (!$is_pro) {
            echo '<div class="usage pk-usage">' . sprintf(esc_html(I18n::t('%d of 40 packages')), $total_count) . '<div class="meter pk-meter"><i data-pk-w="' . esc_attr($usage_pct) . '" style="width:' . esc_attr($usage_pct) . '%"></i></div></div>';
        }
        echo '</div>'; // .toolbar

        // Grid
        echo '<div class="pkg-grid pk-pkg-grid" id="pkgGrid">';
        if ($total_count === 0) {
            echo '<div class="pk-card pk-empty empty empty-card">';
            echo '<div class="em-ico"><svg class="i lg" style="width:48px;height:48px;color:var(--muted)"><use href="#i-box"/></svg></div>';
            echo '<h3>' . esc_html(I18n::t('Create your first Umrah package')) . '</h3>';
            echo '<p class="sub">' . esc_html(I18n::t('Each package gets a dedicated mobile-friendly landing page with pricing, hotels and instant WhatsApp inquiry.')) . '</p>';
            echo '<div class="empty-actions">';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('pothik_new_package');
            echo '<input type="hidden" name="action" value="pothik_new_package">';
            echo '<button type="submit" class="pk-btn pk-btn-primary btn btn-primary"><svg class="i"><use href="#i-plus"/></svg>' . esc_html(I18n::t('New package')) . '</button>';
            echo '</form>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('pothik_demo_data');
            echo '<input type="hidden" name="action" value="pothik_load_demo">';
            echo '<button type="submit" class="pk-btn pk-btn-ghost btn btn-ghost">' . esc_html(I18n::t('Load demo data')) . '</button>';
            echo '</form>';
            echo '</div>'; // actions
            echo '</div>'; // .empty
        } else {
            foreach ($packages as $pkg) {
                self::package_card($pkg);
            }
            // Dashed New Package card as last item
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="form-contents">';
            wp_nonce_field('pothik_new_package');
            echo '<input type="hidden" name="action" value="pothik_new_package">';
            echo '<button type="submit" class="pkg-add pk-pkg-add">';
            echo '<div>';
            echo '<div class="plus"><svg class="i lg"><use href="#i-plus"/></svg></div>';
            echo '<b>' . esc_html(I18n::t('New package')) . '</b>';
            echo '<div class="sub">' . esc_html(I18n::t('Start blank or copy an existing one')) . '</div>';
            echo '</div>';
            echo '</button>';
            echo '</form>';
        }
        echo '</div>'; // .pkg-grid

        echo '</section>'; // .screen

        Chrome::close();
    }

    /**
     * @param array<string, mixed> $package
     */
    public static function package_card(array $package): void
    {
        $id = (string) ($package['id'] ?? '');
        $color_pairs = [
            ['#173d30', '#2d6a4f'], // green
            ['#2b2110', '#c4a574'], // gold-brown
            ['#14273e', '#2563eb'], // blue
            ['#3a1d14', '#b4532a'], // clay
            ['#2d1b36', '#7e22ce'], // plum
        ];
        $color_idx = abs(crc32($id)) % count($color_pairs);
        $c1 = $color_pairs[$color_idx][0];
        $c2 = $color_pairs[$color_idx][1];

        $days = max(1, (int) ($package['days'] ?? 7));
        $is_live = !empty($package['published']) || (($package['status'] ?? 'draft') === 'published');
        $status_str = $is_live ? 'live' : 'draft';
        $nm = (int) ($package['nights_makkah'] ?? 0);
        $nd = (int) ($package['nights_madinah'] ?? 0);

        $title = I18n::field($package, 'title') ?: I18n::t('Untitled package');
        $mh = I18n::field($package, 'makkah_hotel') ?: I18n::t('Makkah hotel');
        $dh = I18n::field($package, 'madinah_hotel') ?: I18n::t('Madinah hotel');

        $ms = max(0, min(5, (int) ($package['makkah_stars'] ?? 4)));
        $ds = max(0, min(5, (int) ($package['madinah_stars'] ?? 4)));
        $m_stars_str = str_repeat('★', $ms) . str_repeat('☆', 5 - $ms);
        $d_stars_str = str_repeat('★', $ds) . str_repeat('☆', 5 - $ds);

        $mm = max(0, (int) ($package['makkah_meters'] ?? 150));
        $dm = max(0, (int) ($package['madinah_meters'] ?? 200));
        $m_dist_pct = max(6, min(100, 100 - (int) ($mm / 20)));
        $d_dist_pct = max(6, min(100, 100 - (int) ($dm / 20)));

        $quad_price = 0;
        if (isset($package['prices']['quad']) && is_numeric($package['prices']['quad'])) {
            $quad_price = (int) $package['prices']['quad'];
        } elseif (isset($package['price_quad']) && is_numeric($package['price_quad'])) {
            $quad_price = (int) $package['price_quad'];
        }

        $edit_url = admin_url('admin.php?page=pothik-package&package=' . rawurlencode($id));
        $public_url = PackagePage::url($id);
        $shortcode = '[pothik_package id="' . esc_attr($id) . '"]';

        echo '<article class="pkg pk-pkg" style="--c1:' . esc_attr($c1) . ';--c2:' . esc_attr($c2) . '" data-id="' . esc_attr($id) . '" data-status="' . esc_attr($status_str) . '" data-search="' . esc_attr(strtolower($title . ' ' . $mh . ' ' . $dh)) . '">';

        // Cover
        echo '<div class="pkg-cover pk-pkg-cover">';
        echo '<div class="row">';
        echo '<div class="days pk-days"><strong>' . $days . '</strong><span>' . esc_html(I18n::t('days')) . '</span></div>';
        echo '<span class="pill pk-pill ' . ($is_live ? 'live' : 'draft') . '">' . esc_html(I18n::t($is_live ? 'Published' : 'Draft')) . '</span>';
        echo '</div>'; // .row
        echo '<div class="nights pk-nights">';
        echo '<span>🕋 ' . sprintf(esc_html(I18n::t('%d nights Makkah')), $nm) . '</span>';
        echo '<span>🕌 ' . sprintf(esc_html(I18n::t('%d Madinah')), $nd) . '</span>';
        echo '</div>'; // .nights
        echo '</div>'; // .pkg-cover

        // Body
        echo '<div class="pkg-body pk-pkg-body">';
        echo '<h3>' . esc_html($title) . '</h3>';
        echo '<div class="hotel pk-hotel">';
        echo '<span class="city">' . esc_html(I18n::t('Makkah')) . '</span>';
        echo '<span class="nm">' . esc_html($mh) . '</span>';
        echo '<span class="stars">' . esc_html($m_stars_str) . '</span>';
        echo '<div class="dist"><div class="meter pk-meter"><i style="width:' . $m_dist_pct . '%"></i></div>' . $mm . ' m</div>';
        echo '</div>'; // .hotel
        echo '<div class="hotel pk-hotel">';
        echo '<span class="city">' . esc_html(I18n::t('Madinah')) . '</span>';
        echo '<span class="nm">' . esc_html($dh) . '</span>';
        echo '<span class="stars">' . esc_html($d_stars_str) . '</span>';
        echo '<div class="dist"><div class="meter pk-meter"><i style="width:' . $d_dist_pct . '%"></i></div>' . $dm . ' m</div>';
        echo '</div>'; // .hotel
        echo '</div>'; // .pkg-body

        // Foot
        echo '<div class="pkg-foot pk-pkg-foot">';
        echo '<div class="price"><small>' . esc_html(I18n::t('from, quad')) . '</small><b>' . Format::money($quad_price) . '</b></div>';
        echo '<div class="flex-center">';

        // Status switch form
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="pk-status-form inline-form">';
        wp_nonce_field('pothik_toggle_package_status');
        echo '<input type="hidden" name="action" value="pothik_toggle_package_status">';
        echo '<input type="hidden" name="package_id" value="' . esc_attr($id) . '">';
        echo '<input type="hidden" name="back" value="pothik-package">';
        echo '<label class="switch pk-switch" title="' . esc_attr(I18n::t($is_live ? 'Published' : 'Draft')) . '">';
        echo '<input type="checkbox" name="status" value="published"' . ($is_live ? ' checked' : '') . '>';
        echo '<span></span>';
        echo '</label>';
        echo '</form>';

        // Edit icon button
        echo '<a class="icon-btn pk-icon-btn" href="' . esc_url($edit_url) . '" title="' . esc_attr(I18n::t('Edit')) . '"><svg class="i"><use href="#i-edit"/></svg></a>';

        // Kebab popover menu
        echo '<div class="menu pk-menu">';
        echo '<button type="button" class="icon-btn pk-icon-btn" data-menu aria-expanded="false" title="' . esc_attr(I18n::t('More')) . '"><svg class="i"><use href="#i-dots"/></svg></button>';
        echo '<div class="menu-pop pk-menu-pop" hidden>';
        echo '<a href="' . esc_url($edit_url) . '"><svg class="i sm"><use href="#i-edit"/></svg>' . esc_html(I18n::t('Edit')) . '</a>';
        if ($public_url !== '') {
            echo '<a href="' . esc_url($public_url) . '" target="_blank" rel="noopener"><svg class="i sm"><use href="#i-eye"/></svg>' . esc_html(I18n::t('View on site')) . '</a>';
        }
        echo '<button type="button" data-pk-copy="' . esc_attr($shortcode) . '"><svg class="i sm"><use href="#i-copy"/></svg>' . esc_html(I18n::t('Copy shortcode')) . '</button>';

        // Duplicate form
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="pk-duplicate-form form-contents">';
        wp_nonce_field('pothik_copy_package');
        echo '<input type="hidden" name="action" value="pothik_copy_package">';
        echo '<input type="hidden" name="package_id" value="' . esc_attr($id) . '">';
        echo '<input type="hidden" name="back" value="pothik-package">';
        echo '<button type="submit"><svg class="i sm"><use href="#i-box"/></svg>' . esc_html(I18n::t('Duplicate')) . '</button>';
        echo '</form>';

        echo '<hr>';

        // Delete form & confirm
        echo '<div class="pk-del-wrap">';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="pk-delete-form form-contents">';
        wp_nonce_field('pothik_delete_package');
        echo '<input type="hidden" name="action" value="pothik_delete_package">';
        echo '<input type="hidden" name="package_id" value="' . esc_attr($id) . '">';
        echo '<input type="hidden" name="back" value="pothik-package">';
        echo '<button type="submit" class="danger pk-del-btn"><svg class="i sm"><use href="#i-trash"/></svg>' . esc_html(I18n::t('Delete')) . '</button>';
        echo '</form>';
        echo '<div class="pk-del-confirm" hidden>';
        echo '<span>' . esc_html(I18n::t('Delete this package?')) . '</span>';
        echo '<div class="pk-del-confirm-actions">';
        echo '<button type="button" class="pk-confirm-no">' . esc_html(I18n::t('Cancel')) . '</button>';
        echo '<button type="button" class="pk-confirm-yes danger">' . esc_html(I18n::t('Yes')) . '</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>'; // .pk-del-wrap

        echo '</div>'; // .menu-pop
        echo '</div>'; // .menu

        echo '</div>'; // foot actions
        echo '</div>'; // .pkg-foot
        echo '</article>';
    }

    /**
     * @param array<string, mixed> $package
     */
    private static function package_form(array $package): void
    {
        $id = (string) ($package['id'] ?? '');
        $is_bn = I18n::is_bn();
        $bn = is_array($package['bn'] ?? null) ? $package['bn'] : [];

        $text_val = static function (string $key) use ($package, $is_bn, $bn): string {
            if ($is_bn) {
                return (string) ($bn[$key] ?? '');
            }
            return (string) ($package[$key] ?? '');
        };

        $text_ph = static function (string $key) use ($package, $is_bn): string {
            if ($is_bn) {
                return (string) ($package[$key] ?? '');
            }
            return '';
        };

        $lines_val = static function (string $key) use ($package, $is_bn, $bn): string {
            if ($is_bn) {
                $raw = $bn[$key] ?? [];
                return is_array($raw) ? implode("\n", $raw) : '';
            }
            $raw = $package[$key] ?? [];
            return is_array($raw) ? implode("\n", $raw) : '';
        };

        $lines_ph = static function (string $key) use ($package, $is_bn): string {
            if ($is_bn) {
                $raw = $package[$key] ?? [];
                return is_array($raw) ? implode("\n", $raw) : '';
            }
            return '';
        };

        $prices = is_array($package['prices'] ?? null) ? $package['prices'] : [];
        $title = $text_val('title');
        $display_title = $title !== '' ? $title : I18n::t('Untitled package');
        $kicker = $text_val('kicker');
        $route = $text_val('route');
        $days = max(1, (int) ($package['days'] ?? 7));
        $nm = (int) ($package['nights_makkah'] ?? 4);
        $nd = (int) ($package['nights_madinah'] ?? 2);
        $group_note = $text_val('group_note');

        $mh = $text_val('makkah_hotel');
        $m_sim = $text_val('makkah_similar');
        $ms = max(1, min(5, (int) ($package['makkah_stars'] ?? 4)));
        $mm = (int) ($package['makkah_meters'] ?? 150);

        $dh = $text_val('madinah_hotel');
        $d_sim = $text_val('madinah_similar');
        $ds = max(1, min(5, (int) ($package['madinah_stars'] ?? 4)));
        $dm = (int) ($package['madinah_meters'] ?? 100);

        $pq = (int) ($prices['quad'] ?? 0);
        $pt = (int) ($prices['triple'] ?? 0);
        $pd = (int) ($prices['double'] ?? 0);
        $ps = (int) ($prices['single'] ?? 0);
        $pcb = (int) ($prices['child_bed'] ?? 0);
        $pcnb = (int) ($prices['child_nobed'] ?? 0);
        $pinf = (int) ($prices['infant'] ?? 0);
        $food_note = $text_val('food_note');

        $inclusions_str = $lines_val('inclusions');
        $exclusions_str = $lines_val('exclusions');
        $itinerary_str = $lines_val('itinerary');
        $docs = $text_val('docs');
        $cancel = $text_val('cancel');

        $settings = Settings::get_all();
        $default_agency = $is_bn ? ($settings['agency_bn'] !== '' ? (string) $settings['agency_bn'] : (string) $settings['agency']) : (string) $settings['agency'];
        $agency_ph = $text_ph('agency') !== '' ? $text_ph('agency') : $default_agency;
        $phone_ph = (string) ($settings['phone'] ?? '');
        $wa_ph = (string) ($settings['whatsapp'] ?? '');

        $current_status = ($package['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
        $public_url = PackagePage::url($id);

        Chrome::open($display_title, 'Type the days, nights, hotels and prices. The site shows this package as you save it.');
        Chrome::saved();

        echo '<section class="screen" data-screen="editor">';

        // 1. Page head
        echo '<div class="page-head pk-page-head mb-18">';
        echo '<div>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=pothik-package')) . '" class="sub back-link"><svg class="i sm"><use href="#i-arrow"/></svg> ' . esc_html(I18n::t('All packages')) . '</a>';
        echo '<h1 id="edTitle">' . esc_html($display_title) . '</h1>';
        echo '</div>';
        echo '<div class="flex-center gap-10">';
        echo '<div class="seg pk-seg" id="statusSeg">';
        echo '<button type="button" class="' . ($current_status === 'draft' ? 'on' : '') . '" data-s="draft">' . esc_html(I18n::t('Draft')) . '</button>';
        echo '<button type="button" class="' . ($current_status === 'published' ? 'on' : '') . '" data-s="live">' . esc_html(I18n::t('Published')) . '</button>';
        echo '</div>';
        if ($public_url !== '') {
            echo '<a class="btn pk-btn pk-btn-ghost btn-ghost" href="' . esc_url($public_url) . '" target="_blank" rel="noopener"><svg class="i sm"><use href="#i-eye"/></svg>' . esc_html(I18n::t('Preview')) . '</a>';
        }
        echo '</div>'; // actions
        echo '</div>'; // .page-head

        // 2. 3-column Editor Container
        echo '<div class="ed pk-ed">';

        // Left nav
        echo '<nav class="ed-nav pk-ed-nav" id="edNav">';
        echo '<a href="#sec-basics" class="on ok"><span class="ed-st">✓</span>' . esc_html(I18n::t('Basics')) . '</a>';
        echo '<a href="#sec-hotels" class="ok"><span class="ed-st">✓</span>' . esc_html(I18n::t('Hotels')) . '</a>';
        echo '<a href="#sec-prices" class="ok"><span class="ed-st">✓</span>' . esc_html(I18n::t('Prices')) . '</a>';
        echo '<a href="#sec-journey" class="ok"><span class="ed-st">✓</span>' . esc_html(I18n::t('Journey & terms')) . '</a>';
        echo '<a href="#sec-contact" class="ok"><span class="ed-st">✓</span>' . esc_html(I18n::t('Contact')) . '</a>';
        echo '<div class="ed-progress pk-ed-progress"><span id="edPctText">80% ' . esc_html(I18n::t('complete')) . '</span><div class="meter pk-meter"><i id="edMeter" data-pk-w="80" style="width:80%"></i></div></div>';
        echo '</nav>';

        // Center form
        echo '<form id="edForm" class="pk-form pk-package-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" data-pk-dirty>';
        echo '<input type="hidden" name="action" value="pothik_save_package">';
        echo '<input type="hidden" name="package_id" value="' . esc_attr($id) . '">';
        echo '<input type="hidden" name="status" id="statusInput" value="' . esc_attr($current_status) . '">';
        wp_nonce_field('pothik_save_package');

        // Language note
        echo '<div class="lang-note pk-lang-note mb-18"><svg class="i"><use href="#i-globe"/></svg>';
        if ($is_bn) {
            echo esc_html(I18n::t('Editing বাংলা. Switch to English above to fill the English version — empty Bangla fields fall back to English.'));
        } else {
            echo esc_html(I18n::t('Editing English. Switch to বাংলা above to fill the Bangla version — empty Bangla fields fall back to English.'));
        }
        echo '</div>';

        // Section 1: Basics
        echo '<div class="card pk-card ed-sec pk-ed-sec" id="sec-basics">';
        echo '<h2><span class="num">1</span>' . esc_html(I18n::t('Basics')) . '</h2><p class="sub pk-sub">' . esc_html(I18n::t('What pilgrims see first on the landing page.')) . '</p>';
        echo '<div class="fgrid pk-fgrid">';
        echo '<label class="field pk-field span pk-span"><span>' . esc_html(I18n::t('Package name')) . '</span><input name="title" data-pv="title" value="' . esc_attr($title) . '" placeholder="' . esc_attr($text_ph('title')) . '" required></label>';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('Eyebrow')) . ' <em>' . esc_html(I18n::t('small line above title')) . '</em></span><input name="kicker" data-pv="kicker" value="' . esc_attr($kicker) . '" placeholder="' . esc_attr($text_ph('kicker')) . '"></label>';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('Flight direction')) . '</span><input name="route" data-pv="route" value="' . esc_attr($route) . '" placeholder="' . esc_attr($text_ph('route')) . '"></label>';
        echo '</div>'; // .fgrid
        echo '<div class="fgrid pk-fgrid three mt-14">';
        echo '<div class="field pk-field"><span>' . esc_html(I18n::t('Total days')) . '</span><div class="stepper pk-stepper"><button type="button" data-step="-1">−</button><input name="days" data-pv="days" value="' . esc_attr((string) $days) . '" inputmode="numeric"><button type="button" data-step="1">+</button></div></div>';
        echo '<div class="field pk-field"><span>' . esc_html(I18n::t('Makkah nights')) . '</span><div class="stepper pk-stepper"><button type="button" data-step="-1">−</button><input name="nights_makkah" data-pv="nm" value="' . esc_attr((string) $nm) . '" inputmode="numeric"><button type="button" data-step="1">+</button></div></div>';
        echo '<div class="field pk-field"><span>' . esc_html(I18n::t('Madinah nights')) . '</span><div class="stepper pk-stepper"><button type="button" data-step="-1">−</button><input name="nights_madinah" data-pv="nd" value="' . esc_attr((string) $nd) . '" inputmode="numeric"><button type="button" data-step="1">+</button></div></div>';
        echo '</div>'; // .three
        $fit = ($nm + $nd < $days);
        echo '<p class="hint' . (!$fit ? ' clay' : '') . '" id="nightWarn">' . ($fit ? '✓ ' . sprintf(esc_html(I18n::t('%d nights fit inside %d days')), ($nm + $nd), $days) : '⚠ ' . sprintf(esc_html(I18n::t('%d nights do not fit inside %d days')), ($nm + $nd), $days)) . '</p>';
        echo '<label class="field pk-field mt-14"><span>' . esc_html(I18n::t('Group note')) . '</span><input name="group_note" value="' . esc_attr($group_note) . '" placeholder="' . esc_attr($text_ph('group_note')) . '"></label>';
        echo '</div>'; // #sec-basics

        // Section 2: Hotels
        echo '<div class="card pk-card ed-sec pk-ed-sec" id="sec-hotels">';
        echo '<h2><span class="num">2</span>' . esc_html(I18n::t('Hotels')) . '</h2><p class="sub pk-sub">' . esc_html(I18n::t('Distance to the Haram sells more than stars — keep it honest.')) . '</p>';
        echo '<div class="fgrid pk-fgrid">';
        
        // Makkah Card
        echo '<div class="hotel-card pk-hotel-card">';
        echo '<h4><i>MK</i>' . esc_html(I18n::t('Makkah')) . '</h4>';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('Hotel')) . '</span><input name="makkah_hotel" data-pv="mh" value="' . esc_attr($mh) . '" placeholder="' . esc_attr($text_ph('makkah_hotel')) . '"></label>';
        echo '<label class="field pk-field mt-10"><span>' . esc_html(I18n::t('Or similar')) . '</span><input name="makkah_similar" value="' . esc_attr($m_sim) . '" placeholder="' . esc_attr($text_ph('makkah_similar')) . '"></label>';
        echo '<div class="field pk-field mt-10"><span>' . esc_html(I18n::t('Stars')) . '</span><div class="rate pk-rate" data-rate="ms" data-val="' . $ms . '"></div><input type="hidden" name="makkah_stars" id="makkahStarsInput" value="' . $ms . '"></div>';
        echo '<div class="field pk-field mt-6"><span>' . esc_html(I18n::t('Distance to the Haram')) . ' <em class="walk" data-walk="mm"></em></span><div class="range-row pk-range-row"><input type="range" min="0" max="2000" step="10" value="' . $mm . '" data-range="mm" id="makkahMetersRange"><input type="hidden" name="makkah_meters" id="makkahMetersInput" value="' . $mm . '"><output data-out="mm">' . $mm . ' m</output></div></div>';
        echo '</div>'; // Makkah card

        // Madinah Card
        echo '<div class="hotel-card pk-hotel-card">';
        echo '<h4><i>MD</i>' . esc_html(I18n::t('Madinah')) . '</h4>';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('Hotel')) . '</span><input name="madinah_hotel" data-pv="dh" value="' . esc_attr($dh) . '" placeholder="' . esc_attr($text_ph('madinah_hotel')) . '"></label>';
        echo '<label class="field pk-field mt-10"><span>' . esc_html(I18n::t('Or similar')) . '</span><input name="madinah_similar" value="' . esc_attr($d_sim) . '" placeholder="' . esc_attr($text_ph('madinah_similar')) . '"></label>';
        echo '<div class="field pk-field mt-10"><span>' . esc_html(I18n::t('Stars')) . '</span><div class="rate pk-rate" data-rate="ds" data-val="' . $ds . '"></div><input type="hidden" name="madinah_stars" id="madinahStarsInput" value="' . $ds . '"></div>';
        echo '<div class="field pk-field mt-6"><span>' . esc_html(I18n::t("Distance to Prophet's Mosque")) . ' <em class="walk" data-walk="dm"></em></span><div class="range-row pk-range-row"><input type="range" min="0" max="2000" step="10" value="' . $dm . '" data-range="dm" id="madinahMetersRange"><input type="hidden" name="madinah_meters" id="madinahMetersInput" value="' . $dm . '"><output data-out="dm">' . $dm . ' m</output></div></div>';
        echo '</div>'; // Madinah card

        echo '</div>'; // .fgrid
        echo '</div>'; // #sec-hotels

        // Section 3: Prices
        echo '<div class="card pk-card ed-sec pk-ed-sec" id="sec-prices">';
        echo '<h2><span class="num">3</span>' . esc_html(I18n::t('Prices per person, BDT')) . '</h2><p class="sub pk-sub">' . esc_html(I18n::t('Leave a tier at 0 to hide it on the site. The lowest price is shown as "from".')) . '</p>';
        echo '<div class="tiers pk-tiers">';
        echo '<label class="tier pk-tier feat"><div class="tt">' . esc_html(I18n::t('Quad')) . '<span class="beds"><i></i><i></i><i></i><i></i></span></div><input name="price_quad" data-money-in data-pv="pq" value="' . ($pq > 0 ? esc_attr(number_format($pq)) : '') . '"><div class="pp">' . esc_html(I18n::t('per person · shown as "from"')) . '</div></label>';
        echo '<label class="tier pk-tier"><div class="tt">' . esc_html(I18n::t('Triple')) . '<span class="beds"><i></i><i></i><i></i></span></div><input name="price_triple" data-money-in data-pv="pt" value="' . ($pt > 0 ? esc_attr(number_format($pt)) : '') . '"><div class="pp">' . esc_html(I18n::t('per person')) . '</div></label>';
        echo '<label class="tier pk-tier"><div class="tt">' . esc_html(I18n::t('Double')) . '<span class="beds"><i></i><i></i></span></div><input name="price_double" data-money-in value="' . ($pd > 0 ? esc_attr(number_format($pd)) : '') . '"><div class="pp">' . esc_html(I18n::t('per person')) . '</div></label>';
        echo '<label class="tier pk-tier"><div class="tt">' . esc_html(I18n::t('Single')) . '<span class="beds"><i></i></span></div><input name="price_single" data-money-in value="' . ($ps > 0 ? esc_attr(number_format($ps)) : '') . '"><div class="pp">' . esc_html(I18n::t('per person')) . '</div></label>';
        echo '<label class="tier pk-tier"><div class="tt">' . esc_html(I18n::t('Child + bed')) . '</div><input name="price_child_bed" data-money-in value="' . ($pcb > 0 ? esc_attr(number_format($pcb)) : '') . '"><div class="pp">2–11 years</div></label>';
        echo '<label class="tier pk-tier"><div class="tt">' . esc_html(I18n::t('Child, no bed')) . '</div><input name="price_child_nobed" data-money-in value="' . ($pcnb > 0 ? esc_attr(number_format($pcnb)) : '') . '"><div class="pp">2–11 years</div></label>';
        echo '<label class="tier pk-tier"><div class="tt">' . esc_html(I18n::t('Infant')) . '</div><input name="price_infant" data-money-in value="' . ($pinf > 0 ? esc_attr(number_format($pinf)) : '') . '"><div class="pp">under 2</div></label>';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('Food note')) . '</span><input name="food_note" value="' . esc_attr($food_note) . '" placeholder="' . esc_attr($text_ph('food_note')) . '"></label>';
        echo '</div>'; // .tiers
        echo '</div>'; // #sec-prices

        // Section 4: Journey & terms
        echo '<div class="card pk-card ed-sec pk-ed-sec" id="sec-journey">';
        echo '<h2><span class="num">4</span>' . esc_html(I18n::t('Journey & terms')) . '</h2><p class="sub pk-sub">' . esc_html(I18n::t('Type and press Enter to add. Drag to reorder.')) . '</p>';
        echo '<div class="field pk-field"><span>✓ ' . esc_html(I18n::t('Included')) . '</span><div class="tags pk-tags" data-tags="inc"><input placeholder="' . esc_attr(I18n::t('e.g. Return air ticket')) . '"></div><textarea name="inclusions" id="incArea" hidden>' . esc_textarea($inclusions_str) . '</textarea></div>';
        echo '<div class="field pk-field mt-14"><span>✕ ' . esc_html(I18n::t('Not included')) . '</span><div class="tags ex pk-tags" data-tags="exc"><input placeholder="' . esc_attr(I18n::t('e.g. Personal shopping')) . '"></div><textarea name="exclusions" id="excArea" hidden>' . esc_textarea($exclusions_str) . '</textarea></div>';
        echo '<div class="field pk-field mt-18"><span>' . esc_html(I18n::t('Day by day')) . '</span><ol class="timeline pk-timeline" id="tl"></ol><button type="button" class="btn pk-btn pk-btn-ghost btn-ghost btn-sm mt-8" id="addDay"><svg class="i sm"><use href="#i-plus"/></svg>' . esc_html(I18n::t('Add day')) . '</button><textarea name="itinerary" id="itinArea" hidden>' . esc_textarea($itinerary_str) . '</textarea></div>';
        echo '<div class="fgrid pk-fgrid mt-14">';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('Documents')) . '</span><input name="docs" value="' . esc_attr($docs) . '" placeholder="' . esc_attr($text_ph('docs')) . '"></label>';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('Cancellation')) . '</span><input name="cancel" value="' . esc_attr($cancel) . '" placeholder="' . esc_attr($text_ph('cancel')) . '"></label>';
        echo '</div>'; // .fgrid
        echo '</div>'; // #sec-journey

        // Section 5: Contact
        echo '<div class="card pk-card ed-sec pk-ed-sec" id="sec-contact">';
        echo '<h2><span class="num">5</span>' . esc_html(I18n::t('Contact for this package')) . '</h2><p class="sub pk-sub">' . esc_html(I18n::t('Leave empty to use the agency phone and WhatsApp from Settings.')) . '</p>';
        echo '<div class="fgrid pk-fgrid three">';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('Agency name')) . '</span><input name="agency" value="' . esc_attr($text_val('agency')) . '" placeholder="' . esc_attr($agency_ph) . '"></label>';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('Phone')) . '</span><input name="phone" value="' . esc_attr((string) ($package['phone'] ?? '')) . '" placeholder="' . esc_attr($phone_ph) . '"></label>';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('WhatsApp')) . '</span><input name="whatsapp" value="' . esc_attr((string) ($package['whatsapp'] ?? '')) . '" placeholder="' . esc_attr($wa_ph !== '' ? $wa_ph : '8801…') . '"></label>';
        echo '</div>'; // .fgrid.three
        echo '</div>'; // #sec-contact

        echo '</form>'; // #edForm

        // Right Phone Live Preview
        echo '<aside class="preview pk-preview">';
        echo '<div class="eyebrow ph-head"><span>' . esc_html(I18n::t('Live preview')) . '</span> <span class="pv-updating">● ' . esc_html(I18n::t('updating')) . '</span></div>';
        echo '<div class="phone pk-phone"><div class="phone-in pk-phone-in">';
        echo '<div class="pv-hero pk-pv-hero">';
        echo '<small data-pvo="kicker">' . esc_html($kicker ?: 'Umrah 2026 · Group') . '</small>';
        echo '<h5 data-pvo="title">' . esc_html($display_title) . '</h5>';
        echo '<div class="pv-chips pk-pv-chips"><span><b data-pvo="days">' . $days . '</b> ' . esc_html(I18n::t('days')) . '</span><span><b data-pvo="nm">' . $nm . '</b> ' . esc_html(I18n::t('Makkah')) . '</span><span><b data-pvo="nd">' . $nd . '</b> ' . esc_html(I18n::t('Madinah')) . '</span></div>';
        echo '</div>'; // .pv-hero
        echo '<div class="pv-body pk-pv-body">';
        echo '<div class="pv-box pk-pv-box"><small class="muted">' . esc_html(I18n::t('Makkah')) . '</small><b data-pvo="mh">' . esc_html($mh ?: 'Makkah hotel') . '</b><span class="muted" data-pvo="mmtxt">' . $mm . ' m ' . esc_html(I18n::t('to the Haram')) . '</span></div>';
        echo '<div class="pv-box pk-pv-box"><small class="muted">' . esc_html(I18n::t('Madinah')) . '</small><b data-pvo="dh">' . esc_html($dh ?: 'Madinah hotel') . '</b><span class="muted" data-pvo="dmtxt">' . $dm . ' m ' . esc_html(I18n::t("to the Prophet's Mosque")) . '</span></div>';
        echo '<div class="pv-price pk-pv-price">';
        echo '<div><small class="muted">' . esc_html(I18n::t('Quad')) . '</small><strong>৳<span data-pvo="pq">' . ($pq > 0 ? number_format($pq) : '—') . '</span></strong></div>';
        echo '<div><small class="muted">' . esc_html(I18n::t('Triple')) . '</small><strong>৳<span data-pvo="pt">' . ($pt > 0 ? number_format($pt) : '—') . '</span></strong></div>';
        echo '</div>'; // .pv-price
        echo '<div class="pv-box pk-pv-box"><small class="muted">' . esc_html(I18n::t('Flight')) . '</small><b data-pvo="route">' . esc_html($route ?: 'Dhaka → Jeddah → Madinah → Dhaka') . '</b></div>';
        echo '<span class="pv-cta pk-pv-cta">' . esc_html(I18n::t('Chat with the office on WhatsApp')) . '</span>';
        echo '</div>'; // .pv-body
        echo '</div></div>'; // .phone-in, .phone
        echo '</aside>'; // .preview

        echo '</div>'; // .ed

        // Sticky save bar
        echo '<div class="savebar pk-savebar" id="savebar">';
        echo '<span class="dot"></span><span>' . esc_html(I18n::t('Unsaved changes')) . '</span>';
        echo '<button type="button" class="btn pk-btn pk-btn-ghost btn-ghost btn-sm" id="discard">' . esc_html(I18n::t('Discard')) . '</button>';
        echo '<button type="button" class="btn pk-btn pk-btn-gold btn-gold btn-sm" id="saveBtn">' . esc_html(I18n::t('Save package')) . ' <kbd>Ctrl S</kbd></button>';
        echo '</div>';

        echo '</section>'; // .screen

        Chrome::close();
    }

    public static function inquiries(): void
    {
        $page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $status = isset($_GET['status']) ? sanitize_key((string) $_GET['status']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash((string) $_GET['s'])) : '';
        $share = isset($_GET['share']) ? sanitize_key((string) $_GET['share']) : '';
        $per_page = 20;

        $paged_data = InquiryRepository::paginate($page, $per_page, $status, $search, $share);
        $counts = InquiryRepository::count_by_status();
        $total_pages = (int) ($paged_data['pages'] ?? 1);
        $rows = (array) ($paged_data['items'] ?? []);
        $total = (int) ($paged_data['total'] ?? 0);

        $actions_html = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="inline-form">'
            . wp_nonce_field('pothik_export_inquiries', '_wpnonce', true, false)
            . '<input type="hidden" name="action" value="pothik_export_inquiries">'
            . '<button type="submit" class="btn pk-btn pk-btn-ghost btn-ghost"><svg class="i sm"><use href="#i-down"/></svg>' . esc_html(I18n::t('Export CSV')) . '</button>'
            . '</form>';

        Chrome::open('Inquiries', 'Every name from the site form. Click a row to see details and reply.', $actions_html);
        Chrome::saved();

        echo '<section class="screen" data-screen="inquiries">';

        // Toolbar
        echo '<div class="toolbar pk-toolbar">';
        echo '<div class="chips pk-chips" id="inqChips">';
        echo '<button type="button" class="chip pk-chip ' . ($status === '' ? 'on' : '') . '" data-f="">' . esc_html(I18n::t('All')) . ' <b data-c="all">' . $counts['all'] . '</b></button>';
        echo '<button type="button" class="chip pk-chip ' . ($status === 'waiting' ? 'on' : '') . '" data-f="waiting">' . esc_html(I18n::t('Waiting')) . ' <b data-c="wait">' . $counts['waiting'] . '</b></button>';
        echo '<button type="button" class="chip pk-chip ' . ($status === 'replied' ? 'on' : '') . '" data-f="replied">' . esc_html(I18n::t('Replied')) . ' <b data-c="done">' . $counts['replied'] . '</b></button>';
        echo '</div>'; // .chips

        echo '<label class="search pk-search"><svg class="i sm"><use href="#i-search"/></svg><input id="inqSearch" value="' . esc_attr($search) . '" placeholder="' . esc_attr(I18n::t('Search name, phone or city  ( / )')) . '"></label>';

        echo '<select class="input pk-field share-sel" id="shareSel">';
        echo '<option value="">' . esc_html(I18n::t('Any share')) . '</option>';
        foreach (['quad', 'triple', 'double', 'single'] as $sh) {
            echo '<option value="' . esc_attr($sh) . '"' . selected($share, $sh, false) . '>' . esc_html(I18n::choice('share', $sh)) . '</option>';
        }
        echo '</select>';
        echo '</div>'; // .toolbar

        // Sticky Bulk Actions Bar
        echo '<form id="bulkForm" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('pothik_bulk_inquiries');
        echo '<input type="hidden" name="action" value="pothik_bulk_inquiries">';
        echo '<input type="hidden" name="op" id="bulkOp" value="reply">';
        echo '<div class="bulk pk-bulk" id="bulk" hidden>';
        echo '<b id="bulkN">0 ' . esc_html(I18n::t('selected')) . '</b>';
        echo '<span class="spacer"></span>';
        echo '<button type="button" class="btn pk-btn pk-btn-ghost btn-ghost" id="bulkReply"><svg class="i sm"><use href="#i-check"/></svg>' . esc_html(I18n::t('Mark replied')) . '</button>';
        echo '<button type="button" class="btn pk-btn pk-btn-ghost btn-ghost" id="bulkExport"><svg class="i sm"><use href="#i-down"/></svg>' . esc_html(I18n::t('Export')) . '</button>';
        echo '<div id="bulkDelWrap" class="bulk-del-wrap">';
        echo '<button type="button" class="btn pk-btn pk-btn-ghost btn-ghost bulk-del-btn" id="bulkDel"><svg class="i sm"><use href="#i-trash"/></svg>' . esc_html(I18n::t('Delete')) . '</button>';
        echo '<div id="bulkDelConfirm" class="bulk-del-confirm" hidden>';
        echo '<span id="bulkDelPrompt">' . esc_html(I18n::t('Delete selected?')) . '</span>';
        echo '<button type="button" id="bulkDelYes" class="bulk-del-yes">' . esc_html(I18n::t('Yes')) . '</button>';
        echo '<button type="button" id="bulkDelCancel" class="bulk-del-cancel">' . esc_html(I18n::t('Cancel')) . '</button>';
        echo '</div>'; // #bulkDelConfirm
        echo '</div>'; // #bulkDelWrap
        echo '</div>'; // #bulk

        // Table Card
        echo '<div class="card pk-card">';
        echo '<div class="tbl-wrap pk-tbl-wrap">';
        echo '<table class="tbl pk-tbl">';
        echo '<thead><tr>';
        echo '<th class="cb-th"><input type="checkbox" class="cb pk-cb" id="cbAll" aria-label="' . esc_attr(I18n::t('Select all')) . '"></th>';
        echo '<th>' . esc_html(I18n::t('Pilgrim')) . '</th>';
        echo '<th>' . esc_html(I18n::t('Phone')) . '</th>';
        echo '<th>' . esc_html(I18n::t('City')) . '</th>';
        echo '<th>' . esc_html(I18n::t('Share')) . '</th>';
        echo '<th>' . esc_html(I18n::t('Pilgrims')) . '</th>';
        echo '<th>' . esc_html(I18n::t('Status')) . '</th>';
        echo '<th>' . esc_html(I18n::t('Received')) . '</th>';
        echo '<th></th>';
        echo '</tr></thead>';

        echo '<tbody id="inqBody">';
        if (!empty($rows)) {
            foreach ($rows as $row) {
                echo self::inquiry_row_html($row);
            }
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>'; // .tbl-wrap

        // Empty state when filtered out
        echo '<div class="empty pk-empty" id="inqEmpty"' . (!empty($rows) ? ' hidden' : '') . '>';
        echo '<div class="em-ico"><svg class="i lg"><use href="#i-search"/></svg></div>';
        echo '<b>' . esc_html(I18n::t('Nothing matches')) . '</b>';
        echo '<div class="sub">' . esc_html(I18n::t('Try another name, phone or city.')) . '</div>';
        echo '</div>';

        // Pager
        $start_item = $total > 0 ? ($page - 1) * $per_page + 1 : 0;
        $end_item = min($total, $page * $per_page);
        echo '<div class="pager pk-pager" id="inqPager">';
        echo '<span id="inqShowing">' . sprintf(esc_html(I18n::t('Showing %d–%d of %d')), $start_item, $end_item, $total) . '</span>';
        if ($total_pages > 1) {
            echo '<div class="pg pk-pg">';
            for ($i = 1; $i <= $total_pages; $i++) {
                $url = add_query_arg(['page' => 'pothik-inquiries', 'paged' => $i, 'status' => $status, 's' => $search, 'share' => $share], admin_url('admin.php'));
                echo '<a href="' . esc_url($url) . '" class="btn pk-btn-sm ' . ($i === $page ? 'on' : '') . '" data-p="' . $i . '">' . $i . '</a>';
            }
            echo '</div>';
        }
        echo '</div>'; // .pager

        echo '</div>'; // .card
        echo '</form>'; // #bulkForm

        // Detail Drawer
        echo '<div class="drawer-bg pk-drawer-bg" id="drBg"></div>';
        echo '<aside class="drawer pk-drawer" id="drawer" aria-label="' . esc_attr(I18n::t('Inquiry details')) . '">';
        echo '<div class="dr-head pk-dr-head"><span class="ava pk-ava" id="drAva"></span><div class="dr-title-meta"><h3 id="drName"></h3><div class="sub" id="drPkg"></div></div><button type="button" class="icon-btn pk-icon-btn" id="drClose" aria-label="' . esc_attr(I18n::t('Close')) . '"><svg class="i"><use href="#i-x"/></svg></button></div>';
        echo '<div class="dr-body pk-dr-body">';
        echo '<div id="drState"></div>';
        echo '<div class="facts pk-facts" id="drFacts"></div>';
        echo '<div class="note pk-note" id="drNote" hidden></div>';
        echo '<div><div class="eyebrow">' . esc_html(I18n::t('Quick WhatsApp replies')) . '</div>';
        echo '<div class="templates pk-templates">';
        $templates = [
            I18n::t('Assalamu alaikum! Thank you for your Umrah inquiry. Our office will call you today.'),
            I18n::t('Here are the full prices and hotel details for your package…'),
            I18n::t('Please send passport copies to book your seat.'),
        ];
        $templates = apply_filters('pothik/reply_templates', $templates);
        foreach ($templates as $tpl) {
            echo '<button type="button" class="tpl pk-tpl" data-tpl="' . esc_attr($tpl) . '"><svg class="i"><use href="#i-wa"/></svg>' . esc_html($tpl) . '</button>';
        }
        echo '</div></div>'; // .templates
        echo '</div>'; // .dr-body
        echo '<div class="dr-foot pk-dr-foot">';
        echo '<a class="btn pk-btn pk-btn-ghost btn-ghost" id="drCall" href="#"><svg class="i sm"><use href="#i-phone"/></svg>' . esc_html(I18n::t('Call')) . '</a>';
        echo '<a class="btn pk-btn pk-btn-wa btn-wa" id="drWa" href="#" target="_blank" rel="noopener"><svg class="i sm"><use href="#i-wa"/></svg>' . esc_html(I18n::t('WhatsApp')) . '</a>';
        echo '<button type="button" class="btn pk-btn pk-btn-primary btn-primary" id="drMark"><svg class="i sm"><use href="#i-check"/></svg><span id="drMarkText">' . esc_html(I18n::t('Mark replied')) . '</span></button>';
        echo '</div>'; // .dr-foot
        echo '</aside>'; // #drawer

        echo '</section>'; // .screen

        Chrome::close();
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function inquiry_row_html(array $row): string
    {
        $id = (string) ($row['id'] ?? '');
        $name = (string) ($row['name'] ?? '');
        $phone = (string) ($row['phone'] ?? '');
        $whatsapp = (string) ($row['whatsapp'] ?? '') ?: $phone;
        $city = (string) ($row['city'] ?? '');
        $share_key = (string) ($row['share'] ?? 'quad');
        $share_label = I18n::choice('share', $share_key);
        $pkg_title = (string) ($row['package_title'] ?? I18n::t('Umrah package'));
        $adults = (int) ($row['adults'] ?? 1);
        $children = (int) ($row['children'] ?? 0);
        $infants = (int) ($row['infants'] ?? 0);
        $trip = (string) ($row['trip'] ?? 'umrah');
        $note = (string) ($row['note'] ?? '');
        $replied = !empty($row['replied']);
        $time_diff = human_time_diff(strtotime((string) ($row['created_gmt'] ?: $row['created'])), time()) . ' ' . I18n::t('ago');
        $full_date = (string) ($row['created'] ?? '');

        $bg = self::avatar_color($name);
        $initial = self::avatar_initial($name);
        $tel_href = Format::phone_href($phone);
        $wa_href = Format::whatsapp_href($whatsapp, I18n::t('Office reply about your Umrah inquiry.'));

        $html = '<tr class="inq-tr" data-id="' . esc_attr($id) . '"'
            . ' data-name="' . esc_attr($name) . '"'
            . ' data-phone="' . esc_attr($phone) . '"'
            . ' data-wa="' . esc_attr($whatsapp) . '"'
            . ' data-city="' . esc_attr($city) . '"'
            . ' data-pkg="' . esc_attr($pkg_title) . '"'
            . ' data-share="' . esc_attr($share_label) . '"'
            . ' data-adults="' . esc_attr((string) $adults) . '"'
            . ' data-children="' . esc_attr((string) $children) . '"'
            . ' data-infants="' . esc_attr((string) $infants) . '"'
            . ' data-trip="' . esc_attr(I18n::choice('trip', $trip)) . '"'
            . ' data-note="' . esc_attr($note) . '"'
            . ' data-time="' . esc_attr($time_diff) . '"'
            . ' data-replied="' . ($replied ? '1' : '0') . '"'
            . '>';

        // Col 1: Checkbox
        $html .= '<td><input type="checkbox" class="cb pk-cb" name="ids[]" value="' . esc_attr($id) . '" aria-label="' . esc_attr(I18n::t('Select')) . '"></td>';

        // Col 2: Pilgrim (avatar + name + pkg + demo chip)
        $html .= '<td><div class="who pk-who">';
        $html .= '<span class="ava pk-ava" style="background:' . esc_attr($bg) . '">' . esc_html($initial) . '</span>';
        $html .= '<div class="dr-title-meta"><b>' . esc_html($name) . '</b>';
        $html .= '<span class="sub pk-sub pkg-sub-cell">' . esc_html($pkg_title);
        if (!empty($row['demo'])) {
            $html .= ' <span class="pill pk-pill demo">' . esc_html(I18n::t('Demo')) . '</span>';
        }
        $html .= '</span></div></div></td>';

        // Col 3: Phone
        $html .= '<td><b class="cell-phone">' . esc_html($phone) . '</b></td>';

        // Col 4: City
        $html .= '<td>' . esc_html($city) . '</td>';

        // Col 5: Share
        $html .= '<td><span class="pill pk-pill plain">' . esc_html($share_label) . '</span></td>';

        // Col 6: Pilgrims chips
        $html .= '<td><div class="pax pk-pax"><span>👤 ' . $adults . '</span>';
        if ($children > 0) {
            $html .= '<span>🧒 ' . $children . '</span>';
        }
        if ($infants > 0) {
            $html .= '<span>👶 ' . $infants . '</span>';
        }
        $html .= '</div></td>';

        // Col 7: Status toggle pill button
        $html .= '<td><button type="button" class="pill pk-pill status-btn ' . ($replied ? 'done' : 'wait') . '" data-inq-id="' . esc_attr($id) . '" title="' . esc_attr(I18n::t('Click to toggle')) . '">' . esc_html(I18n::t($replied ? 'Replied' : 'Waiting')) . '</button></td>';

        // Col 8: Received time
        $html .= '<td class="sub pk-sub nowrap-cell" title="' . esc_attr($full_date) . '">' . esc_html($time_diff) . '</td>';

        // Col 9: Action icons
        $html .= '<td><div class="acts pk-acts">';
        if ($tel_href !== '') {
            $html .= '<a class="icon-btn pk-icon-btn" href="' . esc_url($tel_href) . '" title="' . esc_attr(I18n::t('Call')) . '"><svg class="i"><use href="#i-phone"/></svg></a>';
        }
        if ($wa_href !== '') {
            $html .= '<a class="icon-btn pk-icon-btn wa" href="' . esc_url($wa_href) . '" target="_blank" rel="noopener" title="' . esc_attr(I18n::t('WhatsApp')) . '"><svg class="i"><use href="#i-wa"/></svg></a>';
        }
        $html .= '</div></td>';

        $html .= '</tr>';
        return $html;
    }

    public static function ajax_inquiries_table(): void
    {
        if (!check_ajax_referer('pothik_admin', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $page = isset($_POST['paged']) ? max(1, absint($_POST['paged'])) : 1;
        $status = isset($_POST['status']) ? sanitize_key((string) $_POST['status']) : '';
        $search = isset($_POST['s']) ? sanitize_text_field(wp_unslash((string) $_POST['s'])) : '';
        $share = isset($_POST['share']) ? sanitize_key((string) $_POST['share']) : '';
        $per_page = 20;

        $paged_data = InquiryRepository::paginate($page, $per_page, $status, $search, $share);
        $counts = InquiryRepository::count_by_status();

        ob_start();
        foreach ($paged_data['items'] as $row) {
            echo self::inquiry_row_html($row);
        }
        $tbody_html = ob_get_clean();

        $start_item = $paged_data['total'] > 0 ? ($page - 1) * $per_page + 1 : 0;
        $end_item = min($paged_data['total'], $page * $per_page);
        $showing_text = sprintf(I18n::t('Showing %d–%d of %d'), $start_item, $end_item, $paged_data['total']);

        wp_send_json_success([
            'tbody' => $tbody_html,
            'total' => $paged_data['total'],
            'pages' => $paged_data['pages'],
            'page' => $paged_data['page'],
            'counts' => $counts,
            'showing' => $showing_text,
        ]);
    }

    public static function receipt(): void
    {
        $receipt = ReceiptStore::get();
        $settings = Settings::get_all();
        $is_bn = I18n::is_bn();

        $agency_name = (string) ($is_bn && !empty($settings['agency_bn']) ? $settings['agency_bn'] : ($settings['agency'] ?: 'Pothik Umrah'));
        $phone = (string) ($settings['phone'] ?: '');
        $license = (string) ($settings['license'] ?: '');
        $logo_url = (string) ($settings['logo_url'] ?? '');

        $amount = (int) ($receipt['amount'] ?? 40000);
        $current_channel = I18n::canonical('channel', (string) ($receipt['channel'] ?? 'bkash'));
        $name = (string) ($receipt['name'] ?? '');
        $trx = (string) ($receipt['trx'] ?? '');
        $date = (string) ($receipt['date'] ?? gmdate('Y-m-d'));
        $note = (string) ($receipt['note'] ?? '');
        $words = Format::words($amount, $is_bn);

        $recent_inquiries = InquiryRepository::recent(20);
        $pilgrim_names = [];
        foreach ($recent_inquiries as $inq) {
            $inq_name = trim((string) ($inq['name'] ?? ''));
            if ($inq_name !== '' && !in_array($inq_name, $pilgrim_names, true)) {
                $pilgrim_names[] = $inq_name;
            }
        }

        Chrome::open('Payment receipt', 'Fill the form — the slip on the right updates as you type. Print it or paste it on WhatsApp.');
        Chrome::saved();

        echo '<section class="screen" data-screen="receipt">';
        echo '<div class="rc pk-rc">';

        // Left column: Form
        echo '<form id="rcForm" class="grid pk-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" data-pk-dirty>';
        wp_nonce_field('pothik_save_receipt');
        echo '<input type="hidden" name="action" value="pothik_save_receipt">';

        // Card 1: Amount
        echo '<div class="card pk-card card-pad pk-card-pad">';
        echo '<div class="field pk-field"><span>' . esc_html(I18n::t('Amount received')) . '</span></div>';
        echo '<div class="amount-big pk-amount-big"><i>৳</i><input name="amount" id="rcAmt" value="' . ($amount > 0 ? esc_attr(number_format($amount)) : '') . '" inputmode="numeric" data-money-in required></div>';
        echo '<div class="in-words pk-in-words" id="rcWords">' . esc_html($words) . '</div>';
        echo '<div class="quick-amt pk-quick-amt mt-12">';
        echo '<button type="button" class="chip pk-chip" data-amt="10000">+10,000</button>';
        echo '<button type="button" class="chip pk-chip" data-amt="25000">+25,000</button>';
        echo '<button type="button" class="chip pk-chip" data-amt="50000">+50,000</button>';
        echo '<button type="button" class="chip pk-chip" data-amt="0">' . esc_html(I18n::t('Clear')) . '</button>';
        echo '</div>'; // .quick-amt
        echo '</div>'; // card 1

        // Card 2: Channels
        echo '<div class="card pk-card card-pad pk-card-pad">';
        echo '<div class="field pk-field"><span>' . esc_html(I18n::t('Payment channel')) . '</span></div>';
        echo '<div class="channels pk-channels mt-8">';

        $channel_defs = [
            'bkash' => ['var(--bkash)', 'b', 'bKash'],
            'nagad' => ['var(--nagad)', 'N', 'Nagad'],
            'rocket' => ['var(--rocket)', 'R', 'Rocket'],
            'bank' => ['var(--bank)', '🏦', 'Bank'],
            'cash' => ['var(--cash)', '৳', 'Cash'],
        ];

        foreach ($channel_defs as $ch_key => $ch_meta) {
            $is_checked = ($current_channel === $ch_key);
            echo '<label class="ch pk-ch" style="--c:' . esc_attr($ch_meta[0]) . '">';
            echo '<input type="radio" name="channel" value="' . esc_attr($ch_key) . '"' . ($is_checked ? ' checked' : '') . '>';
            echo '<span><i>' . esc_html($ch_meta[1]) . '</i>' . esc_html(I18n::choice('channel', $ch_key)) . '</span>';
            echo '</label>';
        }
        echo '</div>'; // .channels
        echo '</div>'; // card 2

        // Card 3: Paid by, Date, Trx, Note
        echo '<div class="card pk-card card-pad pk-card-pad">';
        echo '<div class="fgrid pk-fgrid">';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('Paid by')) . '</span><input name="name" id="rcName" value="' . esc_attr($name) . '" list="pilgrimsList" placeholder="' . esc_attr(I18n::t('Pilgrim name')) . '" required></label>';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('Date')) . '</span><input name="date" id="rcDate" type="date" value="' . esc_attr($date) . '"></label>';
        echo '<label class="field pk-field span pk-span"><span>' . esc_html(I18n::t('Transaction ID')) . ' <em>' . esc_html(I18n::t('from the bKash / Nagad SMS')) . '</em></span><input name="trx" id="rcTrx" value="' . esc_attr($trx) . '" placeholder="e.g. BK7X29QL0M"></label>';
        echo '<label class="field pk-field span pk-span"><span>' . esc_html(I18n::t('Note')) . '</span><textarea name="note" id="rcNote" rows="2" placeholder="' . esc_attr(I18n::t('Advance for Umrah package, quad share…')) . '">' . esc_textarea($note) . '</textarea></label>';
        echo '</div>'; // .fgrid

        if (!empty($pilgrim_names)) {
            echo '<datalist id="pilgrimsList">';
            foreach ($pilgrim_names as $p_name) {
                echo '<option value="' . esc_attr($p_name) . '">';
            }
            echo '</datalist>';
        }

        echo '<div class="flex-end mt-16">';
        echo '<button type="submit" class="btn pk-btn pk-btn-primary btn-primary">' . esc_html(I18n::t('Save receipt')) . '</button>';
        echo '</div>';
        echo '</div>'; // card 3

        echo '</form>'; // #rcForm

        // Right column: Ticket-style Slip Preview
        echo '<aside class="slip-wrap pk-slip-wrap">';
        echo '<div class="slip pk-slip" id="slip">';

        // Slip top
        echo '<div class="slip-top pk-slip-top">';
        if ($logo_url !== '') {
            echo '<img src="' . esc_url($logo_url) . '" alt="" class="slip-logo-img">';
        } else {
            echo '<span class="brand-mark pk-brand-mark"><svg class="i lg"><use href="#i-pin"/></svg></span>';
        }
        echo '<div><b>' . esc_html($agency_name) . '</b>';
        $sub_parts = array_filter([$license ? I18n::t('License') . ' ' . $license : '', $phone]);
        echo '<small>' . esc_html(implode(' · ', $sub_parts)) . '</small></div>';
        echo '</div>'; // .slip-top

        // Slip amount
        echo '<div class="slip-amt pk-slip-amt">';
        echo '<span class="stamp pk-stamp">PAID</span>';
        echo '<small>' . esc_html(I18n::t('Amount received')) . '</small>';
        echo '<strong id="sAmt">' . Format::money($amount) . '</strong>';
        echo '<div class="sub pk-sub" id="sWords">' . esc_html($words) . '</div>';
        echo '</div>'; // .slip-amt

        // Slip rows
        echo '<div class="slip-rows pk-slip-rows">';
        echo '<div><span>' . esc_html(I18n::t('Paid by')) . '</span><b id="sName">' . esc_html($name ?: '—') . '</b></div>';
        echo '<div><span>' . esc_html(I18n::t('Channel')) . '</span><b id="sCh">' . esc_html(I18n::choice('channel', $current_channel)) . '</b></div>';
        echo '<div><span>' . esc_html(I18n::t('Transaction ID')) . '</span><b id="sTrx">' . esc_html($trx ?: '—') . '</b></div>';
        echo '<div><span>' . esc_html(I18n::t('Date')) . '</span><b id="sDate">' . esc_html(wp_date('j F Y', strtotime($date))) . '</b></div>';
        echo '<div><span>' . esc_html(I18n::t('Note')) . '</span><b id="sNote" class="slip-note-val">' . esc_html($note ?: '—') . '</b></div>';
        echo '</div>'; // .slip-rows

        echo '</div>'; // .slip

        // Actions under slip
        echo '<div class="slip-actions pk-slip-actions">';
        echo '<button type="button" class="btn pk-btn pk-btn-wa btn-wa" id="copySlip"><svg class="i sm"><use href="#i-wa"/></svg>' . esc_html(I18n::t('Copy for WhatsApp')) . '</button>';
        echo '<button type="button" class="btn pk-btn pk-btn-ghost btn-ghost" onclick="window.print()"><svg class="i sm"><use href="#i-print"/></svg>' . esc_html(I18n::t('Print')) . '</button>';
        echo '</div>'; // .slip-actions

        if (!defined('POTHIK_PRO_FILE')) {
            echo '<p class="hint pk-hint">' . esc_html(I18n::t('Free version keeps one receipt. Full history is in Pro.')) . '</p>';
        }

        echo '</aside>'; // .slip-wrap

        echo '</div>'; // .rc
        echo '</section>'; // .screen

        Chrome::close();
    }
}
