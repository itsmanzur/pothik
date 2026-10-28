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

        foreach (Modules::all() as $id => $module) {
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
        }
    }

    public static function assets(string $hook): void
    {
        if (!str_contains($hook, 'pothik')) {
            return;
        }

        $css = POTHIK_DIR . 'assets/css/admin.css';
        $js = POTHIK_DIR . 'assets/js/admin.js';
        wp_enqueue_style(
            'pothik-fonts',
            'https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap',
            [],
            null
        );
        wp_enqueue_style(
            'pothik-admin',
            POTHIK_URL . 'assets/css/admin.css',
            ['pothik-fonts'],
            is_readable($css) ? (string) filemtime($css) : POTHIK_VERSION
        );
        wp_enqueue_script(
            'pothik-admin',
            POTHIK_URL . 'assets/js/admin.js',
            [],
            is_readable($js) ? (string) filemtime($js) : POTHIK_VERSION,
            true
        );
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

        Screens::locked($module);
    }
}
