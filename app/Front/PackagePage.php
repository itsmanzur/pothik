<?php
/**
 * Public package page and shortcodes.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik\Front;

use Pothik\I18n;
use Pothik\PackageStore;

if (!defined('ABSPATH')) {
    exit;
}

final class PackagePage
{
    /** @var array{hero: bool, finder: bool, columns: int, limit: int} */
    private static array $catalog_opts = [
        'hero' => true,
        'finder' => true,
        'columns' => 3,
        'limit' => 0,
    ];

    private static bool $catalog_css = false;

    private static bool $script_ready = false;
    public static function url(string $id = ''): string
    {
        $page_id = (int) get_option('pothik_page_id');
        if ($page_id < 1) {
            return '';
        }
        $url = get_permalink($page_id);
        if (!is_string($url) || $url === '') {
            return '';
        }
        $id = sanitize_key($id);
        if ($id === '') {
            return $url;
        }

        return add_query_arg('package', $id, $url);
    }

    /**
     * @param string $template
     */
    public static function template(string $template): string
    {
        $page_id = (int) get_option('pothik_page_id');
        if ($page_id > 0 && is_page($page_id)) {
            $view = self::resolve('');
            self::enqueue(($view['type'] ?? '') === 'catalog');
            self::strip_foreign_assets();
            add_action('wp_print_styles', [self::class, 'strip_foreign_assets'], 0);
            add_action('wp_print_scripts', [self::class, 'strip_foreign_assets'], 0);
            add_action('wp_print_footer_scripts', [self::class, 'strip_foreign_assets'], 0);
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('wp_head', 'wp_resource_hints', 2);
            add_filter('pre_get_document_title', [self::class, 'document_title'], 20);
            add_action('wp_head', [self::class, 'head_meta'], 1);

            return POTHIK_DIR . 'templates/front.php';
        }

        return $template;
    }

    /**
     * Register frontend shortcodes.
     */
    public static function register_shortcodes(): void
    {
        add_shortcode('pothik_package', [self::class, 'package_shortcode']);
        add_shortcode('pothik_catalog', [self::class, 'catalog_shortcode']);
        add_shortcode('pothik', [self::class, 'shortcode']);
    }

    /**
     * Legacy shortcode callback.
     */
    public static function shortcode(): string
    {
        return self::package_shortcode();
    }

    /**
     * [pothik_package id="..."] shortcode.
     *
     * @param array<string, mixed>|string $atts
     */
    public static function package_shortcode(array|string $atts = []): string
    {
        $atts = shortcode_atts(['id' => ''], (array) $atts, 'pothik_package');
        self::enqueue();
        ob_start();
        self::render(sanitize_key((string) $atts['id']));

        return (string) ob_get_clean();
    }

    /**
     * [pothik_catalog] shortcode.
     */
    /**
     * @param array<string, mixed>|string $atts
     */
    public static function catalog_shortcode(array|string $atts = []): string
    {
        $atts = shortcode_atts(
            [
                'hero' => 'no',
                'finder' => 'yes',
                'columns' => '3',
                'limit' => '0',
            ],
            (array) $atts,
            'pothik_catalog'
        );
        self::$catalog_opts = [
            'hero' => (string) $atts['hero'] !== 'no',
            'finder' => (string) $atts['finder'] !== 'no',
            'columns' => (string) $atts['columns'] === '2' ? 2 : 3,
            'limit' => max(0, (int) $atts['limit']),
        ];
        self::enqueue(true);
        ob_start();
        self::render_catalog();
        self::$catalog_opts = [
            'hero' => true,
            'finder' => true,
            'columns' => 3,
            'limit' => 0,
        ];

        return (string) ob_get_clean();
    }

    public static function enqueue(bool $catalog = false): void
    {
        $css = POTHIK_DIR . 'assets/css/public.css';
        $js = POTHIK_DIR . 'assets/js/public.js';
        wp_enqueue_style(
            'pothik-public',
            POTHIK_URL . 'assets/css/public.css',
            [],
            is_readable($css) ? (string) filemtime($css) : POTHIK_VERSION
        );
        if ($catalog || self::$catalog_css) {
            $sheet = POTHIK_DIR . 'assets/css/catalog.css';
            wp_enqueue_style(
                'pothik-catalog',
                POTHIK_URL . 'assets/css/catalog.css',
                ['pothik-public'],
                is_readable($sheet) ? (string) filemtime($sheet) : POTHIK_VERSION
            );
            self::$catalog_css = true;
        }
        wp_enqueue_script(
            'pothik-public',
            POTHIK_URL . 'assets/js/public.js',
            [],
            is_readable($js) ? (string) filemtime($js) : POTHIK_VERSION,
            [
                'in_footer' => true,
                'strategy' => 'defer',
            ]
        );
        if (self::$script_ready) {
            return;
        }
        self::$script_ready = true;
        wp_localize_script('pothik-public', 'pothikPublic', [
            'i18n' => [
                'one' => I18n::t('%d package'),
                'many' => I18n::t('%d packages'),
                'any' => I18n::t('Any'),
                'upTo' => I18n::t('up to %s'),
                'pick' => I18n::t('Pick 2–3'),
                'compareN' => I18n::t('Compare %d'),
                'toast' => I18n::t('You can compare up to 3 packages.'),
                'daysLabel' => I18n::t('Days'),
                'daysWord' => I18n::t('days'),
                'makkahHotel' => I18n::t('Makkah hotel'),
                'madinahHotel' => I18n::t('Madinah hotel'),
                'haram' => I18n::t('To the Haram'),
                'mosque' => I18n::t('To the Prophet\'s Mosque'),
                'quad' => I18n::t('Quad price'),
                'triple' => I18n::t('Triple price'),
                'details' => I18n::t('Details'),
                'contact' => I18n::t('Contact the office'),
                'ramadanNow' => I18n::t('Ramadan Mubarak'),
                'hajjNow' => I18n::t('Hajj days'),
            ],
        ]);
    }

    public static function strip_foreign_assets(): void
    {
        $keep = ['pothik-public', 'pothik-catalog'];
        foreach (['wp_styles', 'wp_scripts'] as $global) {
            $queue = $GLOBALS[$global] ?? null;
            if (!$queue instanceof \WP_Dependencies) {
                continue;
            }
            foreach ((array) $queue->queue as $handle) {
                if (!in_array($handle, $keep, true)) {
                    $queue->dequeue((string) $handle);
                }
            }
        }
    }

    public static function maybe_enqueue(): void
    {
        if (!is_singular()) {
            return;
        }
        $post = get_post();
        if (!$post instanceof \WP_Post) {
            return;
        }
        $content = (string) $post->post_content;
        $catalog = has_shortcode($content, 'pothik_catalog');
        if ($catalog || has_shortcode($content, 'pothik_package') || has_shortcode($content, 'pothik')) {
            self::enqueue($catalog);
        }
    }

    public static function seo_plugin(): bool
    {
        return defined('WPSEO_VERSION')
            || defined('RANK_MATH_VERSION')
            || defined('SEOPRESS_VERSION')
            || class_exists('WPSEO_Frontend')
            || class_exists('RankMath');
    }

    public static function document_title(string $title): string
    {
        $page_id = (int) get_option('pothik_page_id');
        if ($page_id < 1 || !is_page($page_id) || self::seo_plugin()) {
            return $title;
        }
        $view = self::resolve('');
        $contact = PackageStore::contact(is_array($view['package'] ?? null) ? $view['package'] : []);
        $agency = $contact['agency'] !== '' ? $contact['agency'] : 'Pothik';
        if (($view['type'] ?? '') === 'package' && is_array($view['package'] ?? null)) {
            $name = I18n::field($view['package'], 'title');
            if ($name === '') {
                $name = I18n::t('Packages');
            }

            return $name . ' — ' . $agency;
        }

        return Catalog::heading_plain() . ' — ' . $agency;
    }

    public static function head_meta(): void
    {
        $page_id = (int) get_option('pothik_page_id');
        if ($page_id < 1 || !is_page($page_id) || self::seo_plugin()) {
            return;
        }
        $view = self::resolve('');
        if (($view['type'] ?? '') !== 'catalog') {
            return;
        }
        $contact = PackageStore::contact([]);
        $lead = I18n::t('Compare days, hotels and walking distance to the Haram. Pick a room share and send an inquiry — the office replies on WhatsApp.');
        $title = Catalog::heading_plain() . ' — ' . ($contact['agency'] !== '' ? $contact['agency'] : 'Pothik');
        $url = self::url();
        echo '<meta name="description" content="' . esc_attr($lead) . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($lead) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        if ($url !== '') {
            echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        }
        if ($contact['logo_url'] !== '') {
            echo '<meta property="og:image" content="' . esc_url($contact['logo_url']) . '">' . "\n";
        }
    }

    public static function render(string $force_id = ''): void
    {
        $all = PackageStore::all();
        $is_admin = current_user_can('manage_options');
        $published = array_values(array_filter($all, static fn (array $p): bool => ($p['status'] ?? 'draft') === 'published'));

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $requested = $force_id !== ''
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ? $force_id
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            : (isset($_GET['package']) ? sanitize_key((string) wp_unslash($_GET['package'])) : '');

        $package = null;
        $is_preview = false;

        if ($requested !== '') {
            $found = PackageStore::find($requested);
            if ($found !== null) {
                if (($found['status'] ?? 'draft') === 'published') {
                    $package = $found;
                } elseif ($is_admin) {
                    $package = $found;
                    $is_preview = true;
                }
            }
        } elseif (count($published) === 1) {
            $package = $published[0];
        } elseif (count($all) === 1 && $is_admin) {
            $package = $all[0];
            if (($package['status'] ?? 'draft') !== 'published') {
                $is_preview = true;
            }
        }

        if ($package === null) {
            self::$catalog_opts = [
                'hero' => true,
                'finder' => true,
                'columns' => 3,
                'limit' => 0,
            ];
            self::enqueue(true);
            self::render_catalog();

            return;
        }

        $packages = $published;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $result = isset($_GET['pothik']) ? sanitize_key((string) wp_unslash($_GET['pothik'])) : '';
        $seat_line = '';
        if (count($packages) === 1) {
            $line = apply_filters('pothik/seat_line', '');
            $seat_line = is_string($line) ? $line : '';
        }

        include POTHIK_DIR . 'templates/package.php';
    }

    public static function render_catalog(): void
    {
        $all = PackageStore::all();
        $is_admin = current_user_can('manage_options');
        $published = array_values(array_filter(
            $all,
            static fn (array $row): bool => ($row['status'] ?? 'draft') === 'published'
        ));
        $packages = $is_admin ? $all : $published;
        if (self::$catalog_opts['limit'] > 0) {
            $packages = array_slice($packages, 0, self::$catalog_opts['limit']);
        }
        $catalog_opts = self::$catalog_opts;
        $show_finder = self::$catalog_opts['finder'] && count($published) >= 3;
        $budget = Catalog::budget($packages);

        include POTHIK_DIR . 'templates/catalog.php';
    }

    /**
     * @return array<string, mixed>
     */
    public static function resolve(string $force_id = ''): array
    {
        $all = PackageStore::all();
        $is_admin = current_user_can('manage_options');
        $published = array_values(array_filter(
            $all,
            static fn (array $row): bool => ($row['status'] ?? 'draft') === 'published'
        ));
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $requested = $force_id !== ''
            ? $force_id
            : (isset($_GET['package']) ? sanitize_key((string) wp_unslash($_GET['package'])) : '');
        $package = null;
        if ($requested !== '') {
            $found = PackageStore::find($requested);
            if ($found !== null && (($found['status'] ?? 'draft') === 'published' || $is_admin)) {
                $package = $found;
            }
        } elseif (count($published) === 1) {
            $package = $published[0];
        } elseif (count($all) === 1 && $is_admin) {
            $package = $all[0];
        }

        return [
            'type' => $package === null ? 'catalog' : 'package',
            'package' => $package,
        ];
    }
}
