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
     * Get days count for the pool, according to given teams count.
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
     * Get matches count per day for this pool, according to given teams count.
     *
     * @param int $teamsCount
     *
     * @return int
     */
    public static function getMatchesCountPerDay($teamsCount)
    {
        $matchesCountPerDay = (int) floor($teamsCount / 2);

        return $matchesCountPerDay;
    }
}
