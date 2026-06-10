<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * GstNumberRule
 *
 * Validates Indian GSTIN (Goods and Services Tax Identification Number).
 * Format: 15 characters: NNAAAANNNNAAZC
 * Example: 24AABCU9603R1ZX
 *
 * Regex breakdown:
 *   [0-9]{2}  — First 2 digits: state code (01-37)
 *   [A-Z]{5}  — Next 5 letters: PAN number
 *   [0-9]{4}  — Next 4 digits: registration number
 *   [A-Z]{1}  — 1 letter: entity number (1-9 or A-Z)
 *   [1-9A-Z]{1} — 1 alphanumeric: Z suffix or 1-9
 *   Z          — Fixed 'Z' character
 *   [0-9A-Z]{1} — Check digit
 *
 * Per SXPRESS_LOGIC_SKILL section 16.
 */
class GstNumberRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (empty($value)) {
            return true; // GST is nullable, use 'required' rule separately
        }
        return (bool) preg_match(
            '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i',
            (string) $value
        );
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must be a valid 15-character Indian GSTIN (e.g. 24AABCU9603R1ZX).';
    }
}