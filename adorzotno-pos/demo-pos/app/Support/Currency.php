<?php

namespace App\Support;

class Currency
{
    public const CODE = 'BDT';

    public static function code(): string
    {
        return self::CODE;
    }

    public static function format(float|int|string|null $amount, int $decimals = 2): string
    {
        return self::CODE . ' ' . number_format((float) $amount, $decimals);
    }
}
