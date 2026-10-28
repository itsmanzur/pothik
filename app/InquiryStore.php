<?php
/**
 * Public inquiries.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik;

use Pothik\Front\PackagePage;
use Pothik\I18n;
use Pothik\PackageStore;

if (!defined('ABSPATH')) {
    exit;
}

final class InquiryStore
{
    /**
     * @return array<string, string>
     */
    public static function sample(): array
    {
        return [
            'id' => 'sample-1',
            'name' => 'মেহজাবিন চৌধুরী',
            'phone' => '01819-111111',
            'whatsapp' => '01819-111111',
            'city' => 'Dhaka',
            'share' => 'quad',
            'adults' => '2',
            'children' => '1',
            'infants' => '0',
            'trip' => 'group',
            'note' => 'March group. Two adults and one child.',
            'created' => '2026-10-24 10:15',
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function all(): array
    {
        $rows = get_option('pothik_inquiries', []);

        return is_array($rows) ? array_values($rows) : [];
    }

    public static function handle_public(): void
    {
        check_admin_referer('pothik_inquiry', 'pothik_inquiry_nonce');

        if (!empty($_POST['company_website'])) {
            self::redirect('sent');
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'local';
        $key = 'pothik_inq_' . md5($ip);
        $count = (int) get_transient($key);
        if ($count > 8) {
            self::redirect('limit');
        }
        set_transient($key, $count + 1, HOUR_IN_SECONDS);

        $post = wp_unslash($_POST);
        $post = is_array($post) ? $post : [];
        $name = isset($post['name']) ? sanitize_text_field((string) $post['name']) : '';
        $phone = isset($post['phone']) ? sanitize_text_field((string) $post['phone']) : '';
        if ($name === '' || $phone === '') {
            self::redirect('missing');
        }

        $share = I18n::canonical('share', isset($post['share']) ? sanitize_text_field((string) $post['share']) : '');
        $trip = I18n::canonical('trip', isset($post['trip']) ? sanitize_text_field((string) $post['trip']) : '');
        if (!isset(['quad' => 1, 'triple' => 1, 'double' => 1, 'single' => 1][$share])) {
            $share = 'quad';
        }
        if ($trip !== 'group' && $trip !== 'private') {
            $trip = 'group';
        }

        $row = [
            'id' => wp_generate_uuid4(),
            'name' => mb_substr($name, 0, 80),
            'phone' => mb_substr($phone, 0, 40),
            'whatsapp' => mb_substr(isset($post['whatsapp']) ? sanitize_text_field((string) $post['whatsapp']) : '', 0, 40),
            'city' => mb_substr(isset($post['city']) ? sanitize_text_field((string) $post['city']) : '', 0, 40),
            'share' => $share,
            'adults' => (string) min(30, absint($post['adults'] ?? 0)),
            'children' => (string) min(20, absint($post['children'] ?? 0)),
            'infants' => (string) min(10, absint($post['infants'] ?? 0)),
            'trip' => $trip,
            'note' => mb_substr(isset($post['note']) ? sanitize_textarea_field((string) $post['note']) : '', 0, 300),
            'package_id' => '',
            'package' => '',
            'created' => wp_date('Y-m-d H:i'),
        ];
        $package_id = isset($post['package_id']) ? sanitize_key((string) $post['package_id']) : '';
        $found = $package_id !== '' ? PackageStore::find($package_id) : null;
        if (is_array($found)) {
            $row['package_id'] = $package_id;
            $row['package'] = mb_substr(I18n::field($found, 'title'), 0, 120);
        }

        $rows = self::all();
        array_unshift($rows, $row);
        update_option('pothik_inquiries', array_slice($rows, 0, 200), false);
        self::redirect('sent');
    }

    public static function handle_delete(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_delete_inquiry');
        $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash((string) $_POST['id'])) : '';
        $rows = array_values(array_filter(
            self::all(),
            static fn (array $row): bool => ($row['id'] ?? '') !== $id
        ));
        update_option('pothik_inquiries', $rows, false);
        wp_safe_redirect(admin_url('admin.php?page=pothik-inquiries&updated=1'));
        exit;
    }

    public static function handle_reply(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_reply_inquiry');
        $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash((string) $_POST['id'])) : '';
        $rows = self::all();
        foreach ($rows as $index => $row) {
            if (($row['id'] ?? '') !== $id) {
                continue;
            }
            $rows[$index]['replied'] = ($row['replied'] ?? '') === '1' ? '0' : '1';
            break;
        }
        update_option('pothik_inquiries', $rows, false);
        $back = isset($_POST['back']) ? sanitize_key(wp_unslash((string) $_POST['back'])) : '';
        if ($back !== 'pothik') {
            $back = 'pothik-inquiries';
        }
        wp_safe_redirect(admin_url('admin.php?page=' . $back . '&updated=1'));
        exit;
    }

    private static function redirect(string $result): void
    {
        $post = wp_unslash($_POST);
        $id = is_array($post) && isset($post['package_id']) ? sanitize_key((string) $post['package_id']) : '';
        $url = PackagePage::url($id);
        if ($url === '') {
            $url = home_url('/');
        }
        wp_safe_redirect(add_query_arg('pothik', $result, $url) . '#inquiry');
        exit;
    }
}
