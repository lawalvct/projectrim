<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class DefaultSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['group' => 'general', 'key' => 'site_name', 'value' => 'ProjectRim', 'type' => 'text'],
            ['group' => 'general', 'key' => 'site_tagline', 'value' => 'Your Research Marketplace', 'type' => 'text'],
            ['group' => 'general', 'key' => 'site_description', 'value' => 'Buy and download quality research papers, projects, and academic works.', 'type' => 'textarea'],
            ['group' => 'general', 'key' => 'contact_email', 'value' => 'info@projectrim.com', 'type' => 'text'],
            ['group' => 'general', 'key' => 'contact_phone', 'value' => '', 'type' => 'text'],
            ['group' => 'general', 'key' => 'contact_address', 'value' => '', 'type' => 'textarea'],

            // Commission
            ['group' => 'commission', 'key' => 'platform_commission_rate', 'value' => '20', 'type' => 'number'],
            ['group' => 'commission', 'key' => 'min_payout_amount', 'value' => '50', 'type' => 'number'],

            // Social links
            ['group' => 'social', 'key' => 'facebook_url', 'value' => '', 'type' => 'text'],
            ['group' => 'social', 'key' => 'twitter_url', 'value' => '', 'type' => 'text'],
            ['group' => 'social', 'key' => 'instagram_url', 'value' => '', 'type' => 'text'],
            ['group' => 'social', 'key' => 'linkedin_url', 'value' => '', 'type' => 'text'],

            // Smart links
            ['group' => 'smartlinks', 'key' => 'smart_link_enabled', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'smartlinks', 'key' => 'smart_link_code', 'value' => '', 'type' => 'textarea'],

            // Seller
            ['group' => 'seller', 'key' => 'auto_approve_sellers', 'value' => 'true', 'type' => 'boolean'],
            ['group' => 'seller', 'key' => 'allow_paid_products', 'value' => 'false', 'type' => 'boolean'],

            // Monetization
            ['group' => 'monetization', 'key' => 'view_reward_usd', 'value' => '0.10', 'type' => 'number'],
            ['group' => 'monetization', 'key' => 'download_reward_usd', 'value' => '1.00', 'type' => 'number'],

            // Payment
            ['group' => 'payment', 'key' => 'currency', 'value' => 'NGN', 'type' => 'text'],
            ['group' => 'payment', 'key' => 'currency_symbol', 'value' => '₦', 'type' => 'text'],
            ['group' => 'payment', 'key' => 'stripe_enabled', 'value' => 'false', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'paypal_enabled', 'value' => 'false', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'paystack_enabled', 'value' => 'false', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'flutterwave_enabled', 'value' => 'false', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'bank_transfer_enabled', 'value' => 'true', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'bank_transfer_details', 'value' => '', 'type' => 'textarea'],

            // SEO
            ['group' => 'seo', 'key' => 'meta_title', 'value' => 'ProjectRim - Research Papers & Projects Marketplace', 'type' => 'text'],
            ['group' => 'seo', 'key' => 'meta_description', 'value' => 'Find and download quality research papers, projects, and academic works on ProjectRim.', 'type' => 'textarea'],
            ['group' => 'seo', 'key' => 'meta_keywords', 'value' => 'research papers, projects, academic, marketplace, download', 'type' => 'text'],
            ['group' => 'seo', 'key' => 'google_analytics_id', 'value' => '', 'type' => 'text'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
