<?php
/**
 * One package card.
 *
 * @var array<string, mixed> $package
 * @var string $badge
 * @var array<string, string> $contact
 * @var int $card_index
 *
 * @package Pothik
 */

declare(strict_types=1);

use Pothik\Format;
use Pothik\Front\Catalog;
use Pothik\Front\PackagePage;
use Pothik\I18n;

if (!defined('ABSPATH')) {
    exit;
}

$id = (string) ($package['id'] ?? '');
$title = I18n::field($package, 'title');
if ($title === '') {
    $title = I18n::t('Untitled package');
}
$prices = is_array($package['prices'] ?? null) ? $package['prices'] : [];
$quad = (int) ($prices['quad'] ?? 0);
$triple = (int) ($prices['triple'] ?? 0);
$days = (int) ($package['days'] ?? 0);
$nm = (int) ($package['nights_makkah'] ?? 0);
$nd = (int) ($package['nights_madinah'] ?? 0);
$mm = (int) ($package['makkah_meters'] ?? 0);
$dm = (int) ($package['madinah_meters'] ?? 0);
$ms = Catalog::stars($package['makkah_stars'] ?? 0);
$ds = Catalog::stars($package['madinah_stars'] ?? 0);
$mh = I18n::field($package, 'makkah_hotel');
$dh = I18n::field($package, 'madinah_hotel');
$route = I18n::field($package, 'route');
$kicker = I18n::field($package, 'kicker');
$colors = Catalog::colors($id);
$href = PackagePage::url($id);
$inclusions = I18n::lines($package, 'inclusions');
$shown = array_slice($inclusions, 0, 4);
$more = max(0, count($inclusions) - count($shown));
$draft = ($package['status'] ?? 'draft') !== 'published';
$wa = Format::whatsapp_href($contact['whatsapp'], sprintf(I18n::t('I want to ask about %s.'), $title));
$hotels = trim($mh . ' ' . $dh);
$depart = Catalog::depart_label($package);
?>
<article
    class="card"
    style="<?php echo esc_attr('--c1:' . $colors[0] . ';--c2:' . $colors[1] . ';animation-delay:' . ($card_index * 60) . 'ms'); ?>"
    data-id="<?php echo esc_attr($id); ?>"
    data-title="<?php echo esc_attr(strtolower($title)); ?>"
    data-hotels="<?php echo esc_attr(strtolower($hotels)); ?>"
    data-days="<?php echo esc_attr((string) $days); ?>"
    data-price="<?php echo esc_attr((string) $quad); ?>"
    data-triple="<?php echo esc_attr((string) $triple); ?>"
    data-makkah-m="<?php echo esc_attr((string) $mm); ?>"
    data-madinah-m="<?php echo esc_attr((string) $dm); ?>"
    data-stars="<?php echo esc_attr((string) ($ms + $ds)); ?>"
    data-ms="<?php echo esc_attr((string) $ms); ?>"
    data-ds="<?php echo esc_attr((string) $ds); ?>"
    data-mh="<?php echo esc_attr($mh); ?>"
    data-dh="<?php echo esc_attr($dh); ?>"
    data-walk-m="<?php echo esc_attr($mm > 0 ? Catalog::walk($mm) : ''); ?>"
    data-walk-d="<?php echo esc_attr($dm > 0 ? Catalog::walk($dm) : ''); ?>"
    data-url="<?php echo esc_url($href); ?>"
    data-month="<?php echo esc_attr((string) ($package['departure'] ?? '')); ?>"
    data-ramadan="<?php echo !empty($package['is_ramadan']) ? '1' : '0'; ?>"
