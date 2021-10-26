<?php

namespace Bundle\Asmb\Visitors\Repository;

use Bolt\Storage\Entity\Entity;
use Bolt\Storage\Repository;
use Bundle\Asmb\Visitors\Entity\VisitorStatistics;
use Carbon\Carbon;

/**
 * Trait pour les repositories d'entité de statistiques par saison.
 */
abstract class AbstractStatisticsPerSeasonRepository extends Repository
{
    /**
     * {@inheritDoc}
     */
    public function save($entity, $silent = null)
    {
        $entity->setUpdatedAt();

        $response = parent::save($entity, $silent);

        return $response;
    }

    /**
     * Retourne l'objet représentant les statistiques de la saison en cours.
     * Créé l'entrée en base si elle n'existe pas encore.
     *
     * @param string|null $season
     *
     * @return Entity
     */
    public function findOfSeason(string $season = null)
    {
        if (null === $season) {
            $currentYear = Carbon::now()->year;
            $currentMonth = Carbon::now()->month;

            if ($currentMonth > 8) {
                // De septembre à décembre
                $season = $currentYear . '_' . ($currentYear + 1);
            } else {
                // De janvier à août
                $season = ($currentYear - 1) . '_' . ($currentYear);
            }
        }

        /** @var Entity $statistics */
        $statistics = $this->findOneBy(['season' => $season]);
        if (false === $statistics) {
            // Les stats de la saison en cours n'existent pas encore, on créé l'entrée
            /** @var VisitorStatistics $statistics */
            $statistics = $this->create(['season' => $season]);
            $statistics->setMaxSimultaneous(0);
            $this->save($statistics, true);
        }

        return $statistics;
    }
}