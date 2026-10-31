<?php
/**
 * Who may open a Pothik screen. Pro extends this through pothik/can.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik;

use Pothik\Admin\Chrome;

if (!defined('ABSPATH')) {
    exit;
}

final class Access
{
    public const AREAS = ['packages', 'inquiries', 'receipt', 'analytics', 'settings'];

    /**
     * Admin page slug to an area name. Pro slugs are names only; this plugin does not grant them.
     *
     * @var array<string, string>
     */
    private const PAGES = [
        'pothik-package' => 'packages',
        'pothik-packages' => 'packages',
        'pothik-inquiries' => 'inquiries',
        'pothik-receipt' => 'receipt',
        'pothik-analytics' => 'analytics',
        'pothik-settings' => 'settings',
        'pothik-departures' => 'groups',
        'pothik-pilgrims' => 'pilgrims',
        'pothik-rooming' => 'rooming',
        'pothik-ledger' => 'ledger',
        'pothik-visa' => 'visa',
        'pothik-team' => 'team',
        'pothik-activity' => 'activity',
    ];

    public static function can(string $area, string $level = 'view'): bool
    {
        if (current_user_can('manage_options')) {
            return true;
        }
        $level = $level === 'edit' ? 'edit' : 'view';
        $allowed = apply_filters('pothik/can', false, $area, $level, get_current_user_id());

        return (bool) $allowed;
    }

    public static function menu_cap(string $area): string
    {
        return self::can($area, 'view') ? 'read' : 'manage_options';
    }

    public static function any(): bool
    {
        if (current_user_can('manage_options')) {
            return true;
        }
        foreach (array_merge(self::AREAS, ['groups', 'pilgrims', 'rooming', 'visa', 'ledger', 'receipt', 'team', 'activity']) as $area) {
            if (self::can($area, 'view')) {
                return true;
            }
        }

        return false;
    }

    public static function area_for_page(string $page): string
    {
        if ($page === 'pothik-package' || str_starts_with($page, 'pothik-package')) {
            return 'packages';
        }

        return self::PAGES[$page] ?? '';
    }

    public static function area_for_module(string $id): string
    {
        return $id === 'package' ? 'packages' : ($id === 'departures' ? 'groups' : $id);
    }

    public static function readonly(): bool
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash((string) $_GET['page'])) : '';
        $area = self::area_for_page($page);
        if ($area === '') {
            return false;
        }

        return self::can($area, 'view') && !self::can($area, 'edit');
    }

    public static function deny(): void
    {
        if (!headers_sent()) {
            status_header(403);
            nocache_headers();
        }
        $message = I18n::t("You don't have access to this screen.");
        $ask = I18n::t('Ask your manager if you need it.');
        $back = I18n::t('Back to Desk');
        $url = admin_url('admin.php?page=pothik');
        if (class_exists(Chrome::class) && is_admin()) {
            Chrome::open('Pothik', '');
            echo '<section class="pk-card pk-empty"><b>' . esc_html($message) . '</b>';
            echo '<p>' . esc_html($ask) . '</p>';
            echo '<a class="pk-btn pk-btn-primary" href="' . esc_url($url) . '">' . esc_html($back) . '</a></section>';
            Chrome::close();
            exit;
        }
        wp_die(
            wp_kses_post($message . ' <a href="' . esc_url($url) . '">' . esc_html($back) . '</a>'),
            esc_html($message),
            ['response' => 403, 'back_link' => false]
        );
    }

    public static function on_denied(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash((string) $_GET['page'])) : '';
        if (!str_starts_with($page, 'pothik')) {
            return;
        }
        self::deny();
    }
}
