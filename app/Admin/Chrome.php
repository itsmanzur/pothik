<?php
/**
 * Admin chrome shared by free screens and pro screens.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik\Admin;

use Pothik\Front\PackagePage;
use Pothik\I18n;
use Pothik\InquiryRepository;
use Pothik\Modules;

if (!defined('ABSPATH')) {
    exit;
}

final class Chrome
{
    private static bool $sprite_printed = false;

    public static function open(string $title, string $lead = '', string $actions_html = ''): void
    {
        // 1. Print SVG icon sprite once
        if (!self::$sprite_printed) {
            $sprite_file = dirname(__DIR__, 2) . '/templates/admin/svg-sprite.php';
            if (file_exists($sprite_file)) {
                require $sprite_file;
            }
            self::$sprite_printed = true;
        }

        $public_url = PackagePage::url();
        $current_page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : 'pothik';
        $wait_count = InquiryRepository::count_waiting();

        // Check if any module is locked (to show Pro with sparkle)
        $has_locked = false;
        foreach (Modules::all() as $m) {
            if (!Modules::is_open($m)) {
                $has_locked = true;
                break;
            }
        }

        $tabs = [
            'desk' => [
                'slug' => 'pothik',
                'label' => I18n::t('Desk'),
                'icon' => 'i-home',
                'active' => ($current_page === 'pothik' || $current_page === ''),
            ],
            'packages' => [
                'slug' => 'pothik-package',
                'label' => I18n::t('Packages'),
                'icon' => 'i-box',
                'active' => in_array($current_page, ['pothik-package', 'pothik-packages'], true),
            ],
            'inquiries' => [
                'slug' => 'pothik-inquiries',
                'label' => I18n::t('Inquiries'),
                'icon' => 'i-inbox',
                'badge' => $wait_count,
                'active' => ($current_page === 'pothik-inquiries'),
            ],
            'receipt' => [
                'slug' => 'pothik-receipt',
                'label' => I18n::t('Receipt'),
                'icon' => 'i-receipt',
                'active' => ($current_page === 'pothik-receipt'),
            ],
            'analytics' => [
                'slug' => 'pothik-analytics',
                'label' => I18n::t('Analytics'),
                'icon' => 'i-chart',
                'active' => ($current_page === 'pothik-analytics'),
            ],
            'settings' => [
                'slug' => 'pothik-settings',
                'label' => I18n::t('Settings'),
                'icon' => 'i-gear',
                'active' => ($current_page === 'pothik-settings'),
            ],
        ];

        if ($has_locked) {
            $tabs['pro'] = [
                'slug' => 'pothik-pro',
                'label' => 'Pro',
                'icon' => 'i-spark',
                'is_pro' => true,
                'active' => ($current_page === 'pothik-pro'),
            ];
        }

        echo '<div class="wrap pk-admin">';

        // Sticky App Bar
        echo '<header class="pk-appbar appbar" id="appbar">';

        // Brand
        echo '<a class="pk-brand brand" href="' . esc_url(admin_url('admin.php?page=pothik')) . '">';
        echo '<span class="pk-brand-mark brand-mark"><svg class="i lg" aria-hidden="true"><use href="#i-pin"/></svg></span>';
        echo '<span><b>Pothik</b><small>' . esc_html(I18n::t('Umrah desk')) . '</small></span>';
        echo '</a>';

        // Nav tabs
        echo '<nav class="pk-tabs tabs" aria-label="' . esc_attr(I18n::t('Pothik sections')) . '">';
        foreach ($tabs as $tab) {
            $class = 'pk-tab-btn';
            if (!empty($tab['active'])) {
                $class .= ' on';
            }
            if (!empty($tab['is_pro'])) {
                $class .= ' pk-pro pro';
            }
            $href = admin_url('admin.php?page=' . $tab['slug']);
            echo '<a class="' . esc_attr($class) . '" href="' . esc_url($href) . '">';
            echo '<svg class="i sm" aria-hidden="true"><use href="#' . esc_attr($tab['icon']) . '"/></svg>';
            echo esc_html($tab['label']);
            if (isset($tab['badge']) && $tab['badge'] > 0) {
                echo ' <span class="badge" data-pk-wait-count>' . (int) $tab['badge'] . '</span>';
            }
            echo '</a>';
        }
        echo '</nav>';

        // Tools
        echo '<div class="pk-tools tools">';
        echo '<button type="button" class="pk-kbd-btn kbd-btn" data-pk-cmdk data-cmdk aria-label="' . esc_attr(I18n::t('Search')) . '">';
        echo '<svg class="i sm" aria-hidden="true"><use href="#i-search"/></svg><kbd>Ctrl K</kbd>';
        echo '</button>';

        // Language toggle
        echo '<div class="pk-seg seg" role="group" aria-label="' . esc_attr(I18n::t('Language')) . '">';
        $current_lang = I18n::lang();
        echo '<a class="' . ($current_lang === 'en' ? 'on' : '') . '" href="' . esc_url(add_query_arg('pothik_lang', 'en')) . '">EN</a>';
        echo '<a class="' . ($current_lang === 'bn' ? 'on' : '') . '" href="' . esc_url(add_query_arg('pothik_lang', 'bn')) . '">বাংলা</a>';
        echo '</div>';

        // Public site button
        if ($public_url !== '') {
            echo '<a class="pk-btn pk-btn-ghost pk-btn-sm btn btn-ghost btn-sm" href="' . esc_url($public_url) . '" target="_blank" rel="noopener" title="' . esc_attr(I18n::t('View on site')) . '">';
            echo '<svg class="i sm" aria-hidden="true"><use href="#i-ext"/></svg>' . esc_html(I18n::t('Site'));
            echo '</a>';
        }
        echo '</div>'; // .tools
        echo '</header>'; // .appbar

        // Page Head
        echo '<div class="pk-page-head page-head">';
        echo '<div>';
        echo '<h1>' . esc_html(I18n::t($title)) . '</h1>';
        if ($lead !== '') {
            echo '<p class="muted">' . esc_html(I18n::t($lead)) . '</p>';
        }
        echo '</div>';
        if ($actions_html !== '') {
            echo '<div class="pk-page-actions">' . $actions_html . '</div>';
        }
        echo '</div>';

        // Flash notices
        self::flash();
    }

    public static function close(): void
    {
        // Command Palette dialog
        echo '<div class="pk-cmdk-bg cmdk-bg" id="cmdk" hidden>';
        echo '<div class="pk-cmdk cmdk" role="dialog" aria-modal="true" aria-label="' . esc_attr(I18n::t('Command palette')) . '">';
        echo '<div class="pk-cmdk-in cmdk-in">';
        echo '<svg class="i sm" aria-hidden="true"><use href="#i-search"/></svg>';
        echo '<input id="cmdkIn" placeholder="' . esc_attr(I18n::t('Type a command or search pilgrims...')) . '" autocomplete="off">';
        echo '<kbd>Esc</kbd>';
        echo '</div>';
        echo '<ul id="cmdkList"></ul>';
        echo '</div>';
        echo '</div>';

        // Toasts live region
        echo '<div class="pk-toasts toasts" id="toasts" aria-live="polite"></div>';

        // Hidden forms for Command Palette quick actions
        echo '<form id="pk-cmdk-new-pkg" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" hidden>';
        wp_nonce_field('pothik_new_package');
        echo '<input type="hidden" name="action" value="pothik_new_package">';
        echo '</form>';

        echo '<form id="pk-cmdk-export-csv" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" hidden>';
        wp_nonce_field('pothik_export_inquiries');
        echo '<input type="hidden" name="action" value="pothik_export_inquiries">';
        echo '</form>';

        echo '</div>'; // .pk-admin
    }

    public static function saved(): void
    {
        self::flash();
    }

    public static function flash(): void
    {
        static $flashed = false;
        if ($flashed) {
            return;
        }

        $items = [
            'updated' => [I18n::t('Saved.'), 'success'],
            'demo_loaded' => [I18n::t('Demo data loaded.'), 'success'],
            'demo_removed' => [I18n::t('Demo data removed.'), 'info'],
            'recreated' => [I18n::t('Package page has been recreated.'), 'success'],
        ];

        foreach ($items as $key => [$msg, $type]) {
            if (isset($_GET[$key])) {
                echo '<div data-pk-flash="' . esc_attr($msg) . '" class="notice notice-' . esc_attr($type) . ' is-dismissible pk-flash"><p>' . esc_html($msg) . '</p></div>';
                $flashed = true;
            }
        }

        if (isset($_GET['error']) && $_GET['error'] === 'limit') {
            $msg = I18n::t('Free version is limited to 40 packages. Upgrade to Pothik Pro for unlimited packages.');
            echo '<div data-pk-flash="' . esc_attr($msg) . '" class="notice notice-warning is-dismissible pk-flash"><p>' . esc_html($msg) . '</p></div>';
            $flashed = true;
        }
    }
}
