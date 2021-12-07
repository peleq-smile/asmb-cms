<?php

namespace Bundle\Asmb\Competition\Parser\Championship;

use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolRanking;

/**
 * Service d'extraction des données FFT pour la récupération du classement dans une poule.
 *
 * @copyright 2019
 */
class TenupPoolRankingParser extends AbstractTenupParser
{
    protected function doParse(Championship $championship, Pool $pool, array $jsonData): ?array
    {
        $poolRankings = [];

        $jsonDataRows = [];
        if (isset($jsonData['results']['components']['classement']['component_data']['rows'])) {
            $jsonDataRows = $jsonData['results']['components']['classement']['component_data']['rows'];
        } elseif (isset($jsonData['results']['components']['classement']['rows'])) {
            $jsonDataRows = $jsonData['results']['components']['classement']['rows'];
        }

        foreach ($jsonDataRows as $row) {
            $poolRanking = new PoolRanking();
            $poolRanking->setRanking($row['classement']);
            $poolRanking->setTeamNameFft($row['name']);
            $poolRanking->setPoolId($pool->getId());
            $poolRanking->setPoints($row['points']);

            if (isset($row['rencontres'])) {
                $daysPlayed = (int) explode(' ',$row['rencontres'])[0];
                $poolRanking->setDaysPlayed($daysPlayed);
            }

            if (isset($row['match_avg'])) {
                $matchDiff = (int) explode(' ',$row['match_avg'])[0];
                $poolRanking->setMatchDiff($matchDiff);
            }

            if (isset($row['sets_avg'])) {
                $setDiff = (int) explode(' ',$row['sets_avg'])[0];
                $poolRanking->setSetDiff($setDiff);
            }

            if (isset($row['jeux_avg'])) {
                $gameDiff = (int) explode(' ',$row['jeux_avg'])[0];
                $poolRanking->setGameDiff($gameDiff);
            }

            $poolRankings[] = $poolRanking;
        }

        return $poolRankings;
    }
}
