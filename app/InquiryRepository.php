<?php
/**
 * Inquiries database repository.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

if (!defined('ABSPATH')) {
    exit;
}

final class InquiryRepository
{
    public const DB_VERSION = '1.1.0';

    public static function table_name(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'pothik_inquiries';
    }

    /**
     * Create or upgrade table schema via dbDelta.
     */
    public static function init_table(): void
    {
        global $wpdb;
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            uuid char(36) NOT NULL DEFAULT '',
            created_gmt datetime NOT NULL,
            name varchar(80) NOT NULL DEFAULT '',
            phone varchar(40) NOT NULL DEFAULT '',
            whatsapp varchar(40) NOT NULL DEFAULT '',
            city varchar(40) NOT NULL DEFAULT '',
            share varchar(20) NOT NULL DEFAULT 'quad',
            adults int(11) NOT NULL DEFAULT 1,
            children int(11) NOT NULL DEFAULT 0,
            infants int(11) NOT NULL DEFAULT 0,
            trip varchar(20) NOT NULL DEFAULT 'group',
            note text NOT NULL,
            package_id varchar(50) NOT NULL DEFAULT '',
            package_title varchar(120) NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'new',
            replied tinyint(1) NOT NULL DEFAULT 0,
            demo tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY created_gmt (created_gmt),
            KEY status (status),
            KEY phone (phone),
            KEY uuid (uuid),
            KEY demo (demo)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    /**
     * Check if table needs creation or migration from legacy options.
     */
    public static function maybe_migrate(): void
    {
        $current_version = get_option('pothik_db_version', '0');
        if (version_compare((string) $current_version, self::DB_VERSION, '<')) {
            self::init_table();
            self::migrate_legacy();
            update_option('pothik_db_version', self::DB_VERSION, false);
        }
    }

    /**
     * One-time migration of options into the inquiries table.
     */
    private static function migrate_legacy(): void
    {
        $legacy = get_option('pothik_inquiries', null);
        if (!is_array($legacy) || $legacy === []) {
            return;
        }

        global $wpdb;
        $table = self::table_name();

        foreach ($legacy as $row) {
            if (!is_array($row)) {
                continue;
            }

            $created_str = (string) ($row['created'] ?? '');
            $created_gmt = gmdate('Y-m-d H:i:s');
            if ($created_str !== '') {
                try {
                    $dt = new DateTimeImmutable($created_str, wp_timezone());
                    $created_gmt = $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                } catch (Exception $e) {
                    $created_gmt = gmdate('Y-m-d H:i:s');
                }
            }

            $uuid = !empty($row['id']) ? (string) $row['id'] : wp_generate_uuid4();
            $replied = (!empty($row['replied']) && (string) $row['replied'] === '1') ? 1 : 0;
            $status = $replied === 1 ? 'replied' : 'new';
            $demo = !empty($row['demo']) || $uuid === 'sample-1' ? 1 : 0;

            $wpdb->insert(
                $table,
                [
                    'uuid' => $uuid,
                    'created_gmt' => $created_gmt,
                    'name' => mb_substr((string) ($row['name'] ?? ''), 0, 80),
                    'phone' => mb_substr((string) ($row['phone'] ?? ''), 0, 40),
                    'whatsapp' => mb_substr((string) ($row['whatsapp'] ?? ''), 0, 40),
                    'city' => mb_substr((string) ($row['city'] ?? ''), 0, 40),
                    'share' => mb_substr((string) ($row['share'] ?? 'quad'), 0, 20),
                    'adults' => (int) ($row['adults'] ?? 1),
                    'children' => (int) ($row['children'] ?? 0),
                    'infants' => (int) ($row['infants'] ?? 0),
                    'trip' => mb_substr((string) ($row['trip'] ?? 'group'), 0, 20),
                    'note' => (string) ($row['note'] ?? ''),
                    'package_id' => mb_substr((string) ($row['package_id'] ?? ''), 0, 50),
                    'package_title' => mb_substr((string) ($row['package'] ?? $row['package_title'] ?? ''), 0, 120),
                    'status' => $status,
                    'replied' => $replied,
                    'demo' => $demo,
                ],
                [
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d'
                ]
            );
        }

        delete_option('pothik_inquiries');
    }

    /**
     * Insert a new inquiry row.
     *
     * @param array<string, mixed> $data
     */
    public static function insert(array $data): string
    {
        global $wpdb;
        $table = self::table_name();

        $uuid = !empty($data['uuid'])
            ? (string) $data['uuid']
            : (!empty($data['id']) ? (string) $data['id'] : wp_generate_uuid4());

        $created_gmt = !empty($data['created_gmt'])
            ? (string) $data['created_gmt']
            : gmdate('Y-m-d H:i:s');

        $inserted = $wpdb->insert(
            $table,
            [
                'uuid' => $uuid,
                'created_gmt' => $created_gmt,
                'name' => mb_substr((string) ($data['name'] ?? ''), 0, 80),
                'phone' => mb_substr((string) ($data['phone'] ?? ''), 0, 40),
                'whatsapp' => mb_substr((string) ($data['whatsapp'] ?? ''), 0, 40),
                'city' => mb_substr((string) ($data['city'] ?? ''), 0, 40),
                'share' => mb_substr((string) ($data['share'] ?? 'quad'), 0, 20),
                'adults' => (int) ($data['adults'] ?? 1),
                'children' => (int) ($data['children'] ?? 0),
                'infants' => (int) ($data['infants'] ?? 0),
                'trip' => mb_substr((string) ($data['trip'] ?? 'group'), 0, 20),
                'note' => (string) ($data['note'] ?? ''),
                'package_id' => mb_substr((string) ($data['package_id'] ?? ''), 0, 50),
                'package_title' => mb_substr((string) ($data['package_title'] ?? $data['package'] ?? ''), 0, 120),
                'status' => mb_substr((string) ($data['status'] ?? 'new'), 0, 20),
                'replied' => !empty($data['replied']) ? 1 : 0,
                'demo' => !empty($data['demo']) ? 1 : 0,
            ],
            [
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d'
            ]
        );

        if ($inserted !== false) {
            self::clear_waiting_cache();
            return $uuid;
        }

        return '';
    }

    /**
     * Check if demo inquiry rows exist.
     */
    public static function has_demo(): bool
    {
        global $wpdb;
        $table = self::table_name();

        return (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table} WHERE demo = 1") > 0;
    }

    /**
     * Remove all demo inquiries.
     */
    public static function remove_demo(): void
    {
        global $wpdb;
        $table = self::table_name();
        $wpdb->query("DELETE FROM {$table} WHERE demo = 1 OR uuid = 'sample-1'");
    }

    /**
     * Get all inquiries, ordered newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        global $wpdb;
        $table = self::table_name();
        $results = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_gmt DESC");
        if (!is_array($results)) {
            return [];
        }

        $items = [];
        foreach ($results as $row) {
            $items[] = self::format_row($row);
        }

        return $items;
    }

    /**
     * Get the newest N inquiries.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function recent(int $limit = 4): array
    {
        global $wpdb;
        $table = self::table_name();
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} ORDER BY created_gmt DESC LIMIT %d", max(1, $limit))
        );
        if (!is_array($results)) {
            return [];
        }

        $items = [];
        foreach ($results as $row) {
            $items[] = self::format_row($row);
        }

        return $items;
    }

    /**
     * Get count of waiting inquiries (replied = 0), cached for 60 seconds.
     */
    public static function count_waiting(): int
    {
        $cached = get_transient('pothik_waiting_count');
        if ($cached !== false && is_numeric($cached)) {
            return (int) $cached;
        }

        global $wpdb;
        $table = self::table_name();
        $count = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table} WHERE replied = 0");

        set_transient('pothik_waiting_count', $count, 60);

        return $count;
    }

    /**
     * Invalidate waiting inquiries transient cache.
     */
    public static function clear_waiting_cache(): void
    {
        delete_transient('pothik_waiting_count');
    }

    /**
     * Get counts grouped by status (all, waiting, replied).
     *
     * @return array{all: int, waiting: int, replied: int}
     */
    public static function count_by_status(): array
    {
        global $wpdb;
        $table = self::table_name();
        $total = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table}");
        $waiting = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table} WHERE replied = 0");
        $replied = max(0, $total - $waiting);

        return [
            'all' => $total,
            'waiting' => $waiting,
            'replied' => $replied,
        ];
    }

    /**
     * Get total count of inquiries.
     */
    public static function count_all(): int
    {
        global $wpdb;
        $table = self::table_name();

        return (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table}");
    }

    /**
     * Paginated inquiries query with optional status, search and room share filters.
     *
     * @param int $page
     * @param int $per_page
     * @param string $status 'waiting'|'replied'|''
     * @param string $search
     * @param string $share
     * @return array{items: array<int, array<string, mixed>>, total: int, pages: int, page: int}
     */
    public static function paginate(int $page = 1, int $per_page = 20, string $status = '', string $search = '', string $share = ''): array
    {
        global $wpdb;
        $table = self::table_name();

        $page = max(1, $page);
        $per_page = max(1, $per_page);
        $offset = ($page - 1) * $per_page;

        $where = ['1=1'];
        $params = [];

        if ($status === 'waiting') {
            $where[] = 'replied = 0';
        } elseif ($status === 'replied') {
            $where[] = 'replied = 1';
        }

        if ($share !== '') {
            $where[] = 'share = %s';
            $params[] = $share;
        }

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = '(name LIKE %s OR phone LIKE %s OR city LIKE %s OR package_title LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $where_sql = implode(' AND ', $where);

        $count_sql = "SELECT COUNT(id) FROM {$table} WHERE {$where_sql}";
        if (!empty($params)) {
            $count_sql = $wpdb->prepare($count_sql, ...$params);
        }
        $total = (int) $wpdb->get_var($count_sql);
        $pages = (int) ceil($total / $per_page);

        $query_params = $params;
        $query_params[] = $per_page;
        $query_params[] = $offset;

        $query_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_gmt DESC LIMIT %d OFFSET %d";
        $results = $wpdb->get_results($wpdb->prepare($query_sql, ...$query_params));

        $items = [];
        if (is_array($results)) {
            foreach ($results as $row) {
                $items[] = self::format_row($row);
            }
        }

        return [
            'items' => $items,
            'total' => $total,
            'pages' => max(1, $pages),
            'page' => $page,
        ];
    }

    /**
     * Toggle the replied status of an inquiry by UUID or ID.
     */
    public static function toggle_reply(string $id): bool
    {
        global $wpdb;
        $table = self::table_name();
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT id, replied, status FROM {$table} WHERE uuid = %s OR id = %s LIMIT 1",
            $id,
            $id
        ));
        if (!$row) {
            return false;
        }

        $new_replied = ((int) $row->replied === 1) ? 0 : 1;
        $new_status = $new_replied === 1 ? 'replied' : 'waiting';

        $updated = $wpdb->update(
            $table,
            [
                'replied' => $new_replied,
                'status' => $new_status,
            ],
            ['id' => (int) $row->id],
            ['%d', '%s'],
            ['%d']
        );

        if ($updated !== false) {
            self::clear_waiting_cache();
            return true;
        }

        return false;
    }

    /**
     * Delete an inquiry by UUID or ID.
     */
    public static function delete_by_id(string $id): bool
    {
        global $wpdb;
        $table = self::table_name();
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE uuid = %s OR id = %s",
            $id,
            $id
        ));

        if ($result !== false) {
            self::clear_waiting_cache();
            return true;
        }

        return false;
    }

    /**
     * Search inquiries by keyword (name, phone, city) returning up to $limit rows.
     *
     * @return array<int, array{id: string, name: string, phone: string, city: string, url: string}>
     */
    public static function search(string $query, int $limit = 6): array
    {
        if (trim($query) === '') {
            return [];
        }

        global $wpdb;
        $table = self::table_name();
        $like = '%' . $wpdb->esc_like(trim($query)) . '%';

        $sql = $wpdb->prepare(
            "SELECT id, uuid, name, phone, city FROM {$table}
             WHERE name LIKE %s OR phone LIKE %s OR city LIKE %s
             ORDER BY created_gmt DESC
             LIMIT %d",
            $like,
            $like,
            $like,
            $limit
        );

        $rows = $wpdb->get_results($sql);
        if (!is_array($rows)) {
            return [];
        }

        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'id' => (string) ($row->uuid ?: $row->id),
                'name' => (string) $row->name,
                'phone' => (string) $row->phone,
                'city' => (string) $row->city,
                'url' => admin_url('admin.php?page=pothik-inquiries&s=' . rawurlencode((string) $row->name)),
            ];
        }

        return $results;
    }

    public static function ajax_search(): void
    {
        check_ajax_referer('pothik_admin', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $query = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $results = self::search($query, 6);

        wp_send_json_success($results);
    }

    /**
     * Get recent inquiries waiting for a reply.
     *
     * @return array<int, object>
     */
    public static function recent_waiting(int $limit = 5): array
    {
        global $wpdb;
        $table = self::table_name();
        $limit = max(1, min(50, $limit));

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE replied = 0 ORDER BY id DESC LIMIT %d",
            $limit
        ));

        return is_array($rows) ? $rows : [];
    }

    /**
     * AJAX handler to toggle reply status of an inquiry.
     */
    public static function ajax_toggle_reply(): void
    {
        if (!check_ajax_referer('pothik_admin', 'nonce', false) && !check_ajax_referer('pothik_reply_inquiry', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        if ($id === '') {
            wp_send_json_error(['message' => 'Missing ID'], 400);
        }

        global $wpdb;
        $table = self::table_name();
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT id, name, replied FROM {$table} WHERE uuid = %s OR id = %s LIMIT 1",
            $id,
            $id
        ));

        if (!$row) {
            wp_send_json_error(['message' => 'Inquiry not found'], 404);
        }

        $success = self::toggle_reply($id);
        if (!$success) {
            wp_send_json_error(['message' => 'Failed to toggle status'], 500);
        }

        $new_replied = ((int) $row->replied === 1) ? 0 : 1;
        $wait_count = self::count_waiting();

        wp_send_json_success([
            'id' => $id,
            'name' => (string) $row->name,
            'replied' => $new_replied,
            'waiting_count' => $wait_count,
        ]);
    }

    /**
     * Handle bulk actions on inquiries (reply, delete, export).
     */
    public static function handle_bulk(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to perform this action.')));
        }
        check_admin_referer('pothik_bulk_inquiries');

        $op = isset($_POST['op']) ? sanitize_key((string) $_POST['op']) : '';
        $raw_ids = isset($_POST['ids']) && is_array($_POST['ids']) ? (array) $_POST['ids'] : [];
        $ids = array_filter(array_map('sanitize_key', $raw_ids));

        if (empty($ids)) {
            wp_safe_redirect(admin_url('admin.php?page=pothik-inquiries'));
            exit;
        }

        if ($op === 'export') {
            self::export_csv($ids);
            exit;
        }

        if ($op === 'reply') {
            foreach ($ids as $id) {
                self::toggle_reply($id);
            }
            self::clear_waiting_cache();
            wp_safe_redirect(admin_url('admin.php?page=pothik-inquiries&updated=replied'));
            exit;
        }

        if ($op === 'delete') {
            foreach ($ids as $id) {
                self::delete($id);
            }
            self::clear_waiting_cache();
            wp_safe_redirect(admin_url('admin.php?page=pothik-inquiries&updated=deleted'));
            exit;
        }

        wp_safe_redirect(admin_url('admin.php?page=pothik-inquiries'));
        exit;
    }

    /**
     * Export all inquiries as a UTF-8 CSV download (optionally filtered by IDs).
     *
     * @param array<int, string>|null $ids
     */
    public static function export_csv(?array $ids = null): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        if (!check_admin_referer('pothik_export_inquiries', '_wpnonce', false) && !check_admin_referer('pothik_bulk_inquiries', '_wpnonce', false)) {
            wp_die(esc_html(I18n::t('Security check failed.')));
        }

        global $wpdb;
        $table = self::table_name();
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '%s'));
            $params = array_merge($ids, $ids);
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE uuid IN ($placeholders) OR id IN ($placeholders) ORDER BY created_gmt DESC", ...$params));
        } else {
            $results = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_gmt DESC");
        }
        $rows = is_array($results) ? $results : [];

        $filename = 'pothik-inquiries-' . gmdate('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output UTF-8 BOM so Excel opens Bengali characters properly
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');
        if ($output === false) {
            exit;
        }

        fputcsv($output, [
            'ID',
            'Date (GMT)',
            'Date (Local)',
            'Name',
            'Phone',
            'WhatsApp',
            'City',
            'Package ID',
            'Package Title',
            'Share',
            'Adults',
            'Children',
            'Infants',
            'Trip Type',
            'Status',
            'Replied',
            'Demo',
            'Note',
        ]);

        foreach ($rows as $r) {
            $local_date = self::format_local_datetime((string) $r->created_gmt);
            fputcsv($output, [
                $r->uuid,
                $r->created_gmt,
                $local_date,
                $r->name,
                $r->phone,
                $r->whatsapp,
                $r->city,
                $r->package_id,
                $r->package_title,
                $r->share,
                (int) $r->adults,
                (int) $r->children,
                (int) $r->infants,
                $r->trip,
                $r->status,
                ((int) $r->replied === 1) ? 'Yes' : 'No',
                ((int) $r->demo === 1) ? 'Yes' : 'No',
                $r->note,
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Format a database row to an array consistent with existing screens and templates.
     *
     * @param object|array<string, mixed> $raw
     * @return array<string, mixed>
     */
    public static function format_row(object|array $raw): array
    {
        $r = (object) $raw;
        $created_gmt = (string) ($r->created_gmt ?? '');

        return [
            'id' => (string) ($r->uuid ?? ''),
            'db_id' => (int) ($r->id ?? 0),
            'created_gmt' => $created_gmt,
            'created' => self::format_local_datetime($created_gmt),
            'name' => (string) ($r->name ?? ''),
            'phone' => (string) ($r->phone ?? ''),
            'whatsapp' => (string) ($r->whatsapp ?? ''),
            'city' => (string) ($r->city ?? ''),
            'share' => (string) ($r->share ?? 'quad'),
            'adults' => (string) ($r->adults ?? '1'),
            'children' => (string) ($r->children ?? '0'),
            'infants' => (string) ($r->infants ?? '0'),
            'trip' => (string) ($r->trip ?? 'group'),
            'note' => (string) ($r->note ?? ''),
            'package_id' => (string) ($r->package_id ?? ''),
            'package_title' => (string) ($r->package_title ?? ''),
            'package' => (string) ($r->package_title ?? ''),
            'status' => (string) ($r->status ?? 'new'),
            'replied' => ((int) ($r->replied ?? 0) === 1) ? '1' : '0',
            'demo' => ((int) ($r->demo ?? 0) === 1),
        ];
    }

    /**
     * Format a GMT datetime string to the WordPress site's local timezone.
     */
    public static function format_local_datetime(string $created_gmt): string
    {
        if ($created_gmt === '') {
            return '';
        }

        try {
            $dt = new DateTimeImmutable($created_gmt, new DateTimeZone('UTC'));
            $local = $dt->setTimezone(wp_timezone());

            return $local->format('Y-m-d H:i');
        } catch (Exception $e) {
            return $created_gmt;
        }
    }
}
