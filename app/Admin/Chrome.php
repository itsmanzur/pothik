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
use Pothik\PackageStore;

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

        $user = wp_get_current_user();
        $display_name = $user && $user->display_name ? $user->display_name : 'admin';
        $role = !empty($user->roles[0]) ? ucfirst((string) $user->roles[0]) : 'Administrator';
        $initial = Screens::avatar_initial($display_name);

        $user_id = get_current_user_id();
        $is_focus = $user_id ? (get_user_meta($user_id, 'pothik_focus', true) === '1') : false;
        $is_mini = $user_id ? (get_user_meta($user_id, 'pothik_side_mini', true) === '1') : false;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $current_page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : 'pothik';
        $wait_count = InquiryRepository::count_waiting();
        $packages_count = count(PackageStore::all());

        // Pro items
        $icon_map = [
            'departures' => 'i-plane',
            'pilgrims' => 'i-passport',
            'rooming' => 'i-bed',
            'ledger' => 'i-wallet',
            'visa' => 'i-shield',
        ];
        $pro_nav_items = [];
        $free_slugs = ['package', 'inquiries', 'receipt', 'analytics'];
        foreach (Modules::all() as $m_id => $m_data) {
            if (in_array($m_id, $free_slugs, true)) {
                continue;
            }
            $is_open = Modules::is_open($m_data);
            $icon = $icon_map[$m_id] ?? 'i-spark';
            $item_title = isset($m_data['title']) ? (string) $m_data['title'] : ucfirst($m_id);
            $pro_nav_items[] = [
                'slug' => 'pothik-' . $m_id,
                'label' => I18n::t($item_title),
                'icon' => $icon,
                'url' => $is_open ? admin_url('admin.php?page=pothik-' . $m_id) : admin_url('admin.php?page=pothik-pro'),
                'locked' => !$is_open,
                'active' => ($current_page === 'pothik-' . $m_id),
            ];
        }

        $nav_groups = [
            'overview' => [
                'label' => I18n::t('Overview'),
                'items' => [
                    [
                        'slug' => 'pothik',
                        'label' => I18n::t('Desk'),
                        'icon' => 'i-home',
                        'url' => admin_url('admin.php?page=pothik'),
                        'active' => in_array($current_page, ['pothik', ''], true),
                    ],
                    [
                        'slug' => 'pothik-analytics',
                        'label' => I18n::t('Analytics'),
                        'icon' => 'i-chart',
                        'url' => admin_url('admin.php?page=pothik-analytics'),
                        'active' => ($current_page === 'pothik-analytics'),
                    ],
                ],
            ],
            'packages' => [
                'label' => I18n::t('Packages'),
                'items' => [
                    [
                        'slug' => 'pothik-package',
                        'label' => I18n::t('All packages'),
                        'icon' => 'i-box',
                        'url' => admin_url('admin.php?page=pothik-package'),
                        'count' => $packages_count,
                        'active' => in_array($current_page, ['pothik-package', 'pothik-packages'], true),
                    ],
                    [
                        'slug' => 'new_package',
                        'label' => I18n::t('New package'),
                        'icon' => 'i-plus',
                        'is_form_action' => 'pothik_new_package',
                    ],
                ],
            ],
            'pilgrims' => [
                'label' => I18n::t('Pilgrims'),
                'items' => [
                    [
                        'slug' => 'pothik-inquiries',
                        'label' => I18n::t('Inquiries'),
                        'icon' => 'i-inbox',
                        'url' => admin_url('admin.php?page=pothik-inquiries'),
                        'count' => $wait_count,
                        'count_hot' => ($wait_count > 0),
                        'active' => ($current_page === 'pothik-inquiries'),
                    ],
                    [
                        'slug' => 'pothik-receipt',
                        'label' => I18n::t('Receipt'),
                        'icon' => 'i-receipt',
                        'url' => admin_url('admin.php?page=pothik-receipt'),
                        'active' => ($current_page === 'pothik-receipt'),
                    ],
                ],
            ],
            'pro' => [
                'label' => 'Pro',
                'items' => $pro_nav_items,
            ],
            'system' => [
                'label' => I18n::t('System'),
                'items' => [
                    [
                        'slug' => 'pothik-settings',
                        'label' => I18n::t('Settings'),
                        'icon' => 'i-gear',
                        'url' => admin_url('admin.php?page=pothik-settings'),
                        'active' => ($current_page === 'pothik-settings'),
                    ],
                ],
            ],
        ];

        /** @var array<string, array<string, mixed>> $nav_groups */
        $nav_groups = apply_filters('pothik/admin_nav', $nav_groups);

        echo '<div class="pk-admin">';
        echo '<div class="pk-app' . ($is_mini ? ' mini' : '') . '" id="pkApp">';

        // Dedicated Pothik Sidebar
        echo '<aside class="pk-side" aria-label="' . esc_attr(I18n::t('Pothik menu')) . '">';
        echo '<div class="side-top">';
        echo '<span class="brand-mark"><svg class="i lg" aria-hidden="true"><use href="#i-pin"/></svg></span>';
        echo '<div><b>Pothik</b><small>' . esc_html(I18n::t('Umrah desk')) . '</small></div>';
        echo '<button type="button" class="side-collapse" id="sideToggle" aria-label="' . esc_attr(I18n::t('Collapse menu')) . '" title="' . esc_attr(I18n::t('Collapse menu')) . '">';
        echo '<svg class="i sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M15 6l-6 6 6 6"/></svg>';
        echo '</button>';
        echo '</div>'; // .side-top

        echo '<nav class="side-nav">';
        foreach ($nav_groups as $grp_key => $grp) {
            if (empty($grp['items']) || !is_array($grp['items'])) {
                continue;
            }
            echo '<div class="side-grp">' . esc_html($grp['label']) . '</div>';
            foreach ($grp['items'] as $item) {
                if (!empty($item['is_form_action'])) {
                    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="form-contents">';
                    wp_nonce_field($item['is_form_action']);
                    echo '<input type="hidden" name="action" value="' . esc_attr($item['is_form_action']) . '">';
                    echo '<button type="submit" class="side-nav-btn" title="' . esc_attr($item['label']) . '">';
                    echo '<svg class="i" aria-hidden="true"><use href="#' . esc_attr($item['icon']) . '"/></svg>';
                    echo '<span>' . esc_html($item['label']) . '</span>';
                    echo '</button>';
                    echo '</form>';
                } else {
                    $class = '';
                    if (!empty($item['active'])) {
                        $class .= ' on';
                    }
                    if (!empty($item['locked'])) {
                        $class .= ' locked';
                    }
                    $class = trim($class);
                    $url = (string) ($item['url'] ?? '#');
                    echo '<a href="' . esc_url($url) . '"' . ($class !== '' ? ' class="' . esc_attr($class) . '"' : '') . ' title="' . esc_attr($item['label']) . '"' . (!empty($item['active']) ? ' aria-current="page"' : '') . '>';
                    echo '<svg class="i" aria-hidden="true"><use href="#' . esc_attr($item['icon']) . '"/></svg>';
                    echo '<span>' . esc_html($item['label']) . '</span>';
                    if (isset($item['count'])) {
                        $cnt_class = 'cnt' . (!empty($item['count_hot']) ? ' hot' : '');
                        if ($item['slug'] === 'pothik-inquiries') {
                            echo '<b class="' . esc_attr($cnt_class) . '" data-wait-count>' . (int) $item['count'] . '</b>';
                        } else {
                            echo '<b class="' . esc_attr($cnt_class) . '">' . (int) $item['count'] . '</b>';
                        }
                    }
                    if (!empty($item['locked'])) {
                        echo '<span class="lk"><svg class="i sm" aria-hidden="true"><use href="#i-lock"/></svg></span>';
                    }
                    echo '</a>';
                }
            }
        }
        echo '</nav>'; // .side-nav

        echo '<div class="side-user">';
        echo '<span class="ava">' . esc_html($initial) . '</span>';
        echo '<div><b>' . esc_html($display_name) . '</b><small>' . esc_html($role) . '</small></div>';
        echo '<span class="ver">v' . esc_html(POTHIK_VERSION) . '</span>';
        echo '</div>'; // .side-user

        echo '</aside>'; // .pk-side

        // Body column
        echo '<div class="pk-body">';

        // Header
        echo '<header class="pk-head">';
        echo '<div>';
        echo '<h1 id="hTitle">' . esc_html(I18n::t($title)) . '</h1>';
        if ($lead !== '') {
            echo '<p id="hLead">' . esc_html(I18n::t($lead)) . '</p>';
        }
        echo '</div>';

        echo '<div class="tools">';
        echo '<button type="button" class="kbd-btn" data-pk-cmdk data-cmdk aria-label="' . esc_attr(I18n::t('Search')) . '">';
        echo '<svg class="i sm" aria-hidden="true"><use href="#i-search"/></svg><kbd>Ctrl K</kbd>';
        echo '</button>';

        // Language toggle
        $current_lang = I18n::lang();
        echo '<div class="pk-seg seg" role="group" aria-label="' . esc_attr(I18n::t('Language')) . '">';
        echo '<a class="' . ($current_lang === 'en' ? 'on' : '') . '" href="' . esc_url(add_query_arg('pothik_lang', 'en')) . '">EN</a>';
        echo '<a class="' . ($current_lang === 'bn' ? 'on' : '') . '" href="' . esc_url(add_query_arg('pothik_lang', 'bn')) . '">বাংলা</a>';
        echo '</div>';

        // Full Screen / Focus toggle button
        echo '<button type="button" class="btn btn-ghost" id="focusBtn" title="' . esc_attr(I18n::t('Toggle full screen (Ctrl+Shift+F)')) . '" aria-pressed="' . ($is_focus ? 'true' : 'false') . '">';
        echo '<svg class="i sm" aria-hidden="true"><use href="#i-ext"/></svg>';
        echo '<span>' . esc_html($is_focus ? I18n::t('Show WP menu') : I18n::t('WP menu')) . '</span>';
        echo '</button>';

        // New Package Button
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="inline-form">';
        wp_nonce_field('pothik_new_package');
        echo '<input type="hidden" name="action" value="pothik_new_package">';
        echo '<button type="submit" class="btn btn-primary"><svg class="i sm" aria-hidden="true"><use href="#i-plus"/></svg>' . esc_html(I18n::t('New package')) . '</button>';
        echo '</form>';

        echo '</div>'; // .tools
        echo '</header>'; // .pk-head

        echo '<hr class="wp-header-end">';

        echo '<div class="pk" id="app">';

        if ($actions_html !== '') {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<div class="pk-page-actions">' . $actions_html . '</div>';
        }

        self::flash();
    }

    public static function close(): void
    {
        echo '</div>'; // #app (.pk)
        echo '</div>'; // .pk-body
        echo '</div>'; // .pk-app

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
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (isset($_GET[$key])) {
                echo '<div data-pk-flash="' . esc_attr($msg) . '" class="notice notice-' . esc_attr($type) . ' is-dismissible pk-flash"><p>' . esc_html($msg) . '</p></div>';
                $flashed = true;
            }
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['error']) && $_GET['error'] === 'limit') {
            $msg = I18n::t('Free version is limited to 40 packages. Upgrade to Pothik Pro for unlimited packages.');
            echo '<div data-pk-flash="' . esc_attr($msg) . '" class="notice notice-warning is-dismissible pk-flash"><p>' . esc_html($msg) . '</p></div>';
            $flashed = true;
        }
    }
}
