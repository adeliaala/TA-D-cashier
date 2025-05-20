<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
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

        // Create default settings if not exists
        if (!Setting::first()) {
            Setting::create([
                'default_currency_id' => $currency->id,
                'company_name' => 'Toko Al Fatih',
                'company_email' => 'info@al-fatih.com',
                'company_phone' => '1234567890',
                'company_address' => 'Jl. Example No. 123',
                'company_logo' => null,
                'company_favicon' => null,
                'company_currency_position' => 'prefix',
                'company_timezone' => 'Asia/Jakarta',
                'company_date_format' => 'd-m-Y',
                'company_time_format' => 'H:i:s',
                'company_fiscal_year' => '1-12',
                'company_tax_number' => '1234567890',
                'company_vat_number' => '1234567890'
            ]);
        }
    }
} 