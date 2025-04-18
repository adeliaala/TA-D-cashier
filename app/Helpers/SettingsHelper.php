<?php

namespace App\Helpers;

use Modules\Setting\Entities\Setting;

class SettingsHelper
{
    public static function settings()
    {
        return Setting::first();
    }
}