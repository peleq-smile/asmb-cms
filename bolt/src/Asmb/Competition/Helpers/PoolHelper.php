<?php

namespace Bundle\Asmb\Competition\Helpers;

/**
 * Pool helper.
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2019
 */
class PoolHelper
{
    /**
     * Compte et retourne le nombre de journées de rencontres, fonction du nombre d'équipes donné.
     *
     * @param int $teamsCount
     *
     * @return int
     */
    public static function getDaysCount($teamsCount)
    {
        $daysCount = ($teamsCount % 2 == 0) ? $teamsCount - 1 : $teamsCount;

        return $daysCount;
    }

    /**
     * Compte et retourne le nombre de rencontres à jouer par journée de championnat, fonction du nombre d'équipes donné.
     *
     * @param int $teamsCount
     *
     * @return int
     */
    public static function getMeetingsCountPerDay($teamsCount)
    {
        $meetingsCountPerDay = (int) floor($teamsCount / 2);

        return $meetingsCountPerDay;
    }

    /**
     * Compte et retourne le nombre de rencontres total d'une poule, fonction du nombre d'équipes donné.
     *
     * @param int $teamsCount
     *
     * @return int
     */
    public static function getTotalMeetingsCount($teamsCount)
    {
        return self::getDaysCount($teamsCount) * self::getMeetingsCountPerDay($teamsCount);
    }
}
