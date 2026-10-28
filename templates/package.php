<?php
/**
 * Package page markup.
 *
 * @var array<string, mixed> $package
 * @var string $result
 * @var string $seat_line
 *
 * @package Pothik
 */

declare(strict_types=1);

use Pothik\Format;
use Pothik\Front\PackagePage;
use Pothik\I18n;
use Pothik\PackageStore;

if (!defined('ABSPATH')) {
    exit;
}

$prices = is_array($package['prices'] ?? null) ? $package['prices'] : [];
$price_rows = [
    'quad' => ['Quad', 'Four sharing a room'],
    'triple' => ['Triple', 'Three sharing a room'],
    'double' => ['Double', 'Two sharing a room'],
    'single' => ['Single', 'One person, one room'],
    'child_bed' => ['Child with bed', 'Agency age band'],
    'child_nobed' => ['Child without bed', 'Agency age band'],
    'infant' => ['Infant', 'On a lap'],
];
$phone_href = Format::phone_href((string) ($package['phone'] ?? ''));
$page_title = I18n::field($package, 'title');
$wa_href = Format::whatsapp_href(
    (string) ($package['whatsapp'] ?? ''),
    sprintf(I18n::t('I want to ask about %s.'), $page_title !== '' ? $page_title : 'Umrah')
);
$page_url = PackagePage::url((string) ($package['id'] ?? ''));
$catalog_url = PackagePage::url();
$many = count(PackageStore::all()) > 1;
$share_href = $page_url === '' ? '' : 'https://wa.me/?text=' . rawurlencode(trim($page_title . "\n" . $page_url));
$price_map = [];
foreach (array_keys($price_rows) as $price_key) {
    $price_map[$price_key] = (int) ($prices[$price_key] ?? 0);
}
$pickable = ['quad' => true, 'triple' => true, 'double' => true, 'single' => true];
$facts = array_filter([
    ((int) ($package['days'] ?? 0)) > 0 ? sprintf(I18n::t('%s days'), (string) (int) $package['days']) : '',
    ((int) ($package['nights_makkah'] ?? 0)) > 0 ? sprintf(I18n::t('%s nights in Makkah'), (string) (int) $package['nights_makkah']) : '',
    ((int) ($package['nights_madinah'] ?? 0)) > 0 ? sprintf(I18n::t('%s nights in Madinah'), (string) (int) $package['nights_madinah']) : '',
    I18n::field($package, 'route'),
    I18n::field($package, 'group_note'),
]);
?>
<article class="pk">
    <header class="pk-bar" id="top">
        <a class="pk-brand" href="<?php echo esc_url($many ? $catalog_url : '#top'); ?>">
            <span>Pothik</span>
            <small><?php echo esc_html(I18n::field($package, 'agency')); ?></small>
        </a>
        <div class="pk-bar-actions">
            <?php echo I18n::toggle(); ?>
            <?php if ($share_href !== '') : ?>
                <a href="<?php echo esc_url($share_href); ?>" target="_blank" rel="noopener"><?php echo esc_html(I18n::t('Send this page')); ?></a>
            <?php endif; ?>
            <?php if ($phone_href !== '') : ?>
                <a href="<?php echo esc_url($phone_href); ?>"><?php echo esc_html((string) $package['phone']); ?></a>
            <?php endif; ?>
            <?php if ($wa_href !== '') : ?>
                <a class="pk-bar-wa" href="<?php echo esc_url($wa_href); ?>" target="_blank" rel="noopener"><?php echo esc_html(I18n::t('WhatsApp')); ?></a>
            <?php endif; ?>
        </div>
    </header>

    <div class="pk-wrap">
        <header class="pk-hero">
            <div class="pk-hero-copy">
                <?php if ($many && $catalog_url !== '') : ?>
                    <p class="pk-back"><a href="<?php echo esc_url($catalog_url); ?>"><?php echo esc_html(I18n::t('All packages')); ?></a></p>
                <?php endif; ?>
                <p class="pk-eyebrow"><?php echo esc_html(I18n::field($package, 'kicker')); ?></p>
                <h1><?php echo esc_html($page_title !== '' ? $page_title : I18n::t('Untitled package')); ?></h1>
                <ul class="pk-facts">
                    <?php foreach ($facts as $fact) : ?>
                        <?php if ($fact !== '') : ?>
                            <li><?php echo esc_html($fact); ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <?php if ($seat_line !== '') : ?>
                    <p class="pk-seats"><?php echo esc_html($seat_line); ?></p>
                <?php endif; ?>

                <div class="pk-hotels">
                    <?php
                    $hotels = [
                        ['Makkah', 'makkah_hotel', 'makkah_similar', 'makkah_stars', 'makkah_meters', 'the Haram'],
                        ['Madinah', 'madinah_hotel', 'madinah_similar', 'madinah_stars', 'madinah_meters', 'the Prophet\'s Mosque'],
                    ];
                    foreach ($hotels as $hotel) :
                        ?>
                        <section>
                            <p><?php echo esc_html(I18n::t($hotel[0])); ?></p>
                            <h2><?php echo esc_html(I18n::field($package, $hotel[1])); ?></h2>
                            <span><?php echo esc_html(I18n::field($package, $hotel[2])); ?> · <?php echo esc_html(I18n::field($package, $hotel[3])); ?></span>
                            <strong><?php echo esc_html((string) (int) ($package[$hotel[4]] ?? 0)); ?> m</strong>
                            <em><?php echo esc_html(sprintf(I18n::t('Walking from %s'), I18n::t($hotel[5]))); ?></em>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>

            <aside class="pk-price" id="prices" aria-label="<?php echo esc_attr(I18n::t('Share prices')); ?>" data-pk-prices="<?php echo esc_attr((string) wp_json_encode($price_map)); ?>" data-adults-word="<?php echo esc_attr(I18n::t('Adults')); ?>" data-child-word="<?php echo esc_attr(I18n::t('Children')); ?>" data-infant-word="<?php echo esc_attr(I18n::t('Infants')); ?>" data-seat-note="<?php echo esc_attr(I18n::t('Published share prices. This does not hold a seat.')); ?>" data-office-note="<?php echo esc_attr(I18n::t('Children and infants are priced at the office.')); ?>">
                <p><?php echo esc_html(I18n::t('Share prices')); ?></p>
                <table>
                    <tbody>
                    <?php foreach ($price_rows as $key => $meta) : ?>
                        <?php
                        $row_class = [];
                        if ($key === 'quad') {
                            $row_class[] = 'is-lead';
                        }
                        if (isset($pickable[$key])) {
                            $row_class[] = 'is-pick';
                        }
                        ?>
                        <tr data-share="<?php echo esc_attr($key); ?>"<?php echo $row_class !== [] ? ' class="' . esc_attr(implode(' ', $row_class)) . '"' : ''; ?><?php echo isset($pickable[$key]) ? ' tabindex="0" role="button" aria-pressed="' . ($key === 'quad' ? 'true' : 'false') . '"' : ''; ?>>
                            <th>
                                <?php echo esc_html(I18n::t($meta[0])); ?>
                                <small><?php echo esc_html(I18n::t($meta[1])); ?></small>
                            </th>
                            <td><?php echo esc_html(Format::money((int) ($prices[$key] ?? 0))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="pk-estimate">
                    <strong data-pk-estimate-line><?php echo esc_html('1 ' . I18n::t('Adults') . ' · ' . I18n::choice('share', 'quad') . ' · ' . Format::money((int) ($prices['quad'] ?? 0))); ?></strong>
                    <span data-pk-estimate-extra><?php echo esc_html(I18n::t('Published share prices. This does not hold a seat.')); ?></span>
                </p>
                <?php if (I18n::field($package, 'food_note') !== '') : ?>
                    <p class="pk-food"><?php echo esc_html(I18n::field($package, 'food_note')); ?></p>
                <?php endif; ?>
                <a class="pk-btn" href="#inquiry"><?php echo esc_html(I18n::t('Send an inquiry')); ?></a>
            </aside>
        </header>

        <nav class="pk-page-nav" aria-label="<?php echo esc_attr(I18n::t('Sections')); ?>">
            <a href="#prices"><?php echo esc_html(I18n::t('Share prices')); ?></a>
            <a href="#days"><?php echo esc_html(I18n::t('Day by day')); ?></a>
            <a href="#included"><?php echo esc_html(I18n::t('Included')); ?></a>
            <a href="#inquiry"><?php echo esc_html(I18n::t('Inquiry')); ?></a>
        </nav>

        <section class="pk-split">
            <div id="days">
                <h2><?php echo esc_html(I18n::t('Day by day')); ?></h2>
                <ol class="pk-days">
                    <?php foreach (I18n::lines($package, 'itinerary') as $day) : ?>
                        <li><?php echo esc_html($day); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <div class="pk-lists">
                <div id="included">
                    <h2><?php echo esc_html(I18n::t('Included')); ?></h2>
                    <ul>
                        <?php foreach (I18n::lines($package, 'inclusions') as $item) : ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h2><?php echo esc_html(I18n::t('Not included')); ?></h2>
                    <ul>
                        <?php foreach (I18n::lines($package, 'exclusions') as $item) : ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </section>

        <section class="pk-inquiry" id="inquiry">
            <div>
                <h2><?php echo esc_html(I18n::t('Inquiry')); ?></h2>
                <p><?php echo esc_html(I18n::t('Leave a name and phone. The office replies on WhatsApp. This form does not hold a seat.')); ?></p>
                <?php if (I18n::field($package, 'docs') !== '') : ?>
                    <p class="pk-docs"><?php echo esc_html(sprintf(I18n::t('Papers: %s'), I18n::field($package, 'docs'))); ?></p>
                <?php endif; ?>
            </div>
            <?php if ($result === 'sent') : ?>
                <div class="pk-notice" role="status">
                    <strong><?php echo esc_html(I18n::t('Received.')); ?></strong>
                    <span><?php echo esc_html(I18n::t('The office will contact this number.')); ?></span>
                </div>
            <?php else : ?>
                <?php if ($result === 'missing') : ?>
                    <p class="pk-alert" role="alert"><?php echo esc_html(I18n::t('Name and phone are both required.')); ?></p>
                <?php elseif ($result === 'limit') : ?>
                    <p class="pk-alert" role="alert"><?php echo esc_html(I18n::t('Please try again shortly.')); ?></p>
                <?php endif; ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="pothik_inquiry">
                    <?php wp_nonce_field('pothik_inquiry', 'pothik_inquiry_nonce'); ?>
                    <input type="hidden" name="package_id" value="<?php echo esc_attr((string) ($package['id'] ?? '')); ?>">
                    <label class="pk-trap">Website<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label>
                    <div class="pk-form-grid">
                        <label><?php echo esc_html(I18n::t('Name')); ?><input name="name" required autocomplete="name"></label>
                        <label><?php echo esc_html(I18n::t('Phone')); ?><input name="phone" required autocomplete="tel"></label>
                        <label><?php echo esc_html(I18n::t('WhatsApp')); ?><input name="whatsapp" autocomplete="tel"></label>
                        <label><?php echo esc_html(I18n::t('City')); ?><input name="city" placeholder="<?php echo esc_attr(I18n::t('Dhaka')); ?>"></label>
                        <label><?php echo esc_html(I18n::t('Share')); ?>
                            <select name="share">
                                <?php foreach (['quad', 'triple', 'double', 'single'] as $share) : ?>
                                    <option value="<?php echo esc_attr($share); ?>"><?php echo esc_html(I18n::choice('share', $share)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label><?php echo esc_html(I18n::t('Type')); ?>
                            <select name="trip">
                                <option value="group"><?php echo esc_html(I18n::choice('trip', 'group')); ?></option>
                                <option value="private"><?php echo esc_html(I18n::choice('trip', 'private')); ?></option>
                            </select>
                        </label>
                        <label><?php echo esc_html(I18n::t('Adults')); ?><input name="adults" type="number" min="1" max="30" value="1"></label>
                        <label><?php echo esc_html(I18n::t('Children')); ?><input name="children" type="number" min="0" max="20" value="0"></label>
                        <label><?php echo esc_html(I18n::t('Infants')); ?><input name="infants" type="number" min="0" max="10" value="0"></label>
                        <label class="pk-span"><?php echo esc_html(I18n::t('Note')); ?><textarea name="note" rows="3"></textarea></label>
                    </div>
                    <button class="pk-btn" type="submit"><?php echo esc_html(I18n::t('Send an inquiry')); ?></button>
                </form>
            <?php endif; ?>
        </section>

        <?php if (I18n::field($package, 'cancel') !== '') : ?>
            <footer class="pk-cancel">
                <h2><?php echo esc_html(I18n::t('Payment and cancellation')); ?></h2>
                <p><?php echo esc_html(I18n::field($package, 'cancel')); ?></p>
            </footer>
        <?php endif; ?>
    </div>
</article>
