<?php
/**
 * Admin chrome shared by free screens and, when present, pro screens.
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

final class Chrome
{
    public static function open(string $title, string $lead = ''): void
    {
        $url = PackagePage::url();
        echo '<div class="wrap pk-admin">';
        echo '<header class="pk-admin-head">';
        echo '<div class="pk-mast">';
        echo '<span class="pk-mark" aria-hidden="true">';
        echo '<svg viewBox="0 0 24 24" width="22" height="22">';
        echo '<path fill="currentColor" fill-rule="evenodd" d="M12 2.15a6.35 6.35 0 0 0-6.35 6.35c0 4.85 6.35 13.15 6.35 13.15s6.35-8.3 6.35-13.15A6.35 6.35 0 0 0 12 2.15zm0 8.55a2.25 2.25 0 1 1 0-4.5 2.25 2.25 0 0 1 0 4.5z"/>';
        echo '</svg>';
        echo '</span>';
        echo '<div class="pk-mast-copy">';
        echo '<p class="pk-kicker">Pothik</p>';
        echo '<h1>' . esc_html(I18n::t($title)) . '</h1>';
        if ($lead !== '') {
            echo '<p class="pk-lead">' . esc_html(I18n::t($lead)) . '</p>';
        }
        echo '</div>';
        echo '</div>';
        echo '<div class="pk-admin-tools">';
        echo I18n::toggle();
        if ($url !== '') {
            echo '<a class="pk-btn pk-btn-ghost" href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html(I18n::t('View on site')) . '</a>';
        }
        echo '</div>';
        echo '</header>';
    }

    public static function close(): void
    {
        echo '</div>';
    }

    public static function saved(): void
    {
        if (!isset($_GET['updated'])) {
            return;
        }
        echo '<p class="pk-saved" role="status">' . esc_html(I18n::t('Saved.')) . '</p>';
    }
}
