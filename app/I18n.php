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

    public static function t(string $text): string
    {
        if (self::lang() !== 'bn') {
            return $text;
        }

        return self::BN[$text] ?? $text;
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
