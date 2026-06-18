<?php

if (!function_exists('indianNumberFormat')) {
    /**
     * Format a number in Indian numbering system.
     * Example: 1234567.89 → "12,34,567.89"
     * Example: 100000 → "1,00,000.00"
     * Example: 999 → "999.00"
     *
     * @param float $number The number to format
     * @param int $decimals Number of decimal places (default 2)
     * @return string Formatted number string
     */
    function indianNumberFormat(float $number, int $decimals = 2): string
    {
        $negative = $number < 0;
        $number = abs($number);

        $intPart = (int) floor($number);
        $decPart = round($number - $intPart, $decimals);

        $intStr = (string) $intPart;
        $result = '';

        if (strlen($intStr) > 3) {
            $result = substr($intStr, -3);
            $remaining = substr($intStr, 0, -3);
            while (strlen($remaining) > 2) {
                $result = substr($remaining, -2) . ',' . $result;
                $remaining = substr($remaining, 0, -2);
            }
            if (strlen($remaining) > 0) {
                $result = $remaining . ',' . $result;
            }
        } else {
            $result = $intStr;
        }

        $decStr = str_pad((string) round($decPart * pow(10, $decimals)), $decimals, '0', STR_PAD_LEFT);
        $formatted = $result . '.' . $decStr;

        return ($negative ? '-' : '') . $formatted;
    }
}
