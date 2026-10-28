<?php
/**
 * Admin menu. Locked rows stay in this plugin as a title and one sentence.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik\Admin;

use Pothik\I18n;
use Pothik\Modules;

if (!defined('ABSPATH')) {
    exit;
}

final class Menu
{
    public static function register(): void
    {
        add_menu_page(
            'Pothik',
            'Pothik',
            'manage_options',
            'pothik',
            [self::class, 'overview'],
            'dashicons-location',
            58
        );

        add_submenu_page('pothik', 'Pothik', I18n::t('Desk'), 'manage_options', 'pothik', [self::class, 'overview']);

        $has_locked = false;
        foreach (Modules::all() as $id => $module) {
            if (Modules::is_open($module)) {
                $title = I18n::t(isset($module['title']) ? (string) $module['title'] : $id);
                $menu = I18n::t(isset($module['menu']) ? (string) $module['menu'] : $title);
                add_submenu_page(
                    'pothik',
                    $title,
                    $menu,
                    'manage_options',
                    'pothik-' . $id,
                    static function () use ($id): void {
                        self::dispatch($id);
                    }
                );
            } else {
                $has_locked = true;
            }
        }

        if ($has_locked) {
            add_submenu_page(
                'pothik',
                'Pothik Pro',
                'Pothik Pro',
                'manage_options',
                'pothik-pro',
                [Screens::class, 'pro']
            );
        }

        add_submenu_page(
            'pothik',
            I18n::t('Settings'),
            I18n::t('Settings'),
            'manage_options',
            'pothik-settings',
            [Settings::class, 'render']
        );
    }

    public static function assets(string $hook): void
    {
        if (!str_contains($hook, 'pothik')) {
            return;
        }

        if (str_contains($hook, 'pothik-settings')) {
            wp_enqueue_media();
        }

        $css = POTHIK_DIR . 'assets/css/admin.css';
        $js = POTHIK_DIR . 'assets/js/admin.js';
        wp_enqueue_style(
            'pothik-admin',
            POTHIK_URL . 'assets/css/admin.css',
            [],
            is_readable($css) ? (string) filemtime($css) : POTHIK_VERSION
        );
        wp_enqueue_script(
            'pothik-admin',
            POTHIK_URL . 'assets/js/admin.js',
            [],
            is_readable($js) ? (string) filemtime($js) : POTHIK_VERSION,
            true
        );

        wp_localize_script('pothik-admin', 'pothikAdmin', [
            'ajax' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pothik_admin'),
            'i18n' => [
                'saved' => I18n::t('Saved.'),
                'copied' => I18n::t('Copied.'),
                'copy' => I18n::t('Copy'),
                'undo' => I18n::t('Undo'),
                'discard' => I18n::t('Discard'),
                'search' => I18n::t('Search...'),
                'searching' => I18n::t('Searching...'),
                'noResults' => I18n::t('No results found.'),
                'markedReplied' => I18n::t('Marked replied.'),
                'markedWaiting' => I18n::t('Marked waiting.'),
                'unsavedChanges' => I18n::t('You have unsaved changes. Are you sure you want to leave?'),
                'goTo' => I18n::t('Go to'),
                'actions' => I18n::t('Actions'),
                'pilgrims' => I18n::t('Pilgrims'),
                'desk' => I18n::t('Desk'),
                'packages' => I18n::t('Packages'),
                'inquiries' => I18n::t('Inquiries'),
                'receipt' => I18n::t('Receipt'),
                'analytics' => I18n::t('Analytics'),
                'settings' => I18n::t('Settings'),
                'newPackage' => I18n::t('New package'),
                'exportCsv' => I18n::t('Export inquiries CSV'),
                'copyCatalog' => I18n::t('Copy [pothik_catalog]'),
                'catalogCopied' => I18n::t('Copied [pothik_catalog]'),
                'pro' => I18n::t('Pothik Pro'),
                'allCaughtUp' => I18n::t('All caught up, alhamdulillah'),
                'newInquiriesHere' => I18n::t('New inquiries will appear here.'),
                'inquiry' => I18n::t('Inquiry'),
                'dua1' => I18n::t("Every reply you send could be someone's first step towards the Haram."),
                'dua2' => I18n::t("May Allah accept the Umrah of every pilgrim you serve."),
                'dua3' => I18n::t("Serve the guests of Ar-Rahman with ihsan — they remember the agency that cared."),
                'dua4' => I18n::t("A pilgrim's dua at the Multazam may include your name."),
                'publishedLive' => I18n::t('Published — live on your site'),
                'movedToDraft' => I18n::t('Moved to draft'),
                'packageDuplicated' => I18n::t('Package duplicated'),
                'packageDeleted' => I18n::t('Package deleted'),
                'packageSaved' => I18n::t('Package saved'),
                'changesDiscarded' => I18n::t('Changes discarded'),
                'deletePackageConfirm' => I18n::t('Delete this package?'),
                'yes' => I18n::t('Yes'),
                'cancel' => I18n::t('Cancel'),
                'showWpMenu' => I18n::t('Show WP menu'),
                'wpMenu' => I18n::t('WP menu'),
                'focusEnabled' => I18n::t('WordPress menu hidden — full-screen desk'),
                'focusDisabled' => I18n::t('WordPress menu shown'),
                'adminUrl' => admin_url('admin.php'),
            ],
        ]);
    }

    public static function overview(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        Screens::overview();
    }

    public static function dispatch(string $id): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $modules = Modules::all();
        if (!isset($modules[$id]) || !is_array($modules[$id])) {
            Screens::overview();
            return;
        }

        $module = $modules[$id];
        if (Modules::is_open($module)) {
            call_user_func($module['render']);
            return;
        }

        Screens::pro();
    }
}
