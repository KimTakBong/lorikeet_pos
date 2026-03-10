<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPhoneNumber implements ValidationRule
{
    protected array $countryCodes = [
        '62' => ['min' => 10, 'max' => 13, 'name' => 'Indonesia'],
        '60' => ['min' => 9, 'max' => 12, 'name' => 'Malaysia'],
        '65' => ['min' => 8, 'max' => 9, 'name' => 'Singapore'],
        '63' => ['min' => 10, 'max' => 11, 'name' => 'Philippines'],
        '66' => ['min' => 9, 'max' => 10, 'name' => 'Thailand'],
        '84' => ['min' => 9, 'max' => 11, 'name' => 'Vietnam'],
        '61' => ['min' => 9, 'max' => 10, 'name' => 'Australia'],
        '1' => ['min' => 10, 'max' => 10, 'name' => 'USA/Canada'],
        '44' => ['min' => 10, 'max' => 11, 'name' => 'UK'],
        '91' => ['min' => 10, 'max' => 10, 'name' => 'India'],
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = preg_replace('/\D/', '', $value);
        
        if (empty($value)) {
            $fail('The phone number is required.');
            return;
        }
        
        // Check if starts with 0 (NOT ALLOWED)
        if (strpos($value, '0') === 0) {
            $fail('The phone number cannot start with 0. Use country code instead (e.g., 62 for Indonesia).');
            return;
        }
        
        $matchedCountry = null;
        foreach (array_keys($this->countryCodes) as $code) {
            if (strpos($value, $code) === 0) {
                $matchedCountry = $code;
                break;
            }
        }
        
        if (!$matchedCountry) {
            $fail('The phone number must start with a valid country code (e.g., 62 for Indonesia).');
            return;
        }
        
        $country = $this->countryCodes[$matchedCountry];
        $phoneWithoutCountry = substr($value, strlen($matchedCountry));
        
        if (strlen($phoneWithoutCountry) < $country['min']) {
            $fail("The phone number is too short for {$country['name']}.");
            return;
        }
        
        if (strlen($phoneWithoutCountry) > $country['max']) {
            $fail("The phone number is too long for {$country['name']}.");
            return;
        }
    }
}
