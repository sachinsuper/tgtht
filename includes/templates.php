<?php
/**
 * ---------------------------------------------------------------------------
 *  TEMPLATE / BLOCK SCHEMA
 * ---------------------------------------------------------------------------
 *  This single array drives everything:
 *    - which fields the admin editor shows (and in what order/grouping)
 *    - the default content a brand-new page starts with
 *    - what the public template can read
 *
 *  To add a new editable field: add one line here. The admin form and the
 *  database rows are generated from it automatically -- no other file to touch
 *  except the template that prints it.
 *
 *  Field types: text | textarea | image | url | tel | toggle | color | select
 * ---------------------------------------------------------------------------
 */

function templates(): array
{
    return [

        'store-locator' => [
            'name' => 'Store Locator LP (Technogym India)',
            'file' => 'store-locator.php',
            'groups' => [

                'Header' => [
                    ['key' => 'logo_image',    'label' => 'Logo',                  'type' => 'image', 'default' => 'assets/img/logo.png'],
                    ['key' => 'header_label',  'label' => 'Small text above phone','type' => 'text',  'default' => 'TALK TO AN EXPERT'],
                    ['key' => 'header_phone',  'label' => 'Phone number (shown)',  'type' => 'text',  'default' => '98200 52225'],
                    ['key' => 'header_tel',    'label' => 'Phone number (dial)',   'type' => 'tel',   'default' => '+919820052225',
                     'help' => 'Include country code, no spaces. Used by the tap-to-call link.'],
                ],

                'Hero slider' => [
                    ['key' => 'slide1_on',      'label' => 'Show slide 1',   'type' => 'toggle', 'default' => '1'],
                    ['key' => 'slide1_image',   'label' => 'Slide 1 image',  'type' => 'image',  'default' => 'assets/img/hero-1.jpg',
                     'help' => 'Recommended 1600 x 1000 px, JPG under 400 KB.'],
                    ['key' => 'slide1_caption', 'label' => 'Slide 1 caption','type' => 'text',   'default' => 'TECHNOGYM RUN'],
                    ['key' => 'slide1_link',    'label' => 'Slide 1 link',   'type' => 'url',    'default' => ''],

                    ['key' => 'slide2_on',      'label' => 'Show slide 2',   'type' => 'toggle', 'default' => '0'],
                    ['key' => 'slide2_image',   'label' => 'Slide 2 image',  'type' => 'image',  'default' => ''],
                    ['key' => 'slide2_caption', 'label' => 'Slide 2 caption','type' => 'text',   'default' => ''],
                    ['key' => 'slide2_link',    'label' => 'Slide 2 link',   'type' => 'url',    'default' => ''],

                    ['key' => 'slide3_on',      'label' => 'Show slide 3',   'type' => 'toggle', 'default' => '0'],
                    ['key' => 'slide3_image',   'label' => 'Slide 3 image',  'type' => 'image',  'default' => ''],
                    ['key' => 'slide3_caption', 'label' => 'Slide 3 caption','type' => 'text',   'default' => ''],
                    ['key' => 'slide3_link',    'label' => 'Slide 3 link',   'type' => 'url',    'default' => ''],
                ],

                'Stores section' => [
                    ['key' => 'stores_title', 'label' => 'Section heading', 'type' => 'text', 'default' => 'TECHNOGYM IN INDIA'],

                    ['key' => 'store1_on',      'label' => 'Show store 1',    'type' => 'toggle',   'default' => '1'],
                    ['key' => 'store1_image',   'label' => 'Store 1 photo',   'type' => 'image',    'default' => 'assets/img/store-bengaluru.jpg'],
                    ['key' => 'store1_city',    'label' => 'Store 1 city',    'type' => 'text',     'default' => 'BENGALURU'],
                    ['key' => 'store1_address', 'label' => 'Store 1 address', 'type' => 'textarea', 'default' => "28, Traan El Dorado, Lavelle Cross Road,\n7th Cross Road, Bengaluru, Karnataka 560001"],
                    ['key' => 'store1_cta',     'label' => 'Store 1 button text', 'type' => 'text', 'default' => 'VISIT THE STORE'],
                    ['key' => 'store1_url',     'label' => 'Store 1 button link', 'type' => 'url',  'default' => 'https://maps.google.com/'],

                    ['key' => 'store2_on',      'label' => 'Show store 2',    'type' => 'toggle',   'default' => '1'],
                    ['key' => 'store2_image',   'label' => 'Store 2 photo',   'type' => 'image',    'default' => 'assets/img/store-newdelhi.jpg'],
                    ['key' => 'store2_city',    'label' => 'Store 2 city',    'type' => 'text',     'default' => 'NEW DELHI'],
                    ['key' => 'store2_address', 'label' => 'Store 2 address', 'type' => 'textarea', 'default' => "D-59, Second Floor, Defence Colony,\nNew Delhi 110024"],
                    ['key' => 'store2_cta',     'label' => 'Store 2 button text', 'type' => 'text', 'default' => 'VISIT THE STORE'],
                    ['key' => 'store2_url',     'label' => 'Store 2 button link', 'type' => 'url',  'default' => 'https://maps.google.com/'],

                    ['key' => 'store3_on',      'label' => 'Show store 3',    'type' => 'toggle',   'default' => '0'],
                    ['key' => 'store3_image',   'label' => 'Store 3 photo',   'type' => 'image',    'default' => ''],
                    ['key' => 'store3_city',    'label' => 'Store 3 city',    'type' => 'text',     'default' => ''],
                    ['key' => 'store3_address', 'label' => 'Store 3 address', 'type' => 'textarea', 'default' => ''],
                    ['key' => 'store3_cta',     'label' => 'Store 3 button text', 'type' => 'text', 'default' => 'VISIT THE STORE'],
                    ['key' => 'store3_url',     'label' => 'Store 3 button link', 'type' => 'url',  'default' => ''],

                    ['key' => 'store4_on',      'label' => 'Show store 4',    'type' => 'toggle',   'default' => '0'],
                    ['key' => 'store4_image',   'label' => 'Store 4 photo',   'type' => 'image',    'default' => ''],
                    ['key' => 'store4_city',    'label' => 'Store 4 city',    'type' => 'text',     'default' => ''],
                    ['key' => 'store4_address', 'label' => 'Store 4 address', 'type' => 'textarea', 'default' => ''],
                    ['key' => 'store4_cta',     'label' => 'Store 4 button text', 'type' => 'text', 'default' => 'VISIT THE STORE'],
                    ['key' => 'store4_url',     'label' => 'Store 4 button link', 'type' => 'url',  'default' => ''],
                ],

                'Connect with us' => [
                    ['key' => 'connect_title',   'label' => 'Section heading', 'type' => 'text', 'default' => 'CONNECT WITH US'],

                    ['key' => 'call_on',      'label' => 'Show "Call us" tile', 'type' => 'toggle', 'default' => '1'],
                    ['key' => 'call_title',   'label' => 'Call tile title',     'type' => 'text',   'default' => 'CALL US'],
                    ['key' => 'call_number',  'label' => 'Call tile number',    'type' => 'text',   'default' => '98200 52225'],
                    ['key' => 'call_tel',     'label' => 'Call tile dial link', 'type' => 'tel',    'default' => '+919820052225'],

                    ['key' => 'wa_on',        'label' => 'Show WhatsApp tile',  'type' => 'toggle', 'default' => '1'],
                    ['key' => 'wa_title',     'label' => 'WhatsApp title',      'type' => 'text',   'default' => 'WHATSAPP US'],
                    ['key' => 'wa_sub',       'label' => 'WhatsApp sub-text',   'type' => 'text',   'default' => 'START A CHAT'],
                    ['key' => 'wa_number',    'label' => 'WhatsApp number',     'type' => 'tel',    'default' => '919820052225',
                     'help' => 'Country code + number, digits only. e.g. 919820052225'],
                    ['key' => 'wa_message',   'label' => 'Pre-filled message',  'type' => 'text',   'default' => 'Hi, I would like to know more about Technogym products.'],

                    ['key' => 'cb_on',        'label' => 'Show call-back tile', 'type' => 'toggle', 'default' => '1'],
                    ['key' => 'cb_title',     'label' => 'Call-back title',     'type' => 'text',   'default' => 'REQUEST A CALL BACK'],
                    ['key' => 'cb_placeholder','label'=> 'Input placeholder',   'type' => 'text',   'default' => 'Phone number'],
                    ['key' => 'cb_thanks',    'label' => 'Thank-you message',   'type' => 'text',   'default' => 'Thanks! Our expert will call you shortly.'],

                    ['key' => 'web_on',       'label' => 'Show website tile',   'type' => 'toggle', 'default' => '1'],
                    ['key' => 'web_logo',     'label' => 'Website tile logo',   'type' => 'image',  'default' => 'assets/img/logo.png'],
                    ['key' => 'web_title',    'label' => 'Website tile text',   'type' => 'text',   'default' => 'VISIT THE WEBSITE'],
                    ['key' => 'web_url',      'label' => 'Website link',        'type' => 'url',    'default' => 'https://www.technogym.com/in/'],
                ],

                'Footer' => [
                    ['key' => 'footer_logo',  'label' => 'Footer logo',    'type' => 'image', 'default' => 'assets/img/logo.png'],
                    ['key' => 'footer_text',  'label' => 'Footer tagline', 'type' => 'text',  'default' => 'THE WELLNESS COMPANY'],
                    ['key' => 'footer_phone', 'label' => 'Footer phone',   'type' => 'text',  'default' => '98200 52225'],
                ],

                'Theme' => [
                    ['key' => 'accent_color', 'label' => 'Accent colour', 'type' => 'color', 'default' => '#FFD100'],
                    ['key' => 'dark_color',   'label' => 'Dark colour',   'type' => 'color', 'default' => '#1A1A1A'],
                    ['key' => 'cream_color',  'label' => 'Page background','type' => 'color','default' => '#F5F2EC'],
                ],
            ],
        ],

    ];
}

/** Flat list of every field definition for a template, keyed by field key. */
function template_fields(string $template): array
{
    $t = templates()[$template] ?? null;
    if (!$t) {
        return [];
    }
    $out = [];
    foreach ($t['groups'] as $group => $fields) {
        foreach ($fields as $f) {
            $f['group']      = $group;
            $out[$f['key']]  = $f;
        }
    }
    return $out;
}
