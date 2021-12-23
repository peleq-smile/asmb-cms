<?php

namespace Bundle\Asmb\Competition\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table for championship categories data.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class ChampionshipCategory extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('identifier', 'string', ['length' => 20, 'notnull' => true]);
        $this->table->addColumn('name', 'string', ['length' => 20, 'notnull' => true]);
        $this->table->addColumn('position', 'smallint');
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['position']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['identifier']);
    }
}
