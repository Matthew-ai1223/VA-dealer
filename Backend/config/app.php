<?php
/**
 * Application configuration
 * Stage 2: extend with lead tracking, WhatsApp API keys, automation settings
 */
return [
    // Set to '' when deployed at domain root (InfinityFree htdocs). null = auto-detect.
    'base_path_override' => null,

    'site_name'        => 'VA Auto Sales',
    'site_tagline'     => 'Premium Pre-Owned Vehicles in Nigeria',
    'whatsapp_number'  => '2348012345678', // Update with real dealership number
    'uploads_path'     => __DIR__ . '/../uploads/cars',
    'max_upload_size'  => 5 * 1024 * 1024, // 5MB
    'max_images_per_car' => 5,
    'allowed_images'   => ['image/jpeg', 'image/png', 'image/webp'],
    'cars_per_page'    => 12,
  'session_name'     => 'va_aut_sales_admin',

  // Stage 2 — lead notifications (set admin_email before going live)
  'admin_email'      => '',
  'mail_from'        => 'noreply@vaautosales.local',

  // Social media links (update with your real profiles)
    'social' => [
        'facebook'  => 'https://facebook.com/vaautosales',
        'instagram' => 'https://instagram.com/vaautosales',
        'twitter'   => 'https://x.com/vaautosales',
        'tiktok'    => 'https://tiktok.com/@vaautosales',
        'youtube'   => 'https://youtube.com/@vaautosales',
        'linkedin'  => 'https://linkedin.com/company/vaautosales',
    ],

    // Groq AI — put your key in Backend/config/groq.local.php (never commit it)
    'groq' => [
        'api_key' => '',
        'model'   => 'llama-3.3-70b-versatile',
        'enabled' => true,
    ],
];
