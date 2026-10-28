<?php
/**
 * Public inquiries handling and store proxy.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik;

use Pothik\Admin\Settings;
use Pothik\Front\PackagePage;

if (!defined('ABSPATH')) {
    exit;
}

final class InquiryStore
{
    /**
     * Sample inquiry used during initial setup/demo.
     *
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
     * Retrieve all inquiries via the repository.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return InquiryRepository::all();
    }

    /**
     * Generate a signed time-trap token for public inquiry forms.
     */
    public static function generate_time_trap(): string
    {
        $ts = time();
        $hash = wp_hash('pothik_time_trap_' . $ts);

        return $ts . '.' . $hash;
    }

    /**
     * Handle public form submission from website visitors.
     */
    public static function handle_public(): void
    {
        $post = wp_unslash($_POST);
        $post = is_array($post) ? $post : [];
        $package_id = isset($post['package_id']) ? sanitize_key((string) $post['package_id']) : '';

        // Honeypot check: reject silently if bot filled hidden field.
        if (!empty($post['company_website'])) {
            self::redirect('sent', [], $package_id);
        }

        // Time-trap verification (replaces nonce to prevent issues on page-cached sites).
        $trap = isset($post['pothik_trap']) ? sanitize_text_field((string) $post['pothik_trap']) : '';
        if ($trap !== '') {
            $parts = explode('.', $trap);
            if (count($parts) === 2) {
                $ts = (int) $parts[0];
                $hash = (string) $parts[1];
                if (hash_equals(wp_hash('pothik_time_trap_' . $ts), $hash)) {
                    $elapsed = time() - $ts;
                    // Reject silently if submitted in under 3 seconds or older than 24 hours.
                    if ($elapsed < 3 || $elapsed > DAY_IN_SECONDS) {
                        self::redirect('sent', [], $package_id);
                    }
                }
                // If timestamp/HMAC is missing or invalid due to full page caching, do not reject;
                // fall through to honeypot + IP rate limiting.
            }
        }

        // Configurable rate limit using client IP.
        $ip = Request::ip();
        $settings = get_option('pothik_settings', []);
        $limit = (is_array($settings) && isset($settings['rate_limit']) && is_numeric($settings['rate_limit']))
            ? (int) $settings['rate_limit']
            : 5;
        if ($limit < 1) {
            $limit = 5;
        }

        $rate_key = 'pothik_inq_' . md5($ip);
        $current_count = (int) get_transient($rate_key);
        if ($current_count >= $limit) {
            self::redirect_with_state('limit', $post, $package_id);
        }
        set_transient($rate_key, $current_count + 1, HOUR_IN_SECONDS);

        // Input validation.
        $name = isset($post['name']) ? sanitize_text_field((string) $post['name']) : '';
        $phone = isset($post['phone']) ? sanitize_text_field((string) $post['phone']) : '';
        if ($name === '' || $phone === '') {
            self::redirect_with_state('missing', $post, $package_id);
        }

        $share = I18n::canonical('share', isset($post['share']) ? sanitize_text_field((string) $post['share']) : '');
        $trip = I18n::canonical('trip', isset($post['trip']) ? sanitize_text_field((string) $post['trip']) : '');
        if (!isset(['quad' => 1, 'triple' => 1, 'double' => 1, 'single' => 1][$share])) {
            $share = 'quad';
        }
        if ($trip !== 'group' && $trip !== 'private') {
            $trip = 'group';
        }

        $package_title = '';
        if ($package_id !== '') {
            $found = PackageStore::find($package_id);
            if (is_array($found)) {
                $package_title = mb_substr(I18n::field($found, 'title'), 0, 120);
            }
        }

        $inquiry_data = [
            'name' => mb_substr($name, 0, 80),
            'phone' => mb_substr($phone, 0, 40),
            'whatsapp' => mb_substr(isset($post['whatsapp']) ? sanitize_text_field((string) $post['whatsapp']) : '', 0, 40),
            'city' => mb_substr(isset($post['city']) ? sanitize_text_field((string) $post['city']) : '', 0, 40),
            'share' => $share,
            'adults' => min(30, max(1, absint($post['adults'] ?? 1))),
            'children' => min(20, absint($post['children'] ?? 0)),
            'infants' => min(10, absint($post['infants'] ?? 0)),
            'trip' => $trip,
            'note' => mb_substr(isset($post['note']) ? sanitize_textarea_field((string) $post['note']) : '', 0, 300),
            'package_id' => $package_id,
            'package_title' => $package_title,
            'status' => 'new',
            'replied' => 0,
        ];

        InquiryRepository::insert($inquiry_data);

        // Send notification email to admin(s)
        self::send_notification_email($inquiry_data);

        // Save submitted inquiry data for WhatsApp CTA button on success redirect
        $token = wp_generate_password(16, false);
        set_transient('pothik_sent_' . $token, [
            'name' => $inquiry_data['name'],
            'package_title' => $package_title,
            'share' => $share,
            'adults' => $inquiry_data['adults'],
            'children' => $inquiry_data['children'],
            'infants' => $inquiry_data['infants'],
        ], 15 * MINUTE_IN_SECONDS);

        self::redirect('sent', ['ptk' => $token], $package_id);
    }

    public static function handle_delete(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(I18n::t('You do not have permission to view this screen.')));
        }
        check_admin_referer('pothik_delete_inquiry');
        $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash((string) $_POST['id'])) : '';
        if ($id !== '') {
            InquiryRepository::delete_by_id($id);
        }
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
        if ($id !== '') {
            InquiryRepository::toggle_reply($id);
        }
        $back = isset($_POST['back']) ? sanitize_key(wp_unslash((string) $_POST['back'])) : '';
        if ($back !== 'pothik') {
            $back = 'pothik-inquiries';
        }
        wp_safe_redirect(admin_url('admin.php?page=' . $back . '&updated=1'));
        exit;
    }

    /**
     * Send email notification on new public inquiry.
     *
     * @param array<string, mixed> $row
     */
    private static function send_notification_email(array $row): void
    {
        try {
            $settings = Settings::get_all();
            $raw_emails = (string) ($settings['notification_emails'] ?? '');
            $recipients = array_filter(
                array_map('trim', explode(',', str_replace(["\r", "\n", ';'], ',', $raw_emails))),
                static fn (string $e): bool => (bool) is_email($e)
            );
            if ($recipients === []) {
                $admin_email = get_option('admin_email');
                if (is_string($admin_email) && is_email($admin_email)) {
                    $recipients = [$admin_email];
                }
            }
            if ($recipients === []) {
                return;
            }

            $name = (string) ($row['name'] ?? '');
            $share = (string) ($row['share'] ?? 'quad');
            $subject = sprintf('New Umrah inquiry — %s (%s)', $name, $share);

            $visitor_phone = (string) ($row['phone'] ?? '');
            $visitor_wa = (string) ($row['whatsapp'] ?? '');
            $target_wa = $visitor_wa !== '' ? $visitor_wa : $visitor_phone;
            $wa_link = '';
            if ($target_wa !== '') {
                $package_title = (string) ($row['package_title'] ?? '');
                $greeting = sprintf('Salam %s, office reply regarding your Umrah inquiry (%s).', $name, $package_title !== '' ? $package_title : 'Umrah');
                $wa_link = Format::whatsapp_href($target_wa, $greeting);
            }

            $site_name = wp_specialchars_decode((string) get_option('blogname'), ENT_QUOTES);
            $body_lines = [
                sprintf("New Umrah inquiry on %s:\n", $site_name),
                sprintf("Package:       %s", (string) ($row['package_title'] ?? 'General')),
                sprintf("Name:          %s", $name),
                sprintf("Phone:         %s", $visitor_phone),
                sprintf("WhatsApp:      %s", $visitor_wa),
                sprintf("City:          %s", (string) ($row['city'] ?? '')),
                sprintf("Room Share:    %s", $share),
                sprintf("Trip Type:     %s", (string) ($row['trip'] ?? 'group')),
                sprintf("Pilgrims:      %d Adult(s), %d Child(ren), %d Infant(s)", (int) ($row['adults'] ?? 1), (int) ($row['children'] ?? 0), (int) ($row['infants'] ?? 0)),
                sprintf("Note:          %s", (string) ($row['note'] ?? '')),
            ];
            if ($wa_link !== '') {
                $body_lines[] = "\nQuick WhatsApp reply link:";
                $body_lines[] = $wa_link;
            }
            $body_lines[] = "\nView in admin:";
            $body_lines[] = admin_url('admin.php?page=pothik-inquiries');

            $message = implode("\n", $body_lines);

            $headers = [];
            if (!empty($row['email']) && is_email((string) $row['email'])) {
                $headers[] = 'Reply-To: ' . sanitize_email((string) $row['email']);
            }

            $mail_args = [
                'to' => $recipients,
                'subject' => $subject,
                'message' => $message,
                'headers' => $headers,
                'inquiry' => $row,
            ];

            /**
             * Filter notification email arguments before sending.
             * Return false to prevent sending (e.g. if handled by Pro).
             *
             * @param array<string, mixed>|false $mail_args
             */
            $mail_args = apply_filters('pothik/notify_email', $mail_args);

            if ($mail_args !== false && is_array($mail_args) && !empty($mail_args['to'])) {
                wp_mail(
                    $mail_args['to'],
                    (string) ($mail_args['subject'] ?? $subject),
                    (string) ($mail_args['message'] ?? $message),
                    $mail_args['headers'] ?? []
                );
            }
        } catch (\Throwable $e) {
            // Failure must never break the visitor flow.
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Pothik notify_email error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Preserve submitted form values in a transient upon validation failure.
     *
     * @param array<string, mixed> $post
     */
    private static function redirect_with_state(string $result, array $post, string $package_id): void
    {
        $token = wp_generate_password(16, false);
        $saved = [
            'name' => isset($post['name']) ? sanitize_text_field((string) $post['name']) : '',
            'phone' => isset($post['phone']) ? sanitize_text_field((string) $post['phone']) : '',
            'whatsapp' => isset($post['whatsapp']) ? sanitize_text_field((string) $post['whatsapp']) : '',
            'city' => isset($post['city']) ? sanitize_text_field((string) $post['city']) : '',
            'share' => isset($post['share']) ? sanitize_text_field((string) $post['share']) : '',
            'trip' => isset($post['trip']) ? sanitize_text_field((string) $post['trip']) : '',
            'adults' => isset($post['adults']) ? (string) absint($post['adults']) : '1',
            'children' => isset($post['children']) ? (string) absint($post['children']) : '0',
            'infants' => isset($post['infants']) ? (string) absint($post['infants']) : '0',
            'note' => isset($post['note']) ? sanitize_textarea_field((string) $post['note']) : '',
        ];
        set_transient('pothik_form_' . $token, $saved, 10 * MINUTE_IN_SECONDS);

        self::redirect($result, ['ptk' => $token], $package_id);
    }

    /**
     * Safe redirect back to the package inquiry section.
     *
     * @param array<string, string> $extra_args
     */
    private static function redirect(string $result, array $extra_args = [], string $package_id = ''): void
    {
        $url = PackagePage::url($package_id);
        if ($url === '') {
            $url = home_url('/');
        }
        $args = array_merge(['pothik' => $result], $extra_args);
        wp_safe_redirect(add_query_arg($args, $url) . '#inquiry');
        exit;
    }
}
