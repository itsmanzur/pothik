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
        add_action('admin_init', [self::class, 'privacy_text']);
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
        // A cached page can show an old code, so assets/js/public.js refreshes this field in the browser.
        echo '<input type="hidden" name="ref" value="' . esc_attr(self::from_query()) . '">';
    }

    /**
     * Describe the cookies in the site's privacy policy guide (Settings > Privacy).
     */
    public static function privacy_text(): void
    {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }
        $text = '<h2>' . esc_html__('Pothik cookies', 'pothik') . '</h2>'
            . '<p>' . esc_html__('When a package or catalog page is opened with a referral code (?ref=), Pothik stores that code in a first-party cookie named pothik_ref for 30 days. The cookie holds only the code, no personal data. When a visitor picks a language, Pothik stores their choice (en or bn) in a cookie named pothik_lang for one year. For a signed-in user the choice is also saved in their profile.', 'pothik') . '</p>'
            . '<h2>' . esc_html__('Pothik inquiries', 'pothik') . '</h2>'
            . '<p>' . esc_html__('When a visitor sends an inquiry, Pothik saves the name, phone, WhatsApp number, city, room share, number of pilgrims and note in the site database, and emails them to the office. To limit spam it keeps a short-lived counter per visitor address for one hour.', 'pothik') . '</p>';
        wp_add_privacy_policy_content('Pothik', wp_kses_post(wpautop($text, false)));
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
