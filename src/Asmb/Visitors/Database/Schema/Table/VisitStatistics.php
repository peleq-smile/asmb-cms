<?php

namespace Bundle\Asmb\Visitors\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table des statistiques sur les visites sur le site, par saison.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2020
 */
class VisitStatistics extends BaseTable
{
    use StatisticsPerSeasonTrait;

    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->addPerSeasonColumns();
    }
}
