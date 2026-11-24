<?php
/**
 * Payment receipts. Every receipt is kept; none is overwritten.
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
    public const PER_PAGE = 10;

    public static function table_name(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'pothik_receipts';
    }

    /**
     * Create the receipts table.
     */
    public static function init_table(): void
    {
        global $wpdb;
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_gmt datetime NOT NULL,
            name varchar(80) NOT NULL DEFAULT '',
            amount bigint(20) unsigned NOT NULL DEFAULT 0,
            channel varchar(10) NOT NULL DEFAULT 'cash',
            trx varchar(40) NOT NULL DEFAULT '',
            pay_date varchar(20) NOT NULL DEFAULT '',
            note text NOT NULL,
            demo tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY created_gmt (created_gmt),
            KEY demo (demo)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    /**
     * Move the single receipt of older versions into the table as the first row.
     */
    public static function migrate_legacy(): void
    {
        $stored = get_option('pothik_receipt', null);
        if (is_array($stored) && $stored !== [] && (int) ($stored['amount'] ?? 0) > 0) {
            self::insert([
                'name' => (string) ($stored['name'] ?? ''),
                'amount' => (string) ($stored['amount'] ?? '0'),
                'channel' => (string) ($stored['channel'] ?? 'cash'),
                'trx' => (string) ($stored['trx'] ?? ''),
                'date' => (string) ($stored['date'] ?? ''),
                'note' => (string) ($stored['note'] ?? ''),
                'demo' => !empty($stored['demo']),
            ]);
        }
        delete_option('pothik_receipt');
    }

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
     * An empty receipt, used by the form for a new one.
     *
     * @return array<string, mixed>
     */
    public static function empty(): array
    {
        return [
            'id' => 0,
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
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function shape(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'amount' => (string) ($row['amount'] ?? '0'),
            'channel' => (string) ($row['channel'] ?? 'cash'),
            'trx' => (string) ($row['trx'] ?? ''),
            'date' => (string) ($row['pay_date'] ?? ''),
            'note' => (string) ($row['note'] ?? ''),
            'demo' => !empty($row['demo']),
            'created_gmt' => (string) ($row['created_gmt'] ?? ''),
        ];
    }

    /**
     * Insert a receipt. Returns the new id.
     *
     * @param array<string, mixed> $data
     */
    public static function insert(array $data): int
    {
        global $wpdb;
        $wpdb->insert(
            self::table_name(),
            [
                'created_gmt' => gmdate('Y-m-d H:i:s'),
                'name' => (string) ($data['name'] ?? ''),
                'amount' => absint($data['amount'] ?? 0),
                'channel' => (string) ($data['channel'] ?? 'cash'),
                'trx' => (string) ($data['trx'] ?? ''),
                'pay_date' => (string) ($data['date'] ?? ''),
                'note' => (string) ($data['note'] ?? ''),
                'demo' => !empty($data['demo']) ? 1 : 0,
            ],
            ['%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d']
        );

        return (int) $wpdb->insert_id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        global $wpdb;
        $wpdb->update(
            self::table_name(),
            [
                'name' => (string) ($data['name'] ?? ''),
                'amount' => absint($data['amount'] ?? 0),
                'channel' => (string) ($data['channel'] ?? 'cash'),
                'trx' => (string) ($data['trx'] ?? ''),
                'pay_date' => (string) ($data['date'] ?? ''),
                'note' => (string) ($data['note'] ?? ''),
                'demo' => 0,
            ],
            ['id' => $id],
            ['%s', '%d', '%s', '%s', '%s', '%s', '%d'],
            ['%d']
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        global $wpdb;
        $table = self::table_name();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);

        return is_array($row) ? self::shape($row) : null;
    }

    /**
     * The newest receipt, or an empty one.
     *
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        global $wpdb;
        $table = self::table_name();
        $row = $wpdb->get_row("SELECT * FROM {$table} ORDER BY id DESC LIMIT 1", ARRAY_A);

        return is_array($row) ? self::shape($row) : self::empty();
    }

    public static function count(): int
    {
        global $wpdb;
        $table = self::table_name();

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }

    /**
     * Newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function page(int $page, int $per_page = self::PER_PAGE): array
    {
        global $wpdb;
        $table = self::table_name();
        $offset = max(0, ($page - 1) * $per_page);
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset),
            ARRAY_A
        );

        return is_array($rows) ? array_map([self::class, 'shape'], $rows) : [];
    }

    /**
     * Check if demo receipts are stored.
     */
    public static function has_demo(): bool
    {
        global $wpdb;
        $table = self::table_name();

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE demo = 1") > 0;
    }

    public static function load_demo(): void
    {
        if (!self::has_demo()) {
            self::insert(self::sample());
        }
    }

    public static function remove_demo(): void
    {
        global $wpdb;
        $table = self::table_name();
        $wpdb->query("DELETE FROM {$table} WHERE demo = 1");
    }

    /**
     * Save a new receipt, or update the one named by receipt_id.
     */
    public static function handle_save(): void
    {
        if (!Access::can('receipt', 'edit')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_save_receipt');

        $channel = I18n::canonical('channel', isset($_POST['channel']) ? sanitize_text_field(wp_unslash((string) $_POST['channel'])) : '');
        if (!in_array($channel, ['bkash', 'nagad', 'rocket', 'bank', 'cash'], true)) {
            $channel = 'cash';
        }

        $after = [
            'name' => mb_substr(isset($_POST['name']) ? sanitize_text_field(wp_unslash((string) $_POST['name'])) : '', 0, 80),
            'amount' => (string) absint(isset($_POST['amount']) ? str_replace(',', '', sanitize_text_field(wp_unslash((string) $_POST['amount']))) : 0),
            'channel' => $channel,
            'trx' => mb_substr(isset($_POST['trx']) ? sanitize_text_field(wp_unslash((string) $_POST['trx'])) : '', 0, 40),
            'date' => mb_substr(isset($_POST['date']) ? sanitize_text_field(wp_unslash((string) $_POST['date'])) : '', 0, 20),
            'note' => mb_substr(isset($_POST['note']) ? sanitize_textarea_field(wp_unslash((string) $_POST['note'])) : '', 0, 240),
            'demo' => false,
        ];

        $id = isset($_POST['receipt_id']) ? absint(wp_unslash($_POST['receipt_id'])) : 0;
        $before = $id > 0 ? (self::find($id) ?? []) : [];
        if ($id > 0 && $before !== []) {
            self::update($id, $after);
        } else {
            $id = self::insert($after);
        }
        do_action('pothik_receipt_saved', $before, $after);

        wp_safe_redirect(admin_url('admin.php?page=pothik-receipt&receipt=' . $id . '&updated=1'));
        exit;
    }

    public static function handle_delete(): void
    {
        if (!Access::can('receipt', 'edit')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_delete_receipt');

        $id = isset($_POST['receipt_id']) ? absint(wp_unslash($_POST['receipt_id'])) : 0;
        $before = $id > 0 ? self::find($id) : null;
        if ($before !== null) {
            global $wpdb;
            $wpdb->delete(self::table_name(), ['id' => $id], ['%d']);
            do_action('pothik_receipt_deleted', $id, $before);
        }

        wp_safe_redirect(admin_url('admin.php?page=pothik-receipt&deleted=1'));
        exit;
    }
}
