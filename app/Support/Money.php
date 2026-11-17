<?php

namespace App\Support;

class Money
{
    /** 3200 -> "3 200", 99.5 -> "99,50" */
    public static function format(float|int|string|null $value): string
    {
        $value = (float) $value;
        $decimals = floor($value) == $value ? 0 : 2;

        return number_format($value, $decimals, ',', "\u{00A0}");
    }
}
