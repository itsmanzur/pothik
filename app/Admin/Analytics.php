<?php
/**
 * Desk counts for inquiries and the one receipt.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik\Admin;

use Pothik\Format;
use Pothik\I18n;
use Pothik\InquiryStore;
use Pothik\ReceiptStore;

if (!defined('ABSPATH')) {
    exit;
}

final class Analytics
{
    public static function render(): void
    {
        $days = self::range();
        $rows = [];
        foreach (InquiryStore::all() as $row) {
            if (is_array($row) && self::in_range($row, $days)) {
                $rows[] = $row;
            }
        }
        $waiting = 0;
        $replied = 0;
        $adults = 0;
        $children = 0;
        $infants = 0;
        $shares = [];
        $trips = [];
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
            $share = I18n::choice('share', (string) ($row['share'] ?? ''));
            $trip = I18n::choice('trip', (string) ($row['trip'] ?? ''));
            $city = trim((string) ($row['city'] ?? ''));
            $shares[$share] = ($shares[$share] ?? 0) + 1;
            $trips[$trip] = ($trips[$trip] ?? 0) + 1;
            $city_label = $city !== '' ? $city : I18n::t('No city');
            $cities[$city_label] = ($cities[$city_label] ?? 0) + 1;
            $stamp = strtotime((string) ($row['created'] ?? ''));
            if ($stamp !== false) {
                $day = wp_date('Y-m-d', $stamp);
                $by_day[$day] = ($by_day[$day] ?? 0) + 1;
            }
        }
        arsort($cities);
        $cities = array_slice($cities, 0, 8, true);

        $receipt = ReceiptStore::get();
        Chrome::open('Analytics', 'Inquiries, shares and the receipt, counted on this desk.');
        echo '<div class="pk-chips">';
        foreach ([0 => 'All', 7 => '7 days', 30 => '30 days'] as $span => $label) {
            $arg = $span === 0 ? 'all' : (string) $span;
            $url = add_query_arg('range', $arg, admin_url('admin.php?page=pothik-analytics'));
            echo '<a class="' . ($days === $span ? 'is-on' : '') . '" href="' . esc_url($url) . '">' . esc_html(I18n::t($label)) . '</a>';
        }
        echo '</div>';
        echo '<div class="pk-stats">';
        echo '<div><strong>' . esc_html((string) count($rows)) . '</strong><span>' . esc_html(I18n::t('Inquiries')) . '</span></div>';
        echo '<div><strong>' . esc_html((string) $waiting) . '</strong><span>' . esc_html(I18n::t('Waiting')) . '</span></div>';
        echo '<div><strong>' . esc_html((string) $replied) . '</strong><span>' . esc_html(I18n::t('Replied')) . '</span></div>';
        echo '</div>';
        echo '<div class="pk-stats">';
        echo '<div><strong>' . esc_html((string) $adults) . '</strong><span>' . esc_html(I18n::t('Adults')) . '</span></div>';
        echo '<div><strong>' . esc_html((string) $children) . '</strong><span>' . esc_html(I18n::t('Children')) . '</span></div>';
        echo '<div><strong>' . esc_html((string) $infants) . '</strong><span>' . esc_html(I18n::t('Infants')) . '</span></div>';
        echo '</div>';
        self::columns('By day', self::series($by_day, $days));
        echo '<div class="pk-analytics-grid">';
        self::ring('By share', $shares);
        self::bars('By type', $trips);
        self::bars('By city', $cities);
        echo '</div>';
        echo '<section class="pk-panel"><h2>' . esc_html(I18n::t('One receipt')) . '</h2>';
        echo '<p><strong>' . esc_html(Format::money((int) ($receipt['amount'] ?? 0))) . '</strong></p>';
        echo '<p class="pk-sub">' . esc_html(I18n::choice('channel', (string) ($receipt['channel'] ?? ''))) . ' · ' . esc_html((string) ($receipt['date'] ?? '')) . '</p>';
        echo '</section>';
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
            $stamp = strtotime((string) $day);
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
     * @param array<string, mixed> $row
     */
    private static function in_range(array $row, int $days): bool
    {
        if ($days === 0) {
            return true;
        }
        $created = strtotime((string) ($row['created'] ?? ''));
        if ($created === false) {
            return false;
        }

        return $created >= strtotime('-' . $days . ' days');
    }

    /**
     * @param array<string, int> $counts
     * @return array<string, int>
     */
    private static function series(array $counts, int $days): array
    {
        if ($days > 0) {
            $filled = [];
            $start = strtotime('-' . ($days - 1) . ' days');
            if ($start === false) {
                return $counts;
            }
            for ($i = 0; $i < $days; $i++) {
                $stamp = strtotime('+' . $i . ' days', $start);
                if ($stamp === false) {
                    continue;
                }
                $key = wp_date('Y-m-d', $stamp);
                $filled[$key] = (int) ($counts[$key] ?? 0);
            }

            return $filled;
        }

        ksort($counts);

        return array_slice($counts, -30, 30, true);
    }
}