>
    <label class="cmp">
        <input type="checkbox" data-pk-cmp value="<?php echo esc_attr($id); ?>">
        <?php echo esc_html(I18n::t('Compare')); ?>
    </label>
    <div class="cover">
        <svg class="mini-sky" viewBox="0 0 300 70" preserveAspectRatio="xMaxYMax meet" aria-hidden="true"><g fill="#fff"><rect x="150" y="38" width="22" height="26"/><rect x="146" y="44" width="30" height="4" fill="#f3d48d"/><rect x="100" y="10" width="5" height="58"/><path d="M99 10l3.5-9 3.5 9z"/><rect x="215" y="10" width="5" height="58"/><path d="M214 10l3.5-9 3.5 9z"/><rect x="60" y="56" width="200" height="14"/><path d="M250 56c0-16 9-24 20-26 11 2 20 10 20 26z"/></g></svg>
        <div class="top">
            <div class="days">
                <?php if ($days > 0) : ?>
                    <strong><?php echo esc_html((string) $days); ?></strong><span><?php echo esc_html(I18n::t('days')); ?></span>
                <?php endif; ?>
            </div>
            <?php if ($badge === 'low') : ?>
                <span class="ribbon"><?php echo esc_html(I18n::t('★ Lowest price')); ?></span>
            <?php elseif ($badge === 'near') : ?>
                <span class="ribbon near"><?php echo esc_html(I18n::t('🕋 Closest to Haram')); ?></span>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($draft && current_user_can('manage_options')) : ?>
                <span class="draft-pill"><?php echo esc_html(I18n::t('Draft — only you can see this')); ?></span>
            <?php endif; ?>
            <?php if ($kicker !== '') : ?>
                <div class="kicker"><?php echo esc_html($kicker); ?></div>
            <?php endif; ?>
            <?php if ($depart !== '') : ?>
                <div class="depart"><?php echo esc_html($depart); ?></div>
            <?php endif; ?>
            <?php if ($nm > 0 || $nd > 0) : ?>
                <div class="nights">
                    <?php if ($nm > 0) : ?>
                        <span><?php echo esc_html(sprintf(I18n::t('🕋 %d nights Makkah'), $nm)); ?></span>
                    <?php endif; ?>
                    <?php if ($nd > 0) : ?>
                        <span><?php echo esc_html(sprintf(I18n::t('🕌 %d nights Madinah'), $nd)); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="body">
        <h3><a href="<?php echo esc_url($href); ?>"><?php echo esc_html($title); ?></a></h3>
        <?php if ($route !== '') : ?>
            <div class="route"><svg class="i sm"><use href="#i-route"/></svg><?php echo esc_html($route); ?></div>
        <?php endif; ?>
        <?php if ($mh !== '') : ?>
            <div class="hotel">
                <span class="ic">MK</span>
                <span class="nm" title="<?php echo esc_attr($mh); ?>"><?php echo esc_html($mh); ?></span>
                <?php if ($ms > 0) : ?>
                    <span class="st" aria-label="<?php echo esc_attr((string) $ms); ?>"><?php echo esc_html(str_repeat('★', $ms)); ?></span>
                <?php endif; ?>
                <?php if ($mm > 0) : ?>
                    <div class="dist"><div class="m"><i style="<?php echo esc_attr('width:' . Catalog::meter_width($mm) . '%'); ?>"></i></div><b><?php echo esc_html((string) $mm); ?> m</b> · <?php echo esc_html(Catalog::walk($mm)); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($dh !== '') : ?>
            <div class="hotel">
                <span class="ic">MD</span>
                <span class="nm" title="<?php echo esc_attr($dh); ?>"><?php echo esc_html($dh); ?></span>
                <?php if ($ds > 0) : ?>
                    <span class="st" aria-label="<?php echo esc_attr((string) $ds); ?>"><?php echo esc_html(str_repeat('★', $ds)); ?></span>
                <?php endif; ?>
                <?php if ($dm > 0) : ?>
                    <div class="dist"><div class="m"><i style="<?php echo esc_attr('width:' . Catalog::meter_width($dm) . '%'); ?>"></i></div><b><?php echo esc_html((string) $dm); ?> m</b> · <?php echo esc_html(Catalog::walk($dm)); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($shown !== []) : ?>
            <div class="incl">
                <?php foreach ($shown as $item) : ?>
                    <span><svg class="i sm"><use href="#i-<?php echo esc_attr(Catalog::inclusion_icon($item)); ?>"/></svg><?php echo esc_html($item); ?></span>
                <?php endforeach; ?>
                <?php if ($more > 0) : ?>
                    <span><?php echo esc_html(sprintf(I18n::t('+%d more'), $more)); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="foot">
        <div class="price">
            <small><?php echo esc_html(I18n::t('From, quad share')); ?></small>
            <b><?php echo esc_html(Catalog::money_or_contact($quad)); ?></b>
            <?php if ($triple > 0) : ?>
                <em><?php echo esc_html(sprintf(I18n::t('Triple %s'), Format::money($triple))); ?></em>
            <?php endif; ?>
        </div>
        <div class="actions">
            <?php if ($wa !== '') : ?>
                <a class="icon-btn wa" href="<?php echo esc_url($wa); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr(I18n::t('Ask on WhatsApp')); ?>">
                    <svg class="i"><use href="#i-wa"/></svg>
                </a>
            <?php endif; ?>
            <a class="btn btn-primary" href="<?php echo esc_url($href); ?>"><?php echo esc_html(I18n::t('Details')); ?> <svg class="i sm"><use href="#i-arrow"/></svg></a>
        </div>
    </div>
</article>
