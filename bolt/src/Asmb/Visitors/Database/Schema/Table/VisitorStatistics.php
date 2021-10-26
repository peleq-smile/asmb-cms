<?php

namespace Bundle\Asmb\Visitors\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table des statistiques sur les visiteurs par saison.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2020
 */
class VisitorStatistics extends BaseTable
{
    use StatisticsPerSeasonTrait;

    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->addPerSeasonColumns();

        $this->table->addColumn('maxSimultaneous', 'integer', ['notnull' => true]);
    }
}
