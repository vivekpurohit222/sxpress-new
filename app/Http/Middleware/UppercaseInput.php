<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;

/**
 * Converts all string input to UPPERCASE before it reaches the controller.
 * Excluded fields: password, password_confirmation, email, _token, _method
 */
class UppercaseInput extends TransformsRequest
{
    /**
     * Fields that should NOT be uppercased.
     */
    protected $except = [
        'password',
        'password_confirmation',
        'manager_password',
        'email',
        'manager_email',
        '_token',
        '_method',
    ];

    protected function transform($key, $value)
    {
        if (in_array($key, $this->except, true)) {
            return $value;
        }

        if (is_string($value)) {
            return mb_strtoupper($value);
        }

        return $value;
    }
}
