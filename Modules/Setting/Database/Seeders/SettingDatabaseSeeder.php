<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Setting\Entities\Setting;
use Modules\Currency\Entities\Currency;

class SettingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get default currency (IDR)
        $currency = Currency::where('code', 'IDR')->first();

        Setting::create([
            'company_name' => 'Toko Al Fatih',
            'company_email' => 'info@al-fatih.com',
            'company_phone' => '1234567890',
            'notification_email' => 'notification@al-fatih.com',
            'default_currency_id' => $currency->id,
            'default_currency_position' => 'left',
            'footer_text' => 'Toko Al Fatih © 2024',
            'company_address' => 'Jl. Example No. 123',
            
        ]);
    }
}
