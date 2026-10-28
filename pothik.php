<?php
/**
 * Plugin Name:       Pothik
 * Plugin URI:        https://thereadscope.com/pothik
 * Description:       Create Umrah and Hajj package landing pages, collect pilgrim inquiries with WhatsApp integration, and print payment receipts.
 * Version:           0.3.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            itsmanzur
 * Author URI:        https://thereadscope.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pothik
 * Domain Path:       /languages
 *
 * @package Pothik
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('POTHIK_VERSION', '0.3.0');
define('POTHIK_FILE', __FILE__);
define('POTHIK_DIR', plugin_dir_path(__FILE__));
define('POTHIK_URL', plugin_dir_url(__FILE__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'Pothik\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = POTHIK_DIR . 'app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_readable($file)) {
        require_once $file;
    }
});

register_activation_hook(POTHIK_FILE, ['Pothik\\Plugin', 'activate']);

add_action('plugins_loaded', static function (): void {
    Pothik\Plugin::boot();
    do_action('pothik/loaded');
}, 10);
