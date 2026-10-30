<?php
/**
 * Catalog hero.
 *
 * @var array<int, array{icon: string, text: string}> $trust
 * @var array{intl: bool, ramadan: int|null, hajj: int|null, in_ramadan: bool, in_hajj: bool} $season
 * @var string $makkah_time
 * @var string $heading_html
 *
 * @package Pothik
 */

declare(strict_types=1);

use Pothik\I18n;

if (!defined('ABSPATH')) {
    exit;
}

$ramadan_label = '';
if ($season['in_ramadan']) {
    $ramadan_label = I18n::t('Ramadan Mubarak');
} elseif ($season['ramadan'] !== null) {
    $ramadan_label = (string) $season['ramadan'];
}
$hajj_label = '';
if ($season['in_hajj']) {
    $hajj_label = I18n::t('Hajj days');
} elseif ($season['hajj'] !== null) {
    $hajj_label = (string) $season['hajj'];
}
?>
<section class="hero">
    <div class="sky" id="pk-sky" data-pk-sky><span class="moon"></span></div>
    <?php include __DIR__ . '/skyline.php'; ?>
    <div class="wrap">
        <p class="talbiyah" lang="ar" dir="rtl">لَبَّيْكَ اللَّهُمَّ لَبَّيْكَ</p>
        <h1><?php echo wp_kses($heading_html, ['span' => []]); ?></h1>
        <p class="lead"><?php echo esc_html(I18n::t('Compare days, hotels and walking distance to the Haram. Pick a room share and send an inquiry — the office replies on WhatsApp.')); ?></p>
        <?php if ($trust !== []) : ?>
            <div class="trust">
                <?php foreach ($trust as $chip) : ?>
                    <span><svg class="i sm"><use href="#i-<?php echo esc_attr($chip['icon']); ?>"/></svg><?php echo esc_html($chip['text']); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="season" aria-label="<?php echo esc_attr(I18n::t('Season')); ?>" data-pk-season <?php echo $season['intl'] ? '' : 'data-pk-season-js="1"'; ?>>
            <span class="pill" data-pk-ramadan>
                <?php if ($season['in_ramadan']) : ?>
                    <?php echo esc_html($ramadan_label); ?> 🌙
                <?php else : ?>
                    🌙 <?php echo esc_html(I18n::t('Ramadan in')); ?> <b data-pk-ramadan-n><?php echo esc_html($ramadan_label !== '' ? $ramadan_label : '—'); ?></b> <?php echo esc_html(I18n::t('days')); ?>
                <?php endif; ?>
            </span>
            <span class="pill" data-pk-hajj>
                <?php if ($season['in_hajj']) : ?>
                    <?php echo esc_html($hajj_label); ?> 🤍
                <?php else : ?>
                    🐪 <?php echo esc_html(I18n::t('Hajj in')); ?> <b data-pk-hajj-n><?php echo esc_html($hajj_label !== '' ? $hajj_label : '—'); ?></b> <?php echo esc_html(I18n::t('days')); ?>
                <?php endif; ?>
            </span>
            <div class="scene" role="group" aria-label="<?php echo esc_attr(I18n::t('Scene')); ?>">
                <button type="button" class="on" data-scene="Makkah">🕋 <?php echo esc_html(I18n::t('Makkah')); ?> <b id="mkTime"><?php echo esc_html($makkah_time); ?></b></button>
                <button type="button" data-scene="Madinah">🕌 <?php echo esc_html(I18n::t('Madinah')); ?></button>
            </div>
        </div>
    </div>
</section>
