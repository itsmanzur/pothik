<?php
/**
 * Remembers a referral code for the inquiry form. No agent logic.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik;

if (!defined('ABSPATH')) {
    exit;
}

final class Referral
{
    public static function register(): void
    {
        add_action('wp', [self::class, 'capture']);
    }

    public static function capture(): void
    {
        if (is_admin()) {
            return;
        }
        $code = self::from_query();
        if ($code === '' || !self::on_pothik_page() || headers_sent()) {
            return;
        }
        if (!apply_filters('pothik/referral_cookie', true)) {
            return;
        }
        setcookie('pothik_ref', $code, [
            'expires' => time() + 30 * DAY_IN_SECONDS,
            'path' => '/',
            'secure' => is_ssl(),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['pothik_ref'] = $code;
    }

    public static function sanitize(string $value): string
    {
        $value = strtoupper(sanitize_text_field($value));
        if (!preg_match('/^[A-Z0-9]{2,10}$/', $value)) {
            return '';
        }

        return $value;
    }

    public static function from_query(): string
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $raw = isset($_GET['ref']) ? sanitize_text_field(wp_unslash((string) $_GET['ref'])) : '';

        return self::sanitize($raw);
    }

    public static function current(): string
    {
        $from_query = self::from_query();
        if ($from_query !== '') {
            return $from_query;
        }
        $cookie = isset($_COOKIE['pothik_ref']) ? sanitize_text_field(wp_unslash((string) $_COOKIE['pothik_ref'])) : '';

        return self::sanitize($cookie);
    }

    public static function field(): void
    {
        echo '<input type="hidden" name="ref" value="' . esc_attr(self::from_query()) . '">';
        echo '<script>(function(){var q=location.search.match(/[?&]ref=([A-Za-z0-9]{2,10})/i);var v=q?q[1].toUpperCase():"";if(!v){var c=document.cookie.match(/(?:^|; )pothik_ref=([A-Za-z0-9]{2,10})/);v=c?c[1].toUpperCase():"";}if(!v)return;document.querySelectorAll(\'input[name="ref"]\').forEach(function(el){el.value=v;});})();</script>';
    }

    private static function on_pothik_page(): bool
    {
        $page_id = (int) get_option('pothik_page_id');
        if ($page_id > 0 && is_page($page_id)) {
            return true;
        }
        $post = get_post();
        if (!$post instanceof \WP_Post) {
            return false;
        }
        foreach (['pothik_package', 'pothik_catalog', 'pothik'] as $tag) {
            if (has_shortcode($post->post_content, $tag)) {
                return true;
            }
        }

        return false;
    }
}
