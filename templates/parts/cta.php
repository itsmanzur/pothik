<?php
/**
 * Catalog call to action.
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
<section class="cta" id="ask">
    <div>
        <h3><?php echo esc_html(I18n::t('Not sure which package suits your family?')); ?></h3>
        <p><?php echo esc_html(I18n::t('Tell the office your dates and budget — they will suggest one.')); ?></p>
    </div>
    <div class="cta-actions">
        <?php if ($wa_general !== '') : ?>
            <a class="btn btn-wa" href="<?php echo esc_url($wa_general); ?>" target="_blank" rel="noopener"><svg class="i"><use href="#i-wa"/></svg><?php echo esc_html(I18n::t('Ask on WhatsApp')); ?></a>
        <?php endif; ?>
        <?php if ($contact['phone_href'] !== '') : ?>
            <a class="btn btn-ghost" href="<?php echo esc_url($contact['phone_href']); ?>"><svg class="i"><use href="#i-phone"/></svg><?php echo esc_html($contact['phone']); ?></a>
        <?php endif; ?>
    </div>
</section>
