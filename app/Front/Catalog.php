<?php
/**
 * Helpers for the public All packages page.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik\Front;

use Pothik\Format;
use Pothik\I18n;

if (!defined('ABSPATH')) {
    exit;
}

final class Catalog
{
    /**
     * Same five cover pairs as the admin package cards.
     *
     * @return array{0: string, 1: string}
     */
    public static function colors(string $id): array
    {
        $pairs = [
            ['#173d30', '#2d6a4f'],
            ['#2b2110', '#c4a574'],
            ['#14273e', '#2563eb'],
            ['#3a1d14', '#b4532a'],
            ['#2d1b36', '#7e22ce'],
        ];
        $index = abs(crc32($id)) % count($pairs);

        return $pairs[$index];
    }

    public static function stars(mixed $value): int
    {
        if (is_int($value) || (is_string($value) && is_numeric($value))) {
            return max(0, min(5, (int) $value));
        }
        if (is_string($value) && preg_match('/(\d)/', $value, $match) === 1) {
            return max(0, min(5, (int) $match[1]));
        }

        return 0;
    }

    public static function walk(int $meters): string
    {
        if ($meters <= 100) {
            return I18n::t('≈1 min walk');
        }
        if ($meters > 1000) {
            return I18n::t('shuttle bus');
        }

        return sprintf(I18n::t('≈%d min walk'), (int) round($meters / 80));
    }

    public static function meter_width(int $meters): int
    {
        return (int) max(6, min(100, 100 - ($meters / 20)));
    }

    public static function inclusion_icon(string $label): string
    {
        $hay = strtolower($label);
        if (preg_match('/air|ticket|flight|airfare|plane/', $hay) === 1) {
            return 'plane';
        }
        if (str_contains($hay, 'visa')) {
            return 'doc';
        }
        if (str_contains($hay, 'hotel')) {
            return 'bed';
        }
        if (preg_match('/bus|coach|transport|shuttle/', $hay) === 1) {
            return 'bus';
        }

        return 'shield';
    }

    /**
     * Last two words are the gold span. The string itself is translated.
     */
    public static function heading_html(): string
    {
        $text = apply_filters('pothik/catalog_heading', I18n::t('Umrah packages from Dhaka'));
        $text = is_string($text) ? trim($text) : '';
        if ($text === '') {
            $text = I18n::t('Umrah packages from Dhaka');
        }
        $words = preg_split('/\s+/u', $text) ?: [];
        if (count($words) < 3) {
            return esc_html($text);
        }
        $gold = array_splice($words, -2);

        return esc_html(implode(' ', $words)) . ' <span>' . esc_html(implode(' ', $gold)) . '</span>';
    }

    public static function heading_plain(): string
    {
        $text = apply_filters('pothik/catalog_heading', I18n::t('Umrah packages from Dhaka'));

        return is_string($text) && trim($text) !== '' ? trim($text) : I18n::t('Umrah packages from Dhaka');
    }

    /**
     * @param array<int, array<string, mixed>> $packages
     * @return array<string, string> id => low|near
     */
    public static function badges(array $packages): array
    {
        $low_price = null;
        $near = null;
        foreach ($packages as $package) {
            $prices = is_array($package['prices'] ?? null) ? $package['prices'] : [];
            $quad = (int) ($prices['quad'] ?? 0);
            if ($quad > 0 && ($low_price === null || $quad < $low_price)) {
                $low_price = $quad;
            }
            $meters = (int) ($package['makkah_meters'] ?? 0);
            if ($meters > 0 && ($near === null || $meters < $near)) {
                $near = $meters;
            }
        }

        $badges = [];
        foreach ($packages as $package) {
            $id = (string) ($package['id'] ?? '');
            $prices = is_array($package['prices'] ?? null) ? $package['prices'] : [];
            $quad = (int) ($prices['quad'] ?? 0);
            $meters = (int) ($package['makkah_meters'] ?? 0);
            if ($low_price !== null && $quad === $low_price) {
                $badges[$id] = 'low';
                continue;
            }
            if ($near !== null && $meters === $near) {
                $badges[$id] = 'near';
            }
        }

        return $badges;
    }

    /**
     * @param array<string, string> $contact
     * @return array<int, array{icon: string, text: string}>
     */
    public static function trust(array $contact, int $count): array
    {
        $chips = [];
        if (($contact['license'] ?? '') !== '') {
            $chips[] = [
                'icon' => 'shield',
                'text' => sprintf(I18n::t('Licence %s'), $contact['license']),
            ];
        }
        if (($contact['whatsapp'] ?? '') !== '') {
            $chips[] = [
                'icon' => 'wa',
                'text' => I18n::t('Reply on WhatsApp'),
            ];
        }
        if ($count > 0) {
            $chips[] = [
                'icon' => 'users',
                'text' => sprintf(I18n::t($count === 1 ? '%d package' : '%d packages'), $count),
            ];
        }
        $extra = apply_filters('pothik/catalog_trust', []);
        if (is_array($extra)) {
            foreach ($extra as $line) {
                if (is_string($line) && $line !== '') {
                    $chips[] = ['icon' => 'shield', 'text' => $line];
                }
            }
        }

        return $chips;
    }

    /**
     * @return array<int, array{title: string, text: string}>
     */
    public static function steps(): array
    {
        $steps = [
            ['title' => I18n::t('Choose a package'), 'text' => I18n::t('Compare days, hotels and price.')],
            ['title' => I18n::t('Send an inquiry'), 'text' => I18n::t('Name, phone and room share.')],
            ['title' => I18n::t('Office replies'), 'text' => I18n::t('On WhatsApp or a phone call.')],
            ['title' => I18n::t('Pay the deposit'), 'text' => I18n::t('bKash, Nagad, bank or cash.')],
        ];
        $filtered = apply_filters('pothik/catalog_steps', $steps);
        if (!is_array($filtered)) {
            return $steps;
        }
        $clean = [];
        foreach ($filtered as $step) {
            if (!is_array($step)) {
                continue;
            }
            $title = isset($step['title']) && is_string($step['title']) ? $step['title'] : '';
            $text = isset($step['text']) && is_string($step['text']) ? $step['text'] : '';
            if ($title !== '') {
                $clean[] = ['title' => $title, 'text' => $text];
            }
        }

        return $clean !== [] ? $clean : $steps;
    }

    /**
     * Days until Ramadan (1 Ramadan) and Hajj (8 Dhul Hijjah) on Umm al-Qura.
     *
     * @return array{intl: bool, ramadan: int|null, hajj: int|null, in_ramadan: bool, in_hajj: bool}
     */
    public static function season(): array
    {
        $empty = [
            'intl' => false,
            'ramadan' => null,
            'hajj' => null,
            'in_ramadan' => false,
            'in_hajj' => false,
        ];
        if (!class_exists(\IntlDateFormatter::class)) {
            return $empty;
        }

        $zone = new \DateTimeZone('Asia/Dhaka');
        $fmt = new \IntlDateFormatter(
            'en_US@calendar=islamic-umalqura',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            $zone,
            \IntlDateFormatter::TRADITIONAL,
            'M-d'
        );
        if ($fmt === null) {
            return $empty;
        }

        $today = new \DateTimeImmutable('today', $zone);
        $ramadan = null;
        $hajj = null;
        $in_ramadan = false;
        $in_hajj = false;
        for ($i = 0; $i < 400; $i++) {
            $stamp = $today->modify('+' . $i . ' days');
            $label = $fmt->format($stamp);
            if (!is_string($label) || !str_contains($label, '-')) {
                continue;
            }
            [$month, $day] = array_map('intval', explode('-', $label, 2));
            if ($i === 0 && $month === 9) {
                $in_ramadan = true;
            }
            if ($i === 0 && $month === 12 && $day >= 8 && $day <= 13) {
                $in_hajj = true;
            }
            if ($ramadan === null && $month === 9 && $day === 1) {
                $ramadan = $i;
            }
            if ($hajj === null && $month === 12 && $day === 8) {
                $hajj = $i;
            }
            if ($ramadan !== null && $hajj !== null && $i > 0) {
                break;
            }
        }

        return [
            'intl' => true,
            'ramadan' => $ramadan,
            'hajj' => $hajj,
            'in_ramadan' => $in_ramadan,
            'in_hajj' => $in_hajj,
        ];
    }

    public static function makkah_time(): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Riyadh'));

        return $now->format('H:i');
    }

    /**
     * @param array<int, array<string, mixed>> $packages
     * @return array{min: int, max: int}
     */
    public static function budget(array $packages): array
    {
        $values = [];
        foreach ($packages as $package) {
            $prices = is_array($package['prices'] ?? null) ? $package['prices'] : [];
            $quad = (int) ($prices['quad'] ?? 0);
            if ($quad > 0) {
                $values[] = $quad;
            }
        }
        if ($values === []) {
            return ['min' => 0, 'max' => 0];
        }
        $min = (int) (floor(min($values) / 5000) * 5000);
        $max = (int) (ceil(max($values) / 5000) * 5000);
        if ($max <= $min) {
            $max = $min + 5000;
        }

        return ['min' => $min, 'max' => $max];
    }

    /**
     * @param array<int, array<string, mixed>> $packages
     * @return array<int, array{value: string, label: string}>
     */
    public static function months(array $packages): array
    {
        $months = [];
        $ramadan = false;
        foreach ($packages as $package) {
            $departure = (string) ($package['departure'] ?? '');
            if ($departure !== '') {
                $months[$departure] = true;
            }
            if (!empty($package['is_ramadan'])) {
                $ramadan = true;
            }
        }
        $keys = array_keys($months);
        sort($keys);
        $span = false;
        if (count($keys) > 1) {
            $span = substr((string) $keys[0], 0, 4) !== substr((string) $keys[count($keys) - 1], 0, 4);
        }
        $chips = [];
        foreach ($keys as $key) {
            $stamp = strtotime($key . '-01');
            $label = $stamp === false
                ? $key
                : wp_date($span ? 'F Y' : 'F', $stamp);
            $chips[] = ['value' => $key, 'label' => $label];
        }
        if ($ramadan) {
            $chips[] = ['value' => 'ramadan', 'label' => I18n::t('Ramadan')];
        }
        if (count($chips) < 2) {
            return [];
        }

        return $chips;
    }

    /**
     * @param array<string, mixed> $package
     */
    public static function depart_label(array $package): string
    {
        $departure = (string) ($package['departure'] ?? '');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $departure)) {
            return '';
        }
        $stamp = strtotime($departure . '-01');
        if ($stamp === false) {
            return '';
        }

        return sprintf(I18n::t('Departs %s'), wp_date('F Y', $stamp));
    }

    public static function money_or_contact(int $amount): string
    {
        return $amount > 0 ? Format::money($amount) : I18n::t('Contact the office');
    }
}
