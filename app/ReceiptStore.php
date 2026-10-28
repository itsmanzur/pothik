<?php
/**
 * One manual receipt. Installments live in Pothik Pro.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik;

if (!defined('ABSPATH')) {
    exit;
}

final class ReceiptStore
{
    /**
     * @return array<string, mixed>
     */
    public static function sample(): array
    {
        return [
            'name' => 'মেহজাবিন চৌধুরী',
            'amount' => '40000',
            'channel' => 'bkash',
            'trx' => 'BKG8SAMPLE01',
            'date' => '2026-10-20',
            'note' => 'Deposit for the March group.',
            'demo' => true,
        ];
    }

    /**
     * Return an empty receipt structure.
     *
     * @return array<string, mixed>
     */
    public static function empty(): array
    {
        return [
            'name' => '',
            'amount' => '0',
            'channel' => 'cash',
            'trx' => '',
            'date' => '',
            'note' => '',
            'demo' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        $stored = get_option('pothik_receipt', null);
        if (!is_array($stored) || $stored === []) {
            return self::empty();
        }

        $receipt = array_merge(self::empty(), $stored);
        $receipt['amount'] = (string) ($stored['amount'] ?? '0');
        $receipt['demo'] = !empty($stored['demo']);

        return $receipt;
    }

    /**
     * Check if a demo receipt is currently stored.
     */
    public static function has_demo(): bool
    {
        $stored = get_option('pothik_receipt', null);

        return is_array($stored) && !empty($stored['demo']);
    }

    /**
     * Load demo receipt.
     */
    public static function load_demo(): void
    {
        update_option('pothik_receipt', self::sample(), false);
    }

    /**
     * Remove demo receipt.
     */
    public static function remove_demo(): void
    {
        if (self::has_demo()) {
            delete_option('pothik_receipt');
        }
    }

    public static function handle_save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_save_receipt');

        $post = wp_unslash($_POST);
        $post = is_array($post) ? $post : [];
        $channel = I18n::canonical('channel', isset($post['channel']) ? sanitize_text_field((string) $post['channel']) : '');
        if (!in_array($channel, ['bkash', 'nagad', 'rocket', 'bank', 'cash'], true)) {
            $channel = 'cash';
        }

        update_option('pothik_receipt', [
            'name' => mb_substr(isset($post['name']) ? sanitize_text_field((string) $post['name']) : '', 0, 80),
            'amount' => (string) absint($post['amount'] ?? 0),
            'channel' => $channel,
            'trx' => mb_substr(isset($post['trx']) ? sanitize_text_field((string) $post['trx']) : '', 0, 40),
            'date' => mb_substr(isset($post['date']) ? sanitize_text_field((string) $post['date']) : '', 0, 20),
            'note' => mb_substr(isset($post['note']) ? sanitize_textarea_field((string) $post['note']) : '', 0, 240),
            'demo' => false,
        ], false);

        wp_safe_redirect(admin_url('admin.php?page=pothik-receipt&updated=1'));
        exit;
    }
}
