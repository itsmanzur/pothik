<?php
/**
 * English is the plugin language. Bangla is a toggle.
 *
 * @package Pothik
 */

declare(strict_types=1);

namespace Pothik;

if (!defined('ABSPATH')) {
    exit;
}

final class I18n
{
    /**
     * @var array<string, string>
     */
    private const BN = [
        '%d nights do not fit inside %d days' => '%d রাত %d দিনের মধ্যে মেলে না',
        '%d nights fit inside %d days' => '%d রাত %d দিনের মধ্যে মিলেছে',
        'Actions' => 'কার্যক্রম',
        'Add day' => 'দিন যোগ করুন',
        'Add email and press Enter' => 'ইমেইল লিখে Enter চাপুন',
        'Advance for Umrah package, quad share…' => 'উমরা প্যাকেজের অগ্রিম, কোয়াড শেয়ার…',
        'Agency' => 'এজেন্সি',
        'Agency name · English' => 'এজেন্সির নাম · ইংরেজি',
        'Agency name · বাংলা' => 'এজেন্সির নাম · বাংলা',
        'Agent commissions' => 'এজেন্ট কমিশন',
        'Amount received' => 'জমা হওয়া টাকা',
        'Any share' => 'যেকোনো শেয়ার',
        'Assalamu alaikum! Thank you for your Umrah inquiry. Our office will call you today.' => 'আসসালামু আলাইকুম! উমরা সম্পর্কে জানার জন্য ধন্যবাদ। আজই আমাদের অফিস থেকে আপনার সাথে যোগাযোগ করা হবে।',
        'Assalamu alaikum,

This is a test notification from your Pothik Umrah plugin.
When a pilgrim submits an inquiry on your site, you will receive an alert like this.

— Pothik Desk' => 'আসসালামু আলাইকুম,

এটি পথিক উমরা প্লাগিনের একটি টেস্ট নোটিফিকেশন।
আপনার সাইটে কোনো যাত্রী আবেদন করলে আপনি এমন একটি বার্তা পাবেন।

— পথিক ডেস্ক',
        'Basics' => 'মৌলিক তথ্য',
        'By room share' => 'রুম শেয়ার অনুযায়ী',
        'Cancellation' => 'বাতিল নীতিমালা',
        'Changes discarded' => 'পরিবর্তন বাতিল করা হয়েছে',
        'Child + bed' => 'শিশু + বিছানা',
        'Child, no bed' => 'শিশু, বিছানা ছাড়া',
        'Clear' => 'মুছুন',
        'Click to toggle' => 'ক্লিক করে পরিবর্তন করুন',
        'Close' => 'বন্ধ করুন',
        'Contact' => 'যোগাযোগ',
        'Contact for this package' => 'এই প্যাকেজের যোগাযোগ',
        'Copied [pothik_catalog]' => '[pothik_catalog] কপি হয়েছে',
        'Copy' => 'কপি',
        'Copy [pothik_catalog]' => '[pothik_catalog] কপি করুন',
        'Copy catalog link' => 'ক্যাটালগ লিংক কপি',
        'Creates the page again with the shortcode' => 'শর্টকোডসহ পেজটি পুনরায় তৈরি করে',
        'Data' => 'ডেটা',
        'Delete everything on uninstall' => 'আনইনস্টল করলে সবকিছু মুছে ফেলুন',
        'Delete selected?' => 'নির্বাচিতগুলো মুছবেন?',
        'Demo' => 'ডেমো',
        'Demo & uninstall' => 'ডেমো ও আনইনস্টল',
        'Demo data' => 'ডেমো ডেটা',
        'Departures board' => 'ফ্লাইট ও কাফেলা বোর্ড',
        'Departures, pilgrim files, rooming lists, installments and the visa board — built for Bangladeshi agencies.' => 'ফ্লাইট শিডিউল, যাত্রী ফাইল, রুম তালিকা, কিস্তির হিসাব ও ভিসা বোর্ড — দেশীয় এজেন্সির উপযোগী।',
        'Direct 1-on-1 agency support' => 'সরাসরি এজেন্সি সাপোর্ট',
        'Discard' => 'বাতিল',
        'Distance to Prophet\'s Mosque' => 'মসজিদে নববীর দূরত্ব',
        'Distance to the Haram' => 'হারামের দূরত্ব',
        'Distance to the Haram sells more than stars — keep it honest.' => 'তারকা মানের চেয়ে হারামের দূরত্বই বেশি গুরুত্বপূর্ণ — সঠিক তথ্য দিন।',
        'Drop your logo here or click to choose' => 'লোগো এখানে ড্রপ করুন বা ক্লিক করে নির্বাচন করুন',
        'Each package gets a dedicated mobile-friendly landing page with pricing, hotels and instant WhatsApp inquiry.' => 'প্রতিটি প্যাকেজে থাকে রেট, হোটেল ও সরাসরি হোয়াটসঅ্যাপের সুবিধা সহ মোবাইল-বান্ধব পাতা।',
        'Edit' => 'এডিট',
        'Editing English. Switch to বাংলা above to fill the Bangla version — empty Bangla fields fall back to English.' => 'ইংরেজি এডিট করছেন। বাংলায় লিখতে উপরে টগল করুন — বাংলায় খালি রাখলে ইংরেজিটি দেখাবে।',
        'Editing বাংলা. Switch to English above to fill the English version — empty Bangla fields fall back to English.' => 'বাংলা এডিট করছেন। ইংরেজিতে লিখতে উপরে টগল করুন — বাংলায় খালি রাখলে ইংরেজিটি দেখাবে।',
        'Email & spam guard' => 'ইমেইল ও স্প্যাম গার্ড',
        'Export' => 'এক্সপোর্ট',
        'Export inquiries CSV' => 'এনকোয়ারি CSV এক্সপোর্ট',
        'Flight & group departures board' => 'ফ্লাইট ও কাফেলা বোর্ড',
        'Flight dates, seats left and group or private quotas at a glance.' => 'ফ্লাইটের তারিখ, অবশিষ্ট আসন এবং গ্রুপ কোটা এক নজরে।',
        'Free version keeps one receipt. Full history is in Pro.' => 'ফ্রি ভার্সনে শেষ রসিদ থাকে। সম্পূর্ণ হিস্ট্রি প্রিমিয়ামে।',
        'Go to' => 'যান',
        'Hajj / Umrah license number' => 'হজ / উমরা লাইসেন্স নম্বর',
        'Here are the full prices and hotel details for your package…' => 'প্যাকেজের হোটেল ও ভাড়ার বিস্তারিত বিবরণ…',
        'Hotel' => 'হোটেল',
        'Hover a bar' => 'বারের উপর কার্সর রাখুন',
        'Inquiries by day' => 'তারিখভিত্তিক এনকোয়ারি',
        'Inquiries, packages, receipt and settings are removed for good when the plugin is deleted.' => 'প্লাগিনটি ডিলিট করলে সব এনকোয়ারি, প্যাকেজ, রসিদ ও সেটিংস চিরতরে মুছে যাবে।',
        'Inquiry details' => 'এনকোয়ারির বিস্তারিত',
        'Inquiry form, WhatsApp, email alerts' => 'এনকোয়ারি ফর্ম, হোয়াটসঅ্যাপ ও ইমেইল নোটিফিকেশন',
        'Installment ledger & bKash matching' => 'কিস্তির খাতা ও bKash ট্রানজেকশন মেলানো',
        'Journey & terms' => 'ভ্রমণসূচি ও শর্তাবলী',
        'Leave a tier at 0 to hide it on the site. The lowest price is shown as "from".' => 'কোনো শেয়ার লুকাতে ০ লিখুন। সর্বনিম্ন মূল্যটি "শুরু" হিসেবে দেখাবে।',
        'Leave empty to use the agency phone and WhatsApp from Settings.' => 'খালি রাখলে সেটিংসের মূল ফোন ও হোয়াটসঅ্যাপ ব্যবহার হবে।',
        'License' => 'লাইসেন্স',
        'Live preview' => 'লাইভ প্রিভিউ',
        'Load demo' => 'ডেমো লোড',
        'Makkah & Madinah bed / rooming lists' => 'মক্কা ও মদিনার রুম বণ্টন তালিকা',
        'Marked replied.' => 'উত্তর সম্পন্ন হিসেবে চিহ্নিত।',
        'Marked waiting.' => 'অপেক্ষমাণ হিসেবে চিহ্নিত।',
        'Max inquiries from one IP per hour' => 'প্রতি ঘণ্টায় এক আইপি থেকে সর্বোচ্চ এনকোয়ারি',
        'More' => 'আরও',
        'Name, phone, logo' => 'নাম, ফোন ও লোগো',
        'New inquiry email' => 'নতুন এনকোয়ারির ইমেইল',
        'No inquiries in this period' => 'এই সময়ে কোনো এনকোয়ারি আসেনি',
        'No packages created yet.' => 'এখনো কোনো প্যাকেজ তৈরি করা হয়নি।',
        'No results found.' => 'কোনো ফলাফল পাওয়া যায়নি।',
        'Nothing matches' => 'কোনো মিল পাওয়া যায়নি',
        'Notifications' => 'নোটিফিকেশন',
        'Office phone' => 'অফিস ফোন',
        'One package' => 'একটি প্যাকেজ',
        'Or similar' => 'বা সমমানের',
        'PNG or SVG, square works best. Opens the Media Library.' => 'PNG বা SVG, চারকোনা সাইজ মানানসই। মিডিয়া লাইব্রেরি খুলবে।',
        'Package landing pages' => 'প্যাকেজ ল্যান্ডিং পেজ',
        'Package page' => 'প্যাকেজ পেজ',
        'Package saved' => 'প্যাকেজ সেভ হয়েছে',
        'Page & shortcodes' => 'পেজ ও শর্টকোড',
        'Page missing or broken?' => 'পেজটি নেই বা কাজ করছে না?',
        'Page missing or not assigned' => 'পেজ নেই বা সেট করা হয়নি',
        'Partial payments, balance due, bKash & Nagad matching.' => 'আংশিক পেমেন্ট, বাকি হিসাব এবং bKash ও Nagad মেলানো।',
        'Passport scans, mahram and family links, visa column per pilgrim.' => 'পাসপোর্ট কপি, মাহরাম ও পারিবারিক সম্পর্ক এবং ভিসা স্ট্যাটাস।',
        'Paste on any page or block' => 'যেকোনো পেজ বা ব্লকে বসিয়ে দিন',
        'Payment channel' => 'পেমেন্ট মাধ্যম',
        'Payment receipts' => 'পেমেন্ট রসিদ',
        'Pick a package to get its code' => 'কোড পেতে প্যাকেজ নির্বাচন করুন',
        'Pilgrim' => 'যাত্রী',
        'Pilgrim name' => 'যাত্রীর নাম',
        'Pilgrim passport & visa tracking' => 'যাত্রীর পাসপোর্ট ও ভিসা ট্র্যাকিং',
        'Pilgrims asked · %d adults · %d children · %d infants' => 'মোট যাত্রী · %d জন বড় · %d জন শিশু · %d জন ইনফ্যান্ট',
        'Please send passport copies to book your seat.' => 'আপনার বুকিং নিশ্চিত করতে পাসপোর্ট কপি পাঠান।',
        'Preview' => 'প্রিভিউ',
        'Prices' => 'মূল্য তালিকা',
        'Prices per person, BDT' => 'জনপ্রতি খরচ, BDT',
        'Public page' => 'পাবলিক পেজ',
        'Quick WhatsApp replies' => 'দ্রুত হোয়াটসঅ্যাপ উত্তর',
        'Received' => 'প্রাপ্তি',
        'Recreate page' => 'পেজ পুনরায় তৈরি করুন',
        'Remove demo' => 'ডেমো সরান',
        'Remove logo' => 'লোগো সরান',
        'Reply rate' => 'উত্তরের হার',
        'Rooming lists' => 'রুম বণ্টন তালিকা',
        'Run the whole Umrah season %s' => 'পুরো উমরা মৌসুম পরিচালনা করুন %s',
        'Sample packages, inquiries and a receipt for trying things out' => 'পরীক্ষা করার জন্য নমুনা প্যাকেজ, এনকোয়ারি ও রসিদ',
        'Scene' => 'দৃশ্য',
        'Search name, phone or city  ( / )' => 'নাম, ফোন বা শহর খুঁজুন  ( / )',
        'Search...' => 'খুঁজুন...',
        'Searching...' => 'অনুসন্ধান চলছে...',
        'Security check failed.' => 'নিরাপত্তা যাচাই ব্যর্থ হয়েছে।',
        'Select' => 'নির্বাচন',
        'Select all' => 'সব নির্বাচন করুন',
        'Send test' => 'টেস্ট পাঠান',
        'Send to' => 'প্রাপক',
        'Separate Makkah and Madinah bed lists, auto-filled by share.' => 'মক্কা ও মদিনার আলাদা বেড লিস্ট, রুম শেয়ার অনুযায়ী তৈরি।',
        'Share your public package catalog so pilgrims can submit inquiries.' => 'প্যাকেজের লিংক শেয়ার করুন যাতে যাত্রীরা যোগাযোগ করতে পারেন।',
        'Show every package' => 'সব প্যাকেজ দেখান',
        'Showing %d–%d of %d' => '%3$dটির মধ্যে %1$d–%2$d দেখানো হচ্ছে',
        'Spam guard' => 'স্প্যাম প্রতিরোধ',
        'Staff move each file through statuses. Issue happens on Masar.' => 'কর্মী ফাইল এগিয়ে নেন। ভিসা ইস্যু হয় সরাসরি Masar-এ।',
        'Stars' => 'তারকা',
        'Still waiting' => 'এখনো অপেক্ষায়',
        'Sub-agent commission reports' => 'সাব-এজেন্ট কমিশন রিপোর্ট',
        'Sub-agent leads and commission reports.' => 'সাব-এজেন্ট লিড ও কমিশন হিসাব।',
        'Test email sent to %s' => '%s-এ টেস্ট ইমেইল পাঠানো হয়েছে',
        'Top cities' => 'শীর্ষ শহরসমূহ',
        'Total days' => 'মোট দিন',
        'Try another name, phone or city.' => 'অন্য কোনো নাম, ফোন বা শহর দিয়ে চেষ্টা করুন।',
        'Type and press Enter to add. Drag to reorder.' => 'টাইপ করে Enter চাপুন। টেনে সাজান।',
        'Umrah package' => 'উমরা প্যাকেজ',
        'Umrah vs Hajj' => 'উমরা বনাম হজ',
        'Undo' => 'পূর্বাবস্থায় আনুন',
        'Unsaved changes' => 'অসংরক্ষিত পরিবর্তন',
        'Unsaved settings' => 'অসংরক্ষিত সেটিংস',
        'Used on packages, receipts and the WhatsApp button' => 'প্যাকেজ, রসিদ ও হোয়াটসঅ্যাপ বাটনে ব্যবহৃত হয়',
        'Used when a pilgrim types 017… without +880' => '+880 ছাড়া নম্বর লিখলে স্বয়ংক্রিয়ভাবে যুক্ত হবে',
        'Visa board with Masar status tracking' => 'Masar স্ট্যাটাস সহ ভিসা বোর্ড',
        'What pilgrims see first on the landing page.' => 'ল্যান্ডিং পেজে যাত্রীরা যা প্রথমেই দেখতে পাবেন।',
        'Yes' => 'হ্যাঁ',
        'You do not have permission to perform this action.' => 'আপনার এই কাজটি করার অনুমতি নেই।',
        'You have unsaved changes. Are you sure you want to leave?' => 'আপনার কিছু অসংরক্ষিত পরিবর্তন আছে। আপনি কি নিশ্চিত বের হতে চান?',
        '[Pothik] Test Notification (%s)' => '[পথিক] টেস্ট নোটিফিকেশন (%s)',
        'ago' => 'আগে',
        'complete' => 'সম্পন্ন',
        'e.g. Personal shopping' => 'যেমন: ব্যক্তিগত কেনাকাটা',
        'e.g. Return air ticket' => 'যেমন: রিটার্ন বিমান টিকিট',
        'from one desk' => 'এক ডেস্ক থেকেই',
        'from the bKash / Nagad SMS' => 'বিকাশ / নগদ এসএমএস থেকে',
        'inquiries' => 'এনকোয়ারি',
        'of inquiries got a reply' => 'এনকোয়ারির উত্তর দেওয়া হয়েছে',
        'per person' => 'জনপ্রতি',
        'per person · shown as "from"' => 'জনপ্রতি · "শুরু" হিসেবে দেখানো',
        'selected' => 'নির্বাচিত',
        'small line above title' => 'শিরোনামের উপরের ছোট লাইন',
        'to the Haram' => 'হারামে',
        'to the Prophet\'s Mosque' => 'মসজিদে নববীতে',
        'updating' => 'আপডেট হচ্ছে',
        'with country code' => 'কান্ট্রি কোড সহ',
        'wp_mail() returned false. Check your mail configuration.' => 'wp_mail() পাঠানো যায়নি। সার্ভারের মেইল কনফিগারেশন চেক করুন।',
        'Pothik Pro' => 'পথিক প্রো',
        'Advanced Umrah & Hajj agency operations management.' => 'উন্নত উমরা ও হজ এজেন্সি পরিচালনা ব্যবস্থাপনা।',
        'Upgrade to Pothik Pro' => 'পথিক প্রো-তে আপগ্রেড করুন',
        'Upgrade to Pro' => 'প্রো-তে আপগ্রেড',
        'Upgrade to Pothik Pro Now' => 'এখনই পথিক প্রো-তে আপগ্রেড করুন',
        'Everything your agency needs to run professional Umrah operations.' => 'আপনার এজেন্সির প্রফেশনাল উমরা কার্যক্রম পরিচালনার জন্য যা কিছু প্রয়োজন।',
        'Feature' => 'ফিচার',
        'Free Version' => 'ফ্রি ভার্সন',
        'Show WP menu' => 'WP মেনু দেখান',
        'WP menu' => 'WP মেনু',
        'WordPress menu hidden — full-screen desk' => 'ওয়ার্ডপ্রেস মেনু আড়াল করা হয়েছে — ফুল-স্ক্রিন ডেস্ক',
        'WordPress menu shown' => 'ওয়ার্ডপ্রেস মেনু প্রদর্শিত হচ্ছে',
        'Toggle full screen (Ctrl+Shift+F)' => 'ফুল স্ক্রিন টগল করুন (Ctrl+Shift+F)',
        'Collapse menu' => 'মেনু ছোট করুন',
        'Overview' => 'ওভারভিউ',
        'Pilgrims' => 'যাত্রী',
        'System' => 'সিস্টেম',
        'Package not found.' => 'প্যাকেজ পাওয়া যায়নি।',
        'Free version is limited to 40 packages. Upgrade to Pothik Pro for unlimited packages.' => 'ফ্রি ভার্সনে সর্বোচ্চ ৪০টি প্যাকেজ রাখা যায়। আনলিমিটেড প্যাকেজের জন্য পথিক প্রো-তে আপগ্রেড করুন।',
        'Export CSV' => 'CSV এক্সপোর্ট',
        'Publish' => 'পাবলিশ',
        'Unpublish' => 'ড্রাফট করুন',
        'Mark waiting' => 'অপেক্ষমাণ রাখুন',
        'Mark replied' => 'উত্তর সম্পন্ন',
        'Draft' => 'ড্রাফট',
        'Published' => 'পাবলিশড',
        'Settings' => 'সেটিংস',
        'Agency details' => 'এজেন্সির বিবরণ',
        'These contact details are used across packages, receipts and public inquiries.' => 'এই যোগাযোগের তথ্য প্যাকেজ, রসিদ ও পাবলিক এনকোয়ারিতে ব্যবহৃত হয়।',
        'Agency name (English)' => 'এজেন্সির নাম (ইংরেজি)',
        'Agency name (Bangla)' => 'এজেন্সির নাম (বাংলা)',
        'License number' => 'লাইসেন্স নম্বর',
        'Logo' => 'লোগো',
        'Select Logo' => 'লোগো নির্বাচন করুন',
        'Use this logo' => 'এই লোগো ব্যবহার করুন',
        'Choose Logo' => 'লোগো বাছুন',
        'Remove' => 'মুছুন',
        'Notifications & System' => 'নোটিফিকেশন ও সিস্টেম',
        'Notification email(s)' => 'নোটিফিকেশন ইমেইল',
        'Default country code' => 'ডিফল্ট কান্ট্রি কোড',
        'Public form rate limit' => 'পাবলিক ফরম রেট লিমিট',
        'Separate multiple emails with commas. Rate limit is per IP per hour.' => 'একাধিক ইমেইল কমা দিয়ে আলাদা করুন। রেট লিমিট প্রতি আইপিতে প্রতি ঘণ্টায়।',
        'Save settings' => 'সেটিংস সেভ',
        'Chat with the office on WhatsApp' => 'অফিসের সাথে হোয়াটসঅ্যাপে কথা বলুন',
        'Language' => 'ভাষা',
        'Desk' => 'ডেস্ক',
        'Package page, inquiries and one receipt live in this plugin.' => 'প্যাকেজ পাতা, এনকোয়ারি ও একটি রসিদ এই প্লাগিনে।',
        'Packages, inquiries and one receipt live in this plugin.' => 'প্যাকেজ, এনকোয়ারি ও একটি রসিদ এই প্লাগিনে।',
        'Inquiries' => 'এনকোয়ারি',
        'Last receipt' => 'শেষ রসিদ',
        'On' => 'চালু',
        'Off' => 'বন্ধ',
        'Open' => 'খোলা',
        'Package' => 'প্যাকেজ',
        'Packages' => 'প্যাকেজ',
        'The page pilgrims see on the site.' => 'সাইটে যাত্রী এই পাতা দেখে।',
        'Each package keeps its own days, hotels and prices.' => 'প্রতিটি প্যাকেজে নিজের দিন, হোটেল ও দাম।',
        'Name, phone, city and room share.' => 'নাম, ফোন, শহর ও শেয়ার।',
        'Receipt' => 'রসিদ',
        'One receipt' => 'একটি রসিদ',
        'Amount, channel and transaction ID.' => 'অঙ্ক, চ্যানেল ও ট্রানজেকশন আইডি।',
        'Departures' => 'ডিপারচার',
        'Departure' => 'ডিপারচার',
        'Date, flight, seats, and group or private.' => 'তারিখ, ফ্লাইট, সিট এবং গ্রুপ না প্রাইভেট।',
        'Pilgrim file' => 'যাত্রী ফাইল',
        'Passport, family link and visa column.' => 'পাসপোর্ট, পরিবারের লিংক ও ভিসার ঘর।',
        'Rooming' => 'রুমিং',
        'Separate Makkah and Madinah bed lists.' => 'মক্কা ও মদিনার আলাদা বিছানার তালিকা।',
        'Installments' => 'কিস্তির খাতা',
        'Balance, who matched it, bKash and Nagad.' => 'বকেয়া, কে মিলিয়েছে, bKash ও Nagad।',
        'Visa board' => 'ভিসা বোর্ড',
        'Staff update the status here. Issue happens on Masar.' => 'স্ট্যাটাস এখানে হাতে বদলায়। ইস্যু হয় Masar পোর্টালে।',
        'Turn on Pothik Pro and this screen opens from that plugin.' => 'Pothik Pro চালু করলে এই ঘর সেই প্লাগিন থেকে খুলবে।',
        'View on site' => 'সাইটে দেখুন',
        'Recent inquiries' => 'সাম্প্রতিক এনকোয়ারি',
        'All inquiries' => 'সব এনকোয়ারি',
        'Search name, phone or city' => 'নাম, ফোন বা শহর খুঁজুন',
        'Nothing matches.' => 'কিছু মেলেনি।',
        'Call' => 'কল',
        'Reply' => 'উত্তর',
        'Delete this inquiry?' => 'এই এনকোয়ারি মুছবেন?',
        'Office reply about your Umrah inquiry.' => 'আপনার উমরা এনকোয়ারি নিয়ে অফিস থেকে বলছি।',
        'Sections' => 'ঘর',
        'Saved.' => 'সেভ হয়েছে।',
        'This screen is what the site shows. Prices are typed by staff.' => 'এই ঘরের লেখাই সাইটের পাতায় ওঠে। দাম হাতে বসানো।',
        'Text fields follow the language toggle. Prices stay in BDT.' => 'লেখার ঘর টগলের ভাষা অনুসরণ করে। দাম BDT-তেই থাকে।',
        'Title' => 'শিরোনাম',
        'Agency name' => 'এজেন্সির নাম',
        'Phone' => 'ফোন',
        'WhatsApp number' => 'হোয়াটসঅ্যাপ নম্বর',
        'Eyebrow' => 'উপরের লাইন',
        'Package name' => 'প্যাকেজের নাম',
        'Days' => 'দিন',
        'Makkah nights' => 'মক্কার রাত',
        'Madinah nights' => 'মদিনার রাত',
        'Flight direction' => 'ফ্লাইটের দিক',
        'Group note' => 'গ্রুপের কথা',
        'Hotels' => 'হোটেল',
        'Makkah hotel' => 'মক্কার হোটেল',
        'Makkah, or similar' => 'মক্কা, সমমান',
        'Makkah stars' => 'মক্কার তারকা',
        'Metres to the Haram' => 'হারাম থেকে মিটার',
        'Madinah hotel' => 'মদিনার হোটেল',
        'Madinah, or similar' => 'মদিনা, সমমান',
        'Madinah stars' => 'মদিনার তারকা',
        'Metres to the Prophet\'s Mosque' => 'নববী থেকে মিটার',
        'Share prices, BDT' => 'শেয়ারের দাম, BDT',
        'Food note' => 'খাবারের নোট',
        'Journey and terms' => 'যাত্রা ও শর্ত',
        'Included, one item per line' => 'যা আছে, এক লাইনে একটি',
        'Not included, one item per line' => 'যা নেই, এক লাইনে একটি',
        'Day by day' => 'দিনভিত্তিক যাত্রা',
        'Documents' => 'কাগজ',
        'Cancellation sentence' => 'বাতিলের বাক্য',
        'Save package' => 'প্যাকেজ সেভ',
        'New package' => 'নতুন প্যাকেজ',
        'Copy package' => 'প্যাকেজ কপি',
        'Package actions' => 'প্যাকেজের কাজ',
        'Delete this package?' => 'এই প্যাকেজ মুছবেন?',
        'All packages' => 'সব প্যাকেজ',
        'No packages yet.' => 'এখনো কোনো প্যাকেজ নেই।',
        'Untitled package' => 'নামহীন প্যাকেজ',
        'Type the days, nights, hotels and prices. The site shows this package as you save it.' => 'দিন, রাত, হোটেল ও দাম যা লিখবেন, সাইটে এই প্যাকেজ সেভাবেই উঠবে।',
        'Choose a package. Each one has its own days and price.' => 'একটি প্যাকেজ বেছে নিন। প্রতিটির দিন ও দাম আলাদা।',
        'Open package' => 'প্যাকেজ খুলুন',
        'Names from the site form. The pilgrim file is in Pro.' => 'সাইটের ফর্ম থেকে আসা নাম। যাত্রী ফাইল Pro-তে।',
        'No inquiries yet.' => 'এখনো কোনো এনকোয়ারি আসেনি।',
        'Time' => 'সময়',
        'Name' => 'নাম',
        'City' => 'শহর',
        'Share' => 'শেয়ার',
        'Pilgrims' => 'যাত্রী',
        'Type' => 'ধরন',
        'Delete' => 'মুছুন',
        'adults' => 'বড়',
        'children' => 'শিশু',
        'infants' => 'ইনফ্যান্ট',
        'One payment proof. The installment ledger is in Pro.' => 'একটি জমার প্রমাণ। কিস্তির খাতা Pro-তে।',
        'Paid by' => 'যার নামে',
        'Amount, taka' => 'অঙ্ক, টাকা',
        'Channel' => 'চ্যানেল',
        'Transaction ID' => 'ট্রানজেকশন আইডি',
        'Date' => 'তারিখ',
        'Note' => 'নোট',
        'Save receipt' => 'রসিদ সেভ',
        'The package page is ready.' => 'প্যাকেজ পাতা প্রস্তুত।',
        'Admin' => 'অ্যাডমিন',
        'Dismiss' => 'বন্ধ করুন',
        'You do not have permission to view this screen.' => 'এই স্ক্রিন দেখার অনুমতি নেই।',
        'Quad' => 'কোয়াড',
        'Triple' => 'ট্রিপল',
        'Double' => 'ডাবল',
        'Single' => 'সিঙ্গেল',
        'Child with bed' => 'শিশু, বিছানা সহ',
        'Child without bed' => 'শিশু, বিছানা ছাড়া',
        'Infant' => 'ইনফ্যান্ট',
        'Four sharing a room' => 'চারজনে এক রুম',
        'Three sharing a room' => 'তিনজনে এক রুম',
        'Two sharing a room' => 'দুজনে এক রুম',
        'One person, one room' => 'একজনে এক রুম',
        'Agency age band' => 'এজেন্সির বয়সসীমা',
        'On a lap' => 'কোলে',
        'Group' => 'গ্রুপ',
        'Private' => 'প্রাইভেট',
        'Bank' => 'ব্যাংক',
        'Cash' => 'ক্যাশ',
        'Male' => 'পুরুষ',
        'Female' => 'নারী',
        'Done' => 'সম্পন্ন',
        'Failed' => 'ফেইল',
        'Not started' => 'শুরু হয়নি',
        'Draft' => 'ড্রাফট',
        'Invoice' => 'ইনভয়েস',
        'Paid' => 'পরিশোধ',
        'Issued' => 'ইস্যু',
        'Refused' => 'রিফিউজ',
        'Embassy visit' => 'এম্বাসি ভিজিট',
        'Group linked' => 'গ্রুপ লিংকড',
        'Share prices' => 'শেয়ারের দাম',
        'Makkah' => 'মক্কা',
        'Madinah' => 'মদিনা',
        'the Haram' => 'হারাম',
        'the Prophet\'s Mosque' => 'নববী',
        'Walking from %s' => '%s থেকে হাঁটা',
        '%s days' => '%s দিন',
        '%s nights in Makkah' => '%s রাত মক্কা',
        '%s nights in Madinah' => '%s রাত মদিনা',
        '%1$d seats confirmed · %2$d total' => '%1$d সিট কনফার্ম · মোট %2$d',
        'Send an inquiry' => 'এনকোয়ারি পাঠান',
        'Included' => 'যা আছে',
        'Not included' => 'যা নেই',
        'Inquiry' => 'এনকোয়ারি',
        'Leave a name and phone. The office replies on WhatsApp. This form does not hold a seat.' => 'নাম ও ফোন দিলে অফিস হোয়াটসঅ্যাপে ফিরে বলে। সিট এই ফর্মে ধরা হয় না।',
        'Papers: %s' => 'কাগজ: %s',
        'Received.' => 'পেয়েছি।',
        'The office will contact this number.' => 'অফিস এই নম্বরে যোগাযোগ করবে।',
        'Name and phone are both required.' => 'নাম ও ফোন দুটোই লাগবে।',
        'Please try again shortly.' => 'একটু পরে আবার পাঠান।',
        'WhatsApp' => 'হোয়াটসঅ্যাপ',
        'Adults' => 'প্রাপ্তবয়স্ক',
        'Children' => 'শিশু',
        'Infants' => 'ইনফ্যান্ট',
        'I want to ask about %s.' => '%s নিয়ে জানতে চাই।',
        'Payment and cancellation' => 'টাকা ও বাতিল',
        'Dhaka' => 'ঢাকা',
        'One group file. This screen does not submit to Masar.' => 'একটি গ্রুপ ফাইল। Masar-এ জমা এই স্ক্রিন থেকে হয় না।',
        'Confirmed seats' => 'কনফার্ম সিট',
        'Total seats' => 'মোট সিট',
        'Program' => 'প্রোগ্রাম',
        'Leave date' => 'ছাড়ার দিন',
        'Return date' => 'ফেরার দিন',
        'Direction' => 'দিক',
        'Origin' => 'উৎস',
        'Airline' => 'এয়ারলাইন',
        'Flight' => 'ফ্লাইট',
        'Saudi company' => 'সৌদি কোম্পানি',
        'Program ID' => 'প্রোগ্রাম আইডি',
        'Group leader' => 'গ্রুপ লিডার',
        'Guide' => 'মুয়াল্লিম',
        'Passport number stays on this desk. The photo file comes in a later version.' => 'পাসপোর্ট নম্বর শুধু এই ডেস্কে। ছবির ফাইল পরের ভার্সনে।',
        'All pilgrims' => 'সব যাত্রী',
        'Gender' => 'লিঙ্গ',
        'Birth' => 'জন্ম',
        'Passport' => 'পাসপোর্ট',
        'Expiry' => 'মেয়াদ',
        'Family' => 'পরিবার',
        'Relation' => 'সম্পর্ক',
        'Visa' => 'ভিসা',
        'Vaccine' => 'টিকা',
        'Biometric' => 'বায়োমেট্রিক',
        'Insurance' => 'বিমা',
        'Travelling alone' => 'আলাদা যাত্রী',
        'A sample of four pilgrims. Mahram is a relationship here. It does not block anyone.' => 'নমুনার চারজন। মাহরাম এখানে সম্পর্ক, কাউকে আটকায় না।',
        'Makkah list and Madinah list are separate. Choose pilgrims from the file.' => 'মক্কার তালিকা ও মদিনার তালিকা আলাদা। যাত্রী ফাইল থেকে বেছে নিন।',
        'Room number %s' => 'রুম নম্বর %s',
        'Room number has not arrived yet' => 'রুম নম্বর এখনো আসেনি',
        'Installment ledger' => 'কিস্তির খাতা',
        'This ledger is the pilgrim\'s BDT. The ticket dollar and the Saudi riyal stay as notes. This version does not recalculate them.' => 'এই খাতা যাত্রীর BDT। টিকিটের ডলার ও সৌদি রিয়াল আলাদা নোট। এই ভার্সনে অটো হিসাব নয়।',
        'Contract' => 'চুক্তি',
        'Balance' => 'বকেয়া',
        'Transaction' => 'ট্রানজেকশন',
        'Staff change the status here. The visa is issued on the Masar portal.' => 'স্ট্যাটাস এখানে হাতে বদলায়। ইস্যু হয় Masar পোর্টালে।',
        'Status' => 'স্ট্যাটাস',
        'Save status' => 'স্ট্যাটাস সেভ',
        'Pothik Pro needs the Pothik plugin installed and active.' => 'Pothik Pro চালাতে আগে Pothik ইনস্টল ও অ্যাকটিভ করুন।',
        'Deposit' => 'ডিপোজিট',
        'Same transaction' => 'একই ট্রানজেকশন',
        'Balance due' => 'বকেয়া',
        'Paid in full' => 'পূর্ণ',
        'Edit package' => 'প্যাকেজ এডিট',
        'Mark replied' => 'উত্তর দিয়েছি',
        'Mark waiting' => 'আবার অপেক্ষায়',
        'Replied' => 'উত্তর হয়েছে',
        'Waiting' => 'অপেক্ষায়',
        'All' => 'সব',
        'Copy for WhatsApp' => 'হোয়াটসঅ্যাপে কপি',
        'Copied.' => 'কপি হয়েছে।',
        'Print' => 'প্রিন্ট',
        'Send this page' => 'এই পাতা পাঠান',
        'Published share prices. This does not hold a seat.' => 'ছাপানো শেয়ার দাম। এতে সিট ধরা হয় না।',
        'Children and infants are priced at the office.' => 'শিশু ও ইনফ্যান্টের দাম অফিস বসায়।',
        'Save departure' => 'ডিপারচার সেভ',
        'A group file shows this seat line when the site has one package. A private file leaves that line off the page.' => 'গ্রুপ ফাইলে এই সিট লাইন তখনই প্যাকেজ পাতায় ওঠে, যখন সাইটে একটি প্যাকেজ থাকে। প্রাইভেট ফাইলে সেই লাইন পাতায় থাকে না।',
        'The office file for this departure. Mahram is a relationship here. It does not block anyone.' => 'এই ডিপারচারের অফিস ফাইল। মাহরাম এখানে সম্পর্ক, কাউকে আটকায় না।',
        'New pilgrim' => 'নতুন যাত্রী',
        'Save pilgrim' => 'যাত্রী সেভ',
        'Delete this pilgrim?' => 'এই যাত্রী মুছবেন?',
        'Save rooming' => 'রুমিং সেভ',
        'Save ledger' => 'খাতা সেভ',
        'Room label' => 'রুমের নাম',
        'Names, one per line' => 'নাম, এক লাইনে একটি',
        'Remove this room' => 'এই রুম সরান',
        'Remove this row' => 'এই সারি সরান',
        'New room' => 'নতুন রুম',
        'New row' => 'নতুন সারি',
        'Jeddah first' => 'জেদ্দা আগে',
        'Madinah first' => 'মদিনা আগে',
        'Room number' => 'রুম নম্বর',
        'Contract, taka' => 'চুক্তি, টাকা',
        'Paid, taka' => 'জমা, টাকা',
        'Season dates' => 'সিজনের তারিখ',
        'These dates warn the desk. They do not stop a save.' => 'এই তারিখ ডেস্ককে সতর্ক করে। সেভ আটকায় না।',
        'Visa opens' => 'ভিসা খোলে',
        'Last visa' => 'শেষ ভিসা',
        'Last entry' => 'শেষ প্রবেশ',
        'Last departure' => 'শেষ ছাড়া',
        'Visa issue is dated to open on %s.' => 'ভিসা ইস্যুর তারিখ ফাইলে %s।',
        'The last visa date on file is %s.' => 'ফাইলে শেষ ভিসার তারিখ %s।',
        'This leave date is after the last entry date %s.' => 'ছাড়ার দিন শেষ প্রবেশ %s-এর পরে।',
        'This return date is after the last departure date %s.' => 'ফেরার দিন শেষ ছাড়া %s-এর পরে।',
        'Ticket, USD note' => 'টিকিট, ডলার নোট',
        'Ground, SAR note' => 'গ্রাউন্ড, রিয়াল নোট',
        'Pick a pilgrim from the file. Dollar and riyal stay as notes.' => 'যাত্রী ফাইল থেকে বেছে নিন। ডলার ও রিয়াল নোটই থাকে।',
        'Analytics' => 'অ্যানালিটিক্স',
        'Counts from inquiries and the receipt.' => 'এনকোয়ারি ও রসিদ থেকে গোনা।',
        'Inquiries, shares and the receipt, counted on this desk.' => 'এনকোয়ারি, শেয়ার ও রসিদ এই ডেস্কে গোনা।',
        '7 days' => '৭ দিন',
        '30 days' => '৩০ দিন',
        'By share' => 'শেয়ার অনুযায়ী',
        'By type' => 'ধরন অনুযায়ী',
        'By city' => 'শহর অনুযায়ী',
        'By day' => 'দিন অনুযায়ী',
        'No city' => 'শহর নেই',
        'By visa' => 'ভিসা অনুযায়ী',
        'By biometric' => 'বায়োমেট্রিক অনুযায়ী',
        'Makkah beds' => 'মক্কার বিছানা',
        'Madinah beds' => 'মদিনার বিছানা',
        'Open seats' => 'খালি সিট',
        'Paid by channel' => 'চ্যানেলে জমা',
        'No channel' => 'চ্যানেল নেই',
        'Group file' => 'গ্রুপ ফাইল',
        'Settings' => 'সেটিংস',
        'Save settings' => 'সেটিংস সংরক্ষণ করুন',
        'Plugin Data' => 'প্লাগিনের ডেটা',
        'Public Page Setup' => 'পাবলিক পেজ সেটআপ',
        'Page status: %s' => 'পেজের অবস্থা: %s',
        'Delete data on uninstall' => 'প্লাগিন আনইনস্টল করলে সব ডেটা মুছে ফেলুন',
        'When checked, deleting the plugin will permanently remove all inquiries, packages and settings.' => 'চিহ্নিত থাকলে, প্লাগিনটি ডিলিট করলে সব ইনকোয়ারি, প্যাকেজ ও সেটিংস স্থায়ীভাবে মুছে যাবে।',
        'Load demo data' => 'ডেমো ডেটা লোড করুন',
        'Remove demo data' => 'ডেমো ডেটা মুছুন',
        'Demo data loaded.' => 'ডেমো ডেটা যুক্ত করা হয়েছে।',
        'Demo data removed.' => 'ডেমো ডেটা মুছে ফেলা হয়েছে।',
        'The package page is currently a draft.' => 'প্যাকেজ পেজটি বর্তমানে ড্রাফট অবস্থায় আছে।',
        'Publish page' => 'পেজ প্রকাশ করুন',
        'Recreate package page' => 'প্যাকেজ পেজ পুনরায় তৈরি করুন',
        'Package page has been recreated.' => 'প্যাকেজ পেজ পুনরায় তৈরি হয়েছে।',
        'Package not found.' => 'প্যাকেজ পাওয়া যায়নি।',
        'Package limit reached (maximum 40 packages).' => 'প্যাকেজের সর্বোচ্চ সীমা (৪০টি) পূর্ণ হয়েছে।',
        'Published' => 'প্রকাশিত',
        'Publish' => 'প্রকাশ করুন',
        'Unpublish' => 'ড্রাফট করুন',
        'Draft Preview — Not published to the public.' => 'ড্রাফট প্রিভিউ — সাধারণ ভিজিটররা দেখতে পাবেন না।',
        'Contact the office' => 'অফিসে যোগাযোগ করুন',
        'Export CSV' => 'CSV এক্সপোর্ট',
        'Umrah desk' => 'উমরা ডেস্ক',
        'Assalamu alaikum, %s.<br><mark data-pk-wait-count data-wait-count>%d</mark> guests of Allah are waiting for your reply.' => 'আসসালামু আলাইকুম, %s।<br><mark data-pk-wait-count data-wait-count>%d</mark> জন আল্লাহর মেহমান আপনার উত্তরের অপেক্ষায়।',
        'Assalamu alaikum, %s.<br>All guests of Allah have been answered. Alhamdulillah.' => 'আসসালামু আলাইকুম, %s।<br>আল্লাহর সকল মেহমানের উত্তর সম্পন্ন হয়েছে। আলহামদুলিল্লাহ।',
        "Every reply you send could be someone's first step towards the Haram." => 'আপনার পাঠানো প্রতিটি উত্তর হতে পারে কারো হেরেমের প্রথম পদক্ষেপ।',
        'May Allah accept the Umrah of every pilgrim you serve.' => 'আপনার মাধ্যমে সেবা পাওয়া প্রত্যেক যাত্রীর উমরা আল্লাহ কবুল করুন।',
        'Serve the guests of Ar-Rahman with ihsan — they remember the agency that cared.' => 'রহমানের মেহমানদের এহসানের সাথে সেবা করুন — তারা যত্নশীল এজেন্সিকে মনে রাখে।',
        "A pilgrim's dua at the Multazam may include your name." => 'মুলতাজামে কোনো এক যাত্রীর দোয়ায় হয়তো আপনার নামও থাকবে।',
        'Open reply queue' => 'উত্তরের সিরিয়াল খুলুন',
        'Finish setting up' => 'সেটআপ সম্পন্ন করুন',
        'Two steps left before you go live' => 'লাইভ হওয়ার আগে ২টি ধাপ বাকি',
        '%d steps left before you go live' => 'লাইভ হওয়ার আগে %dটি ধাপ বাকি',
        'Hide setup checklist' => 'সেটআপ চেকলিস্ট লুকান',
        'Hide' => 'লুকান',
        'Add agency name & phone' => 'এজেন্সির নাম ও ফোন নম্বর দিন',
        'Create your first package' => 'প্রথম প্যাকেজ তৈরি করুন',
        'Set WhatsApp number' => 'হোয়াটসঅ্যাপ নম্বর সেট করুন',
        'Upload agency logo' => 'এজেন্সির লোগো আপলোড করুন',
        'Publish a package' => 'প্যাকেজ পাবলিশ করুন',
        'Makkah al-Mukarramah' => 'মক্কা আল-মুকাররমা',
        'Madinah al-Munawwarah' => 'মদিনা আল-মুনাওয়ারা',
        'Ramadan in' => 'রমজান বাকি',
        'Hajj in' => 'হজ বাকি',
        'days' => 'দিন',
        'Inquiries · last 7 days' => 'এনকোয়ারি · গত ৭ দিন',
        'Needs reply' => 'উত্তর প্রয়োজন',
        'oldest %s' => 'সবচেয়ে পুরোনো %s',
        'of 40 free' => 'সর্বোচ্চ ৪০টি (ফ্রি)',
        'Packages · %d published' => 'প্যাকেজ · %dটি পাবলিশড',
        'Unlimited Pro packages' => 'আনলিমিটেড প্রো প্যাকেজ',
        'Reply queue' => 'উত্তরের সিরিয়াল',
        'All caught up, alhamdulillah' => 'সব উত্তর সম্পন্ন, আলহামদুলিল্লাহ',
        'New inquiries will appear here.' => 'নতুন এনকোয়ারি এখানে দেখা যাবে।',
        'pilgrims' => 'যাত্রী',
        'Call %s' => '%s-কে কল করুন',
        'Reply on WhatsApp' => 'হোয়াটসঅ্যাপে উত্তর দিন',
        'Quick actions' => 'দ্রুত কাজ',
        'Create fresh or draft' => 'নতুন বা ড্রাফট তৈরি',
        'Payment slip' => 'পেমেন্ট স্লিপ',
        'Create receipt & copy' => 'রসিদ বানিয়ে কপি',
        'Download all inquiries' => 'সব এনকোয়ারি ডাউনলোড',
        'Copy shortcode' => 'শর্টকোড কপি',
        'Manage' => 'ম্যানেজ',
        'DAYS' => 'দিন',
        'from' => 'শুরু',
        'Live' => 'পাবলিশড',
        'Draft' => 'ড্রাফট',
        'Grow with Pothik Pro' => 'পথিক প্রো দিয়ে এগিয়ে যান',
        'Explore Pro features' => 'প্রো ফিচার দেখুন',
        'Trying things out?' => 'টেস্ট করছেন?',
        'Command palette' => 'কমান্ড প্যালেট',
        'Type a command or search pilgrims...' => 'কমান্ড টাইপ করুন বা যাত্রী খুঁজুন...',
        'Pothik sections' => 'পথিক সেকশন',
        'Search' => 'অনুসন্ধান',
        'Site' => 'সাইট',
        'Each package becomes a landing page with prices, hotels and a WhatsApp button.' => 'প্রতিটি প্যাকেজে মূল্য, হোটেল এবং একটি হোয়াটসঅ্যাপ বাটনসহ ল্যান্ডিং পেজ তৈরি হয়।',
        'Search packages or hotels…' => 'প্যাকেজ বা হোটেল খুঁজুন…',
        'Create your first Umrah package' => 'আপনার প্রথম উমরা প্যাকেজ তৈরি করুন',
        'Each package gets its own landing page with hotels, itinerary and pricing tiers.' => 'প্রতিটি প্যাকেজে হোটেল, ভ্রমণসূচি এবং প্রাইসিং টায়ারসহ নিজস্ব ল্যান্ডিং পেজ থাকবে।',
        'Start blank or copy an existing one' => 'নতুনভাবে শুরু করুন বা কপি করুন',
        '%d nights Makkah' => '%d রাত মক্কা',
        '%d Madinah' => '%d রাত মদিনা',
        'from, quad' => 'শুরু, কোয়াড',
        'Toggle published' => 'পাবলিশ স্ট্যাটাস পরিবর্তন',
        'Package actions' => 'প্যাকেজ অ্যাকশন',
        'Duplicate' => 'ডুপ্লিকেট',
        'Package duplicated' => 'প্যাকেজ ডুপ্লিকেট হয়েছে',
        'Package deleted' => 'প্যাকেজ মুছে ফেলা হয়েছে',
        'Published — live on your site' => 'পাবলিশড — আপনার সাইটে লাইভ আছে',
        'Moved to draft' => 'ড্রাফট করা হয়েছে',
        '%d of 40 packages' => '৪০টির মধ্যে %dটি প্যাকেজ',
        'Delete this package?' => 'এই প্যাকেজটি মুছবেন?',
        'Delete %s? Yes / Cancel' => '%s মুছবেন? হ্যাঁ / বাতিল',
        'Yes, delete' => 'হ্যাঁ, মুছুন',
        'Cancel' => 'বাতিল',
        'Makkah' => 'মক্কা',
        'Madinah' => 'মদিনা',
    ];

