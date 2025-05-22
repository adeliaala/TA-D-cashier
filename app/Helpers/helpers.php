<?php

use Modules\Setting\Entities\Setting;
use Illuminate\Support\Facades\Log;

if (!function_exists('settings')) {
    function settings()
    {
        return Setting::first();
    }
}

if (!function_exists('format_currency')) {
    function format_currency($price)
    {
        try {
            $settings = settings();
            Log::info('Format Currency', [
                'price' => $price,
                'currency_symbol' => $settings->currency->symbol ?? 'Rp'
            ]);
            
            if (!isset($settings->currency)) {
                return 'Rp ' . number_format($price, 0, ',', '.') . ',-';
            }
            
            return $settings->currency->symbol . ' ' . number_format($price, 0, $settings->currency->decimal_separator, $settings->currency->thousand_separator) . ',-';
        } catch (\Exception $e) {
            Log::error('Error in format_currency', [
                'price' => $price,
                'error' => $e->getMessage()
            ]);
            return 'Rp ' . number_format($price, 0, ',', '.') . ',-';
        }
    }
}

if (!function_exists('make_reference_id')) {
    function make_reference_id($prefix, $number) {
        $padded_text = $prefix . '-' . str_pad($number, 5, 0, STR_PAD_LEFT);

        return $padded_text;
    }
}

if (!function_exists('array_merge_numeric_values')) {
    function array_merge_numeric_values() {
        $arrays = func_get_args();
        $merged = array();
        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (!is_numeric($value)) {
                    continue;
                }
                if (!isset($merged[$key])) {
                    $merged[$key] = $value;
                } else {
                    $merged[$key] += $value;
                }
            }
        }

        return $merged;
    }
}
