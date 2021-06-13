<?php
namespace Bundle\Asmb\Competition\Helpers;

use Carbon\Carbon;

class DateHelper
{
    public static function formatWithLocalizedDayAndMonth(Carbon $date)
    {
        if ($date->daysInMonth == 1) {
            $outputFormat = '%a %eer %b';
        } else {
            $outputFormat = '%a %e %b';
        }

        return $date->formatLocalized($outputFormat);
    }

    public static function formatWithLocalizedDayMonthAndYear(Carbon $date)
    {
        return self::formatWithLocalizedDayAndMonth($date) . ' ' . $date->year;
    }
}