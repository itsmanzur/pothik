<?php
/**
 * Free admin screens.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik\Admin;

use Pothik\Format;
use Pothik\Front\PackagePage;
use Pothik\I18n;
use Pothik\InquiryStore;
use Pothik\Modules;
use Pothik\PackageStore;
use Pothik\ReceiptStore;

if (!defined('ABSPATH')) {
    exit;
}

final class Screens
{
    public static function overview(): void
    {
        $inquiries = InquiryStore::all();
        $receipt = ReceiptStore::get();
        $pro = defined('POTHIK_PRO_FILE');
        Chrome::open('Desk', 'Packages, inquiries and one receipt live in this plugin.');
        self::package_offer();
        echo '<div class="pk-stats">';
        echo '<a class="pk-stat" href="' . esc_url(admin_url('admin.php?page=pothik-inquiries')) . '"><strong>' . esc_html((string) count($inquiries)) . '</strong><span>' . esc_html(I18n::t('Inquiries')) . '</span></a>';
        echo '<a class="pk-stat" href="' . esc_url(admin_url('admin.php?page=pothik-receipt')) . '"><strong>' . esc_html(Format::money((int) $receipt['amount'])) . '</strong><span>' . esc_html(I18n::t('Last receipt')) . '</span></a>';
        echo '<a class="pk-stat" href="' . esc_url(admin_url('admin.php?page=pothik-departures')) . '"><strong>' . esc_html(I18n::t($pro ? 'On' : 'Off')) . '</strong><span>Pothik Pro</span></a>';
        echo '</div>';
        $recent = array_slice(array_reverse($inquiries), 0, 4);
        echo '<section class="pk-panel pk-recent">';
        echo '<header class="pk-panel-head"><h2>' . esc_html(I18n::t('Recent inquiries')) . '</h2>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=pothik-inquiries')) . '">' . esc_html(I18n::t('All inquiries')) . '</a></header>';
        if ($recent === []) {
            echo '<p class="pk-empty">' . esc_html(I18n::t('No inquiries yet.')) . '</p>';
        } else {
            echo '<div class="pk-recent-list">';
            foreach ($recent as $row) {
                if (!is_array($row)) {
                    continue;
                }
                self::inquiry_line($row);
            }
            echo '</div>';
        }
        echo '</section>';
        $open = [];
        $locked = [];
        foreach (Modules::all() as $id => $module) {
            if (Modules::is_open($module)) {
                $open[$id] = $module;
            } else {
                $locked[$id] = $module;
            }
        }
        self::module_grid($open);
        if ($locked !== []) {
            echo '<h2 class="pk-section-label">Pothik Pro</h2>';
            self::module_grid($locked);
        }
        Chrome::close();
    }

    /**
     * @param array<string, mixed> $module
     */
    public static function locked(array $module): void
    {
        Chrome::open((string) ($module['title'] ?? ''), (string) ($module['summary'] ?? ''));
        echo '<section class="pk-lock">';
        echo '<p class="pk-lock-mark">Pro</p>';
        echo '<h2>' . esc_html(I18n::t((string) ($module['title'] ?? ''))) . '</h2>';
        echo '<p>' . esc_html(I18n::t((string) ($module['summary'] ?? ''))) . '</p>';
        echo '<p class="pk-lock-note">' . esc_html(I18n::t('Turn on Pothik Pro and this screen opens from that plugin.')) . '</p>';
        echo '</section>';
        Chrome::close();
    }

    public static function package(): void
    {
        $id = isset($_GET['package']) ? sanitize_key(wp_unslash((string) $_GET['package'])) : '';
        if ($id !== '') {
            $package = PackageStore::find($id);
            if ($package === null) {
                Chrome::open('Packages', 'Each package keeps its own days, hotels and prices.');
                echo '<p class="pk-empty">' . esc_html(I18n::t('No packages yet.')) . '</p>';
                Chrome::close();
                return;
            }
            self::package_form($package);
            return;
        }

        self::package_list();
    }

    private static function package_list(): void
    {
        $packages = PackageStore::all();
        Chrome::open('Packages', 'Each package keeps its own days, hotels and prices.');
        Chrome::saved();
        echo '<section class="pk-panel pk-packages">';
        echo '<header class="pk-panel-head"><h2>' . esc_html(I18n::t('Packages')) . '</h2>';
        self::new_package_form();
        echo '</header>';
        if ($packages === []) {
            echo '<p class="pk-empty">' . esc_html(I18n::t('No packages yet.')) . '</p>';
        }
        foreach ($packages as $package) {
            self::package_row($package, 'pothik-package');
        }
        echo '</section>';
        Chrome::close();
    }

    /**
     * @param array<string, mixed> $package
     */
    private static function package_form(array $package): void
    {
        $prices = is_array($package['prices']) ? $package['prices'] : [];
        $lines = static function (string $key) use ($package): string {
            return implode("\n", I18n::lines($package, $key));
        };
        $text = static function (string $key) use ($package): string {
            return I18n::field($package, $key);
        };
        $price_labels = [
            'quad' => 'Quad',
            'triple' => 'Triple',
            'double' => 'Double',
            'single' => 'Single',
            'child_bed' => 'Child with bed',
            'child_nobed' => 'Child without bed',
            'infant' => 'Infant',
        ];

        Chrome::open('Package', 'Type the days, nights, hotels and prices. The site shows this package as you save it.');
        echo '<p class="pk-lead">' . esc_html(I18n::t('Text fields follow the language toggle. Prices stay in BDT.')) . '</p>';
        Chrome::saved();
        echo '<div class="pk-pkg-tools">';
        echo '<nav class="pk-jump" aria-label="' . esc_attr(I18n::t('Sections')) . '">';
        foreach (['pk-sec-title' => 'Title', 'pk-sec-hotels' => 'Hotels', 'pk-sec-prices' => 'Share prices, BDT', 'pk-sec-terms' => 'Journey and terms'] as $anchor => $label) {
            echo '<a href="#' . esc_attr($anchor) . '">' . esc_html(I18n::t($label)) . '</a>';
        }
        echo '</nav>';
        self::package_menu($package, 'edit');
        echo '</div>';
        echo '<form class="pk-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="pothik_save_package">';
        echo '<input type="hidden" name="package_id" value="' . esc_attr((string) ($package['id'] ?? '')) . '">';
        wp_nonce_field('pothik_save_package');

        echo '<section class="pk-panel" id="pk-sec-title"><h2>' . esc_html(I18n::t('Title')) . '</h2><div class="pk-grid">';
        self::field('agency', 'Agency name', $text('agency'));
        self::field('phone', 'Phone', (string) $package['phone']);
        self::field('whatsapp', 'WhatsApp number', (string) $package['whatsapp'], '8801…');
        self::field('kicker', 'Eyebrow', $text('kicker'));
        self::field('title', 'Package name', $text('title'));
        self::field('days', 'Days', (string) $package['days'], '', 'number');
        self::field('nights_makkah', 'Makkah nights', (string) $package['nights_makkah'], '', 'number');
        self::field('nights_madinah', 'Madinah nights', (string) $package['nights_madinah'], '', 'number');
        self::field('route', 'Flight direction', $text('route'));
        self::field('group_note', 'Group note', $text('group_note'));
        echo '</div></section>';

        echo '<section class="pk-panel" id="pk-sec-hotels"><h2>' . esc_html(I18n::t('Hotels')) . '</h2><div class="pk-grid">';
        self::field('makkah_hotel', 'Makkah hotel', $text('makkah_hotel'));
        self::field('makkah_similar', 'Makkah, or similar', $text('makkah_similar'));
        self::field('makkah_stars', 'Makkah stars', $text('makkah_stars'));
        self::field('makkah_meters', 'Metres to the Haram', (string) $package['makkah_meters'], '', 'number');
        self::field('madinah_hotel', 'Madinah hotel', $text('madinah_hotel'));
        self::field('madinah_similar', 'Madinah, or similar', $text('madinah_similar'));
        self::field('madinah_stars', 'Madinah stars', $text('madinah_stars'));
        self::field('madinah_meters', 'Metres to the Prophet\'s Mosque', (string) $package['madinah_meters'], '', 'number');
        echo '</div></section>';

        echo '<section class="pk-panel" id="pk-sec-prices"><h2>' . esc_html(I18n::t('Share prices, BDT')) . '</h2><div class="pk-grid">';
        foreach ($price_labels as $key => $label) {
            self::field('price_' . $key, $label, (string) ($prices[$key] ?? 0), '', 'number');
        }
        self::field('food_note', 'Food note', $text('food_note'));
        echo '</div></section>';

        echo '<section class="pk-panel" id="pk-sec-terms"><h2>' . esc_html(I18n::t('Journey and terms')) . '</h2>';
        self::area('inclusions', 'Included, one item per line', $lines('inclusions'));
        self::area('exclusions', 'Not included, one item per line', $lines('exclusions'));
        self::area('itinerary', 'Day by day', $lines('itinerary'));
        self::field('docs', 'Documents', $text('docs'));
        self::area('cancel', 'Cancellation sentence', $text('cancel'));
        echo '</section>';

        echo '<p class="pk-form-actions">';
        echo '<a class="pk-btn pk-btn-ghost" href="' . esc_url(admin_url('admin.php?page=pothik-package')) . '">' . esc_html(I18n::t('All packages')) . '</a>';
        echo '<button class="pk-btn" type="submit">' . esc_html(I18n::t('Save package')) . '</button>';
        echo '</p>';
        echo '</form>';
        Chrome::close();
    }

    public static function inquiries(): void
    {
        Chrome::open('Inquiries', 'Names from the site form. The pilgrim file is in Pro.');
        Chrome::saved();
        $rows = InquiryStore::all();
        if ($rows === []) {
            echo '<p class="pk-empty">' . esc_html(I18n::t('No inquiries yet.')) . '</p>';
            Chrome::close();
            return;
        }

        echo '<div class="pk-toolbar">';
        echo '<input type="search" data-pk-filter="pk-inquiries" placeholder="' . esc_attr(I18n::t('Search name, phone or city')) . '" aria-label="' . esc_attr(I18n::t('Search name, phone or city')) . '">';
        echo '<div class="pk-chips" data-pk-status-filter>';
        echo '<button type="button" class="is-on" data-pk-status="">' . esc_html(I18n::t('All')) . '</button>';
        echo '<button type="button" data-pk-status="waiting">' . esc_html(I18n::t('Waiting')) . '</button>';
        echo '<button type="button" data-pk-status="replied">' . esc_html(I18n::t('Replied')) . '</button>';
        echo '</div></div>';
        echo '<p class="pk-empty" data-pk-filter-empty hidden>' . esc_html(I18n::t('Nothing matches.')) . '</p>';
        echo '<div class="pk-table-wrap"><table class="pk-table"><thead><tr>';
        foreach (['Time', 'Name', 'Phone', 'City', 'Share', 'Pilgrims', 'Type', 'Status', ''] as $head) {
            echo '<th>' . esc_html($head === '' ? '' : I18n::t($head)) . '</th>';
        }
        echo '</tr></thead><tbody id="pk-inquiries">';
        foreach ($rows as $row) {
            $count = ((int) ($row['adults'] ?? 0)) . ' ' . I18n::t('adults');
            if ((int) ($row['children'] ?? 0) > 0) {
                $count .= ', ' . (int) $row['children'] . ' ' . I18n::t('children');
            }
            if ((int) ($row['infants'] ?? 0) > 0) {
                $count .= ', ' . (int) $row['infants'] . ' ' . I18n::t('infants');
            }
            $hay = mb_strtolower(implode(' ', [
                (string) ($row['name'] ?? ''),
                (string) ($row['phone'] ?? ''),
                (string) ($row['whatsapp'] ?? ''),
                (string) ($row['city'] ?? ''),
                (string) ($row['note'] ?? ''),
                I18n::choice('share', (string) ($row['share'] ?? '')),
                I18n::choice('trip', (string) ($row['trip'] ?? '')),
            ]));
            $replied = ($row['replied'] ?? '') === '1';
            echo '<tr data-pk-hay="' . esc_attr($hay) . '" data-pk-status="' . esc_attr($replied ? 'replied' : 'waiting') . '">';
            echo '<td>' . esc_html((string) ($row['created'] ?? '')) . '</td>';
            echo '<td><strong>' . esc_html((string) ($row['name'] ?? '')) . '</strong>';
            if (!empty($row['package'])) {
                echo '<span class="pk-sub">' . esc_html((string) $row['package']) . '</span>';
            }
            if (!empty($row['note'])) {
                echo '<span class="pk-sub">' . esc_html((string) $row['note']) . '</span>';
            }
            echo '</td>';
            echo '<td><div class="pk-row-actions">' . self::phone_links($row) . '</div></td>';
            echo '<td>' . esc_html((string) ($row['city'] ?? '')) . '</td>';
            echo '<td>' . esc_html(I18n::choice('share', (string) ($row['share'] ?? ''))) . '</td>';
            echo '<td>' . esc_html($count) . '</td>';
            echo '<td>' . esc_html(I18n::choice('trip', (string) ($row['trip'] ?? ''))) . '</td>';
            echo '<td>' . self::reply_state($row) . '</td>';
            echo '<td class="pk-row-actions">';
            echo self::reply_form($row, 'pothik-inquiries');
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" data-pk-confirm="' . esc_attr(I18n::t('Delete this inquiry?')) . '">';
            echo '<input type="hidden" name="action" value="pothik_delete_inquiry">';
            echo '<input type="hidden" name="id" value="' . esc_attr((string) ($row['id'] ?? '')) . '">';
            wp_nonce_field('pothik_delete_inquiry');
            echo '<button class="pk-text-btn" type="submit">' . esc_html(I18n::t('Delete')) . '</button>';
            echo '</form></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
        Chrome::close();
    }

    public static function receipt(): void
    {
        $receipt = ReceiptStore::get();
        $channels = ['bkash', 'nagad', 'rocket', 'bank', 'cash'];
        Chrome::open('One receipt', 'One payment proof. The installment ledger is in Pro.');
        Chrome::saved();
        echo '<div class="pk-receipt">';
        echo '<form class="pk-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" data-pk-receipt>';
        echo '<input type="hidden" name="action" value="pothik_save_receipt">';
        wp_nonce_field('pothik_save_receipt');
        echo '<section class="pk-panel"><div class="pk-grid">';
        self::field('name', 'Paid by', $receipt['name']);
        self::field('amount', 'Amount, taka', $receipt['amount'], '', 'number');
        echo '<label><span>' . esc_html(I18n::t('Channel')) . '</span><select name="channel">';
        $current_channel = I18n::canonical('channel', $receipt['channel']);
        foreach ($channels as $channel) {
            echo '<option value="' . esc_attr($channel) . '"' . selected($current_channel, $channel, false) . '>' . esc_html(I18n::choice('channel', $channel)) . '</option>';
        }
        echo '</select></label>';
        self::field('trx', 'Transaction ID', $receipt['trx']);
        self::field('date', 'Date', $receipt['date'], '', 'date');
        echo '</div>';
        self::area('note', 'Note', $receipt['note']);
        echo '</section>';
        echo '<p class="pk-form-actions"><button class="pk-btn" type="submit">' . esc_html(I18n::t('Save receipt')) . '</button></p>';
        echo '</form>';
        echo '<aside class="pk-slip" data-pk-slip><p>' . esc_html(I18n::t('Receipt')) . '</p>';
        echo '<strong data-pk-slip-amount>' . esc_html(Format::money((int) $receipt['amount'])) . '</strong>';
        echo '<span data-pk-slip-who>' . esc_html($receipt['name']) . ' · ' . esc_html(I18n::choice('channel', $receipt['channel'])) . '</span>';
        echo '<span data-pk-slip-trx>' . esc_html($receipt['trx']) . '</span>';
        echo '<span data-pk-slip-date>' . esc_html($receipt['date']) . '</span>';
        echo '<span data-pk-slip-note>' . esc_html($receipt['note']) . '</span>';
        echo '<div class="pk-slip-actions">';
        echo '<button type="button" class="pk-btn" data-pk-copy>' . esc_html(I18n::t('Copy for WhatsApp')) . '</button>';
        echo '<button type="button" class="pk-btn pk-btn-ghost" data-pk-print>' . esc_html(I18n::t('Print')) . '</button>';
        echo '</div>';
        echo '<p class="pk-slip-copied" data-pk-copied hidden>' . esc_html(I18n::t('Copied.')) . '</p>';
        echo '</aside>';
        echo '</div>';
        Chrome::close();
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function inquiry_line(array $row): void
    {
        echo '<article class="pk-recent-row">';
        echo '<div><strong>' . esc_html((string) ($row['name'] ?? '')) . '</strong> ' . self::reply_state($row);
        $meta = trim((string) ($row['city'] ?? '') . ' · ' . I18n::choice('share', (string) ($row['share'] ?? '')), ' ·');
        echo '<span class="pk-sub">' . esc_html($meta) . '</span></div>';
        echo '<div class="pk-row-actions">' . self::phone_links($row) . self::reply_form($row, 'pothik') . '</div>';
        echo '<time>' . esc_html((string) ($row['created'] ?? '')) . '</time>';
        echo '</article>';
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function phone_links(array $row): string
    {
        $phone = (string) ($row['phone'] ?? '');
        $whatsapp = (string) ($row['whatsapp'] ?? '');
        if ($whatsapp === '') {
            $whatsapp = $phone;
        }
        $call = Format::phone_href($phone);
        $reply = Format::whatsapp_href($whatsapp, I18n::t('Office reply about your Umrah inquiry.'));
        $html = '';
        if ($call !== '') {
            $html .= '<a href="' . esc_url($call) . '">' . esc_html($phone) . '</a>';
        }
        if ($reply !== '') {
            $html .= '<a href="' . esc_url($reply) . '" target="_blank" rel="noopener">' . esc_html(I18n::t('Reply')) . '</a>';
        }

        return $html;
    }

    private static function package_offer(): void
    {
        $packages = PackageStore::all();
        echo '<section class="pk-panel pk-packages">';
        echo '<header class="pk-panel-head"><h2>' . esc_html(I18n::t('Packages')) . '</h2>';
        self::new_package_form();
        echo '</header>';
        if ($packages === []) {
            echo '<p class="pk-empty">' . esc_html(I18n::t('No packages yet.')) . '</p>';
        }
        foreach ($packages as $package) {
            self::package_row($package, 'pothik');
        }
        echo '</section>';
    }

    /**
     * @param array<string, mixed> $package
     */
    private static function package_row(array $package, string $back): void
    {
        $id = (string) ($package['id'] ?? '');
        $prices = is_array($package['prices'] ?? null) ? $package['prices'] : [];
        $title = I18n::field($package, 'title');
        if ($title === '') {
            $title = I18n::t('Untitled package');
        }
        $bits = [];
        if ((int) ($package['days'] ?? 0) > 0) {
            $bits[] = sprintf(I18n::t('%s days'), (string) (int) $package['days']);
        }
        if ((int) ($package['nights_makkah'] ?? 0) > 0) {
            $bits[] = sprintf(I18n::t('%s nights in Makkah'), (string) (int) $package['nights_makkah']);
        }
        if ((int) ($package['nights_madinah'] ?? 0) > 0) {
            $bits[] = sprintf(I18n::t('%s nights in Madinah'), (string) (int) $package['nights_madinah']);
        }
        $edit = admin_url('admin.php?page=pothik-package&package=' . rawurlencode($id));
        echo '<article class="pk-pkg-row">';
        echo '<div><a href="' . esc_url($edit) . '"><strong>' . esc_html($title) . '</strong></a>';
        if ($bits !== []) {
            echo '<span class="pk-sub">' . esc_html(implode(' · ', $bits)) . '</span>';
        }
        echo '</div>';
        echo '<b>' . esc_html(Format::money((int) ($prices['quad'] ?? 0))) . '</b>';
        self::package_menu($package, $back);
        echo '</article>';
    }

    /**
     * @param array<string, mixed> $package
     */
    private static function package_menu(array $package, string $back): void
    {
        $id = (string) ($package['id'] ?? '');
        $edit = admin_url('admin.php?page=pothik-package&package=' . rawurlencode($id));
        $url = PackagePage::url($id);
        echo '<div class="pk-more">';
        echo '<button type="button" class="pk-more-btn" data-pk-more aria-expanded="false" aria-label="' . esc_attr(I18n::t('Package actions')) . '">';
        echo '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><circle cx="12" cy="5" r="1.7" fill="currentColor"></circle><circle cx="12" cy="12" r="1.7" fill="currentColor"></circle><circle cx="12" cy="19" r="1.7" fill="currentColor"></circle></svg>';
        echo '</button>';
        echo '<div class="pk-more-menu" data-pk-more-menu hidden>';
        if ($back !== 'edit') {
            echo '<a href="' . esc_url($edit) . '">' . esc_html(I18n::t('Edit package')) . '</a>';
        }
        if ($url !== '') {
            echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html(I18n::t('View on site')) . '</a>';
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="pothik_copy_package">';
        echo '<input type="hidden" name="package_id" value="' . esc_attr($id) . '">';
        echo '<input type="hidden" name="back" value="' . esc_attr($back) . '">';
        wp_nonce_field('pothik_copy_package');
        echo '<button type="submit">' . esc_html(I18n::t('Copy package')) . '</button>';
        echo '</form>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" data-pk-confirm="' . esc_attr(I18n::t('Delete this package?')) . '">';
        echo '<input type="hidden" name="action" value="pothik_delete_package">';
        echo '<input type="hidden" name="package_id" value="' . esc_attr($id) . '">';
        wp_nonce_field('pothik_delete_package');
        echo '<button type="submit">' . esc_html(I18n::t('Delete')) . '</button>';
        echo '</form>';
        echo '</div></div>';
    }

    private static function new_package_form(): void
    {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="pothik_new_package">';
        wp_nonce_field('pothik_new_package');
        echo '<button class="pk-btn" type="submit">' . esc_html(I18n::t('New package')) . '</button>';
        echo '</form>';
    }

    /**
     * @param array<string, array<string, mixed>> $modules
     */
    private static function module_grid(array $modules): void
    {
        if ($modules === []) {
            return;
        }
        echo '<div class="pk-module-grid">';
        foreach ($modules as $id => $module) {
            $open = Modules::is_open($module);
            $href = admin_url('admin.php?page=pothik-' . $id);
            echo '<a class="pk-module' . ($open ? '' : ' is-locked') . '" href="' . esc_url($href) . '">';
            echo '<span class="pk-module-kicker">' . esc_html($open ? I18n::t('Open') : 'Pro') . '</span>';
            echo '<strong>' . esc_html(I18n::t((string) ($module['title'] ?? ''))) . '</strong>';
            echo '<span>' . esc_html(I18n::t((string) ($module['summary'] ?? ''))) . '</span>';
            echo '</a>';
        }
        echo '</div>';
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function reply_state(array $row): string
    {
        $replied = ($row['replied'] ?? '') === '1';

        return '<span class="pk-state' . ($replied ? ' is-done' : '') . '">' . esc_html(I18n::t($replied ? 'Replied' : 'Waiting')) . '</span>';
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function reply_form(array $row, string $back): string
    {
        $replied = ($row['replied'] ?? '') === '1';
        $html = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        $html .= '<input type="hidden" name="action" value="pothik_reply_inquiry">';
        $html .= '<input type="hidden" name="id" value="' . esc_attr((string) ($row['id'] ?? '')) . '">';
        $html .= '<input type="hidden" name="back" value="' . esc_attr($back) . '">';
        $html .= wp_nonce_field('pothik_reply_inquiry', '_wpnonce', true, false);
        $html .= '<button class="pk-text-btn' . ($replied ? ' is-done' : '') . '" type="submit">' . esc_html(I18n::t($replied ? 'Mark waiting' : 'Mark replied')) . '</button>';
        $html .= '</form>';

        return $html;
    }

    private static function field(string $name, string $label, string $value, string $hint = '', string $type = 'text'): void
    {
        echo '<label><span>' . esc_html(I18n::t($label)) . '</span>';
        echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"';
        if ($hint !== '') {
            echo ' placeholder="' . esc_attr($hint) . '"';
        }
        echo '></label>';
    }

    private static function area(string $name, string $label, string $value): void
    {
        echo '<label class="pk-span"><span>' . esc_html(I18n::t($label)) . '</span>';
        echo '<textarea name="' . esc_attr($name) . '" rows="5">' . esc_textarea($value) . '</textarea></label>';
    }
}
