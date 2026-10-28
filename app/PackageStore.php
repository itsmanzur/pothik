<?php
/**
 * The public package, edited by staff.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik;

if (!defined('ABSPATH')) {
    exit;
}

final class PackageStore
{
    /** @var array<int, array<string, mixed>>|null */
    private static ?array $memo = null;

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'agency' => 'Pothik Umrah',
            'phone' => '01711-000000',
            'whatsapp' => '8801711000000',
            'kicker' => 'March group · from Dhaka',
            'title' => '7-day superior Umrah',
            'days' => 7,
            'nights_makkah' => 4,
            'nights_madinah' => 2,
            'route' => 'Jeddah first',
            'group_note' => 'Fixed group',
            'makkah_hotel' => 'Dar Al Tawhid Hotel',
            'makkah_similar' => 'or similar',
            'makkah_stars' => '5 star',
            'makkah_meters' => 180,
            'madinah_hotel' => 'Taiba Front Hotel',
            'madinah_similar' => 'or similar',
            'madinah_stars' => '4 star',
            'madinah_meters' => 250,
            'prices' => [
                'quad' => 145000,
                'triple' => 165000,
                'double' => 195000,
                'single' => 275000,
                'child_bed' => 110000,
                'child_nobed' => 85000,
                'infant' => 25000,
            ],
            'food_note' => 'Meals are outside this price.',
            'inclusions' => [
                'Dhaka–Jeddah–Dhaka airfare',
                'Umrah visa',
                'Makkah and Madinah hotels',
                'AC coach, Jeddah–Makkah–Madinah',
                'Ziyarah',
                'Bengali muallim',
                'Travel insurance',
            ],
            'exclusions' => [
                'Meals',
                'Personal expenses',
                'Extra baggage',
                'Passport fee',
            ],
            'itinerary' => [
                'Day 1 — Dhaka to Jeddah. Hotel in Makkah.',
                'Day 2 — Umrah. The rest of the time at the Haram.',
                'Day 3 — Makkah.',
                'Day 4 — Makkah. Afternoon transfer toward Madinah.',
                'Day 5 — Madinah. The Prophet\'s Mosque.',
                'Day 6 — Ziyarah.',
                'Day 7 — Madinah to Jeddah, Dhaka at night.',
            ],
            'docs' => 'Passport photo page, photo, meningitis certificate.',
            'cancel' => 'The deposit holds the seat. The cancellation rule is this sentence: after the deposit, a cancellation follows the agency\'s written terms.',
            'bn' => [
                'agency' => 'পথিক উমরা',
                'kicker' => 'মার্চ গ্রুপ · ঢাকা থেকে',
                'title' => '৭ দিন সুপিরিয়র উমরা',
                'route' => 'জেদ্দা আগে',
                'group_note' => 'ফিক্সড গ্রুপ',
                'makkah_hotel' => 'হোটেল দার আল তাওহিদ',
                'makkah_similar' => 'অথবা সমমান',
                'makkah_stars' => '৫ তারকা',
                'madinah_hotel' => 'হোটেল তাইবা ফ্রন্ট',
                'madinah_similar' => 'অথবা সমমান',
                'madinah_stars' => '৪ তারকা',
                'food_note' => 'খাবার এই দামের বাইরে।',
                'inclusions' => [
                    'ঢাকা–জেদ্দা–ঢাকা এয়ার টিকিট',
                    'উমরা ভিসা',
                    'মক্কা ও মদিনার হোটেল',
                    'এসি বাস, জেদ্দা–মক্কা–মদিনা',
                    'জিয়ারত',
                    'বাংলা মুয়াল্লিম',
                    'ট্রাভেল ইনস্যুরেন্স',
                ],
                'exclusions' => [
                    'খাবার',
                    'ব্যক্তিগত খরচ',
                    'অতিরিক্ত লাগেজ',
                    'পাসপোর্টের ফি',
                ],
                'itinerary' => [
                    '১ম দিন — ঢাকা থেকে জেদ্দা। মক্কায় হোটেল।',
                    '২য় দিন — উমরা। বাকি সময় হারামে।',
                    '৩য় দিন — মক্কা।',
                    '৪র্থ দিন — মক্কা। বিকেলে মদিনার পথে।',
                    '৫ম দিন — মদিনা। মসজিদে নববী।',
                    '৬ষ্ঠ দিন — জিয়ারত।',
                    '৭ম দিন — মদিনা থেকে জেদ্দা, রাতে ঢাকা।',
                ],
                'docs' => 'পাসপোর্টের ছবির পাতা, ছবি, মেনিনজাইটিসের সনদ।',
                'cancel' => 'ডিপোজিটে সিট ধরা হয়। বাতিলের নিয়ম এই বাক্যই: ডিপোজিটের পর বাতিল হলে এজেন্সির লিখিত শর্ত অনুযায়ী সমন্বয় হবে।',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        if (is_array(self::$memo)) {
            return self::$memo;
        }

        $stored = get_option('pothik_packages', null);
        if (!is_array($stored)) {
            $stored = self::from_legacy();
            update_option('pothik_packages', $stored, false);
        }

        $rows = [];
        foreach ($stored as $row) {
            if (is_array($row)) {
                $rows[] = self::ready($row);
            }
        }
        self::$memo = $rows;

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $id): ?array
    {
        $id = sanitize_key($id);
        foreach (self::all() as $row) {
            if (($row['id'] ?? '') === $id) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        $rows = self::all();
        if ($rows === []) {
            $sample = self::defaults();
            $sample['id'] = 'sample';

            return $sample;
        }

        return $rows[0];
    }

    /**
     * A new offer starts empty. Office phone and name copy from the first package.
     *
     * @return array<string, mixed>
     */
    public static function blank(): array
    {
        $office = self::all()[0] ?? [];
        $bn = is_array($office['bn'] ?? null) ? $office['bn'] : [];
        $empty = [
            'agency' => (string) ($bn['agency'] ?? ''),
            'kicker' => '',
            'title' => '',
            'route' => '',
            'group_note' => '',
            'makkah_hotel' => '',
            'makkah_similar' => '',
            'makkah_stars' => '',
            'madinah_hotel' => '',
            'madinah_similar' => '',
            'madinah_stars' => '',
            'food_note' => '',
            'docs' => '',
            'cancel' => '',
            'inclusions' => [],
            'exclusions' => [],
            'itinerary' => [],
        ];

        return [
            'id' => self::fresh_id(),
            'agency' => (string) ($office['agency'] ?? ''),
            'phone' => (string) ($office['phone'] ?? ''),
            'whatsapp' => (string) ($office['whatsapp'] ?? ''),
            'kicker' => '',
            'title' => '',
            'days' => 0,
            'nights_makkah' => 0,
            'nights_madinah' => 0,
            'route' => '',
            'group_note' => '',
            'makkah_hotel' => '',
            'makkah_similar' => '',
            'makkah_stars' => '',
            'makkah_meters' => 0,
            'madinah_hotel' => '',
            'madinah_similar' => '',
            'madinah_stars' => '',
            'madinah_meters' => 0,
            'prices' => [
                'quad' => 0,
                'triple' => 0,
                'double' => 0,
                'single' => 0,
                'child_bed' => 0,
                'child_nobed' => 0,
                'infant' => 0,
            ],
            'food_note' => '',
            'inclusions' => [],
            'exclusions' => [],
            'itinerary' => [],
            'docs' => '',
            'cancel' => '',
            'bn' => $empty,
        ];
    }

    public static function handle_create(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_new_package');

        $rows = self::all();
        if (count($rows) >= 40) {
            wp_safe_redirect(admin_url('admin.php?page=pothik-package'));
            exit;
        }
        $row = self::blank();
        $rows[] = $row;
        self::put($rows);

        wp_safe_redirect(admin_url('admin.php?page=pothik-package&package=' . rawurlencode((string) $row['id'])));
        exit;
    }

    public static function handle_save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_save_package');

        $post = wp_unslash($_POST);
        $post = is_array($post) ? $post : [];
        $id = sanitize_key((string) ($post['package_id'] ?? ''));
        $current = self::find($id);
        if ($current === null) {
            wp_safe_redirect(admin_url('admin.php?page=pothik-package'));
            exit;
        }

        $merged = self::merge_language($current, self::from_post($post));
        $merged['id'] = $id;
        $rows = self::all();
        foreach ($rows as $index => $row) {
            if (($row['id'] ?? '') === $id) {
                $rows[$index] = $merged;
            }
        }
        self::put($rows);

        wp_safe_redirect(admin_url('admin.php?page=pothik-package&package=' . rawurlencode($id) . '&updated=1'));
        exit;
    }

    public static function handle_delete(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_delete_package');

        $id = isset($_POST['package_id']) ? sanitize_key(wp_unslash((string) $_POST['package_id'])) : '';
        $rows = array_values(array_filter(
            self::all(),
            static fn (array $row): bool => ($row['id'] ?? '') !== $id
        ));
        self::put($rows);

        wp_safe_redirect(admin_url('admin.php?page=pothik-package&updated=1'));
        exit;
    }

    public static function handle_copy(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_copy_package');

        $post = wp_unslash($_POST);
        $post = is_array($post) ? $post : [];
        $id = sanitize_key((string) ($post['package_id'] ?? ''));
        $current = self::find($id);
        $rows = self::all();
        if ($current === null || count($rows) >= 40) {
            wp_safe_redirect(self::copy_back($post, ''));
            exit;
        }

        $copy = $current;
        $copy['id'] = self::fresh_id();
        $copy['title'] = self::copied_title((string) ($copy['title'] ?? ''), ' copy');
        $bn = is_array($copy['bn'] ?? null) ? $copy['bn'] : [];
        if (isset($bn['title']) && is_string($bn['title'])) {
            $bn['title'] = self::copied_title($bn['title'], ' কপি');
        }
        $copy['bn'] = $bn;
        $rows[] = $copy;
        self::put($rows);

        wp_safe_redirect(self::copy_back($post, (string) $copy['id']));
        exit;
    }

    /**
     * English stays on the root fields. Bangla lives under bn.
     *
     * @param array<string, mixed> $current
     * @param array<string, mixed> $incoming
     * @return array<string, mixed>
     */
    private static function merge_language(array $current, array $incoming): array
    {
        $prose = [
            'agency', 'kicker', 'title', 'route', 'group_note',
            'makkah_hotel', 'makkah_similar', 'makkah_stars',
            'madinah_hotel', 'madinah_similar', 'madinah_stars',
            'food_note', 'docs', 'cancel', 'inclusions', 'exclusions', 'itinerary',
        ];
        $neutral = ['phone', 'whatsapp', 'days', 'nights_makkah', 'nights_madinah', 'makkah_meters', 'madinah_meters', 'prices'];

        if (I18n::lang() === 'bn') {
            $bn = is_array($current['bn'] ?? null) ? $current['bn'] : [];
            foreach ($prose as $key) {
                $bn[$key] = $incoming[$key];
            }
            $current['bn'] = $bn;
            foreach ($neutral as $key) {
                $current[$key] = $incoming[$key];
            }

            return $current;
        }

        $bn = is_array($current['bn'] ?? null) ? $current['bn'] : [];
        $incoming['bn'] = $bn;

        return $incoming;
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private static function from_post(array $post): array
    {
        $base = self::defaults();
        $text = static function (string $key, int $limit) use ($post): string {
            $value = isset($post[$key]) ? sanitize_text_field((string) $post[$key]) : '';

            return mb_substr($value, 0, $limit);
        };
        $lines = static function (string $key) use ($post): array {
            $raw = isset($post[$key]) ? sanitize_textarea_field((string) $post[$key]) : '';
            $rows = preg_split('/\r\n|\r|\n/', $raw) ?: [];
            $clean = [];
            foreach ($rows as $row) {
                $row = trim($row);
                if ($row !== '') {
                    $clean[] = mb_substr($row, 0, 180);
                }
            }

            return array_slice($clean, 0, 45);
        };

        $prices = [];
        foreach (array_keys($base['prices']) as $key) {
            $prices[$key] = isset($post['price_' . $key]) ? absint($post['price_' . $key]) : 0;
        }

        return [
            'agency' => $text('agency', 80),
            'phone' => $text('phone', 40),
            'whatsapp' => $text('whatsapp', 20),
            'kicker' => $text('kicker', 80),
            'title' => $text('title', 120),
            'days' => isset($post['days']) ? absint($post['days']) : 0,
            'nights_makkah' => isset($post['nights_makkah']) ? absint($post['nights_makkah']) : 0,
            'nights_madinah' => isset($post['nights_madinah']) ? absint($post['nights_madinah']) : 0,
            'route' => $text('route', 80),
            'group_note' => $text('group_note', 80),
            'makkah_hotel' => $text('makkah_hotel', 120),
            'makkah_similar' => $text('makkah_similar', 40),
            'makkah_stars' => $text('makkah_stars', 40),
            'makkah_meters' => isset($post['makkah_meters']) ? absint($post['makkah_meters']) : 0,
            'madinah_hotel' => $text('madinah_hotel', 120),
            'madinah_similar' => $text('madinah_similar', 40),
            'madinah_stars' => $text('madinah_stars', 40),
            'madinah_meters' => isset($post['madinah_meters']) ? absint($post['madinah_meters']) : 0,
            'prices' => $prices,
            'food_note' => $text('food_note', 160),
            'inclusions' => $lines('inclusions'),
            'exclusions' => $lines('exclusions'),
            'itinerary' => $lines('itinerary'),
            'docs' => $text('docs', 200),
            'cancel' => mb_substr(isset($post['cancel']) ? sanitize_textarea_field((string) $post['cancel']) : '', 0, 400),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function from_legacy(): array
    {
        $stored = get_option('pothik_package', []);
        if (!is_array($stored) || $stored === [] || !isset($stored['bn']) || ($stored['title'] ?? '') === '৭ দিন সুপিরিয়র উমরা') {
            $row = self::defaults();
        } else {
            $row = array_replace_recursive(self::defaults(), $stored);
        }
        $row['id'] = 'sample';

        return [$row];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function ready(array $row): array
    {
        $id = sanitize_key((string) ($row['id'] ?? ''));
        $row['id'] = $id !== '' ? $id : 'sample';
        $row['days'] = min(365, absint($row['days'] ?? 0));
        $row['nights_makkah'] = min(365, absint($row['nights_makkah'] ?? 0));
        $row['nights_madinah'] = min(365, absint($row['nights_madinah'] ?? 0));
        $row['makkah_meters'] = absint($row['makkah_meters'] ?? 0);
        $row['madinah_meters'] = absint($row['madinah_meters'] ?? 0);
        $prices = is_array($row['prices'] ?? null) ? $row['prices'] : [];
        $clean = [];
        foreach (['quad', 'triple', 'double', 'single', 'child_bed', 'child_nobed', 'infant'] as $key) {
            $clean[$key] = absint($prices[$key] ?? 0);
        }
        $row['prices'] = $clean;
        if (!is_array($row['bn'] ?? null)) {
            $row['bn'] = [];
        }
        foreach (['inclusions', 'exclusions', 'itinerary'] as $key) {
            $row[$key] = is_array($row[$key] ?? null) ? array_values($row[$key]) : [];
        }

        return $row;
    }

    private static function copied_title(string $title, string $suffix): string
    {
        $title = trim($title);
        if ($title === '' || str_ends_with($title, $suffix)) {
            return $title;
        }

        return mb_substr($title . $suffix, 0, 120);
    }

    /**
     * @param array<string, mixed> $post
     */
    private static function copy_back(array $post, string $id): string
    {
        $back = sanitize_key((string) ($post['back'] ?? ''));
        if ($id !== '' && $back === 'edit') {
            return admin_url('admin.php?page=pothik-package&package=' . rawurlencode($id));
        }
        if ($back === 'pothik') {
            return admin_url('admin.php?page=pothik&updated=1');
        }

        return admin_url('admin.php?page=pothik-package&updated=1');
    }

    private static function fresh_id(): string
    {
        $used = [];
        foreach (self::all() as $row) {
            $used[(string) ($row['id'] ?? '')] = true;
        }
        do {
            $id = strtolower(wp_generate_password(8, false, false));
            $id = preg_replace('/[^a-z0-9]/', '', $id) ?? '';
        } while ($id === '' || isset($used[$id]));

        return $id;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private static function put(array $rows): void
    {
        self::$memo = null;
        update_option('pothik_packages', array_slice(array_values($rows), 0, 40), false);
    }
}
