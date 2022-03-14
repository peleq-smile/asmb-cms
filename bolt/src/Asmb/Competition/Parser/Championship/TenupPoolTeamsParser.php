<?php

namespace Bundle\Asmb\Competition\Parser\Championship;

use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Entity\Championship\PoolTeam;

/**
 * Service d'extraction des données FFT pour la récupération des équipes de poules, sur Tenup
 *
 * @copyright 2021
 */
class TenupPoolTeamsParser extends AbstractTenupJsonParser
{
    public function doParse(Championship $championship, Pool $pool, ?PoolMeeting $poolMeeting, array $jsonData): ?array
    {
        $poolTeams = [];

        if (isset($jsonData['results']['raw_data']['equipes'])) {
            foreach ($jsonData['results']['raw_data']['equipes'] as $equipe) {
                $poolTeam = new PoolTeam();
                $poolTeam->setPoolId($pool->getId());
                $poolTeam->setNameFft($equipe['name']);

                $poolTeams[] = $poolTeam;
            }
        }

        return $poolTeams;
    }
}
