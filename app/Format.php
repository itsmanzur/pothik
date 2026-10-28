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

    /**
     * Resolve the default country calling code from plugin settings.
     */
    public static function default_country_code(): string
    {
        $settings = get_option('pothik_settings', []);
        if (is_array($settings) && !empty($settings['country_code'])) {
            $cc = preg_replace('/\D+/', '', (string) $settings['country_code']) ?? '';
            if ($cc !== '') {
                return $cc;
            }
        }

        return '880';
    }

    /**
     * Normalize a phone number to standard international MSISDN format (digits only).
     */
    public static function normalize_msisdn(string $raw, string $default_cc = '880'): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        $cc = preg_replace('/\D+/', '', $default_cc) ?? '880';
        if ($cc === '') {
            $cc = '880';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if ($digits !== '' && str_starts_with($digits, $cc)) {
            // Number already begins with the country code.
        } elseif (str_starts_with($digits, '0')) {
            $digits = $cc . substr($digits, 1);
        }

        $length = strlen($digits);
        if ($length < 10 || $length > 15) {
            return '';
        }

        return $digits;
    }

    public static function phone_href(string $phone): string
    {
        $msisdn = self::normalize_msisdn($phone, self::default_country_code());

        return $msisdn === '' ? '' : 'tel:+' . $msisdn;
    }

    public static function whatsapp_href(string $number, string $text): string
    {
        $msisdn = self::normalize_msisdn($number, self::default_country_code());
        if ($msisdn === '') {
            return '';
        }

        return 'https://wa.me/' . $msisdn . '?text=' . rawurlencode($text);
    }

    /**
     * Convert an integer amount to words (Lakh/Crore system) in English or Bangla.
     */
    public static function words(int $amount, bool $is_bn = false): string
    {
        if ($amount <= 0) {
            return '';
        }

        if ($is_bn) {
            $bn_digits = [
                0 => '', 1 => 'এক', 2 => 'দুই', 3 => 'তিন', 4 => 'চার', 5 => 'পাঁচ',
                6 => 'ছয়', 7 => 'সাত', 8 => 'আট', 9 => 'নয়', 10 => 'দশ',
                11 => 'এগারো', 12 => 'বারো', 13 => 'তেরো', 14 => 'চৌদ্দ', 15 => 'পনেরো',
                16 => 'ষোলো', 17 => 'সতেরো', 18 => 'আঠারো', 19 => 'উনিশ', 20 => 'বিশ',
                21 => 'একুশ', 22 => 'বাইশ', 23 => 'তেইশ', 24 => 'চব্বিশ', 25 => 'পঁচিশ',
                26 => 'ছাব্বিশ', 27 => 'সাতাশ', 28 => 'আটাশ', 29 => 'উনত্রিশ', 30 => 'ত্রিশ',
                31 => 'একত্রিশ', 32 => 'বত্রিশ', 33 => 'তেত্রিশ', 34 => 'চৌত্রিশ', 35 => 'পঁয়ত্রিশ',
                36 => 'ছত্রিশ', 37 => 'সাঁইত্রিশ', 38 => 'আটত্রিশ', 39 => 'উনচল্লিশ', 40 => 'চল্লিশ',
                41 => 'একচল্লিশ', 42 => 'বিয়াল্লিশ', 43 => 'তেতাল্লিশ', 44 => 'চুয়াল্লিশ', 45 => 'পঁয়তাল্লিশ',
                46 => 'ছেচল্লিশ', 47 => 'সাতচল্লিশ', 48 => 'আটচল্লিশ', 49 => 'উনপঞ্চাশ', 50 => 'পঞ্চাশ',
                51 => 'একান্ন', 52 => 'বায়ান্ন', 53 => 'তিপ্পান্ন', 54 => 'চুয়ান্ন', 55 => 'পঞ্চান্ন',
                56 => 'ছাপ্পান্ন', 57 => 'সাতান্ন', 58 => 'আটান্ন', 59 => 'উনষাট', 60 => 'ষাট',
                61 => 'একষট্টি', 62 => 'বাষট্টি', 63 => 'তেষট্টি', 64 => 'চৌষট্টি', 65 => 'পঁয়ষট্টি',
                66 => 'ছেষট্টি', 67 => 'সাতষট্টি', 68 => 'আটষট্টি', 69 => 'উনসত্তর', 70 => 'সত্তর',
                71 => 'একাত্তর', 72 => 'বাহাত্তর', 73 => 'তিয়াত্তর', 74 => 'চুয়াত্তর', 75 => 'পঁচাত্তর',
                76 => 'ছিয়াত্তর', 77 => 'সাতাত্তর', 78 => 'আটাত্তর', 79 => 'উনআশি', 80 => 'আশি',
                81 => 'একাশি', 82 => 'বিরাশি', 83 => 'তিরাশি', 84 => 'চুরাশি', 85 => 'পঁচাশি',
                86 => 'ছিয়াশি', 87 => 'সাতাশি', 88 => 'অষ্টআশি', 89 => 'ঊননব্বই', 90 => 'নব্বই',
                91 => 'একানব্বই', 92 => 'বিরানব্বই', 93 => 'তিরানব্বই', 94 => 'চুরানব্বই', 95 => 'পঁচানব্বই',
                96 => 'ছিয়ানব্বই', 97 => 'সাতানব্বই', 98 => 'আটানব্বই', 99 => 'নিরানব্বই'
            ];

            $crore = intdiv($amount, 10000000);
            $rem = $amount % 10000000;
            $lakh = intdiv($rem, 100000);
            $rem %= 100000;
            $thousand = intdiv($rem, 1000);
            $rem %= 1000;
            $hundred = intdiv($rem, 100);
            $rest = $rem % 100;

            $parts = [];
            if ($crore > 0) {
                $parts[] = ($bn_digits[$crore] ?? (string) $crore) . ' কোটি';
            }
            if ($lakh > 0) {
                $parts[] = ($bn_digits[$lakh] ?? (string) $lakh) . ' লাখ';
            }
            if ($thousand > 0) {
                $parts[] = ($bn_digits[$thousand] ?? (string) $thousand) . ' হাজার';
            }
            if ($hundred > 0) {
                $parts[] = ($bn_digits[$hundred] ?? (string) $hundred) . ' শত';
            }
            if ($rest > 0) {
                $parts[] = $bn_digits[$rest] ?? (string) $rest;
            }

            return implode(' ', $parts) . ' টাকা মাত্র';
        }

        $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        $w2 = static function (int $n) use ($ones, $tens): string {
            if ($n < 20) {
                return $ones[$n];
            }
            return $tens[intdiv($n, 10)] . ($n % 10 ? '-' . $ones[$n % 10] : '');
        };

        $w3 = static function (int $n) use ($ones, $w2): string {
            $h = intdiv($n, 100);
            $r = $n % 100;
            return ($h > 0 ? $ones[$h] . ' hundred' . ($r > 0 ? ' ' : '') : '') . $w2($r);
        };

        $crore = intdiv($amount, 10000000);
        $rem = $amount % 10000000;
        $lakh = intdiv($rem, 100000);
        $rem %= 100000;
        $thousand = intdiv($rem, 1000);
        $rem %= 1000;

        $parts = [];
        if ($crore > 0) {
            $parts[] = $w3($crore) . ' crore';
        }
        if ($lakh > 0) {
            $parts[] = $w2($lakh) . ' lakh';
        }
        if ($thousand > 0) {
            $parts[] = $w2($thousand) . ' thousand';
        }
        if ($rem > 0) {
            $parts[] = $w3($rem);
        }

        $str = implode(' ', array_filter($parts));
        return ucfirst($str) . ' taka only';
    }
}
