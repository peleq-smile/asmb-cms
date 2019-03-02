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
     * Return all championship ordered by year, from newest to oldest.
     *
     * @return bool|mixed|object[]
     */
    public function findAll()
    {
        return $this->findBy([], ['year', 'DESC']);
    }
}