    public static function capture(): void
    {
        if (!isset($_GET['pothik_lang'])) {
            return;
        }

        $lang = sanitize_key((string) wp_unslash($_GET['pothik_lang'])) === 'bn' ? 'bn' : 'en';
        setcookie('pothik_lang', $lang, [
            'expires' => time() + YEAR_IN_SECONDS,
            'path' => '/',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['pothik_lang'] = $lang;

        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'pothik_lang', $lang);
        }

        wp_safe_redirect(remove_query_arg('pothik_lang'));
        exit;
    }

    public static function lang(): string
    {
        $cookie = isset($_COOKIE['pothik_lang']) ? sanitize_key((string) $_COOKIE['pothik_lang']) : '';
        if ($cookie === 'bn' || $cookie === 'en') {
            return $cookie;
        }

        if (is_user_logged_in()) {
            $saved = sanitize_key((string) get_user_meta(get_current_user_id(), 'pothik_lang', true));
            if ($saved === 'bn' || $saved === 'en') {
                return $saved;
            }
        }

        return 'en';
    }

    public static function is_bn(): bool
    {
        return self::lang() === 'bn';
    }

    public static function load_textdomain(): void
    {
        load_plugin_textdomain('pothik', false, dirname(plugin_basename(POTHIK_FILE)) . '/languages');
    }

