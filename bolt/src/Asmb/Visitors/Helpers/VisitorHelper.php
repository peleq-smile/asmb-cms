<?php

namespace Bundle\Asmb\Visitors\Helpers;

use Bundle\Asmb\Visitors\Entity\AbstractStatisticsPerSeason;
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

    static public $desktopVisitorTotalCount;

    static public $notDesktopVisitorTotalCount;

    private static $knownBrowsers = [
        'Seamonkey' => ['seamonkey/([\d\.-_]+)'],
        'Chromium' => ['chromium/([\d\.-_]+)'],
        'Firefox' => ['firefox/([\d\.-_]+)'],
        'Chrome' => ['chrome/([\d\.-_]+)'],
        'Safari' => ['safari/([\d\.-_]+)'],
        'Opera' => ['opr/([\d\.-_]+)', 'opera/([\d\.-_]+)'],
        'IE' => [';\s?msie ([\d\.-_]+);', ';\s?Trident/[\d\.-_]+'],
        'Edge' => [';\s?edge ([\d\.-_]+);'],
    ];

    private static $knownOs = [
        'Windows 10' => ['windows nt 10'],
        'Windows 8.1' => ['windows nt 6.3'],
        'Windows 8' => ['windows nt 6.2'],
        'Windows 7' => ['windows nt 6.1'],
        'Windows Vista' => ['windows nt 6.0'],
        'Windows Server 2003/XP x64' => ['windows nt 5.2'],
        'Windows XP' => ['windows nt 5.1', 'windows xp'],
        'Mac OS X' => ['macintosh|mac os x'],
        'Mac OS 9' => ['mac_powerpc'],
        'Linux/Unix' => ['ubuntu', 'linux', 'unix'],
    ];

    private static $knownTerminals = [
        'Android' => ['android'],
        'BlackBerry' => ['blackberry'],
        'iPhone' => ['iphone'],
        'iPad' => ['ipad'],
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

    /**
     * Retourne les données à afficher dans un graphique de stats des visites/visiteurs par jour sur le dernier mois.
     *
     * @param AbstractStatisticsPerSeason $statisticsPerSeason
     * @param string                      $color
     * @param int                         $todayCount
     * @return array
     */
    public static function getLastMonthDataForChart(AbstractStatisticsPerSeason $statisticsPerSeason, string $color, int $todayCount)
    {
        $data = [];
        $yesterday = Carbon::yesterday()->setTime(23, 59, 59);

        // 1. On affiche les données du mois précédent
        $firstDayOfChart = Carbon::today()->modify('-1 month'); // Même jour qu'auj mais du mois précédent
        $lastDayOfChart = $yesterday;

        $dayOfChart = $firstDayOfChart->copy();

        $bottomCount = null;
        $topCount = 2;
        $bottomIndexes = [];
        $topIndexes = [];
        $idx = 0;
        do {
            $columnOfDay = 'dayOfMonth' . sprintf("%02d", $dayOfChart->day);
            $statisticCount = $statisticsPerSeason->get($columnOfDay);

            // On formate la date en "1er janv. 2020", "10 févr. 2020", etc.
            $shortFormat = ($dayOfChart->day === 1) ? '%eer %b' : '%e %b';
            $shortFormattedDayOfChart = $dayOfChart->formatLocalized($shortFormat);

            /** @see https://canvasjs.com/php-charts/chart-index-data-label/ */
            $data[$idx] = [
                'label' => $shortFormattedDayOfChart, // x représente le jour du mois
                'y' => $statisticCount, // y réprésente le nb de visiteurs
            ];

            // Calcul du BOTTOM
            if ($statisticCount > 0 && ($bottomCount === null || $statisticCount < $bottomCount)) {
                $bottomIndexes = [$idx];
                $bottomCount = $statisticCount;
            } elseif ($statisticCount === $bottomCount) {
                $bottomIndexes[] = $idx;
            }

            // Calcul du TOP
            if ($statisticCount > $topCount) {
                $topIndexes = [$idx];
                $topCount = $statisticCount;
            } elseif ($statisticCount === $topCount) {
                $topIndexes[] = $idx;
            }

            $dayOfChart->addDay(); // On incrémente d'1 jour
            $idx++;
        } while ($dayOfChart->lessThanOrEqualTo($lastDayOfChart));

        // On ajoute les stats du jour
        $data[$idx] = [
            'label' => $dayOfChart->formatLocalized($shortFormat),
            'y' => $todayCount,
            'color' => $color . '5c', // concaténer '5c' ajoute de la transparence
        ];

        foreach ($bottomIndexes as $i) {
            $data[$i]['indexLabel'] = "☁$bottomCount";
        }
        foreach ($topIndexes as $i) {
            $data[$i]['indexLabel'] = "☀$topCount";
        }

        return $data;
    }

    /**
     * Retourne les données à afficher dans un graphique de stats des visites/visiteurs par mois sur la dernière saison.
     *
     * @param AbstractStatisticsPerSeason $statisticsPerSeason
     * @param string                      $color
     * @return array
     */
    public static function getLastSeasonDataForChart(AbstractStatisticsPerSeason $statisticsPerSeason, string $color)
    {
        $data = [];
        $bottomCount = null;
        $topCount = 2;
        $bottomIndexes = [];
        $topIndexes = [];
        $idx = 0;

        // Données des mois de septembre à décembre
        self::addDataChartBetweenMonths(
            $statisticsPerSeason,
            $data,
            $idx,
            $statisticsPerSeason->getSeasonStartYear(),
            9,
            12,
            $topCount,
            $bottomCount,
            $topIndexes,
            $bottomIndexes,
            $color
        );

        // Données des mois de janvier à août
        self::addDataChartBetweenMonths(
            $statisticsPerSeason,
            $data,
            $idx,
            $statisticsPerSeason->getSeasonEndYear(),
            1,
            8,
            $topCount,
            $bottomCount,
            $topIndexes,
            $bottomIndexes,
            $color
        );

        foreach ($bottomIndexes as $i) {
            $data[$i]['indexLabel'] = "☁$bottomCount";
        }
        foreach ($topIndexes as $i) {
            $data[$i]['indexLabel'] = "☀$topCount";
        }

        return $data;
    }

    protected static function addDataChartBetweenMonths(
        AbstractStatisticsPerSeason $statisticsPerSeason,
        array &$data,
        int &$idx,
        int $year,
        int $startMonth,
        int $endMonth,
        int &$topCount,
        ?int &$bottomCount,
        array &$topIndexes,
        array &$bottomIndexes,
        string $color
    ) {
        for ($month = $startMonth; $month <= $endMonth; $month++) {
            $columnOfMonth = 'month' . sprintf("%02d", $month);
            $count = $statisticsPerSeason->get($columnOfMonth);
            $monthOfChart = Carbon::create($year, $month, 1);

            // On formate la date en 'janv. 2020'
            $formattedMonthOfChart = $monthOfChart->formatLocalized('%b %Y');
            $data[$idx] = [
                'label' => $formattedMonthOfChart,
                'y' => $count,
            ];

            // Mois courant : on met de la transparence sur la couleur
            if (Carbon::today()->month === $month) {
                $data[$idx]['color'] = $color . '5c'; // concaténer '5c' ajoute de la transparence
            } else {
                // Calcul du BOTTOM (on exclut le mois en cours, qui a des chances d'être bottom !)
                if ($count > 0 && ($bottomCount === null || $count < $bottomCount)) {
                    $bottomIndexes = [$idx];
                    $bottomCount = $count;
                } elseif ($count === $bottomCount) {
                    $bottomIndexes[] = $idx;
                }
            }

            // Calcul du TOP
            if ($count > $topCount) {
                $topIndexes = [$idx];
                $topCount = $count;
            } elseif ($count === $topCount) {
                $topIndexes[] = $idx;
            }

            $idx++;
        }
    }

    public static function getDesktopDataForChart(array $desktopVisitorData)
    {
        $data = [];
        self::$desktopVisitorTotalCount = 0;

        foreach ($desktopVisitorData as $idx => $row) {
            $browserName = (in_array($row['browserName'], ['-', 'Other'])) ? 'Autre' : $row['browserName'];
            $osName = (in_array($row['osName'], ['-', 'Other'])) ? 'Autre' : $row['osName'];
            $data[] = [
                'label' => "$browserName - $osName",
                'y' => $row['count'],
            ];

            self::$desktopVisitorTotalCount += $row['count'];
        }

        return $data;
    }

    public static function getNotDesktopDataForChart(array $notDesktopVisitorData)
    {
        $data = [];
        self::$notDesktopVisitorTotalCount = 0;

        foreach ($notDesktopVisitorData as $idx => $row) {
            $browserName = (in_array($row['browserName'], ['-', 'Other'])) ? 'Autre' : $row['browserName'];
            $terminal = (in_array($row['terminal'], ['-', 'Other'])) ? 'Autre' : $row['terminal'];
            $data[] = [
                'label' => "$browserName - $terminal",
                'y' => $row['count'],
            ];

            self::$notDesktopVisitorTotalCount += $row['count'];
        }

        return $data;
    }
}