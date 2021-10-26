<?php
namespace Bundle\Asmb\Visitors\Entity;

/**
 * Entité pour les statistiques sur les visiteurs par saison.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2020
 */
class VisitorStatistics extends AbstractStatisticsPerSeason
{
    protected $maxSimultaneous = 0;

    /**
     * @return mixed
     */
    public function getMaxSimultaneous()
    {
        return $this->maxSimultaneous;
    }

    /**
     * @param mixed $maxSimultaneous
     */
    public function setMaxSimultaneous($maxSimultaneous): void
    {
        $this->maxSimultaneous = $maxSimultaneous;
    }
}
