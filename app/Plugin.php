<?php
/**
 * Bootstrap.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik;

use Pothik\Admin\Menu;
use Pothik\Front\PackagePage;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    public static function boot(): void
    {
        add_action('admin_menu', [Menu::class, 'register']);
        add_action('admin_enqueue_scripts', [Menu::class, 'assets']);
        add_action('admin_notices', [self::class, 'welcome']);
        add_action('admin_post_pothik_dismiss_welcome', [self::class, 'dismiss_welcome']);
        add_action('admin_post_pothik_save_package', [PackageStore::class, 'handle_save']);
        add_action('admin_post_pothik_new_package', [PackageStore::class, 'handle_create']);
        add_action('admin_post_pothik_delete_package', [PackageStore::class, 'handle_delete']);
        add_action('admin_post_pothik_copy_package', [PackageStore::class, 'handle_copy']);
        add_action('admin_post_pothik_save_receipt', [ReceiptStore::class, 'handle_save']);
        add_action('admin_post_pothik_delete_inquiry', [InquiryStore::class, 'handle_delete']);
        add_action('admin_post_pothik_reply_inquiry', [InquiryStore::class, 'handle_reply']);
        add_action('admin_post_pothik_inquiry', [InquiryStore::class, 'handle_public']);
        add_action('admin_post_nopriv_pothik_inquiry', [InquiryStore::class, 'handle_public']);
        add_action('init', [I18n::class, 'capture'], 0);
        add_action('init', [self::class, 'ensure_page']);
        add_filter('template_include', [PackagePage::class, 'template'], 1000);
    }

    public static function activate(): void
    {
        if (get_option('pothik_packages', null) === null && get_option('pothik_package', null) === null) {
            $sample = PackageStore::defaults();
            $sample['id'] = 'sample';
            add_option('pothik_packages', [$sample], '', false);
        }

        if (get_option('pothik_inquiries') === false) {
            add_option('pothik_inquiries', [InquiryStore::sample()], '', false);
        }

        if (get_option('pothik_receipt') === false) {
            add_option('pothik_receipt', ReceiptStore::sample(), '', false);
        }

        $page_id = (int) get_option('pothik_page_id');
        $status = $page_id ? get_post_status($page_id) : false;
        if (!$page_id || $status === false) {
            $page_id = (int) wp_insert_post([
                'post_title' => 'উমরা প্যাকেজ',
                'post_name' => 'umrah-package',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '[pothik_package]',
            ]);
            if ($page_id > 0) {
                update_option('pothik_page_id', $page_id, false);
            }
        }

        update_option('pothik_welcome', 1, false);
    }

    public static function welcome(): void
    {
        if (!current_user_can('manage_options') || !get_option('pothik_welcome')) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->id !== 'dashboard' && !str_contains((string) $screen->id, 'pothik')) {
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

    public static function ensure_page(): void
    {
        $page_id = (int) get_option('pothik_page_id');
        if ($page_id > 0 && get_post_status($page_id)) {
            return;
        }
        self::activate();
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
}
