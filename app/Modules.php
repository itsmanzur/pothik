<?php
/**
 * The screens this plugin provides. Add-ons extend the list through the pothik_modules filter.
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
                'title' => 'Receipts',
                'summary' => 'Amount, channel and transaction ID. Every receipt is kept.',
                'render' => [Screens::class, 'receipt'],
            ],
            'analytics' => [
                'menu' => 'Analytics',
                'title' => 'Analytics',
                'summary' => 'Counts from inquiries and receipts.',
                'render' => [Admin\Analytics::class, 'render'],
            ],
        ];

        $filtered = apply_filters('pothik_modules', $modules);

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
