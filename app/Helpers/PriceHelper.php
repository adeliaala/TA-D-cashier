<?php

namespace App\Helpers;

use App\Helpers\SettingsHelper;

class PriceHelper
{
    public static function formatPrice($price)
    {
        $settings = SettingsHelper::settings();
        return $settings->currency->symbol . ' ' . number_format($price, 0, $settings->currency->decimal_separator, $settings->currency->thousand_separator) . ',-';
    }
}