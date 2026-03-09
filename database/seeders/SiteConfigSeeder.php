<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SiteConfig;

class SiteConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SiteConfig::set('site_name', 'Metapilot', 'text');
        
        // Add other defaults if needed
        if (!SiteConfig::get('site_logo')) {
            SiteConfig::set('site_logo', '', 'image');
        }
    }
}
