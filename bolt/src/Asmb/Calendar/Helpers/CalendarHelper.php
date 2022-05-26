<?php

namespace Bundle\Asmb\Calendar\Helpers;

use Carbon\Carbon;

/**
 * Calendar helper.
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2019
 */
class CalendarHelper
{
    /**
     * Jours fériés français, par année
     *
     * @var array
     */
    public static $frenchPublicHolidaysByYear = [];

    /**
     * Construit un calendrier de Septembre de l'année donnée à Juin de l'année suivante.
     * Retourne un tableau de jours du mois regroupés par mois :
     * [
     *     'septembre' => [
     *         '01 sa' => [],
     *         '02 di' => [],
     *         '03 lu' => [],
     *         ...
     *     ],
     *     'octobre' => [
     *         '01 lu' => [],
     *         '02 ma' => [],
     *         '03 me' => [],
     *         ...
     *     ],
     *     ...
     * ]
     *
     * @param integer        $year
     * @param \Carbon\Carbon $lessonsFromDate
     * @param \Carbon\Carbon $lessonsToDate
     *
     * @return array
     */
    public static function buildAnnualCalendar($year, Carbon $lessonsFromDate, Carbon $lessonsToDate)
    {
        $annualCalendar = [];

        $nextYear = $year + 1;
        // On construit un calendrier du 1er sept au 30 juin.
        $calendarStartDate = Carbon::createFromFormat('Y-m-d', "$year-9-1");
        $calendarEndDate = Carbon::createFromFormat('Y-m-d', "$nextYear-6-30");

        do {
            $event = []; // par défaut : pas d'événement

            $date = $calendarStartDate;
            $monthLabel = self::buildCalendarDateMonthLabel($date); // ex: "septembre"
            $dayLabel = self::buildCalendarDateDayLabel($date); // ex: "01 lun"

            $classNames = [];
            if ($date->isSunday()) {
                $classNames[] = 'is-sunday';
            }
            $publicHolidaysData = self::getFrenchPublicHolidaysFromDate($date);
            if (!empty($publicHolidaysData)) {
                $classNames[] = 'is-public-holiday';
                $event = [
                    'name'     => $publicHolidaysData['name'],
                    'color'    => '',
                    'duration' => 1,
                ];
            }
            if ($date->lt($lessonsFromDate) || $date->gt($lessonsToDate)) {
                $classNames[] = 'no-lessons';
            }
            if ($date->isToday()) {
                $classNames[] = 'is-today';
            } elseif ($date->isPast()) {
                $classNames[] = 'is-past';
            }

            $annualCalendar[$monthLabel][$dayLabel] = [
                'event'             => $event,
                'classNames'        => $classNames,
            ];

            $date->addDay();
        } while ($date <= $calendarEndDate);

        return $annualCalendar;
    }

    /**
     * Construit le label du mois à partir d'une date, tel qu'il sera affiché sur le calendrier d'une année.
     * Ex: "septembre", "juin"
     *
     * @param \Carbon\Carbon $date
     *
     * @return string
     */
    public static function buildCalendarDateMonthLabel(Carbon $date)
    {

        $monthLabel = $date->formatLocalized('%B');

        return $monthLabel;
    }

    /**
     * Construit le label du jour à partir d'une date, tel qu'il sera affiché sur le calendrier d'une année.
     * Ex: "01 sa", "02 di", "03 lu", "04 mar", "05 me", "06 je", "07 ve"
     *
     * @param \Carbon\Carbon $date
     *
     * @return string
     */
    public static function buildCalendarDateDayLabel(Carbon $date)
    {
        $localizedDayOfWeek = substr($date->formatLocalized('%a'), 0, 2); // ex: "lu", "ma", ...
        $dayLabel = $date->format('d') . ' ' . $localizedDayOfWeek;

        return $dayLabel;
    }


    /**
     * Retourne les éventuelles infos de jour férié à partir de la date donnée.
     *
     * @param \Carbon\Carbon $date
     *
     * @return array
     */
    protected static function getFrenchPublicHolidaysFromDate(Carbon $date)
    {
        $frenchPublicHolidays = self::getFrenchPublicHolidaysFromYear($date->year);
        $frenchPublicHolidaysKey = $date->format('m-d');

        return isset($frenchPublicHolidays[$frenchPublicHolidaysKey]) ? $frenchPublicHolidays[$frenchPublicHolidaysKey] : [];
    }

    /**
     * Retourne la liste des jours fériés de l'année donnée.
     *
     * @param integer $year
     *
     * @return mixed
     */
    protected static function getFrenchPublicHolidaysFromYear($year)
    {
        if (!isset(self::$frenchPublicHolidaysByYear[$year])) {
            // Jours fériés fixes en France
            self::$frenchPublicHolidaysByYear[$year] = [
                '01-01' => ['date' => Carbon::create($year, 1, 1, 0, 0, 0), 'name' => 'Jour de l\'An'],
                '05-01' => ['date' => Carbon::create($year, 5, 1, 0, 0, 0), 'name' => 'Fête du travail'],
                '05-08' => ['date' => Carbon::create($year, 5, 8, 0, 0, 0), 'name' => 'Victoire 45'],
                '07-14' => ['date' => Carbon::create($year, 7, 14, 0, 0, 0), 'name' => 'Fête Nat.'],
                '08-15' => ['date' => Carbon::create($year, 8, 15, 0, 0, 0), 'name' => 'Assomption'],
                '11-01' => ['date' => Carbon::create($year, 11, 1, 0, 0, 0), 'name' => 'Toussaint'],
                '11-11' => ['date' => Carbon::create($year, 11, 11, 0, 0, 0), 'name' => 'Armistice 18'],
                '12-25' => ['date' => Carbon::create($year, 12, 25, 0, 0, 0), 'name' => 'Noël'],
            ];

            // Jours fériés basés sur Pâques
            $easterDate = self::getEasterDate($year);
            $easterMonday = clone $easterDate;
            $easterMonday->addDay(1);

            $easterThursday = clone $easterDate;
            $easterThursday->addDays(39);

            $pentecostMonday = clone $easterDate;
            $pentecostMonday->addDays(50);

            // Ajout du Lundi de Pâques et du Jeudi de l'ascension
            self::$frenchPublicHolidaysByYear[$year][$easterMonday->format('m-d')] = ['date' => $easterMonday, 'name' => 'Pâques'];
            self::$frenchPublicHolidaysByYear[$year][$easterThursday->format('m-d')] = ['date' => $easterThursday, 'name' => 'Ascension'];

            // Ajout du Lundi de Pentecôte
            self::$frenchPublicHolidaysByYear[$year][$pentecostMonday->format('m-d')] = ['date' => $pentecostMonday, 'name' => 'Pentecôte'];

            ksort(self::$frenchPublicHolidaysByYear[$year]);
        }

        return self::$frenchPublicHolidaysByYear[$year];
    }

    /**
     * Returne la date du dimanche de Pâques de l'année donnée.
     *
     * @param integer $year
     *
     * @return Carbon
     */
    protected static function getEasterDate($year)
    {
        // Calcul du jour de Pâques - basé sur l'équinoxe de printemps qui tombe toujours le 21 mars
        $springEquinox = Carbon::createFromFormat('Y-m-d', "$year-03-21");
        $easterDate = clone $springEquinox;
        $easterDate->addDays(easter_days($year)); // On ajoute le nb de jours qui sépare l'équinoxe de Pâques

        return $easterDate;
    }
}
