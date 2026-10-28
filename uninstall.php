<?php
/**
 * Remove Pothik options when the plugin is deleted.
 *
 * @package Pothik
 */

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('pothik_package');
delete_option('pothik_packages');
delete_option('pothik_inquiries');
delete_option('pothik_receipt');
delete_option('pothik_page_id');
delete_option('pothik_welcome');
