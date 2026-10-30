<?php
/**
 * Stable markup for Pothik screens and add-ons.
 *
 * POTHIK_UI_VERSION is 1. Add-ons should check class_exists before calling these.
 *
 * card_open('Flight', 'Dates for this group.', 'plane') / card_close()
 * grid_open(2) / grid_close()
 * field(['name' => 'from', 'label' => 'Origin', 'value' => 'DAC', 'hint' => 'airport code'])
 * select(['name' => 'route', 'label' => 'Direction', 'value' => 'jeddah_first', 'options' => ['jeddah_first' => 'Jeddah first']])
 * textarea(['name' => 'note', 'label' => 'Note', 'value' => ''])
 * seg(['name' => 'type', 'label' => 'Type', 'value' => 'group', 'options' => ['group' => 'Group', 'private' => 'Private']])
 * stepper(['name' => 'seats', 'label' => 'Total seats', 'value' => '40'])
 * stat('28', 'Confirmed seats', 'seat')
 * notice('All dates fit inside the Saudi season.', 'ok')
 * button('Save', ['variant' => 'gold', 'type' => 'submit'])
 * person(['name' => 'leader', 'name_label' => 'Group leader', 'name_value' => 'Kamrul', 'phone' => 'leader_phone', 'phone_label' => 'Phone', 'phone_value' => '01711'])
 * savebar('Unsaved departure', 'Save departure')
 * icon('plane')
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik\Admin;

use Pothik\Format;
use Pothik\I18n;

if (!defined('ABSPATH')) {
    exit;
}

final class UI
{
    public static function icon(string $name): string
    {
        $name = preg_replace('/[^a-z0-9_-]/', '', strtolower($name)) ?? '';
        if ($name === '') {
            return '';
        }

        return '<svg class="i pk-i" aria-hidden="true"><use href="#pk-i-' . esc_attr($name) . '"/></svg>';
    }

    public static function card_open(string $title = '', string $sub = '', string $icon = ''): string
    {
        $html = '<section class="pk-card">';
        if ($title !== '' || $sub !== '') {
            $html .= '<div class="pk-card-head">';
            $html .= '<h3>';
            if ($icon !== '') {
                $html .= '<span class="kpi-ico">' . self::icon($icon) . '</span>';
            }
            $html .= esc_html($title) . '</h3>';
            if ($sub !== '') {
                $html .= '<p class="sub">' . esc_html($sub) . '</p>';
            }
            $html .= '</div>';
        }
        $html .= '<div class="pk-card-pad">';

        return $html;
    }

    public static function card_close(): string
    {
        return '</div></section>';
    }

    public static function grid_open(int $cols = 2): string
    {
        $class = $cols === 3 ? 'pk-fgrid three' : 'pk-fgrid';

        return '<div class="' . esc_attr($class) . '">';
    }

    public static function grid_close(): string
    {
        return '</div>';
    }

    /**
     * @param array<string, mixed> $a
     */
    public static function field(array $a): string
    {
        $type = isset($a['type']) && is_string($a['type']) ? $a['type'] : 'text';
        if (!in_array($type, ['text', 'number', 'date', 'email', 'tel'], true)) {
            $type = 'text';
        }
        $name = isset($a['name']) ? (string) $a['name'] : '';
        $class = 'pk-field' . (!empty($a['span']) ? ' pk-span' : '');
        if (!empty($a['class']) && is_string($a['class'])) {
            $class .= ' ' . $a['class'];
        }
        $html = '<label class="' . esc_attr($class) . '"><span>' . esc_html((string) ($a['label'] ?? ''));
        if (!empty($a['hint']) && is_string($a['hint'])) {
            $html .= ' <em>' . esc_html($a['hint']) . '</em>';
        }
        $html .= '</span><input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string) ($a['value'] ?? '')) . '"';
        if (!empty($a['placeholder']) && is_string($a['placeholder'])) {
            $html .= ' placeholder="' . esc_attr($a['placeholder']) . '"';
        }
        $html .= self::attrs(is_array($a['attrs'] ?? null) ? $a['attrs'] : []);
        $html .= '></label>';

        return $html;
    }

    /**
     * @param array<string, mixed> $a
     */
    public static function select(array $a): string
    {
        $class = 'pk-field' . (!empty($a['span']) ? ' pk-span' : '');
        $html = '<label class="' . esc_attr($class) . '"><span>' . esc_html((string) ($a['label'] ?? '')) . '</span>';
        $html .= '<select name="' . esc_attr((string) ($a['name'] ?? '')) . '"' . self::attrs(is_array($a['attrs'] ?? null) ? $a['attrs'] : []) . '>';
        $current = (string) ($a['value'] ?? '');
        $options = is_array($a['options'] ?? null) ? $a['options'] : [];
        foreach ($options as $value => $label) {
            $html .= '<option value="' . esc_attr((string) $value) . '"' . selected($current, (string) $value, false) . '>' . esc_html((string) $label) . '</option>';
        }
        $html .= '</select></label>';

        return $html;
    }

    /**
     * @param array<string, mixed> $a
     */
    public static function textarea(array $a): string
    {
        $class = 'pk-field' . (!empty($a['span']) ? ' pk-span' : '');
        $html = '<label class="' . esc_attr($class) . '"><span>' . esc_html((string) ($a['label'] ?? '')) . '</span>';
        $html .= '<textarea name="' . esc_attr((string) ($a['name'] ?? '')) . '"' . self::attrs(is_array($a['attrs'] ?? null) ? $a['attrs'] : []) . '>';
        $html .= esc_textarea((string) ($a['value'] ?? ''));
        $html .= '</textarea></label>';

        return $html;
    }

    /**
     * Segmented buttons plus a real select so the form posts without JS.
     *
     * @param array<string, mixed> $a
     */
    public static function seg(array $a): string
    {
        $name = (string) ($a['name'] ?? '');
        $current = (string) ($a['value'] ?? '');
        $options = is_array($a['options'] ?? null) ? $a['options'] : [];
        $html = '<div class="pk-field pk-span"><span>' . esc_html((string) ($a['label'] ?? '')) . '</span>';
        $html .= '<div class="pk-seg seg" data-pk-seg data-bind="' . esc_attr($name) . '">';
        foreach ($options as $value => $label) {
            $on = (string) $value === $current ? ' on' : '';
            $html .= '<button type="button" class="' . esc_attr(trim($on)) . '" data-v="' . esc_attr((string) $value) . '">' . esc_html((string) $label) . '</button>';
        }
        $html .= '</div>';
        $html .= '<select class="pk-seg-native" name="' . esc_attr($name) . '">';
        foreach ($options as $value => $label) {
            $html .= '<option value="' . esc_attr((string) $value) . '"' . selected($current, (string) $value, false) . '>' . esc_html((string) $label) . '</option>';
        }
        $html .= '</select></div>';

        return $html;
    }

    /**
     * @param array<string, mixed> $a
     */
    public static function stepper(array $a): string
    {
        $class = 'pk-field' . (!empty($a['span']) ? ' pk-span' : '');
        $html = '<div class="' . esc_attr($class) . '"><span>' . esc_html((string) ($a['label'] ?? ''));
        if (!empty($a['hint']) && is_string($a['hint'])) {
            $html .= ' <em>' . esc_html($a['hint']) . '</em>';
        }
        $html .= '</span><div class="pk-stepper stepper">';
        $html .= '<button type="button" data-step="-1" aria-label="-">−</button>';
        $html .= '<input type="number" inputmode="numeric" name="' . esc_attr((string) ($a['name'] ?? '')) . '" value="' . esc_attr((string) ($a['value'] ?? '0')) . '"' . self::attrs(is_array($a['attrs'] ?? null) ? $a['attrs'] : []) . '>';
        $html .= '<button type="button" data-step="1" aria-label="+">+</button>';
        $html .= '</div></div>';

        return $html;
    }

    public static function stat(string $value, string $label, string $icon = '', string $tone = ''): string
    {
        $class = 'pk-stat' . ($tone !== '' ? ' is-' . preg_replace('/[^a-z]/', '', $tone) : '');
        $html = '<div class="' . esc_attr($class) . '">';
        if ($icon !== '') {
            $html .= '<span class="kpi-ico">' . self::icon($icon) . '</span>';
        }
        $html .= '<strong>' . esc_html($value) . '</strong><span>' . esc_html($label) . '</span></div>';

        return $html;
    }

    public static function notice(string $text, string $tone = 'warn'): string
    {
        $tone = in_array($tone, ['warn', 'ok', 'info'], true) ? $tone : 'warn';
        $icon = $tone === 'ok' ? 'check' : 'warn';

        return '<div class="pk-notice-row is-' . esc_attr($tone) . '">' . self::icon($icon) . '<span>' . esc_html($text) . '</span></div>';
    }

    /**
     * @param array<string, mixed> $a
     */
    public static function button(string $label, array $a = []): string
    {
        $variant = isset($a['variant']) && is_string($a['variant']) ? $a['variant'] : 'primary';
        if (!in_array($variant, ['primary', 'ghost', 'wa', 'gold', 'danger'], true)) {
            $variant = 'primary';
        }
        $size = isset($a['size']) && $a['size'] === 'lg' ? ' pk-btn-lg' : ' pk-btn-sm';
        $type = isset($a['type']) && is_string($a['type']) ? $a['type'] : 'button';
        $html = '<button type="' . esc_attr($type) . '" class="pk-btn pk-btn-' . esc_attr($variant) . $size . '"';
        $html .= self::attrs(is_array($a['attrs'] ?? null) ? $a['attrs'] : []);
        $html .= '>' . esc_html($label);
        if (!empty($a['kbd']) && is_string($a['kbd'])) {
            $html .= ' <kbd>' . esc_html($a['kbd']) . '</kbd>';
        }
        $html .= '</button>';

        return $html;
    }

    /**
     * @param array<string, mixed> $a
     */
    public static function person(array $a): string
    {
        $name = trim((string) ($a['name_value'] ?? ''));
        $phone = trim((string) ($a['phone_value'] ?? ''));
        $initials = self::initials($name);
        $colors = ['#2d6a4f', '#b4532a', '#8c6a2a', '#3f6b8a', '#6b4f8a'];
        $color = $colors[abs((int) crc32($name !== '' ? $name : 'x')) % count($colors)];
        $tel = Format::phone_href($phone);
        $wa = Format::whatsapp_href($phone, '');
        $hide = $phone === '' ? ' hidden' : '';
        $html = '<div class="pk-person person" data-pk-person>';
        $html .= '<span class="ava" style="' . esc_attr('background:' . $color) . '" data-pk-ava>' . esc_html($initials) . '</span>';
        $html .= '<div class="pin">';
        $html .= self::field([
            'name' => (string) ($a['name'] ?? ''),
            'label' => (string) ($a['name_label'] ?? ''),
            'value' => $name,
        ]);
        $html .= self::field([
            'name' => (string) ($a['phone'] ?? ''),
            'label' => (string) ($a['phone_label'] ?? ''),
            'value' => $phone,
            'type' => 'tel',
            'placeholder' => (string) ($a['placeholder'] ?? ''),
            'attrs' => ['data-pk-phone' => '1'],
        ]);
        $html .= '</div><div class="acts">';
        $html .= '<a class="icon-btn" data-pk-call href="' . esc_url($tel !== '' ? $tel : '#') . '"' . $hide . ' aria-label="' . esc_attr((string) ($a['call_label'] ?? I18n::t('Call'))) . '">' . self::icon('phone') . '</a>';
        $html .= '<a class="icon-btn wa" data-pk-wa href="' . esc_url($wa !== '' ? $wa : '#') . '"' . $hide . ' target="_blank" rel="noopener" aria-label="WhatsApp">' . self::icon('wa') . '</a>';
        $html .= '</div></div>';

        return $html;
    }

    public static function savebar(string $label, string $button): string
    {
        $html = '<div class="pk-savebar savebar" data-pk-savebar>';
        $html .= '<span class="dot"></span><span>' . esc_html($label) . '</span>';
        $html .= '<button type="button" class="pk-btn pk-btn-ghost pk-btn-sm" data-pk-discard>' . esc_html(I18n::t('Discard')) . '</button>';
        $html .= '<button type="submit" class="pk-btn pk-btn-gold pk-btn-sm" data-pk-save>' . esc_html($button) . ' <kbd>Ctrl S</kbd></button>';
        $html .= '</div>';

        return $html;
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $letters = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $letters .= function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1);
            if (function_exists('mb_strlen') ? mb_strlen($letters) >= 2 : strlen($letters) >= 2) {
                break;
            }
        }

        return $letters !== '' ? $letters : '?';
    }

    /**
     * @param array<string, mixed> $attrs
     */
    private static function attrs(array $attrs): string
    {
        $html = '';
        foreach ($attrs as $key => $value) {
            $key = preg_replace('/[^a-zA-Z0-9_:-]/', '', (string) $key) ?? '';
            if ($key === '') {
                continue;
            }
            if ($value === true) {
                $html .= ' ' . esc_attr($key);
                continue;
            }
            if ($value === false || $value === null) {
                continue;
            }
            $html .= ' ' . esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }

        return $html;
    }
}
