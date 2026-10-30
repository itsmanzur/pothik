<?php
/**
 * Package page markup.
 *
 * @var array<string, mixed> $package
 * @var string $result
 * @var string $seat_line
 * @var bool $is_preview
 *
 * @package Pothik
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

declare(strict_types=1);

use Pothik\Format;
use Pothik\Front\PackagePage;
use Pothik\I18n;
use Pothik\InquiryStore;
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
$contact = PackageStore::contact($package);
$phone = $contact['phone'];
$phone_href = $contact['phone_href'];
$page_title = I18n::field($package, 'title');
$wa_href = Format::whatsapp_href(
    $contact['whatsapp'],
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
<?php if (!empty($is_preview)) : ?>
    <div style="background:#fff3cd;color:#856404;padding:12px 20px;text-align:center;font-weight:600;border-bottom:1px solid #ffeeba;">
        <?php echo esc_html(I18n::t('Draft Preview — Not published to the public.')); ?>
    </div>
<?php endif; ?>
<article class="pk">
    <header class="pk-bar" id="top">
        <a class="pk-brand" href="<?php echo esc_url($many ? $catalog_url : '#top'); ?>">
            <?php if ($contact['logo_url'] !== '') : ?>
                <img src="<?php echo esc_url($contact['logo_url']); ?>" alt="<?php echo esc_attr($contact['agency']); ?>">
            <?php else : ?>
                <span>Pothik</span>
            <?php endif; ?>
            <small><?php echo esc_html($contact['agency']); ?></small>
        </a>
        <div class="pk-bar-actions">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo I18n::toggle();
            ?>
            <?php if ($share_href !== '') : ?>
                <a href="<?php echo esc_url($share_href); ?>" target="_blank" rel="noopener"><?php echo esc_html(I18n::t('Send this page')); ?></a>
            <?php endif; ?>
            <?php if ($phone_href !== '') : ?>
                <a href="<?php echo esc_url($phone_href); ?>"><?php echo esc_html($phone); ?></a>
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
                        $hotel_name = I18n::field($package, $hotel[1]);
                        if (trim($hotel_name) === '') {
                            continue;
                        }
                        $meters = (int) ($package[$hotel[4]] ?? 0);
                        ?>
                        <section>
                            <p><?php echo esc_html(I18n::t($hotel[0])); ?></p>
                            <h2><?php echo esc_html($hotel_name); ?></h2>
                            <span><?php echo esc_html(I18n::field($package, $hotel[2])); ?> · <?php echo esc_html(I18n::field($package, $hotel[3])); ?></span>
                            <?php if ($meters > 0) : ?>
                                <strong><?php echo esc_html((string) $meters); ?> m</strong>
                                <em><?php echo esc_html(sprintf(I18n::t('Walking from %s'), I18n::t($hotel[5]))); ?></em>
                            <?php endif; ?>
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
                        $amount = (int) ($prices[$key] ?? 0);
                        if ($amount <= 0 && $key !== 'quad') {
                            continue;
                        }
                        $row_class = [];
                        if ($key === 'quad') {
                            $row_class[] = 'is-lead';
                        }
                        if (isset($pickable[$key])) {
                            $row_class[] = 'is-pick';
                        }
                        $price_text = $amount > 0 ? Format::money($amount) : I18n::t('Contact the office');
                        ?>
                        <tr data-share="<?php echo esc_attr($key); ?>"<?php echo $row_class !== [] ? ' class="' . esc_attr(implode(' ', $row_class)) . '"' : ''; ?><?php echo isset($pickable[$key]) ? ' tabindex="0" role="button" aria-pressed="' . ($key === 'quad' ? 'true' : 'false') . '"' : ''; ?>>
                            <th>
                                <?php echo esc_html(I18n::t($meta[0])); ?>
                                <small><?php echo esc_html(I18n::t($meta[1])); ?></small>
                            </th>
                            <td><?php echo esc_html($price_text); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
                $quad_amount = (int) ($prices['quad'] ?? 0);
                $quad_line = $quad_amount > 0 ? Format::money($quad_amount) : I18n::t('Contact the office');
                ?>
                <p class="pk-estimate">
                    <strong data-pk-estimate-line><?php echo esc_html('1 ' . I18n::t('Adults') . ' · ' . I18n::choice('share', 'quad') . ' · ' . $quad_line); ?></strong>
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
                <?php
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $ptk_token = isset($_GET['ptk']) ? sanitize_key((string) wp_unslash($_GET['ptk'])) : '';
                $sent_info = [];
                if ($ptk_token !== '') {
                    $saved_sent = get_transient('pothik_sent_' . $ptk_token);
                    if (is_array($saved_sent)) {
                        $sent_info = $saved_sent;
                    }
                }
                $sent_name = (string) ($sent_info['name'] ?? '');
                $sent_pkg = (string) ($sent_info['package_title'] ?? $page_title);
                $sent_share = (string) ($sent_info['share'] ?? 'quad');
                $sent_adults = (int) ($sent_info['adults'] ?? 1);
                $sent_children = (int) ($sent_info['children'] ?? 0);
                $sent_infants = (int) ($sent_info['infants'] ?? 0);

                if (I18n::is_bn()) {
                    $wa_cta_body = sprintf(
                        "আসসালামু আলাইকুম, আমি %s প্যাকেজের জন্য ইনকোয়ারি জমা দিয়েছি।\nনাম: %s\nরুম শেয়ার: %s\nযাত্রী: %d জন প্রাপ্তবয়স্ক, %d জন শিশু, %d জন নবজাতক।",
                        $sent_pkg !== '' ? $sent_pkg : 'উমরা',
                        $sent_name !== '' ? $sent_name : 'যাত্রী',
                        I18n::choice('share', $sent_share),
                        $sent_adults,
                        $sent_children,
                        $sent_infants
                    );
                } else {
                    $wa_cta_body = sprintf(
                        "Salam, I submitted an inquiry for %s.\nName: %s\nShare: %s\nPilgrims: %d adult(s), %d child(ren), %d infant(s).",
                        $sent_pkg !== '' ? $sent_pkg : 'Umrah',
                        $sent_name !== '' ? $sent_name : 'Visitor',
                        I18n::choice('share', $sent_share),
                        $sent_adults,
                        $sent_children,
                        $sent_infants
                    );
                }
                $wa_cta_href = Format::whatsapp_href($contact['whatsapp'], $wa_cta_body);
                ?>
                <div class="pk-notice" role="status">
                    <strong><?php echo esc_html(I18n::t('Received.')); ?></strong>
                    <span><?php echo esc_html(I18n::t('The office will contact this number.')); ?></span>
                    <?php if ($wa_cta_href !== '') : ?>
                        <p style="margin-top:14px;margin-bottom:0;">
                            <a class="pk-btn pk-btn-wa" href="<?php echo esc_url($wa_cta_href); ?>" target="_blank" rel="noopener">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:6px;"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                <?php echo esc_html(I18n::t('Chat with the office on WhatsApp')); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <?php
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $ptk_token = isset($_GET['ptk']) ? sanitize_key((string) wp_unslash($_GET['ptk'])) : '';
                $prefill = [];
                if ($ptk_token !== '') {
                    $saved = get_transient('pothik_form_' . $ptk_token);
                    if (is_array($saved)) {
                        $prefill = $saved;
                    }
                }
                ?>
                <?php if ($result === 'missing') : ?>
                    <p class="pk-alert" role="alert"><?php echo esc_html(I18n::t('Name and phone are both required.')); ?></p>
                <?php elseif ($result === 'limit') : ?>
                    <p class="pk-alert" role="alert"><?php echo esc_html(I18n::t('Please try again shortly.')); ?></p>
                <?php endif; ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="pothik_inquiry">
                    <input type="hidden" name="pothik_trap" value="<?php echo esc_attr(InquiryStore::generate_time_trap()); ?>">
                    <input type="hidden" name="package_id" value="<?php echo esc_attr((string) ($package['id'] ?? '')); ?>">
                    <label class="pk-trap" aria-hidden="true">Website<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label>
                    <div class="pk-form-grid">
                        <label><?php echo esc_html(I18n::t('Name')); ?><input name="name" required autocomplete="name" value="<?php echo esc_attr((string) ($prefill['name'] ?? '')); ?>"></label>
                        <label><?php echo esc_html(I18n::t('Phone')); ?><input name="phone" required autocomplete="tel" value="<?php echo esc_attr((string) ($prefill['phone'] ?? '')); ?>"></label>
                        <label><?php echo esc_html(I18n::t('WhatsApp')); ?><input name="whatsapp" autocomplete="tel" value="<?php echo esc_attr((string) ($prefill['whatsapp'] ?? '')); ?>"></label>
                        <label><?php echo esc_html(I18n::t('City')); ?><input name="city" placeholder="<?php echo esc_attr(I18n::t('Dhaka')); ?>" value="<?php echo esc_attr((string) ($prefill['city'] ?? '')); ?>"></label>
                        <label><?php echo esc_html(I18n::t('Share')); ?>
                            <select name="share">
                                <?php
                                $selected_share = (string) ($prefill['share'] ?? 'quad');
                                foreach (['quad', 'triple', 'double', 'single'] as $share) :
                                    ?>
                                    <option value="<?php echo esc_attr($share); ?>" <?php selected($selected_share, $share); ?>><?php echo esc_html(I18n::choice('share', $share)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label><?php echo esc_html(I18n::t('Type')); ?>
                            <select name="trip">
                                <?php $selected_trip = (string) ($prefill['trip'] ?? 'group'); ?>
                                <option value="group" <?php selected($selected_trip, 'group'); ?>><?php echo esc_html(I18n::choice('trip', 'group')); ?></option>
                                <option value="private" <?php selected($selected_trip, 'private'); ?>><?php echo esc_html(I18n::choice('trip', 'private')); ?></option>
                            </select>
                        </label>
                        <label><?php echo esc_html(I18n::t('Adults')); ?><input name="adults" type="number" min="1" max="30" value="<?php echo esc_attr((string) ($prefill['adults'] ?? '1')); ?>"></label>
                        <label><?php echo esc_html(I18n::t('Children')); ?><input name="children" type="number" min="0" max="20" value="<?php echo esc_attr((string) ($prefill['children'] ?? '0')); ?>"></label>
                        <label><?php echo esc_html(I18n::t('Infants')); ?><input name="infants" type="number" min="0" max="10" value="<?php echo esc_attr((string) ($prefill['infants'] ?? '0')); ?>"></label>
                        <label class="pk-span"><?php echo esc_html(I18n::t('Note')); ?><textarea name="note" rows="3"><?php echo esc_textarea((string) ($prefill['note'] ?? '')); ?></textarea></label>
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
