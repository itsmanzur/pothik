<?php
/**
 * Package list on the public page.
 *
 * @var array<int, array<string, mixed>> $packages
 *
 * @package Pothik
 */

declare(strict_types=1);

use Pothik\Format;
use Pothik\Front\PackagePage;
use Pothik\I18n;

if (!defined('ABSPATH')) {
    exit;
}

$contact = PackageStore::contact($packages[0] ?? []);
$phone = $contact['phone'];
$phone_href = $contact['phone_href'];
$wa_href = Format::whatsapp_href(
    $contact['whatsapp'],
    sprintf(I18n::t('I want to ask about %s.'), 'Umrah')
);
?>
<article class="pk">
    <header class="pk-bar" id="top">
        <a class="pk-brand" href="#top">
            <?php if ($contact['logo_url'] !== '') : ?>
                <img src="<?php echo esc_url($contact['logo_url']); ?>" alt="<?php echo esc_attr($contact['agency']); ?>" style="max-height:32px;width:auto;vertical-align:middle;margin-right:8px;">
            <?php else : ?>
                <span>Pothik</span>
            <?php endif; ?>
            <small><?php echo esc_html($contact['agency']); ?></small>
        </a>
        <div class="pk-bar-actions">
            <?php echo I18n::toggle(); ?>
            <?php if ($phone_href !== '') : ?>
                <a href="<?php echo esc_url($phone_href); ?>"><?php echo esc_html($phone); ?></a>
            <?php endif; ?>
            <?php if ($wa_href !== '') : ?>
                <a class="pk-bar-wa" href="<?php echo esc_url($wa_href); ?>" target="_blank" rel="noopener"><?php echo esc_html(I18n::t('WhatsApp')); ?></a>
            <?php endif; ?>
        </div>
    </header>

    <div class="pk-wrap">
        <header class="pk-hero pk-hero-plain">
            <div class="pk-hero-copy">
                <h1><?php echo esc_html(I18n::t('Packages')); ?></h1>
                <p class="pk-en"><?php echo esc_html(I18n::t('Choose a package. Each one has its own days and price.')); ?></p>
            </div>
        </header>

        <?php if ($packages === []) : ?>
            <p class="pk-empty"><?php echo esc_html(I18n::t('No packages yet.')); ?></p>
        <?php else : ?>
            <div class="pk-catalog">
                <?php foreach ($packages as $package) : ?>
                    <?php
                    $title = I18n::field($package, 'title');
                    if ($title === '') {
                        $title = I18n::t('Untitled package');
                    }
                    $prices = is_array($package['prices'] ?? null) ? $package['prices'] : [];
                    $quad = (int) ($prices['quad'] ?? 0);
                    $quad_label = $quad > 0 ? Format::money($quad) : I18n::t('Contact the office');

                    $bits = array_filter([
                        ((int) ($package['days'] ?? 0)) > 0 ? sprintf(I18n::t('%s days'), (string) (int) $package['days']) : '',
                        ((int) ($package['nights_makkah'] ?? 0)) > 0 ? sprintf(I18n::t('%s nights in Makkah'), (string) (int) $package['nights_makkah']) : '',
                        ((int) ($package['nights_madinah'] ?? 0)) > 0 ? sprintf(I18n::t('%s nights in Madinah'), (string) (int) $package['nights_madinah']) : '',
                        I18n::field($package, 'route'),
                    ]);
                    $href = PackagePage::url((string) ($package['id'] ?? ''));
                    ?>
                    <a href="<?php echo esc_url($href); ?>">
                        <?php if (($package['status'] ?? 'draft') === 'draft') : ?>
                            <span class="pk-chip" style="font-size:11px;"><?php echo esc_html(I18n::t('Draft')); ?></span>
                        <?php endif; ?>
                        <?php if (I18n::field($package, 'kicker') !== '') : ?>
                            <span><?php echo esc_html(I18n::field($package, 'kicker')); ?></span>
                        <?php endif; ?>
                        <strong><?php echo esc_html($title); ?></strong>
                        <?php if ($bits !== []) : ?>
                            <em><?php echo esc_html(implode(' · ', $bits)); ?></em>
                        <?php endif; ?>
                        <b><?php echo esc_html($quad_label); ?></b>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</article>
