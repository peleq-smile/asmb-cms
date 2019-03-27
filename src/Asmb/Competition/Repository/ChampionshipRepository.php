<?php

namespace Bundle\Asmb\Competition\Repository;

use Bolt\Storage\Repository;

/**
 * Repository for championships.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class ChampionshipRepository extends Repository
{
    /**
     * Retourne tous les championnats, par ordre d'année décroissante puis nom croissant.
     *
     * @return bool|mixed|object[]
     */
    public function findAll()
    {
        $championships = [];

        $qb = $this->findWithCriteria([]);
        $qb->orderBy('year', 'DESC');
        $qb->orderBy('name', 'ASC');
        $result = $qb->execute()->fetchAll();

        if ($result) {
            $championships = $this->hydrateAll($result, $qb);
        }

        return $championships;
    }
}
