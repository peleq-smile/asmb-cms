<?php

namespace Bundle\Asmb\Visitors\Repository;

use Bolt\Storage\Repository;
use Bundle\Asmb\Visitors\Entity\VisitorStatistics;
use Carbon\Carbon;

/**
 * Repository pour les statistiques des visiteurs par saison.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2020
 */
class VisitorStatisticsRepository extends Repository
{
    /**
     * {@inheritDoc}
     */
    public function save($entity, $silent = null)
    {
        /** @var VisitorStatistics $entity */
        $entity->setUpdatedAt();

        return parent::save($entity, $silent);
    }

    /**
     * Met à jour le nombre de visiteurs simultanés de la saison en cours.
     *
     * @param int $simultaneous
     */
    public function updateMaxSimultaneous(int $simultaneous)
    {
        $statistics = $this->findStatisticsOfSeason();

        if ($simultaneous > $statistics->getMaxSimultaneous()) {
            $statistics->setMaxSimultaneous($simultaneous);
            $this->save($statistics, true);
        }
    }

    /**
     *
     *
     * @param string|null $season
     * @return VisitorStatistics|false|object
     */
    public function findStatisticsOfSeason(string $season = null)
    {
        if (null === $season) {
            $currentYear = Carbon::now()->year;
            $currentMonth = Carbon::now()->month;
            $currentDay = Carbon::now()->day;

            if ($currentMonth > 8) {
                // De septembre à décembre
                $season = $currentYear . '_' . ($currentYear + 1);
            } else {
                // De janvier à août
                $season = ($currentYear - 1) . '_' . ($currentYear);
            }
        }

        $visitorStatistics = $this->findOneBy(['season' => $season]);
        if (false === $visitorStatistics) {
            // Les stats de la saison en cours n'existent pas encore, on créé l'entrée
            $visitorStatistics = new VisitorStatistics();
            $visitorStatistics->setSeason($season);
        }

        return $visitorStatistics;
    }
}
