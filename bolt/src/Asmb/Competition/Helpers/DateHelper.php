<?php

namespace Bundle\Asmb\Competition\Helpers;

use Carbon\Carbon;

class DateHelper
{
    public static function formatWithLocalizedDayAndMonth(?Carbon $date, bool $short = true): ?string
    {
        if (null !== $date) {
            if ($date->daysInMonth == 1) {
                $outputFormat = $short ? '%a %eer %b' : '%A %eer %B';
            } else {
                $outputFormat = $short ? '%a %e %b' : '%A %e %B';
            }

            return $date->formatLocalized($outputFormat);
        }

        return null;
    }

    public static function formatWithLocalizedDayMonthAndYear(?Carbon $date, bool $short = true): ?string
    {
        if (null !== $date) {
            return self::formatWithLocalizedDayAndMonth($date) . ' ' . $date->year;
        }

        return null;
    }
}