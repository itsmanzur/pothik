<?php
/**
 * HTTP request helpers.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik;

if (!defined('ABSPATH')) {
    exit;
}

final class Request
{
    /**
     * Resolve the client IP address.
     * Respects Cloudflare Connecting IP if request comes through Cloudflare,
     * or any proxy header permitted via 'pothik/trusted_proxy_headers' filter.
     */
    public static function ip(): string
    {
        $trusted_headers = (array) apply_filters('pothik/trusted_proxy_headers', []);
        $remote = isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR']))
            : '';

        $cf_ip = isset($_SERVER['HTTP_CF_CONNECTING_IP'])
            ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_CF_CONNECTING_IP']))
            : '';

        $is_cf = in_array('HTTP_CF_CONNECTING_IP', $trusted_headers, true)
            || in_array('CF-Connecting-IP', $trusted_headers, true)
            || self::is_cloudflare_ip($remote);

        if ($is_cf && $cf_ip !== '' && filter_var($cf_ip, FILTER_VALIDATE_IP)) {
            return $cf_ip;
        }

        foreach ($trusted_headers as $header) {
            $key = strtoupper(str_replace('-', '_', (string) $header));
            if (!str_starts_with($key, 'HTTP_') && $key !== 'REMOTE_ADDR') {
                $key = 'HTTP_' . $key;
            }
            if (isset($_SERVER[$key])) {
                $val = sanitize_text_field(wp_unslash((string) $_SERVER[$key]));
                $first = trim(explode(',', $val)[0]);
                if (filter_var($first, FILTER_VALIDATE_IP)) {
                    return $first;
                }
            }
        }

        if ($remote !== '' && filter_var($remote, FILTER_VALIDATE_IP)) {
            return $remote;
        }

        return '127.0.0.1';
    }

    /**
     * Check if an IPv4 address belongs to Cloudflare IP ranges.
     */
    private static function is_cloudflare_ip(string $ip): bool
    {
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $cf_cidrs = [
            '173.245.48.0/20',
            '103.21.244.0/22',
            '103.22.200.0/22',
            '103.31.4.0/22',
            '141.101.64.0/18',
            '108.162.192.0/18',
            '190.93.240.0/20',
            '188.114.96.0/20',
            '197.234.240.0/22',
            '198.41.128.0/17',
            '162.158.0.0/15',
            '104.16.0.0/13',
            '104.24.0.0/14',
            '172.64.0.0/13',
            '131.0.72.0/22',
        ];

        $ip_long = ip2long($ip);
        if ($ip_long === false) {
            return false;
        }

        foreach ($cf_cidrs as $cidr) {
            [$subnet, $mask] = explode('/', $cidr);
            $subnet_long = ip2long($subnet);
            $mask_long = ~((1 << (32 - (int) $mask)) - 1);
            if (($ip_long & $mask_long) === ($subnet_long & $mask_long)) {
                return true;
            }
        }

        return false;
    }
}
