<?php
/**
 * Desk counts for inquiries and the one receipt.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik\Admin;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Pothik\Format;
use Pothik\I18n;
use Pothik\InquiryStore;
use Pothik\ReceiptStore;

if (!defined('ABSPATH')) {
    exit;
}

final class Analytics
{
    /**
     * Timezone handling and verification notes:
     *
     * Unit test checklist:
     * - An inquiry created at 2026-10-24 22:00 Asia/Dhaka is stored in the database as 2026-10-24 16:00:00 GMT.
     * - When computing Analytics for site timezone Asia/Dhaka (UTC+6):
     *   16:00:00 UTC + 6 hours = 22:00:00 on 2026-10-24 local time.
     *   The inquiry is counted on 2026-10-24, NOT 2026-10-25.
     * - Range cutoffs (7 days / 30 days) and by-day series compute based on midnight boundaries
     *   in the site's local timezone (wp_timezone()).
     */
    public static function render(): void
    {
        $days = self::range();
        $all_rows = InquiryStore::all();

        $rows = [];
        $prev_rows = [];
        $today_midnight = new DateTimeImmutable('today midnight', wp_timezone());

        foreach ($all_rows as $row) {
            // Exclude demo rows from analytics.
            if (!empty($row['demo']) || !is_array($row)) {
                continue;
            }
            if (self::in_range($row, $days)) {
                $rows[] = $row;
            }
            if ($days > 0) {
                $local_dt = self::row_local_datetime($row);
                if ($local_dt !== null) {
                    $prev_start = $today_midnight->modify('-' . (2 * $days - 1) . ' days');
                    $prev_end = $today_midnight->modify('-' . $days . ' days 23:59:59');
                    if ($local_dt >= $prev_start && $local_dt <= $prev_end) {
                        $prev_rows[] = $row;
                    }
                }
            }
        }

        $total_inquiries = count($rows);
        $waiting = 0;
        $replied = 0;
        $adults = 0;
        $children = 0;
        $infants = 0;
        $shares = [];
        $trips = ['umrah' => 0, 'hajj' => 0];
        $cities = [];
        $by_day = [];

        foreach ($rows as $row) {
            if (($row['replied'] ?? '') === '1') {
                $replied++;
            } else {
                $waiting++;
            }
            $adults += (int) ($row['adults'] ?? 0);
            $children += (int) ($row['children'] ?? 0);
            $infants += (int) ($row['infants'] ?? 0);

            $raw_share = (string) ($row['share'] ?? 'quad');
            $share_lbl = I18n::choice('share', $raw_share);
            $shares[$share_lbl] = ($shares[$share_lbl] ?? 0) + 1;

            $raw_trip = (string) ($row['trip'] ?? 'umrah');
            $trips[$raw_trip] = ($trips[$raw_trip] ?? 0) + 1;

            $city = trim((string) ($row['city'] ?? ''));
            $city_label = $city !== '' ? $city : I18n::t('No city');
            $cities[$city_label] = ($cities[$city_label] ?? 0) + 1;

            $local_dt = self::row_local_datetime($row);
            if ($local_dt !== null) {
                $day = $local_dt->format('Y-m-d');
                $by_day[$day] = ($by_day[$day] ?? 0) + 1;
            }
        }

        arsort($cities);
        $top_cities = array_slice($cities, 0, 7, true);

        // Trend calculation
        $trend_html = '';
        if ($days > 0) {
            $prev_count = count($prev_rows);
            if ($prev_count > 0) {
                $diff = $total_inquiries - $prev_count;
                $pct = round(($diff / $prev_count) * 100);
                if ($pct >= 0) {
                    $trend_html = '<span class="trend pos">▲ ' . $pct . '%</span>';
                } else {
                    $trend_html = '<span class="trend neg">▼ ' . abs($pct) . '%</span>';
                }
            } else {
                $trend_html = '<span class="trend pos">▲ 100%</span>';
            }
        }

        $reply_rate = $total_inquiries > 0 ? (int) round(($replied / $total_inquiries) * 100) : 0;
        $total_pax = $adults + $children + $infants;

        // Page head segmented control
        $actions_html = '<div class="seg pk-seg" id="rangeSeg">';
        foreach ([7 => '7 days', 30 => '30 days', 0 => 'All'] as $span => $label) {
            $arg = $span === 0 ? 'all' : (string) $span;
            $url = add_query_arg('range', $arg, admin_url('admin.php?page=pothik-analytics'));
            $actions_html .= '<a class="' . ($days === $span ? 'on' : '') . '" href="' . esc_url($url) . '" data-r="' . $span . '">' . esc_html(I18n::t($label)) . '</a>';
        }
        $actions_html .= '</div>';

        Chrome::open('Analytics', 'Where your pilgrims come from and what they ask for. Demo rows are excluded.', $actions_html);

        echo '<section class="screen" data-screen="analytics">';

        if ($total_inquiries === 0) {
            echo '<div class="card pk-card empty pk-empty">';
            echo '<div class="em-ico"><svg class="i lg"><use href="#i-chart"/></svg></div>';
            echo '<h3>' . esc_html(I18n::t('No inquiries in this period')) . '</h3>';
            echo '<p class="sub">' . esc_html(I18n::t('Share your public package catalog so pilgrims can submit inquiries.')) . '</p>';
            echo '<button type="button" class="btn pk-btn pk-btn-primary btn-primary" data-pk-copy="' . esc_url(\Pothik\Front\PackagePage::url()) . '"><svg class="i"><use href="#i-copy"/></svg>' . esc_html(I18n::t('Copy catalog link')) . '</button>';
            echo '</div>';
            echo '</section>';
            do_action('pothik/analytics');
            Chrome::close();
            return;
        }

        // 1. KPI row (4 cards)
        echo '<div class="kpis pk-kpis">';
        
        // KPI 1: Inquiries
        echo '<div class="kpi pk-kpi"><div class="kpi-top"><span class="kpi-ico"><svg class="i"><use href="#i-inbox"/></svg></span>' . $trend_html . '</div><strong data-pk-count="' . $total_inquiries . '">' . $total_inquiries . '</strong><span class="sub">' . esc_html(I18n::t('Inquiries')) . '</span></div>';
        
        // KPI 2: Still waiting
        echo '<div class="kpi pk-kpi warn"><div class="kpi-top"><span class="kpi-ico"><svg class="i"><use href="#i-clock"/></svg></span></div><strong data-pk-count="' . $waiting . '">' . $waiting . '</strong><span class="sub">' . esc_html(I18n::t('Still waiting')) . '</span></div>';
        
        // KPI 3: Pilgrims asked
        echo '<div class="kpi pk-kpi"><div class="kpi-top"><span class="kpi-ico"><svg class="i"><use href="#i-users"/></svg></span></div><strong data-pk-count="' . $total_pax . '">' . $total_pax . '</strong><span class="sub">' . sprintf(esc_html(I18n::t('Pilgrims asked · %d adults · %d children · %d infants')), $adults, $children, $infants) . '</span></div>';
        
        // KPI 4: Reply rate
        echo '<div class="kpi pk-kpi gold"><div class="kpi-top"><span class="kpi-ico"><svg class="i"><use href="#i-check"/></svg></span></div><strong data-pk-count="' . $reply_rate . '" data-suffix="%">' . $reply_rate . '%</strong><span class="sub">' . esc_html(I18n::t('Reply rate')) . '</span></div>';
        
        echo '</div>'; // .kpis

        // 2. "Inquiries by day" card (PHP rendered SVG chart)
        $series = self::series($by_day, $days);
        $num_bars = max(1, count($series));
        $max_val = max(1, max(array_values($series) ?: [1]));
        $W = 1000;
        $H = 220;
        $bw = $W / $num_bars;

        echo '<div class="card pk-card chart-card">';
        echo '<div class="chart-head"><h3>' . esc_html(I18n::t('Inquiries by day')) . '</h3><span class="sub">' . esc_html(I18n::t('Hover a bar')) . '</span></div>';
        echo '<div class="chart pk-chart" id="dayChart">';

        echo '<svg viewBox="0 0 ' . $W . ' ' . ($H + 24) . '" preserveAspectRatio="none">';
        echo '<defs><linearGradient id="bg1" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#40916c"/><stop offset="1" stop-color="#1b4336"/></linearGradient></defs>';
        for ($g = 0; $g <= 4; $g++) {
            $y = $H - ($H * $g / 4);
            echo '<line x1="0" x2="' . $W . '" y1="' . $y . '" y2="' . $y . '" stroke="#f2ece2"/>';
        }

        $bar_idx = 0;
        foreach ($series as $day_str => $cnt) {
            $h = max(4, round(($cnt / $max_val) * ($H - 14)));
            $x = $bar_idx * $bw + ($bw * 0.18);
            $w = $bw * 0.64;
            $rx = min(6, $bw * 0.2);
            $y = $H - $h;
            $fill = $cnt > 0 ? 'url(#bg1)' : '#ebe4d8';
            $stamp = strtotime($day_str . ' 12:00:00');
            $date_lbl = $stamp ? wp_date('j M', $stamp) : $day_str;
            $day_num = $stamp ? wp_date('j', $stamp) : (string) ($bar_idx + 1);

            echo '<rect class="day-bar" data-i="' . $bar_idx . '" data-label="' . esc_attr($date_lbl) . '" data-v="' . $cnt . '" x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" rx="' . $rx . '" fill="' . $fill . '"/>';

            if ($num_bars <= 14 || ($bar_idx % 5 === 0)) {
                echo '<text x="' . ($bar_idx * $bw + $bw / 2) . '" y="' . ($H + 18) . '" text-anchor="middle" font-size="12" fill="#9a9286">' . esc_html($day_num) . '</text>';
            }
            $bar_idx++;
        }

        echo '</svg>';
        echo '<div class="tip pk-tip" id="tip"></div>';

        // Accessible table for screen readers
        echo '<table class="screen-reader-text"><caption>' . esc_html(I18n::t('Inquiries by day')) . '</caption><thead><tr><th>' . esc_html(I18n::t('Date')) . '</th><th>' . esc_html(I18n::t('Inquiries')) . '</th></tr></thead><tbody>';
        foreach ($series as $day_str => $cnt) {
            echo '<tr><td>' . esc_html($day_str) . '</td><td>' . $cnt . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '</div>'; // #dayChart
        echo '</div>'; // .chart-card

        // 3. Grid of 3 cards
        echo '<div class="an-grid pk-an-grid">';

        // Card 1: By room share (Donut)
        $donut_colors = ['#1b4336', '#40916c', '#d4a64a', '#b4532a', '#7e22ce'];
        $donut_total = max(1, array_sum($shares));
        $R = 60;
        $C = 2 * M_PI * $R;

        echo '<div class="card pk-card chart-card"><div class="chart-head"><h3>' . esc_html(I18n::t('By room share')) . '</h3></div>';
        echo '<div class="donut-wrap pk-donut-wrap">';
        echo '<div class="donut pk-donut" id="donut">';
        echo '<svg viewBox="0 0 150 150" width="150" height="150"><circle cx="75" cy="75" r="' . $R . '" fill="none" stroke="#f2ece2" stroke-width="18"/>';

        $d_offset = 0.0;
        $sh_idx = 0;
        foreach ($shares as $sh_name => $sh_cnt) {
            $len = ($sh_cnt / $donut_total) * $C;
            $col = $donut_colors[$sh_idx % count($donut_colors)];
            echo '<circle class="donut-seg" data-k="' . $sh_idx . '" data-name="' . esc_attr($sh_name) . '" data-val="' . $sh_cnt . '" cx="75" cy="75" r="' . $R . '" fill="none" stroke="' . esc_attr($col) . '" stroke-width="18" stroke-dasharray="' . max(0, $len - 2) . ' ' . ($C - $len + 2) . '" stroke-dashoffset="' . (-$d_offset) . '"/>';
            $d_offset += $len;
            $sh_idx++;
        }
        echo '</svg>';
        echo '<div class="c"><div><strong id="dnum">' . $donut_total . '</strong><span class="sub" id="dlab">' . esc_html(I18n::t('inquiries')) . '</span></div></div>';
        echo '</div>'; // #donut

        echo '<ul class="legend pk-legend" id="legend">';
        $sh_idx = 0;
        foreach ($shares as $sh_name => $sh_cnt) {
            $col = $donut_colors[$sh_idx % count($donut_colors)];
            echo '<li class="legend-item" data-k="' . $sh_idx . '"><i style="background:' . esc_attr($col) . '"></i><span>' . esc_html($sh_name) . '</span><b>' . $sh_cnt . '</b></li>';
            $sh_idx++;
        }
        echo '</ul>';
        echo '</div>'; // .donut-wrap
        echo '</div>'; // Card 1

        // Card 2: Top cities
        $max_city = max(1, max(array_values($top_cities) ?: [1]));
        echo '<div class="card pk-card chart-card"><div class="chart-head"><h3>' . esc_html(I18n::t('Top cities')) . '</h3></div>';
        echo '<div class="hbars pk-hbars" id="cities">';
        foreach ($top_cities as $city_name => $city_count) {
            $pct = round(($city_count / $max_city) * 100);
            echo '<div class="hbar pk-hbar">';
            echo '<span class="hbar-name">' . esc_html($city_name) . '</span>';
            echo '<span class="hbar-track"><i style="width:' . $pct . '%"></i></span>';
            echo '<b class="hbar-val">' . $city_count . '</b>';
            echo '</div>';
        }
        echo '</div>'; // #cities
        echo '</div>'; // Card 2

        // Card 3: Umrah vs Hajj + Semicircle gauge
        $max_trip = max(1, max(array_values($trips) ?: [1]));
        $gauge_offset = round(251 * (1 - ($reply_rate / 100)));
        echo '<div class="card pk-card chart-card"><div class="chart-head"><h3>' . esc_html(I18n::t('Umrah vs Hajj')) . '</h3></div>';
        echo '<div class="hbars pk-hbars" id="types">';
        foreach (['umrah' => 'Umrah', 'hajj' => 'Hajj'] as $trip_k => $trip_l) {
            $t_cnt = (int) ($trips[$trip_k] ?? 0);
            $pct = round(($t_cnt / $max_trip) * 100);
            $tr_class = $trip_k === 'hajj' ? 'hbar-track hajj' : 'hbar-track';
            echo '<div class="hbar pk-hbar">';
            echo '<span>' . esc_html(I18n::choice('trip', $trip_k)) . '</span>';
            echo '<span class="' . $tr_class . '"><i style="width:' . $pct . '%"></i></span>';
            echo '<b class="hbar-val">' . $t_cnt . '</b>';
            echo '</div>';
        }
        echo '</div>'; // #types

        // Semicircle gauge
        echo '<div class="gauge pk-gauge">';
        echo '<svg viewBox="0 0 200 110">';
        echo '<path d="M20 100 A80 80 0 0 1 180 100" fill="none" stroke="#f2ece2" stroke-width="16" stroke-linecap="round"/>';
        echo '<path id="gArc" d="M20 100 A80 80 0 0 1 180 100" fill="none" stroke="url(#gg)" stroke-width="16" stroke-linecap="round" stroke-dasharray="251" stroke-dashoffset="' . $gauge_offset . '"/>';
        echo '<defs><linearGradient id="gg"><stop offset="0" stop-color="#40916c"/><stop offset="1" stop-color="#1b4336"/></linearGradient></defs>';
        echo '<text x="100" y="92" text-anchor="middle" font-size="26" font-weight="800" fill="#1c1916">' . $reply_rate . '%</text>';
        echo '</svg>';
        echo '<div class="sub">' . esc_html(I18n::t('of inquiries got a reply')) . '</div>';
        echo '</div>'; // .gauge
        echo '</div>'; // Card 3
        echo '</div>'; // .an-grid
        echo '</div>'; // .gauge

        echo '</div>'; // Card 3

        echo '</div>'; // .an-grid

        echo '</section>'; // .screen

        do_action('pothik/analytics');
        Chrome::close();
    }

    /**
     * @param array<string, int> $counts
     */
    public static function columns(string $title, array $counts): void
    {
        if ($counts === []) {
            return;
        }
        $max = max(1, max($counts));
        $compact = count($counts) > 14;
        echo '<section class="pk-panel pk-cols-card"><h2>' . esc_html(I18n::t($title)) . '</h2><div class="pk-cols">';
        foreach ($counts as $day => $count) {
            $count = (int) $count;
            $height = max(4, (int) round(($count / $max) * 120));
            $stamp = strtotime((string) $day . ' 12:00:00');
            $short = $stamp === false ? (string) $day : wp_date($compact ? 'j' : 'j M', $stamp);
            $class = $count === 0 ? ' is-zero' : '';
            echo '<div class="pk-col" title="' . esc_attr((string) $day . ' · ' . $count) . '">';
            echo '<b class="' . esc_attr(trim($class)) . '" style="height:' . $height . 'px"></b>';
            echo '<span>' . esc_html($short) . '</span></div>';
        }
        echo '</div></section>';
    }

    /**
     * @param array<string, int> $counts
     */
    public static function ring(string $title, array $counts): void
    {
        $total = array_sum($counts);
        if ($total < 1) {
            return;
        }
        $colors = ['#1b4336', '#2d6a4f', '#8a4030', '#c4a574', '#5e584e', '#52796f', '#1c1916'];
        $radius = 46;
        $circ = 2 * M_PI * $radius;
        $offset = 0.0;
        $index = 0;
        echo '<section class="pk-panel"><h2>' . esc_html(I18n::t($title)) . '</h2><div class="pk-ring-wrap">';
        echo '<div class="pk-ring-plot"><svg class="pk-ring" viewBox="0 0 120 120" role="img" aria-label="' . esc_attr(I18n::t($title)) . '">';
        echo '<circle cx="60" cy="60" r="46" fill="none" stroke="#f3eee6" stroke-width="14"></circle>';
        foreach ($counts as $count) {
            $count = (int) $count;
            if ($count < 1) {
                continue;
            }
            $length = ($count / $total) * $circ;
            $color = $colors[$index % count($colors)];
            echo '<circle cx="60" cy="60" r="46" fill="none" stroke="' . esc_attr($color) . '" stroke-width="14"';
            echo ' stroke-dasharray="' . esc_attr($length . ' ' . ($circ - $length)) . '"';
            echo ' stroke-dashoffset="' . esc_attr((string) (0 - $offset)) . '" transform="rotate(-90 60 60)"></circle>';
            $offset += $length;
            $index++;
        }
        echo '</svg><strong>' . esc_html((string) $total) . '</strong></div><ul class="pk-ring-key">';
        $index = 0;
        foreach ($counts as $label => $count) {
            $count = (int) $count;
            if ($count < 1) {
                continue;
            }
            $color = $colors[$index % count($colors)];
            echo '<li><span><i style="background:' . esc_attr($color) . '"></i>' . esc_html((string) $label) . '</span><strong>' . esc_html((string) $count) . '</strong></li>';
            $index++;
        }
        echo '</ul></div></section>';
    }

    /**
     * @param array<string, int> $counts
     */
    public static function bars(string $title, array $counts, bool $money = false): void
    {
        if ($counts === []) {
            return;
        }
        $max = max($counts);
        echo '<section class="pk-panel"><h2>' . esc_html(I18n::t($title)) . '</h2><div class="pk-bars">';
        foreach ($counts as $label => $count) {
            $count = (int) $count;
            $width = $max > 0 ? (int) round(($count / $max) * 100) : 0;
            $figure = $money ? Format::money($count) : (string) $count;
            echo '<div class="pk-bar-row"><span>' . esc_html((string) $label) . '</span>';
            echo '<span class="pk-bar-track"><span class="pk-bar-fill" style="width:' . $width . '%"></span></span>';
            echo '<strong>' . esc_html($figure) . '</strong></div>';
        }
        echo '</div></section>';
    }

    private static function range(): int
    {
        $raw = isset($_GET['range']) ? sanitize_key((string) wp_unslash($_GET['range'])) : 'all';

        return $raw === '7' || $raw === '30' ? (int) $raw : 0;
    }

    /**
     * Resolve row timestamp to DateTimeImmutable in the site timezone.
     *
     * @param array<string, mixed> $row
     */
    public static function row_local_datetime(array $row): ?DateTimeImmutable
    {
        $gmt = (string) ($row['created_gmt'] ?? '');
        if ($gmt !== '') {
            try {
                $dt = new DateTimeImmutable($gmt, new DateTimeZone('UTC'));

                return $dt->setTimezone(wp_timezone());
            } catch (Exception $e) {
                // fall through
            }
        }

        $created = (string) ($row['created'] ?? '');
        if ($created !== '') {
            try {
                return new DateTimeImmutable($created, wp_timezone());
            } catch (Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function in_range(array $row, int $days): bool
    {
        if ($days === 0) {
            return true;
        }
        $local_dt = self::row_local_datetime($row);
        if ($local_dt === null) {
            return false;
        }

        $today_midnight = new DateTimeImmutable('today midnight', wp_timezone());
        $cutoff = $today_midnight->modify('-' . ($days - 1) . ' days');

        return $local_dt >= $cutoff;
    }

    /**
     * @param array<string, int> $counts
     * @return array<string, int>
     */
    private static function series(array $counts, int $days): array
    {
        if ($days > 0) {
            $filled = [];
            $today = new DateTimeImmutable('today midnight', wp_timezone());
            $start = $today->modify('-' . ($days - 1) . ' days');
            for ($i = 0; $i < $days; $i++) {
                $date = $start->modify('+' . $i . ' days');
                $key = $date->format('Y-m-d');
                $filled[$key] = (int) ($counts[$key] ?? 0);
            }

            return $filled;
        }

        ksort($counts);

        return array_slice($counts, -30, 30, true);
    }

    /**
     * Compute 7-day sparkline, trends, and 30-day reply rate for the Desk overview.
     *
     * @return array{
     *     curr_7_count: int,
     *     prev_7_count: int,
     *     trend_label: string,
     *     trend_down: bool,
     *     line_d: string,
     *     fill_d: string,
     *     reply_rate_30: int
     * }
     */
    public static function desk_metrics(): array
    {
        $all_inq = InquiryStore::all();
        $today = new DateTimeImmutable('today midnight', wp_timezone());
        $daily_counts = array_fill(0, 7, 0);
        $curr_7_count = 0;
        $prev_7_count = 0;

        $inq_30_total = 0;
        $inq_30_replied = 0;
        $cutoff_30 = $today->modify('-29 days');

        foreach ($all_inq as $inq) {
            if (!empty($inq['demo']) || !is_array($inq)) {
                continue;
            }
            $dt = self::row_local_datetime($inq);
            if (!$dt) {
                continue;
            }

            // 7-day sparkline and trend
            $diff_days = (int) $today->diff($dt->setTime(0, 0, 0))->format('%r%a');
            if ($diff_days >= -6 && $diff_days <= 0) {
                $slot = $diff_days + 6;
                $daily_counts[$slot]++;
                $curr_7_count++;
            } elseif ($diff_days >= -13 && $diff_days <= -7) {
                $prev_7_count++;
            }

            // 30-day reply rate
            if ($dt >= $cutoff_30) {
                $inq_30_total++;
                if (!empty($inq['replied']) && ((string) $inq['replied'] === '1' || (int) $inq['replied'] === 1)) {
                    $inq_30_replied++;
                }
            }
        }

        $trend_down = false;
        if ($prev_7_count === 0) {
            $trend_label = ($curr_7_count > 0 ? '▲ 100%' : '0%');
        } else {
            $change = (int) round((($curr_7_count - $prev_7_count) / $prev_7_count) * 100);
            if ($change >= 0) {
                $trend_label = '▲ ' . $change . '%';
            } else {
                $trend_down = true;
                $trend_label = '▼ ' . abs($change) . '%';
            }
        }

        $max_c = max(1, max($daily_counts));
        $pts = [];
        for ($i = 0; $i < 7; $i++) {
            $x = round($i * (100 / 6), 1);
            $y = round(34 - ($daily_counts[$i] / $max_c) * 26);
            $pts[] = "{$x} {$y}";
        }
        $line_d = 'M' . implode(' L', $pts);
        $fill_d = $line_d . ' L100 40 L0 40Z';

        $reply_rate_30 = $inq_30_total > 0 ? (int) round(($inq_30_replied / $inq_30_total) * 100) : 0;

        return [
            'curr_7_count' => $curr_7_count,
            'prev_7_count' => $prev_7_count,
            'trend_label' => $trend_label,
            'trend_down' => $trend_down,
            'line_d' => $line_d,
            'fill_d' => $fill_d,
            'reply_rate_30' => $reply_rate_30,
        ];
    }
}
