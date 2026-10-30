<?php
/**
 * Standalone package page, outside the theme canvas.
 *
 * @package Pothik
 */

declare(strict_types=1);

use Pothik\Front\PackagePage;
use Pothik\I18n;

if (!defined('ABSPATH')) {
    exit;
}

?><!DOCTYPE html>
<html lang="<?php echo esc_attr(I18n::lang() === 'bn' ? 'bn' : 'en'); ?>">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('pothik-front'); ?>>
<?php wp_body_open(); ?>
<?php PackagePage::render(); ?>
<?php wp_footer(); ?>
</body>
</html>
