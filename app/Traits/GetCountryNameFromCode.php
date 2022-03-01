<?php

namespace App\Traits;

use App\Constants\CountryCodes;

trait GetCountryNameFromCode
{
    public function getCountryNameFromCode(string $countryCode): string
    {
        return CountryCodes::COUNTRY_CODES_ARRAY[$countryCode] ?? $countryCode;
    }
}