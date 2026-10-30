<?php
/**
 * How booking works.
 *
 * @var array<int, array{title: string, text: string}> $steps
 *
 * @package Pothik
 */

declare(strict_types=1);

use Pothik\I18n;

if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="steps" id="how">
    <h2><?php echo esc_html(I18n::t('How booking works')); ?></h2>
    <p><?php echo esc_html(I18n::t('No online payment. You talk to the office before anything is fixed.')); ?></p>
    <div class="step-row">
        <?php foreach ($steps as $index => $step) : ?>
            <div class="step">
                <span class="n"><?php echo esc_html((string) ($index + 1)); ?></span>
                <b><?php echo esc_html($step['title']); ?></b>
                <?php if ($step['text'] !== '') : ?>
                    <span><?php echo esc_html($step['text']); ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
