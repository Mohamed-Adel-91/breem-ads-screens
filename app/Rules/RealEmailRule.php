<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class RealEmailRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (strpos($value, '@') === false) {
            return false;
        }
        $parts = explode('@', $value);
        $domain = $parts[1];
        return checkdnsrr($domain, 'MX');
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.real_email');
    }
}
