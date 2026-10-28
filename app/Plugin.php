<?php
/**
 * Bootstrap.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik;

use Pothik\Admin\Menu;
use Pothik\Admin\Settings;
use Pothik\Front\PackagePage;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    public static function boot(): void
    {
        InquiryRepository::maybe_migrate();

        add_action('admin_menu', [Menu::class, 'register']);
        add_action('admin_enqueue_scripts', [Menu::class, 'assets']);
        add_action('admin_notices', [self::class, 'welcome']);
        add_action('admin_post_pothik_dismiss_welcome', [self::class, 'dismiss_welcome']);
        add_action('admin_post_pothik_save_package', [PackageStore::class, 'handle_save']);
        add_action('admin_post_pothik_new_package', [PackageStore::class, 'handle_create']);
        add_action('admin_post_pothik_delete_package', [PackageStore::class, 'handle_delete']);
        add_action('admin_post_pothik_copy_package', [PackageStore::class, 'handle_copy']);
        add_action('admin_post_pothik_toggle_package_status', [PackageStore::class, 'handle_toggle_status']);
        add_action('admin_post_pothik_save_receipt', [ReceiptStore::class, 'handle_save']);
        add_action('admin_post_pothik_delete_inquiry', [InquiryStore::class, 'handle_delete']);
        add_action('admin_post_pothik_reply_inquiry', [InquiryStore::class, 'handle_reply']);
        add_action('admin_post_pothik_export_inquiries', [InquiryRepository::class, 'export_csv']);
        add_action('admin_post_pothik_bulk_inquiries', [InquiryRepository::class, 'handle_bulk']);
        add_action('admin_post_pothik_load_demo', [self::class, 'handle_load_demo']);
        add_action('admin_post_pothik_remove_demo', [self::class, 'handle_remove_demo']);
        add_action('admin_post_pothik_publish_page', [self::class, 'handle_publish_page']);
        add_action('admin_post_pothik_recreate_page', [self::class, 'handle_recreate_page']);
        add_action('admin_post_pothik_save_settings', [Settings::class, 'handle_save']);
        add_action('admin_init', [Settings::class, 'register_settings']);
        add_action('wp_ajax_pothik_search_inquiries', [InquiryRepository::class, 'ajax_search']);
        add_action('wp_ajax_pothik_toggle_reply', [InquiryRepository::class, 'ajax_toggle_reply']);
        add_action('wp_ajax_pothik_inquiries_table', [Screens::class, 'ajax_inquiries_table']);
        add_action('wp_ajax_pothik_test_email', [Settings::class, 'ajax_test_email']);
        add_action('wp_ajax_pothik_toggle_package_status_ajax', [PackageStore::class, 'ajax_toggle_status']);
        add_action('wp_ajax_pothik_duplicate_package_ajax', [PackageStore::class, 'ajax_copy']);
        add_action('wp_ajax_pothik_delete_package_ajax', [PackageStore::class, 'ajax_delete']);
        add_action('admin_post_pothik_hide_setup', [self::class, 'handle_hide_setup']);
        add_action('wp_ajax_pothik_ui_pref', [self::class, 'ajax_ui_pref']);
        add_action('admin_post_pothik_ui_pref', [self::class, 'handle_ui_pref']);
        add_filter('admin_body_class', [self::class, 'filter_admin_body_class']);
        add_action('admin_post_pothik_inquiry', [InquiryStore::class, 'handle_public']);
        add_action('admin_post_nopriv_pothik_inquiry', [InquiryStore::class, 'handle_public']);

        add_action('init', [I18n::class, 'capture'], 0);
        add_action('init', [I18n::class, 'load_textdomain'], 0);
        add_filter('determine_locale', [I18n::class, 'filter_locale']);
        add_action('init', [PackagePage::class, 'register_shortcodes']);
        add_filter('template_include', [PackagePage::class, 'template'], 1000);
    }

    public static function activate(): void
    {
        InquiryRepository::init_table();
        InquiryRepository::maybe_migrate();

        if (get_option('pothik_packages', null) === null && get_option('pothik_package', null) === null) {
            add_option('pothik_packages', [], '', false);
        }

        $page_id = (int) get_option('pothik_page_id');
        $status = $page_id ? get_post_status($page_id) : false;
        if (!$page_id || $status === false) {
            self::create_page('draft');
        }

        update_option('pothik_welcome', 1, false);
    }

    public static function create_page(string $status = 'publish'): int
    {
        $page_id = (int) wp_insert_post([
            'post_title' => 'উমরা প্যাকেজ',
            'post_name' => 'umrah-package',
            'post_status' => $status,
            'post_type' => 'page',
            'post_content' => '[pothik_package]',
        ]);
        if ($page_id > 0) {
            update_option('pothik_page_id', $page_id, false);
        }

        return $page_id;
    }

    public static function welcome(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->id !== 'dashboard' && !str_contains((string) $screen->id, 'pothik')) {
            return;
        }

        $page_id = (int) get_option('pothik_page_id');
        $is_draft = $page_id > 0 && get_post_status($page_id) === 'draft';
        if ($is_draft) {
            $publish_url = wp_nonce_url(admin_url('admin-post.php?action=pothik_publish_page'), 'pothik_publish_page');
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>Pothik:</strong> <?php echo esc_html(I18n::t('The package page is currently a draft.')); ?>
                    <a class="button button-small button-primary" href="<?php echo esc_url($publish_url); ?>" style="margin-left:8px;"><?php echo esc_html(I18n::t('Publish page')); ?></a>
                </p>
            </div>
            <?php
        }

        if (!get_option('pothik_welcome')) {
            return;
        }

        $url = PackagePage::url();
        $admin = admin_url('admin.php?page=pothik');
        ?>
        <div class="notice notice-info">
            <p>
                <strong>Pothik</strong>
                <?php echo esc_html(I18n::t('The package page is ready.')); ?>
                <?php if ($url !== '') : ?>
                    <a href="<?php echo esc_url($url); ?>"><?php echo esc_html(I18n::t('View on site')); ?></a>
                <?php endif; ?>
                · <a href="<?php echo esc_url($admin); ?>"><?php echo esc_html(I18n::t('Admin')); ?></a>
            </p>
            <p>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=pothik_dismiss_welcome'), 'pothik_dismiss_welcome')); ?>"><?php echo esc_html(I18n::t('Dismiss')); ?></a>
            </p>
        </div>
        <?php
    }

    public static function handle_publish_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_publish_page');

        $page_id = (int) get_option('pothik_page_id');
        if ($page_id > 0) {
            wp_update_post([
                'ID' => $page_id,
                'post_status' => 'publish',
            ]);
        }

        wp_safe_redirect(admin_url('admin.php?page=pothik&published=1'));
        exit;
    }

    public static function handle_recreate_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_recreate_page');

        self::create_page('publish');

        wp_safe_redirect(admin_url('admin.php?page=pothik-settings&recreated=1'));
        exit;
    }

    public static function handle_load_demo(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_demo_data');

        PackageStore::load_demo();
        if (!InquiryRepository::has_demo()) {
            $sample = InquiryStore::sample();
            $sample['demo'] = 1;
            $sample['uuid'] = 'demo-inquiry-1';
            InquiryRepository::insert($sample);
        }
        ReceiptStore::load_demo();

        wp_safe_redirect(admin_url('admin.php?page=pothik&demo_loaded=1'));
        exit;
    }

    public static function handle_remove_demo(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_demo_data');

        PackageStore::remove_demo();
        InquiryRepository::remove_demo();
        ReceiptStore::remove_demo();

        wp_safe_redirect(admin_url('admin.php?page=pothik&demo_removed=1'));
        exit;
    }

    public static function dismiss_welcome(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_dismiss_welcome');
        delete_option('pothik_welcome');
        wp_safe_redirect(admin_url('admin.php?page=pothik'));
        exit;
    }

    public static function handle_hide_setup(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_hide_setup');
        update_user_meta(get_current_user_id(), 'pothik_hide_setup', 1);
        wp_safe_redirect(admin_url('admin.php?page=pothik'));
        exit;
    }

    public static function filter_admin_body_class(string $classes): string
    {
        global $hook_suffix;
        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        $is_pothik = (is_string($hook_suffix) && str_contains($hook_suffix, 'pothik')) || str_starts_with($page, 'pothik');

        if ($is_pothik) {
            $classes .= ' pothik-screen';
            $user_id = get_current_user_id();
            if ($user_id) {
                if (get_user_meta($user_id, 'pothik_focus', true) === '1') {
                    $classes .= ' pothik-focus';
                }
                if (get_user_meta($user_id, 'pothik_side_mini', true) === '1') {
                    $classes .= ' pothik-side-mini';
                }
            }
        }

        return $classes;
    }

    public static function ajax_ui_pref(): void
    {
        check_ajax_referer('pothik_admin', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $key = isset($_POST['key']) ? sanitize_key((string) $_POST['key']) : '';
        $val = isset($_POST['value']) && $_POST['value'] === '1' ? '1' : '0';

        if (!in_array($key, ['focus', 'side_mini', 'hide_setup'], true)) {
            wp_send_json_error(['message' => 'Invalid key'], 400);
        }

        update_user_meta(get_current_user_id(), 'pothik_' . $key, $val);
        wp_send_json_success(['key' => $key, 'value' => $val]);
    }

    public static function handle_ui_pref(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_ui_pref');

        $key = isset($_POST['key']) ? sanitize_key((string) $_POST['key']) : '';
        $val = isset($_POST['value']) && $_POST['value'] === '1' ? '1' : '0';

        if (in_array($key, ['focus', 'side_mini', 'hide_setup'], true)) {
            update_user_meta(get_current_user_id(), 'pothik_' . $key, $val);
        }

        wp_safe_redirect(wp_get_referer() ?: admin_url('admin.php?page=pothik'));
        exit;
    }
}
