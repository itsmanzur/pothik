<?php
/**
 * Money and text helpers.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik;

if (!defined('ABSPATH')) {
    exit;
}

final class Format
{
    public static function money(int $amount): string
    {
        $negative = $amount < 0;
        $digits = (string) abs($amount);
        if (strlen($digits) > 3) {
            $tail = substr($digits, -3);
            $head = substr($digits, 0, -3);
            $head = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $head) ?? $head;
            $digits = $head . ',' . $tail;
        }

        return ($negative ? '−' : '') . '৳' . $digits;
    }

    public static function phone_href(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return $digits === '' ? '' : 'tel:' . $digits;
    }

    public static function whatsapp_href(string $number, string $text): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';
        if ($digits === '') {
            return '';
        }

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
    }
}
