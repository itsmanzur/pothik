<?php
/**
 * All packages page.
 *
 * @var array<int, array<string, mixed>> $packages
 * @var array{hero: bool, finder: bool, columns: int, limit: int} $catalog_opts
 * @var bool $show_finder
 * @var array{min: int, max: int} $budget
 *
 * @package Pothik
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

declare(strict_types=1);

use Pothik\Format;
use Pothik\Front\Catalog;
use Pothik\Front\PackagePage;
use Pothik\I18n;
use Pothik\PackageStore;

if (!defined('ABSPATH')) {
    exit;
}

$contact = PackageStore::contact([]);
if ($contact['agency'] === '' && isset($packages[0]) && is_array($packages[0])) {
    $contact = PackageStore::contact($packages[0]);
}
$catalog_url = PackagePage::url();
$wa_general = Format::whatsapp_href($contact['whatsapp'], I18n::t('I want to ask about Umrah.'));
$hero = !empty($catalog_opts['hero']);
$columns = (int) ($catalog_opts['columns'] ?? 3) === 2 ? 2 : 3;
$badges = Catalog::badges($packages);
$chips = Catalog::months($packages);
$season = Catalog::season();
$makkah_time = Catalog::makkah_time();
$heading_html = Catalog::heading_html();
$trust = Catalog::trust($contact, count(array_filter(
    $packages,
    static fn (array $row): bool => ($row['status'] ?? 'draft') === 'published'
)));
$steps = Catalog::steps();
$count = count($packages);
$count_label = sprintf(I18n::t($count === 1 ? '%d package' : '%d packages'), $count);

$schema_items = [];
$position = 1;
foreach ($packages as $package) {
    if (($package['status'] ?? 'draft') !== 'published') {
        continue;
    }
    $name = I18n::field($package, 'title');
    if ($name === '') {
        continue;
    }
    $prices = is_array($package['prices'] ?? null) ? $package['prices'] : [];
    $item = [
        '@type' => 'TouristTrip',
        'name' => $name,
        'url' => PackagePage::url((string) ($package['id'] ?? '')),
        'description' => I18n::field($package, 'route'),
    ];
    $quad = (int) ($prices['quad'] ?? 0);
    if ($quad > 0) {
        $item['offers'] = [
            '@type' => 'Offer',
            'price' => $quad,
            'priceCurrency' => 'BDT',
        ];
    }
    $schema_items[] = [
        '@type' => 'ListItem',
        'position' => $position,
        'item' => $item,
    ];
    $position++;
}
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'itemListElement' => $schema_items,
];
?>
<article class="pk pk-all<?php echo $columns === 2 ? ' cols-2' : ''; ?><?php echo $hero ? '' : ' is-embed'; ?>" id="top" data-pk-catalog>
    <?php include __DIR__ . '/parts/sprite.php'; ?>
    <?php if ($hero) : ?>
        <?php include __DIR__ . '/parts/topbar.php'; ?>
        <?php include __DIR__ . '/parts/hero.php'; ?>
    <?php endif; ?>

    <main class="wrap" id="packages">
        <?php if ($show_finder && $budget['max'] > 0) : ?>
            <form class="finder" data-pk-finder action="" method="get" aria-label="<?php echo esc_attr(I18n::t('Find a package')); ?>">
                <label class="f-field">
                    <span><?php echo esc_html(I18n::t('Search')); ?></span>
                    <input type="search" data-pk-q placeholder="<?php echo esc_attr(I18n::t('Package or hotel name')); ?>">
                </label>
                <div class="f-field">
                    <span><?php echo esc_html(I18n::t('Days')); ?></span>
                    <div class="segs" data-pk-days>
                        <button type="button" class="on" data-v=""><?php echo esc_html(I18n::t('Any')); ?></button>
                        <button type="button" data-v="s"><?php echo esc_html(I18n::t('≤10')); ?></button>
                        <button type="button" data-v="m"><?php echo esc_html(I18n::t('11–15')); ?></button>
                        <button type="button" data-v="l"><?php echo esc_html(I18n::t('16+')); ?></button>
                    </div>
                </div>
                <label class="f-field">
                    <span><?php echo esc_html(I18n::t('Budget per person')); ?> <b data-pk-bud-out><?php echo esc_html(I18n::t('Any')); ?></b></span>
                    <input type="range" data-pk-bud min="<?php echo esc_attr((string) $budget['min']); ?>" max="<?php echo esc_attr((string) $budget['max']); ?>" step="5000" value="<?php echo esc_attr((string) $budget['max']); ?>">
                </label>
                <label class="f-field">
                    <span><?php echo esc_html(I18n::t('Sort by')); ?></span>
                    <select data-pk-sort>
                        <option value="price"><?php echo esc_html(I18n::t('Lowest price')); ?></option>
                        <option value="near"><?php echo esc_html(I18n::t('Closest to the Haram')); ?></option>
                        <option value="days"><?php echo esc_html(I18n::t('Fewest days')); ?></option>
                        <option value="stars"><?php echo esc_html(I18n::t('Best hotels')); ?></option>
                    </select>
                </label>
                <button type="button" class="btn btn-primary go" data-pk-reset><?php echo esc_html(I18n::t('Reset')); ?></button>
            </form>
        <?php endif; ?>

        <div class="results-head">
            <div>
                <h2 id="pk-count" data-pk-count aria-live="polite"><?php echo esc_html($count_label); ?></h2>
                <p><?php echo esc_html(I18n::t('Prices are per person in Bangladeshi taka, quad share unless noted.')); ?></p>
            </div>
            <?php if ($chips !== []) : ?>
                <div class="chips" data-pk-month>
                    <button type="button" class="chip on" data-v=""><?php echo esc_html(I18n::t('All months')); ?></button>
                    <?php foreach ($chips as $chip) : ?>
                        <button type="button" class="chip" data-v="<?php echo esc_attr($chip['value']); ?>"><?php echo esc_html($chip['label']); ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid" data-pk-grid>
            <?php if ($packages === []) : ?>
                <div class="empty">
                    <b><?php echo esc_html(I18n::t('New packages are coming soon')); ?></b>
                    <?php if ($wa_general !== '') : ?>
                        <div class="empty-actions">
                            <a class="btn btn-wa btn-sm" href="<?php echo esc_url($wa_general); ?>" target="_blank" rel="noopener"><svg class="i sm"><use href="#i-wa"/></svg><?php echo esc_html(I18n::t('Ask on WhatsApp')); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <?php foreach ($packages as $card_index => $package) : ?>
                    <?php
                    $badge = $badges[(string) ($package['id'] ?? '')] ?? '';
                    include __DIR__ . '/parts/package-card.php';
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <template data-pk-empty>
            <div class="empty">
                <b><?php echo esc_html(I18n::t('No package matches')); ?></b>
                <?php echo esc_html(I18n::t('Try a higher budget or fewer filters')); ?>
                <div class="empty-actions">
                    <button type="button" class="btn btn-ghost btn-sm" data-pk-reset><?php echo esc_html(I18n::t('Reset')); ?></button>
                    <?php if ($wa_general !== '') : ?>
                        <a class="btn btn-wa btn-sm" href="<?php echo esc_url($wa_general); ?>" target="_blank" rel="noopener"><svg class="i sm"><use href="#i-wa"/></svg><?php echo esc_html(I18n::t('Ask on WhatsApp')); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </template>

        <?php if ($hero) : ?>
            <?php include __DIR__ . '/parts/steps.php'; ?>
            <?php include __DIR__ . '/parts/cta.php'; ?>
        <?php endif; ?>
    </main>

    <?php if ($hero) : ?>
        <?php include __DIR__ . '/parts/footer.php'; ?>
    <?php endif; ?>

    <div class="tray" data-pk-tray role="region" aria-label="<?php echo esc_attr(I18n::t('Compare')); ?>">
        <svg class="i"><use href="#i-cmp"/></svg>
        <div class="t" data-pk-tray-list></div>
        <button type="button" class="btn btn-gold btn-sm" data-pk-cmp-open><?php echo esc_html(I18n::t('Pick 2–3')); ?></button>
        <button type="button" class="icon-btn tray-clear" data-pk-cmp-clear aria-label="<?php echo esc_attr(I18n::t('Clear')); ?>"><svg class="i sm"><use href="#i-x"/></svg></button>
    </div>
    <div class="modal-bg" data-pk-modal hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr(I18n::t('Compare packages')); ?>">
            <header>
                <h3><?php echo esc_html(I18n::t('Compare packages')); ?></h3>
                <button type="button" class="icon-btn" data-pk-cmp-close aria-label="<?php echo esc_attr(I18n::t('Close')); ?>"><svg class="i"><use href="#i-x"/></svg></button>
            </header>
            <div class="modal-scroll"><table class="ctable" data-pk-table></table></div>
        </div>
    </div>
    <div class="pk-toast" data-pk-toast hidden role="status"></div>
    <?php if ($schema_items !== []) : ?>
        <script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php endif; ?>
</article>
