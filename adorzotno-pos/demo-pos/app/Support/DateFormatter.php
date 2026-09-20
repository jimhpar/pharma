<?php

namespace App\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class DateFormatter
{
    public const DATE = 'd/m/Y';
    public const DATE_TIME = 'd/m/Y h:i A';
    public const DATE_TIME_24 = 'd/m/Y H:i';
    public const SHORT_DATE_TIME = 'd/m h:i A';
    public const INPUT_DATE = 'Y-m-d';
    public const FILE_TIMESTAMP = 'Y-m-d-His';
    public const HUMAN_DATE_TIME = 'j M Y|h:i A';

    public static function date(DateTimeInterface|string|null $value, string $empty = ''): string
    {
        return self::format($value, self::DATE, $empty);
    }

    public static function dateTime(DateTimeInterface|string|null $value, string $empty = ''): string
    {
        return self::format($value, self::DATE_TIME, $empty);
    }

    public static function dateTime24(DateTimeInterface|string|null $value, string $empty = ''): string
    {
        return self::format($value, self::DATE_TIME_24, $empty);
    }

    public static function shortDateTime(DateTimeInterface|string|null $value, string $empty = ''): string
    {
        return self::format($value, self::SHORT_DATE_TIME, $empty);
    }

    public static function humanDateTime(DateTimeInterface|string|null $value, string $empty = ''): string
    {
        return self::format($value, self::HUMAN_DATE_TIME, $empty);
    }

    public static function inputDate(DateTimeInterface|string|null $value, string $empty = ''): string
    {
        return self::format($value, self::INPUT_DATE, $empty);
    }

    public static function fileTimestamp(DateTimeInterface|string|null $value = null): string
    {
        return self::format($value ?? now(), self::FILE_TIMESTAMP);
    }

    public static function format(DateTimeInterface|string|null $value, string $format, string $empty = ''): string
    {
        if ($value === null || $value === '') {
            return $empty;
        }

        $date = $value instanceof CarbonInterface
            ? $value
            : Carbon::parse($value);

        return $date->timezone(config('app.timezone'))->format($format);
    }
}
