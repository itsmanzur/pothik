<?php
/**
 * Catalog top bar.
 *
 * @var array<string, string> $contact
 * @var string $catalog_url
 * @var string $wa_general
 *
 * @package Pothik
 */

declare(strict_types=1);

use Pothik\I18n;

if (!defined('ABSPATH')) {
    exit;
}

$share = $catalog_url === '' ? '' : 'https://wa.me/?text=' . rawurlencode(trim(I18n::t('Umrah packages from Dhaka') . "\n" . $catalog_url));
?>
<header class="bar">
    <div class="wrap">
        <a class="brand" href="#top">
            <?php if ($contact['logo_url'] !== '') : ?>
                <img class="brand-logo" src="<?php echo esc_url($contact['logo_url']); ?>" alt="<?php echo esc_attr($contact['agency']); ?>">
            <?php else : ?>
                <span class="brand-mark"><svg class="i"><use href="#i-pin"/></svg></span>
            <?php endif; ?>
            <span>
                <b><?php echo esc_html($contact['agency'] !== '' ? $contact['agency'] : 'Pothik'); ?></b>
                <?php if ($contact['license'] !== '') : ?>
                    <small><?php echo esc_html(sprintf(I18n::t('Hajj & Umrah licence %s'), $contact['license'])); ?></small>
                <?php endif; ?>
            </span>
        </a>
        <nav aria-label="<?php echo esc_attr(I18n::t('On this page')); ?>">
            <a href="#packages"><?php echo esc_html(I18n::t('Packages')); ?></a>
            <a href="#how"><?php echo esc_html(I18n::t('How booking works')); ?></a>
            <a href="#ask"><?php echo esc_html(I18n::t('Ask the office')); ?></a>
        </nav>
        <div class="tools">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo I18n::toggle();
            ?>
            <?php if ($share !== '') : ?>
                <a class="icon-btn share" href="<?php echo esc_url($share); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr(I18n::t('Share this page')); ?>">
                    <svg class="i"><use href="#i-share"/></svg>
                </a>
            <?php endif; ?>
            <?php if ($contact['phone_href'] !== '') : ?>
                <a class="phone" href="<?php echo esc_url($contact['phone_href']); ?>">
                    <svg class="i"><use href="#i-phone"/></svg><span><?php echo esc_html($contact['phone']); ?></span>
                </a>
            <?php endif; ?>
            <?php if ($wa_general !== '') : ?>
                <a class="btn btn-wa btn-sm" href="<?php echo esc_url($wa_general); ?>" target="_blank" rel="noopener">
                    <svg class="i"><use href="#i-wa"/></svg><span><?php echo esc_html(I18n::t('WhatsApp')); ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>
