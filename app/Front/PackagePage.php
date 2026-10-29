<?php
/**
 * Public package page and shortcodes.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik\Front;

use Pothik\PackageStore;

if (!defined('ABSPATH')) {
    exit;
}

final class PackagePage
{
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
            self::enqueue();

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
    public static function catalog_shortcode(): string
    {
        self::enqueue();
        ob_start();
        self::render_catalog();

        return (string) ob_get_clean();
    }

    public static function enqueue(): void
    {
        $css = POTHIK_DIR . 'assets/css/public.css';
        $js = POTHIK_DIR . 'assets/js/public.js';
        wp_enqueue_style(
            'pothik-public',
            POTHIK_URL . 'assets/css/public.css',
            [],
            is_readable($css) ? (string) filemtime($css) : POTHIK_VERSION
        );
        wp_enqueue_script(
            'pothik-public',
            POTHIK_URL . 'assets/js/public.js',
            [],
            is_readable($js) ? (string) filemtime($js) : POTHIK_VERSION,
            true
        );
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
            $packages = $is_admin ? $all : $published;
            include POTHIK_DIR . 'templates/catalog.php';

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
        $packages = $is_admin ? $all : array_values(array_filter($all, static fn (array $p): bool => ($p['status'] ?? 'draft') === 'published'));

        include POTHIK_DIR . 'templates/catalog.php';
    }
}