    public static function filter_locale(string $locale): string
    {
        $lang = self::lang();
        if ($lang === 'bn') {
            return 'bn_BD';
        }
        if ($lang === 'en') {
            return 'en_US';
        }

        return $locale;
    }

    public static function t(string $text, string $context = ''): string
    {
        $translated = $context !== '' ? _x($text, $context, 'pothik') : __($text, 'pothik');
        if ($translated !== $text) {
            return $translated;
        }

        if (self::is_bn() && isset(self::BN[$text])) {
            return self::BN[$text];
        }

        return $translated;
    }

    public static function toggle(): string
    {
        $current = self::lang();
        $html = '<nav class="pk-lang" aria-label="' . esc_attr(self::t('Language')) . '">';
        foreach (['en' => 'EN', 'bn' => 'বাংলা'] as $code => $label) {
            $class = $current === $code ? ' is-on' : '';
            $html .= '<a class="pk-lang-btn' . $class . '" href="' . esc_url(add_query_arg('pothik_lang', $code)) . '">' . esc_html($label) . '</a>';
        }
        $html .= '</nav>';

        return $html;
    }

    /**
     * @param array<string, mixed> $source
     */
    public static function field(array $source, string $key): string
    {
        if (self::lang() === 'bn') {
            $bn = $source['bn'][$key] ?? null;
            if (is_string($bn) && $bn !== '') {
                return $bn;
            }
        }

        $value = $source[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * @param array<string, mixed> $source
     * @return array<int, string>
     */
    public static function lines(array $source, string $key): array
    {
        if (self::lang() === 'bn' && isset($source['bn'][$key]) && is_array($source['bn'][$key]) && $source['bn'][$key] !== []) {
            return array_values(array_map('strval', $source['bn'][$key]));
        }

        $value = $source[$key] ?? [];

        return is_array($value) ? array_values(array_map('strval', $value)) : [];
    }

    public static function choice(string $group, string $value): string
    {
        $key = self::canonical($group, $value);
        $english = self::CHOICES[$group][$key] ?? $value;

        return self::t($english);
    }

    public static function canonical(string $group, string $value): string
    {
        $aliases = self::ALIASES[$group] ?? [];
        if (isset($aliases[$value])) {
            return $aliases[$value];
        }
        if (isset(self::CHOICES[$group][$value])) {
            return $value;
        }

        return $value;
    }

    /**
     * @var array<string, array<string, string>>
     */
    private const CHOICES = [
        'share' => [
            'quad' => 'Quad',
            'triple' => 'Triple',
            'double' => 'Double',
            'single' => 'Single',
            'child_bed' => 'Child with bed',
            'child_nobed' => 'Child without bed',
            'infant' => 'Infant',
        ],
        'trip' => [
            'group' => 'Group',
            'private' => 'Private',
        ],
        'channel' => [
            'bkash' => 'bKash',
            'nagad' => 'Nagad',
            'rocket' => 'Rocket',
            'bank' => 'Bank',
            'cash' => 'Cash',
        ],
        'gender' => [
            'male' => 'Male',
            'female' => 'Female',
        ],
        'biometric' => [
            'done' => 'Done',
            'failed' => 'Failed',
            'not_started' => 'Not started',
        ],
        'visa' => [
            'draft' => 'Draft',
            'invoice' => 'Invoice',
            'paid' => 'Paid',
            'issued' => 'Issued',
            'refused' => 'Refused',
            'embassy' => 'Embassy visit',
        ],
        'status' => [
            'group_linked' => 'Group linked',
        ],
        'direction' => [
            'jeddah_first' => 'Jeddah first',
            'madinah_first' => 'Madinah first',
        ],
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private const ALIASES = [
        'share' => [
            'কোয়াড' => 'quad',
            'ট্রিপল' => 'triple',
            'ডাবল' => 'double',
            'সিঙ্গেল' => 'single',
            'শিশু, বিছানা সহ' => 'child_bed',
            'শিশু, বিছানা ছাড়া' => 'child_nobed',
            'ইনফ্যান্ট' => 'infant',
        ],
        'trip' => [
            'গ্রুপ' => 'group',
            'প্রাইভেট' => 'private',
        ],
        'channel' => [
            'bKash' => 'bkash',
            'Nagad' => 'nagad',
            'Rocket' => 'rocket',
            'ব্যাংক' => 'bank',
            'ক্যাশ' => 'cash',
        ],
        'gender' => [
            'পুরুষ' => 'male',
            'নারী' => 'female',
        ],
        'biometric' => [
            'সম্পন্ন' => 'done',
            'ফেইল' => 'failed',
            'শুরু হয়নি' => 'not_started',
        ],
        'visa' => [
            'ড্রাফট' => 'draft',
            'ইনভয়েস' => 'invoice',
            'পরিশোধ' => 'paid',
            'ইস্যু' => 'issued',
            'রিফিউজ' => 'refused',
            'এম্বাসি ভিজিট' => 'embassy',
        ],
        'status' => [
            'গ্রুপ লিংকড' => 'group_linked',
        ],
        'direction' => [
            'Jeddah first' => 'jeddah_first',
            'জেদ্দা আগে' => 'jeddah_first',
            'Madinah first' => 'madinah_first',
            'মদিনা আগে' => 'madinah_first',
        ],
    ];
}
