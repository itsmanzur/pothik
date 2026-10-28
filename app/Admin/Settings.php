<?php
/**
 * Settings and maintenance screen.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik\Admin;

use Pothik\Front\PackagePage;
use Pothik\I18n;

if (!defined('ABSPATH')) {
    exit;
}

final class Settings
{
    /**
     * Alias for get_all() for convenience and backwards-compatibility.
     *
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        return self::get_all();
    }

    /**
     * Whitelist and return all settings merged with defaults.
     *
     * @return array<string, mixed>
     */
    public static function get_all(): array
    {
        $defaults = [
            'agency' => 'Pothik Umrah',
            'agency_bn' => 'পথিক উমরা',
            'phone' => '01711-000000',
            'whatsapp' => '8801711000000',
            'license' => '',
            'logo_id' => 0,
            'logo_url' => '',
            'notification_emails' => (string) get_option('admin_email', ''),
            'country_code' => '880',
            'rate_limit' => 5,
            'delete_on_uninstall' => false,
        ];

        $saved = get_option('pothik_settings', []);
        if (!is_array($saved)) {
            return $defaults;
        }

        return array_merge($defaults, $saved);
    }

    /**
     * Register settings in WordPress settings API.
     */
    public static function register_settings(): void
    {
        register_setting('pothik_settings_group', 'pothik_settings', [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize'],
            'default' => self::get_all(),
        ]);
    }

    /**
     * Sanitize settings fields with whitelisted keys.
     *
     * @param mixed $input
     * @return array<string, mixed>
     */
    public static function sanitize(mixed $input): array
    {
        if (!is_array($input)) {
            return [];
        }

        $clean = [];

        // Agency details
        $clean['agency'] = isset($input['agency']) ? sanitize_text_field((string) $input['agency']) : '';
        $clean['agency_bn'] = isset($input['agency_bn']) ? sanitize_text_field((string) $input['agency_bn']) : '';
        $clean['phone'] = isset($input['phone']) ? sanitize_text_field((string) $input['phone']) : '';
        $clean['whatsapp'] = isset($input['whatsapp']) ? sanitize_text_field((string) $input['whatsapp']) : '';
        $clean['license'] = isset($input['license']) ? sanitize_text_field((string) $input['license']) : '';

        // Logo
        $clean['logo_id'] = isset($input['logo_id']) ? absint($input['logo_id']) : 0;
        $clean['logo_url'] = isset($input['logo_url']) ? esc_url_raw((string) $input['logo_url']) : '';

        // Notification emails
        $raw_emails = isset($input['notification_emails']) ? sanitize_text_field((string) $input['notification_emails']) : '';
        $emails = array_filter(
            array_map('trim', explode(',', str_replace(["\r", "\n", ';'], ',', $raw_emails))),
            static fn (string $e): bool => (bool) is_email($e)
        );
        $clean['notification_emails'] = implode(', ', $emails);

        // Country code
        $cc = isset($input['country_code']) ? (string) preg_replace('/\D+/', '', (string) $input['country_code']) : '880';
        $clean['country_code'] = ($cc !== '' && strlen($cc) <= 5) ? $cc : '880';

        // Rate limit
        $limit = isset($input['rate_limit']) ? absint($input['rate_limit']) : 5;
        $clean['rate_limit'] = ($limit > 0 && $limit <= 100) ? $limit : 5;

        // Delete on uninstall
        $clean['delete_on_uninstall'] = !empty($input['delete_on_uninstall']);

        return $clean;
    }

    public static function render(): void
    {
        $settings = self::get_all();

        $page_id = (int) get_option('pothik_page_id');
        $page_exists = $page_id > 0 && get_post_status($page_id) !== false;
        $page_status = $page_exists ? (string) get_post_status($page_id) : 'missing';
        $page_url = $page_exists ? PackagePage::url() : '';
        $packages = \Pothik\PackageStore::all();

        Chrome::open('Settings', 'Agency details, notifications, the public page and plugin data.');
        Chrome::saved();

        if (isset($_GET['recreated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(I18n::t('Package page has been recreated.')) . '</p></div>';
        }

        echo '<section class="screen" data-screen="settings">';
        echo '<noscript><style>.pk-admin .st-pane { display: block !important; margin-bottom: 24px; }</style></noscript>';
        echo '<div class="st pk-st">';

        // Left Navigation
        echo '<nav class="st-nav pk-st-nav" id="stNav">';
        echo '<button type="button" class="on" data-p="agency"><span class="kpi-ico"><svg class="i"><use href="#i-home"/></svg></span><span>' . esc_html(I18n::t('Agency')) . '<small>' . esc_html(I18n::t('Name, phone, logo')) . '</small></span></button>';
        echo '<button type="button" data-p="notify"><span class="kpi-ico"><svg class="i"><use href="#i-mail"/></svg></span><span>' . esc_html(I18n::t('Notifications')) . '<small>' . esc_html(I18n::t('Email & spam guard')) . '</small></span></button>';
        echo '<button type="button" data-p="page"><span class="kpi-ico"><svg class="i"><use href="#i-globe"/></svg></span><span>' . esc_html(I18n::t('Public page')) . '<small>' . esc_html(I18n::t('Page & shortcodes')) . '</small></span></button>';
        echo '<button type="button" data-p="data"><span class="kpi-ico"><svg class="i"><use href="#i-db"/></svg></span><span>' . esc_html(I18n::t('Data')) . '<small>' . esc_html(I18n::t('Demo & uninstall')) . '</small></span></button>';
        echo '</nav>';

        // Right Panes
        echo '<div>';

        // MAIN FORM wrapping Agency, Notifications, and Data
        echo '<form id="stForm" class="pk-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" data-pk-dirty>';
        echo '<input type="hidden" name="action" value="pothik_save_settings">';
        wp_nonce_field('pothik_save_settings');

        // Pane 1: Agency
        echo '<div class="st-pane pk-st-pane" data-pane="agency">';
        echo '<div class="card pk-card">';
        echo '<div class="card-head pk-card-head"><h3>' . esc_html(I18n::t('Agency details')) . '</h3><span class="sub">' . esc_html(I18n::t('Used on packages, receipts and the WhatsApp button')) . '</span></div>';
        echo '<div class="card-pad pk-card-pad">';
        echo '<div class="fgrid pk-fgrid">';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('Agency name · English')) . '</span><input name="agency" value="' . esc_attr((string) $settings['agency']) . '" placeholder="Pothik Umrah"></label>';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('Agency name · বাংলা')) . '</span><input name="agency_bn" value="' . esc_attr((string) $settings['agency_bn']) . '" placeholder="পথিক উমরা"></label>';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('Office phone')) . '</span><input name="phone" value="' . esc_attr((string) $settings['phone']) . '" placeholder="01711-000000"></label>';
        echo '<label class="field pk-field"><span>' . esc_html(I18n::t('WhatsApp')) . ' <em>' . esc_html(I18n::t('with country code')) . '</em></span><div class="affix pk-affix"><i>+</i><input name="whatsapp" value="' . esc_attr((string) $settings['whatsapp']) . '" placeholder="8801711000000"></div></label>';
        echo '<label class="field pk-field span pk-span"><span>' . esc_html(I18n::t('Hajj / Umrah license number')) . '</span><input name="license" value="' . esc_attr((string) $settings['license']) . '" placeholder="e.g. HL-1234"></label>';
        echo '</div>'; // .fgrid

        // Logo Dropzone Card
        $logo_url = (string) $settings['logo_url'];
        $has_logo = !empty($logo_url);
        echo '<div class="field pk-field logo-field"><span>' . esc_html(I18n::t('Logo')) . '</span></div>';
        echo '<input type="hidden" name="logo_id" id="logoIdInput" value="' . esc_attr((string) $settings['logo_id']) . '">';
        echo '<input type="hidden" name="logo_url" id="logoUrlInput" value="' . esc_url($logo_url) . '">';

        echo '<div class="drop pk-drop" id="drop">';
        echo '<div class="logo-prev pk-logo-prev">';
        if ($has_logo) {
            echo '<img id="pk-logo-preview" src="' . esc_url($logo_url) . '" alt="Logo">';
            echo '<svg class="i lg" id="pk-logo-fallback" hidden><use href="#i-pin"/></svg>';
        } else {
            echo '<svg class="i lg" id="pk-logo-fallback"><use href="#i-pin"/></svg>';
            echo '<img id="pk-logo-preview" src="" alt="Logo" hidden>';
        }
        echo '</div>'; // .logo-prev
        echo '<div class="drop-meta"><b>' . esc_html(I18n::t('Drop your logo here or click to choose')) . '</b><div class="sub">' . esc_html(I18n::t('PNG or SVG, square works best. Opens the Media Library.')) . '</div></div>';
        echo '<svg class="i lg drop-ico"><use href="#i-upload"/></svg>';
        echo '</div>'; // #drop

        echo '<button type="button" class="btn pk-btn-ghost btn-ghost btn-sm btn-remove-logo" id="removeLogoBtn"' . ($has_logo ? '' : ' hidden') . '>' . esc_html(I18n::t('Remove logo')) . '</button>';
        echo '</div>'; // .card-pad
        echo '</div>'; // .card
        echo '</div>'; // Pane 1

        // Pane 2: Notifications
        echo '<div class="st-pane pk-st-pane" data-pane="notify" hidden>';
        echo '<div class="card pk-card">';
        echo '<div class="card-head pk-card-head"><h3>' . esc_html(I18n::t('New inquiry email')) . '</h3><button type="button" class="btn pk-btn pk-btn-ghost btn-ghost btn-sm" id="testEmailBtn"><svg class="i sm"><use href="#i-mail"/></svg>' . esc_html(I18n::t('Send test')) . '</button></div>';
        echo '<div class="card-pad pk-card-pad">';
        echo '<div class="field pk-field"><span>' . esc_html(I18n::t('Send to')) . '</span>';
        echo '<div class="tags pk-tags" data-tags="mail"><input placeholder="' . esc_attr(I18n::t('Add email and press Enter')) . '"></div>';
        echo '<input type="hidden" name="notification_emails" id="notificationEmailsInput" value="' . esc_attr((string) $settings['notification_emails']) . '">';
        echo '</div>'; // .field
        echo '</div>'; // .card-pad

        echo '<div class="set-row pk-set-row">';
        echo '<div><b>' . esc_html(I18n::t('Default country code')) . '</b><span class="sub">' . esc_html(I18n::t('Used when a pilgrim types 017… without +880')) . '</span></div>';
        echo '<div class="affix pk-affix set-row-affix"><i>+</i><input name="country_code" class="input" value="' . esc_attr((string) $settings['country_code']) . '"></div>';
        echo '</div>'; // .set-row

        echo '<div class="set-row pk-set-row">';
        echo '<div><b>' . esc_html(I18n::t('Spam guard')) . '</b><span class="sub">' . esc_html(I18n::t('Max inquiries from one IP per hour')) . '</span></div>';
        echo '<div class="range-row pk-range-row">';
        echo '<input type="range" min="1" max="30" value="' . esc_attr((string) $settings['rate_limit']) . '" id="rl">';
        echo '<input type="hidden" name="rate_limit" id="rateLimitInput" value="' . esc_attr((string) $settings['rate_limit']) . '">';
        echo '<output id="rlo">' . esc_html((string) $settings['rate_limit']) . '</output>';
        echo '</div>';
        echo '</div>'; // .set-row

        echo '</div>'; // .card
        echo '</div>'; // Pane 2

        // Pane 4: Data (Inside the form for delete_on_uninstall toggle)
        echo '<div class="st-pane pk-st-pane" data-pane="data" hidden>';
        echo '<div class="card pk-card">';
        echo '<div class="set-row pk-set-row">';
        echo '<div><b>' . esc_html(I18n::t('Demo data')) . '</b><span class="sub">' . esc_html(I18n::t('Sample packages, inquiries and a receipt for trying things out')) . '</span></div>';
        $has_demo = \Pothik\PackageStore::has_demo() || \Pothik\InquiryRepository::has_demo() || \Pothik\ReceiptStore::has_demo();
        if ($has_demo) {
            echo '<button type="submit" form="demoRemoveForm" class="btn pk-btn pk-btn-ghost btn-ghost btn-sm btn-clay"><svg class="i sm"><use href="#i-trash"/></svg>' . esc_html(I18n::t('Remove demo')) . '</button>';
        } else {
            echo '<button type="submit" form="demoLoadForm" class="btn pk-btn pk-btn-ghost btn-ghost btn-sm"><svg class="i sm"><use href="#i-flask"/></svg>' . esc_html(I18n::t('Load demo')) . '</button>';
        }
        echo '</div>';
        echo '</div>'; // card demo

        echo '<div class="card pk-card danger-card pk-danger-card">';
        echo '<div class="set-row pk-set-row">';
        echo '<div><b>' . esc_html(I18n::t('Delete everything on uninstall')) . '</b><span class="sub">' . esc_html(I18n::t('Inquiries, packages, receipt and settings are removed for good when the plugin is deleted.')) . '</span></div>';
        echo '<label class="switch pk-switch"><input type="checkbox" name="delete_on_uninstall" value="1"' . checked(!empty($settings['delete_on_uninstall']), true, false) . '><span></span></label>';
        echo '</div>';
        echo '</div>'; // card danger

        echo '</div>'; // Pane 4 (Data)

        // Sticky save bar
        echo '<div class="savebar pk-savebar" id="stSave">';
        echo '<span class="dot"></span><span>' . esc_html(I18n::t('Unsaved settings')) . '</span>';
        echo '<button type="submit" class="btn pk-btn pk-btn-gold btn-gold btn-sm">' . esc_html(I18n::t('Save settings')) . '</button>';
        echo '</div>';

        echo '</form>'; // #stForm

        // Pane 3: Public Page Setup (Outside #stForm to allow recreate form submit)
        echo '<div class="st-pane pk-st-pane" data-pane="page" hidden>';
        echo '<div class="card pk-card">';

        // Row 1: Status
        echo '<div class="set-row pk-set-row">';
        echo '<div><b>' . esc_html(I18n::t('Package page')) . '</b><span class="sub">' . esc_html($page_exists ? $page_url : I18n::t('Page missing or not assigned')) . '</span></div>';
        echo '<span class="status-dot pk-pill ' . ($page_status === 'publish' ? 'live' : 'draft') . '">' . esc_html(strtoupper($page_status)) . '</span>';
        echo '</div>';

        // Row 2: All packages shortcode
        echo '<div class="set-row pk-set-row">';
        echo '<div><b>' . esc_html(I18n::t('Show every package')) . '</b><span class="sub">' . esc_html(I18n::t('Paste on any page or block')) . '</span></div>';
        echo '<div class="code pk-code"><code>[pothik_catalog]</code><button type="button" class="icon-btn pk-icon-btn" data-pk-copy="[pothik_catalog]"><svg class="i sm"><use href="#i-copy"/></svg></button></div>';
        echo '</div>';

        // Row 3: Single package shortcode selector
        $first_pkg_id = !empty($packages[0]['id']) ? (string) $packages[0]['id'] : 'pkg_sample';
        echo '<div class="set-row pk-set-row">';
        echo '<div><b>' . esc_html(I18n::t('One package')) . '</b><span class="sub">' . esc_html(I18n::t('Pick a package to get its code')) . '</span></div>';
        echo '<div class="pkg-code-wrap">';
        if (!empty($packages)) {
            echo '<select id="pkgCodeSelect" class="pk-pkg-select">';
            foreach ($packages as $pkg) {
                echo '<option value="' . esc_attr((string) $pkg['id']) . '">' . esc_html(I18n::field($pkg, 'title')) . '</option>';
            }
            echo '</select>';
        }
        echo '<div class="code pk-code"><code id="singlePkgCode">[pothik_package id="' . esc_attr($first_pkg_id) . '"]</code><button type="button" class="icon-btn pk-icon-btn" id="copySinglePkgCode" data-pk-copy="[pothik_package id=&quot;' . esc_attr($first_pkg_id) . '&quot;]"><svg class="i sm"><use href="#i-copy"/></svg></button></div>';
        echo '</div>';
        echo '</div>';

        // Row 4: Recreate page
        echo '<div class="set-row pk-set-row">';
        echo '<div><b>' . esc_html(I18n::t('Page missing or broken?')) . '</b><span class="sub">' . esc_html(I18n::t('Creates the page again with the shortcode')) . '</span></div>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('pothik_recreate_page');
        echo '<input type="hidden" name="action" value="pothik_recreate_page">';
        echo '<button type="submit" class="btn pk-btn pk-btn-ghost btn-ghost btn-sm">' . esc_html(I18n::t('Recreate page')) . '</button>';
        echo '</form>';
        echo '</div>';

        echo '</div>'; // .card
        echo '</div>'; // Pane 3

        echo '</div>'; // right column container
        echo '</div>'; // .st

        // Hidden standalone demo forms
        echo '<form id="demoLoadForm" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" hidden>';
        wp_nonce_field('pothik_demo_data');
        echo '<input type="hidden" name="action" value="pothik_load_demo">';
        echo '</form>';

        echo '<form id="demoRemoveForm" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" hidden>';
        wp_nonce_field('pothik_demo_data');
        echo '<input type="hidden" name="action" value="pothik_remove_demo">';
        echo '</form>';

        echo '</section>'; // .screen

        Chrome::close();
    }

    public static function ajax_test_email(): void
    {
        if (!check_ajax_referer('pothik_admin', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $settings = self::get_all();
        $email = (string) ($settings['notification_emails'] ?: get_option('admin_email'));

        $subject = sprintf(I18n::t('[Pothik] Test Notification (%s)'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
        $message = I18n::t("Assalamu alaikum,\n\nThis is a test notification from your Pothik Umrah plugin.\nWhen a pilgrim submits an inquiry on your site, you will receive an alert like this.\n\n— Pothik Desk");

        $sent = wp_mail($email, $subject, $message);
        if ($sent) {
            wp_send_json_success(['message' => sprintf(I18n::t('Test email sent to %s'), $email)]);
        } else {
            wp_send_json_error(['message' => I18n::t('wp_mail() returned false. Check your mail configuration.')]);
        }
    }

    public static function handle_save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_save_settings');

        $post = wp_unslash($_POST);
        $post = is_array($post) ? $post : [];

        $sanitized = self::sanitize($post);
        update_option('pothik_settings', $sanitized, false);

        wp_safe_redirect(admin_url('admin.php?page=pothik-settings&updated=1'));
        exit;
    }
}
