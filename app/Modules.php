<?php
/**
 * Free module list. Pro rows carry a title and one sentence only.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik;

use Pothik\Admin\Screens;

if (!defined('ABSPATH')) {
    exit;
}

final class Modules
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        $modules = [
            'package' => [
                'menu' => 'Packages',
                'title' => 'Packages',
                'summary' => 'Each package keeps its own days, hotels and prices.',
                'render' => [Screens::class, 'package'],
            ],
            'inquiries' => [
                'menu' => 'Inquiries',
                'title' => 'Inquiries',
                'summary' => 'Name, phone, city and room share.',
                'render' => [Screens::class, 'inquiries'],
            ],
            'receipt' => [
                'menu' => 'Receipt',
                'title' => 'One receipt',
                'summary' => 'Amount, channel and transaction ID.',
                'render' => [Screens::class, 'receipt'],
            ],
            'analytics' => [
                'menu' => 'Analytics',
                'title' => 'Analytics',
                'summary' => 'Counts from inquiries and the receipt.',
                'render' => [Admin\Analytics::class, 'render'],
            ],
            'departures' => [
                'menu' => 'Departures',
                'title' => 'Departure',
                'summary' => 'Date, flight, seats, and group or private.',
            ],
            'pilgrims' => [
                'menu' => 'Pilgrim file',
                'title' => 'Pilgrim file',
                'summary' => 'Passport, family link and visa column.',
            ],
            'rooming' => [
                'menu' => 'Rooming',
                'title' => 'Rooming',
                'summary' => 'Separate Makkah and Madinah bed lists.',
            ],
            'ledger' => [
                'menu' => 'Installments',
                'title' => 'Installment ledger',
                'summary' => 'Balance, who matched it, bKash and Nagad.',
            ],
            'visa' => [
                'menu' => 'Visa board',
                'title' => 'Visa board',
                'summary' => 'Staff update the status here. Issue happens on Masar.',
            ],
        ];

        $filtered = apply_filters('pothik/modules', $modules);

        return is_array($filtered) ? $filtered : $modules;
    }

    /**
     * @param array<string, mixed> $module
     */
    public static function is_open(array $module): bool
    {
        return isset($module['render']) && is_callable($module['render']);
    }
}
