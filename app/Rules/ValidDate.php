<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidDate implements Rule
{
    protected $formats = [
        'm/d/Y',
        'd/m/Y',
        'Y-m-d',
        'd-m-Y',
        'Y/m/d',
        'd/m/Y',
        'Y-m-d',
        'd-m-Y',
        'Y/m/d',
        'd/m/Y',
        'Y-m-d',
        'm-d-Y',
        'm/d/Y',
        'd-m-Y',
        'd/m/Y',
    ];

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $trimValue = trim($value);
        foreach ($this->formats as $format) {
            $parsed = \DateTime::createFromFormat($format, $trimValue);
            if ($parsed && $parsed->format($format) === $trimValue) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a valid date in one of the accepted formats';
    }
}
