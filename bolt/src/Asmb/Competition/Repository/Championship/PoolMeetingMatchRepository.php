<?php

namespace Bundle\Asmb\Competition\Repository\Championship;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeetingMatch;
use Bundle\Asmb\Competition\Entity\Championship\PoolRanking;
use Carbon\Carbon;

/**
 * Repository pour les matchs des rencontres entre équipes.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2022
 */
class PoolMeetingMatchRepository extends Repository
{
    /**
     * Sauvegarde les données des feuilles de matchs pour la rencontre d'id donnée.
     *
     * @param PoolMeetingMatch[] $poolMeetingMatches
     * @param int $poolMeetingId
     */
    public function saveAll(array $poolMeetingMatches, int $poolMeetingId)
    {
        foreach ($poolMeetingMatches as $poolMeetingMatch) {
            // Création ou mise à jour ?
            /** @var PoolMeetingMatch $existingPoolMeetingMatch */
            $existingPoolMeetingMatch = $this->findOneBy(
                [
                    'pool_meeting_id' => $poolMeetingId,
                    'label' => $poolMeetingMatch->getLabel(),
                ]
            );

            if (false !== $existingPoolMeetingMatch) {
                // Mise à jour : on spécifie l'id pour se mettre en mode "update" + date de màj
                $poolMeetingMatch->setId($existingPoolMeetingMatch->getId());
                $poolMeetingMatch->setCreatedAt($existingPoolMeetingMatch->getCreatedAt());
            }
            $this->save($poolMeetingMatch, true);
        }
    }
}
