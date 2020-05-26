<?php

namespace Bundle\Asmb\Visitors\Repository;

/**
 * Repository pour les statistiques des visiteurs par saison.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2020
 */
class VisitorStatisticsRepository extends AbstractStatisticsPerSeasonRepository
{
    /**
     * Met à jour le nombre de visiteurs simultanés de la saison en cours.
     *
     * @param int $simultaneous
     */
    public function updateMaxSimultaneous(int $simultaneous)
    {
        $statistics = $this->findOfSeason();

        if ($simultaneous > $statistics->getMaxSimultaneous()) {
            $statistics->setMaxSimultaneous($simultaneous);
            $this->save($statistics, true);
        }
    }
}
