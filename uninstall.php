<?php
/**
 * Remove Pothik data when the plugin is deleted.
 *
 * @package Pothik
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$pothik_settings = get_option('pothik_settings', []);
if (!is_array($pothik_settings) || empty($pothik_settings['delete_on_uninstall'])) {
    return;
}

function pothik_clean_single_blog(): void
{
    global $wpdb;

    // Drop inquiries custom table.
    $table = $wpdb->prefix . 'pothik_inquiries';
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`");

    // Delete all options starting with pothik_.
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'pothik_%'");

    // Delete pothik_lang from user metadata.
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'pothik_lang'");

    // Delete all transients.
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pothik_%' OR option_name LIKE '_transient_timeout_pothik_%'");
}

if (is_multisite()) {
    $pothik_sites = get_sites(['fields' => 'ids']);
    if (is_array($pothik_sites)) {
        foreach ($pothik_sites as $pothik_site_id) {
            switch_to_blog((int) $pothik_site_id);
            pothik_clean_single_blog();
            restore_current_blog();
        }
    }
} else {
    pothik_clean_single_blog();
}
