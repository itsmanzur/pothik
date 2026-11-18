<?php
/**
 * The feature list on the Pro comparison screen. Plain information, no checks and no locks.
 *
 * When a roadmap step is marked done, switch its row to 'available' in the same release.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik\Admin;

use Pothik\I18n;

if (!defined('ABSPATH')) {
    exit;
}

final class ProFeatures
{
    /**
     * Groups of rows. 'free' is text, '—' or '✓'. 'pro' is text, '✓' or '' (coming soon).
     * 'status' is 'available' or 'soon'. 'step' is the roadmap step the row belongs to.
     *
     * @return list<array{group: string, rows: list<array<string, string>>}>
     */
    public static function all(): array
    {
        $row = static fn (string $title, string $desc, string $free, string $pro, string $status, string $step = ''): array => [
            'title' => $title,
            'desc' => $desc,
            'free' => $free,
            'pro' => $pro,
            'status' => $status,
            'step' => $step,
        ];

        $groups = [
            [
                'group' => 'Sell',
                'rows' => [
                    $row('Package landing pages', 'Days, hotels, prices and a WhatsApp button on each page.', 'Up to 40 packages', 'Unlimited packages', 'available'),
                    $row('Inquiry form, WhatsApp reply, email alerts', 'Anti-spam time trap included.', '✓', '✓', 'available'),
                    $row('Inquiry to pilgrim in one click', 'Turn a won inquiry into a booked pilgrim without typing again.', '—', '✓', 'available', '2.1'),
                    $row('Agent & sub-agent commission', 'Who brought which pilgrim, earned and owed commission.', '—', '✓', 'available', '2.3'),
                    $row('Season report', 'Best packages, districts, inquiry conversion, agents.', '—', '✓', 'available', '5.2'),
                ],
            ],
            [
                'group' => 'Pilgrims & papers',
                'rows' => [
                    $row('Departures board', 'Several groups per season, dates and seats.', '—', '✓', 'available', '1.1'),
                    $row('Pilgrim file', 'Passport details, mahram and family links, readiness.', '—', '✓', 'available'),
                    $row('Paper upload & checklist', 'Passport, photo, NID and vaccine card in a private folder.', '—', '✓', 'available', '3.1'),
                    $row('Pilgrim’s own page', 'A private link with payments, papers, flight and hotel.', '—', '✓', 'available', '3.3'),
                    $row('Message many pilgrims', 'WhatsApp one by one, SMS gateway later.', '—', '✓', 'available', '3.2'),
                ],
            ],
            [
                'group' => 'Money',
                'rows' => [
                    $row('Payment receipts', 'Printable receipt with your agency details.', '1 latest receipt', 'Full receipt history', 'available'),
                    $row('Installment ledger', 'Partial payments, balance due, bKash / Nagad transaction IDs.', '—', '✓', 'available'),
                    $row('Payment history with receipt numbers', 'Payments are never edited — voided with a reason.', '—', '✓', 'available', '2.2'),
                    $row('Cost & profit per group', 'Tickets in USD, Saudi ground in SAR, home costs in BDT.', '—', '✓', 'available', '5.1'),
                ],
            ],
            [
                'group' => 'Travel',
                'rows' => [
                    $row('Makkah & Madinah rooming lists', 'Separate bed lists for each city.', '—', '✓', 'available'),
                    $row('Day-by-day plan', 'Ziyarah, bus times, meeting points — print and share.', '—', '✓', 'available', '3.4'),
                    $row('ID cards & badges', '8 per A4 with a QR to the pilgrim’s page.', '—', '✓', 'available', '4.1'),
                    $row('Bus seats, luggage tags, passenger list', 'Families sit together; CSV for the airline.', '—', '✓', 'available', '4.2'),
                ],
            ],
            [
                'group' => 'Visa',
                'rows' => [
                    $row('Visa board', 'Staff move each file through statuses. The visa is issued on Masar.', '—', '✓', 'available'),
                    $row('Masar-ready export & import', 'Download in your Saudi company’s format; bring visa numbers back.', '—', '', 'soon', '6A'),
                ],
            ],
            [
                'group' => 'Team',
                'rows' => [
                    $row('Staff roles & group access', 'Who can open which screens and groups.', '—', '✓', 'available', '1.2'),
                    $row('Activity log', 'Who changed what, and when.', '—', '✓', 'available', '1.3'),
                ],
            ],
            [
                'group' => '',
                'rows' => [
                    $row('Support', '', 'Community forum', '1-on-1 agency onboarding', 'available'),
                ],
            ],
        ];

        /**
         * Adjust the feature list without editing the screen.
         *
         * @param array $groups The groups described above.
         */
        $filtered = apply_filters('pothik_pro_features', $groups);

        return is_array($filtered) ? array_values($filtered) : $groups;
    }

    /**
     * @param list<array{group: string, rows: list<array<string, string>>}> $groups
     * @return array{available: int, soon: int}
     */
    public static function counts(array $groups): array
    {
        $count = ['available' => 0, 'soon' => 0];
        foreach ($groups as $group) {
            foreach ((array) ($group['rows'] ?? []) as $row) {
                $key = ($row['status'] ?? '') === 'soon' ? 'soon' : 'available';
                $count[$key]++;
            }
        }

        return $count;
    }

    /**
     * Title of a "coming soon" row whose optional 'module' key names the requested Pro menu item, or ''.
     */
    public static function soon_title(string $module): string
    {
        if ($module === '') {
            return '';
        }
        foreach (self::all() as $group) {
            foreach ((array) ($group['rows'] ?? []) as $row) {
                if (($row['status'] ?? '') === 'soon' && ($row['module'] ?? '') === $module) {
                    return I18n::t((string) $row['title']);
                }
            }
        }

        return '';
    }
}
