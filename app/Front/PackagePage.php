<?php
/**
 * Public package page.
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
            return POTHIK_DIR . 'templates/front.php';
        }

        return $template;
    }

    public static function shortcode(): string
    {
        ob_start();
        self::render();

        return (string) ob_get_clean();
    }

    public static function enqueue(): void
    {
        $css = POTHIK_DIR . 'assets/css/public.css';
        $js = POTHIK_DIR . 'assets/js/public.js';
        wp_enqueue_style(
            'pothik-fonts',
            'https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap',
            [],
            null
        );
        wp_enqueue_style(
            'pothik-public',
            POTHIK_URL . 'assets/css/public.css',
            ['pothik-fonts'],
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

    public static function render(): void
    {
        self::enqueue();

        $packages = PackageStore::all();
        $requested = isset($_GET['package']) ? sanitize_key((string) wp_unslash($_GET['package'])) : '';
        $package = $requested !== '' ? PackageStore::find($requested) : null;
        if ($package === null && count($packages) === 1 && $requested === '') {
            $package = $packages[0];
        }
        if ($package === null) {
            include POTHIK_DIR . 'templates/catalog.php';
            return;
        }

        $result = isset($_GET['pothik']) ? sanitize_key((string) wp_unslash($_GET['pothik'])) : '';
        $seat_line = '';
        if (count($packages) === 1) {
            $line = apply_filters('pothik/seat_line', '');
            $seat_line = is_string($line) ? $line : '';
        }
        include POTHIK_DIR . 'templates/package.php';
    }
}
