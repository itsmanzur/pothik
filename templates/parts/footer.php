<?php
/**
 * Catalog footer.
 *
 * @var array<string, string> $contact
 * @var string $wa_general
 *
 * @package Pothik
 */

declare(strict_types=1);

use Pothik\I18n;

if (!defined('ABSPATH')) {
    exit;
}
?>
<footer>
    <div class="wrap">
        <span>
            <b><?php echo esc_html($contact['agency'] !== '' ? $contact['agency'] : 'Pothik'); ?></b>
            <?php if ($contact['license'] !== '') : ?>
                · <?php echo esc_html(sprintf(I18n::t('Hajj & Umrah licence %s'), $contact['license'])); ?>
            <?php endif; ?>
        </span>
        <span><?php echo esc_html(I18n::t('Prices are published share prices and do not hold a seat.')); ?></span>
    </div>
</footer>
<?php if ($wa_general !== '') : ?>
    <a class="fab" href="<?php echo esc_url($wa_general); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr(I18n::t('WhatsApp')); ?>">
        <svg class="i"><use href="#i-wa"/></svg>
    </a>
<?php endif; ?>
