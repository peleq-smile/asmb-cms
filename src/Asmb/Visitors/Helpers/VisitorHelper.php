<?php

namespace Bundle\Asmb\Visitors\Helpers;

use Bundle\Asmb\Visitors\Entity\VisitorStatistics;
use Carbon\Carbon;

/**
 * Visitor helper, pour extraire des données de statistiques de visiteurs.
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2020
 */
class VisitorHelper
{
    /**
     * Temps d'expiration d'une visite, en minutes.
     *
     * @var int
     */
    static public $expirationTime = 30;

    static public $emptyValue = '-';

    private static $knownBrowsers = [
        'Seamonkey' => ['seamonkey/([\d\.-_]+)'],
        'Chromium'  => ['chromium/([\d\.-_]+)'],
        'Firefox'   => ['firefox/([\d\.-_]+)'],
        'Chrome'    => ['chrome/([\d\.-_]+)'],
        'Safari'    => ['safari/([\d\.-_]+)'],
        'Opera'     => ['opr/([\d\.-_]+)', 'opera/([\d\.-_]+)'],
        'IE'        => [';\s?msie ([\d\.-_]+);', ';\s?Trident/[\d\.-_]+'],
        'Edge'      => [';\s?edge ([\d\.-_]+);'],
    ];

    private static $knownOs = [
        'Windows 10'                 => ['windows nt 10'],
        'Windows 8.1'                => ['windows nt 6.3'],
        'Windows 8'                  => ['windows nt 6.2'],
        'Windows 7'                  => ['windows nt 6.1'],
        'Windows Vista'              => ['windows nt 6.0'],
        'Windows Server 2003/XP x64' => ['windows nt 5.2'],
        'Windows XP'                 => ['windows nt 5.1', 'windows xp'],
        'Mac OS X'                   => ['macintosh|mac os x'],
        'Mac OS 9'                   => ['mac_powerpc'],
        'Linux/Unix'                 => ['ubuntu', 'linux', 'unix'],
    ];

    private static $knownTerminals = [
        'Android'         => ['android'],
        'BlackBerry'      => ['blackberry'],
        'iPhone'          => ['iphone'],
        'iPad'            => ['ipad'],
        'Mobile (divers)' => ['webos', 'mobile'],
    ];

    /**
     * Extrait les infos sur le navigateur utilisé : nom et version.
     *
     * @return array
     */
    public static function getBrowserNameAndVersion()
    {
        $browserName = self::$emptyValue;
        $browserVersion = self::$emptyValue;

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $httpUserAgent = $_SERVER['HTTP_USER_AGENT'];

            $browserName = 'Other';

            foreach (self::$knownBrowsers as $knownBrowserName => $knownBrowserPatterns) {
                foreach ($knownBrowserPatterns as $knownBrowserPattern) {
                    $matches = [];
                    preg_match('#' . $knownBrowserPattern . '#i', $httpUserAgent, $matches);

                    if (count($matches) > 0) {
                        $browserName = $knownBrowserName;
                        if (strpos($knownBrowserPattern, '(')) {
                            $browserVersion = $matches[1]; // Le pattern permet de détecter la version du navigateur
                        }
                        break 2;
                    }
                }
            }
        }

        return [$browserName, $browserVersion];
    }

    /**
     * Extrait les infos sur le système d'exploitation utilisé + le terminal utilisé (mobile ou non).
     *
     * @return array
     */
    public static function getOsNameAndTerminal()
    {
        $osName = self::$emptyValue;
        $terminal = self::$emptyValue;

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $httpUserAgent = $_SERVER['HTTP_USER_AGENT'];

            foreach (self::$knownOs as $knownOsName => $knownOsPatterns) {
                foreach ($knownOsPatterns as $knownOsPattern) {
                    $matches = [];
                    preg_match('#' . $knownOsPattern . '#i', $httpUserAgent, $matches);

                    if (count($matches) > 0) {
                        $osName = $knownOsName;
                        break 2;
                    }
                }
            }

            $terminal = 'Desktop';
            foreach (self::$knownTerminals as $knownTerminal => $knownTerminalPatterns) {
                foreach ($knownTerminalPatterns as $knownTerminalPattern) {
                    $matches = [];
                    preg_match('#' . $knownTerminalPattern . '#i', $httpUserAgent, $matches);

                    if (count($matches) > 0) {
                        $terminal = $knownTerminal;
                        break 2;
                    }
                }
            }
        }

        return [$osName, $terminal];
    }

    public static function getLastMonthDataForChart(VisitorStatistics $visitorStatistics)
    {
        $data = [];

        $today = Carbon::now();
        // TODO dev à retirer - pour tests
//        $today = Carbon::createFromDate(2020, 04,30);
//        $today = Carbon::createFromDate(2020, 05,01);
        $yesterday = Carbon::now()->modify('-1 day')->setTime(23, 59, 59);

        // 1. On affiche les données du mois précédent
        $firstDayOfChart = Carbon::now()->modify('-1 month'); // Même jour qu'auj mais du mois précédent
        $lastDayOfChart = $yesterday;

        /** @var Carbon $dayOfChart */
        $dayOfChart = $firstDayOfChart->copy();
        $skipAsVisitorIsZero = true;
//        $topVisitorsCount = 2;
//        $topVisitorsIndexes = [];
        do {
            $columnOfDay = 'dayOfMonth' . sprintf("%02d", $dayOfChart->day);
            $visitorsCount = $visitorStatistics->get($columnOfDay);

            // On formate la date en "1er janv. 2020", "10 févr. 2020", etc.
            $format = ($dayOfChart->daysInMonth === 1) ? '%der %b %Y' : '%d %b %Y';
            $formattedDayOfChart = $dayOfChart->formatLocalized($format);

            if ($visitorsCount > 0 || !$skipAsVisitorIsZero) {
                $toolTipContent = "$formattedDayOfChart: $visitorsCount visiteur";
                $toolTipContent .= ($visitorsCount > 1) ? 's' : '';

                /** @see https://canvasjs.com/php-charts/chart-index-data-label/ */
                $data[] = [
                    'x' => ($dayOfChart->timestamp) * 1000, // x représente le jour du mois
                    'y' => $visitorsCount, // y réprésente le nb de visiteurs
                    'toolTipContent' => $toolTipContent,
                ];

//                if ($visitorsCount > $topVisitorsCount) {
//                    $topVisitorsDays = [$dayOfChart->day];
//                    $topVisitorsCount = $visitorsCount;
//                } elseif ($visitorsCount === $topVisitorsCount) {
//                    $topVisitorsDays[] = $dayOfChart->day;
//                }

                $skipAsVisitorIsZero = false;
            }

//            foreach ($topVisitorsDays as $day) {
//                $data[$day]['indexLabel'] = "Top : {$topVisitorsCount} visiteurs";
//            }

            $dayOfChart->addDay(); // On incrémente d'1 jour
        } while ($dayOfChart->lessThanOrEqualTo($lastDayOfChart));


        return $data;
    }
}