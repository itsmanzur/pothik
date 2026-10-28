<?php
/**
 * Haramain skyline partial for Desk hero.
 * Two scenes: Makkah al-Mukarramah & Madinah al-Munawwarah.
 *
 * @package Pothik
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="skyline" aria-hidden="true">
  <!-- MAKKAH: mountains, Abraj clock tower, Haram minarets, Kaaba with tawaf ring -->
  <svg id="scMakkah" viewBox="0 0 1200 190" preserveAspectRatio="xMidYMax slice">
    <defs>
      <linearGradient id="kGlow" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#f3d48d" stop-opacity=".0"/><stop offset="1" stop-color="#f3d48d" stop-opacity=".35"/></linearGradient>
      <radialGradient id="kHalo" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#f7dc9a" stop-opacity=".55"/><stop offset="1" stop-color="#f7dc9a" stop-opacity="0"/></radialGradient>
    </defs>
    <path d="M0 190V112c60-26 120-40 190-18s120 4 190-12 110 10 170 2 120-30 200-16 110 22 170 8 120-26 160-14 80 18 120 10V190z" fill="#0c241c" opacity=".9"/>
    <g fill="#081a14">
      <rect x="700" y="96" width="44" height="94"/><rect x="752" y="80" width="40" height="110"/><rect x="1010" y="100" width="46" height="90"/><rect x="1062" y="118" width="40" height="72"/><rect x="952" y="88" width="50" height="102"/>
      <rect x="842" y="70" width="98" height="120"/><rect x="860" y="38" width="62" height="40"/>
      <path d="M872 38l19-14 19 14z"/><rect x="888" y="2" width="6" height="24"/>
    </g>
    <circle cx="891" cy="54" r="12" fill="#f3d48d" class="lamp"/><circle cx="891" cy="54" r="12" fill="none" stroke="#081a14" stroke-width="1.5"/>
    <path d="M891 54v-7M891 54l5 3" stroke="#081a14" stroke-width="1.6" stroke-linecap="round"/>
    <g fill="#f3d48d" class="lamp" opacity=".8"><rect x="758" y="90" width="3" height="3"/><rect x="770" y="104" width="3" height="3"/><rect x="712" y="110" width="3" height="3"/><rect x="964" y="100" width="3" height="3"/><rect x="980" y="120" width="3" height="3"/><rect x="1020" y="114" width="3" height="3"/><rect x="854" y="92" width="3" height="3"/><rect x="920" y="104" width="3" height="3"/></g>
    <g transform="translate(130 0)">
      <!-- Haram arcade + minarets -->
      <g fill="#0a2019">
        <rect x="250" y="150" width="440" height="40"/>
        <g><rect x="262" y="66" width="10" height="90"/><path d="M260 66l7-18 7 18z"/><rect x="259" y="92" width="16" height="4"/></g>
        <g><rect x="330" y="76" width="10" height="80"/><path d="M328 76l7-18 7 18z"/><rect x="327" y="100" width="16" height="4"/></g>
        <g><rect x="600" y="76" width="10" height="80"/><path d="M598 76l7-18 7 18z"/><rect x="597" y="100" width="16" height="4"/></g>
        <g><rect x="668" y="66" width="10" height="90"/><path d="M666 66l7-18 7 18z"/><rect x="665" y="92" width="16" height="4"/></g>
      </g>
      <g fill="#f3d48d" opacity=".55" class="lamp"><path d="M268 166a8 8 0 0 1 16 0v14h-16zM300 166a8 8 0 0 1 16 0v14h-16zM620 166a8 8 0 0 1 16 0v14h-16zM652 166a8 8 0 0 1 16 0v14h-16z"/></g>
      <!-- Kaaba -->
      <ellipse cx="470" cy="176" rx="120" ry="28" fill="url(#kHalo)"/>
      <ellipse class="tawaf" cx="470" cy="178" rx="92" ry="13" fill="none" stroke="#fff" stroke-opacity=".55" stroke-width="2.2" stroke-dasharray="1.5 6" stroke-linecap="round"/>
      <ellipse class="tawaf" cx="470" cy="178" rx="66" ry="9" fill="none" stroke="#f3d48d" stroke-opacity=".6" stroke-width="2" stroke-dasharray="1.5 5" stroke-linecap="round" style="animation-duration:2.2s"/>
      <path d="M444 126l26-6 26 6v52l-26 6-26-6z" fill="#0b0b0b"/>
      <path d="M470 120v64" stroke="#1c1c1c" stroke-width="1"/>
      <path d="M444 136l26-5 26 5v6l-26-5-26 5z" fill="#d4a64a"/>
      <rect x="476" y="152" width="9" height="16" fill="#d4a64a" opacity=".9"/>
    </g>
  </svg>
  <!-- MADINAH: green dome, white minarets, courtyard umbrellas -->
  <svg id="scMadinah" class="off" viewBox="0 0 1200 190" preserveAspectRatio="xMidYMax slice">
    <defs>
      <linearGradient id="dome" x1="0" x2="1"><stop offset="0" stop-color="#2f9e64"/><stop offset="1" stop-color="#1e6b45"/></linearGradient>
      <radialGradient id="mHalo" cx=".5" cy=".6" r=".5"><stop offset="0" stop-color="#bff0d2" stop-opacity=".35"/><stop offset="1" stop-color="#bff0d2" stop-opacity="0"/></radialGradient>
    </defs>
    <path d="M0 190V130c90-18 180-24 300-12s200-14 300-6 200 18 300 8 200-20 300-6V190z" fill="#0c241c" opacity=".9"/>
    <ellipse cx="600" cy="150" rx="210" ry="70" fill="url(#mHalo)"/>
    <g fill="#e9e2d0" opacity=".9">
      <g><rect x="380" y="58" width="12" height="122"/><path d="M378 58l8-22 8 22z"/><rect x="376" y="84" width="20" height="4"/><rect x="376" y="118" width="20" height="4"/></g>
      <g><rect x="470" y="40" width="12" height="140"/><path d="M468 40l8-24 8 24z"/><rect x="466" y="68" width="20" height="4"/><rect x="466" y="104" width="20" height="4"/></g>
      <g><rect x="718" y="40" width="12" height="140"/><path d="M716 40l8-24 8 24z"/><rect x="714" y="68" width="20" height="4"/><rect x="714" y="104" width="20" height="4"/></g>
      <g><rect x="808" y="58" width="12" height="122"/><path d="M806 58l8-22 8 22z"/><rect x="804" y="84" width="20" height="4"/><rect x="804" y="118" width="20" height="4"/></g>
      <rect x="350" y="142" width="500" height="48"/>
    </g>
    <g fill="#f3d48d" opacity=".75" class="lamp"><rect x="384" y="78" width="4" height="4"/><rect x="474" y="60" width="4" height="4"/><rect x="722" y="60" width="4" height="4"/><rect x="812" y="78" width="4" height="4"/></g>
    <g fill="#c8b98f" opacity=".6"><path d="M366 162a9 9 0 0 1 18 0v20h-18zM400 162a9 9 0 0 1 18 0v20h-18zM434 162a9 9 0 0 1 18 0v20h-18zM748 162a9 9 0 0 1 18 0v20h-18zM782 162a9 9 0 0 1 18 0v20h-18zM816 162a9 9 0 0 1 18 0v20h-18z"/></g>
    <!-- the Green Dome -->
    <rect x="560" y="118" width="80" height="30" fill="#1b5e3f"/>
    <path d="M560 120c0-34 18-50 40-54 22 4 40 20 40 54z" fill="url(#dome)"/>
    <path d="M600 66v-18" stroke="#d4a64a" stroke-width="2.5"/><circle cx="600" cy="44" r="4" fill="#d4a64a"/>
    <path d="M604 36a6 6 0 1 1-4-10 5 5 0 1 0 4 10z" fill="#d4a64a"/>
    <rect x="500" y="132" width="40" height="16" fill="#e9e2d0"/><path d="M500 132c0-12 9-18 20-18s20 6 20 18z" fill="#e9e2d0"/>
    <!-- umbrellas -->
    <g class="hide-sm" fill="none" stroke="#f0e9d6" stroke-opacity=".8" stroke-width="2.5" stroke-linejoin="round">
      <path d="M70 172l35-15 35 15M150 172l35-15 35 15M230 172l35-15 35 15M900 172l35-15 35 15M980 172l35-15 35 15M1060 172l35-15 35 15"/>
    </g>
    <g class="hide-sm" stroke="#f0e9d6" stroke-opacity=".6" stroke-width="2"><path d="M105 157v33M185 157v33M265 157v33M935 157v33M1015 157v33M1095 157v33"/></g>
  </svg>
</div>
