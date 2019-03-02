<?php

namespace Bundle\Asmb\Competition\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table for championship matches data.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class ChampionshipPoolDay extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('pool_id', 'integer');
        $this->table->addColumn('day', 'smallint', ['notnull' => true, 'unsigned' => true]);
        $this->table->addColumn('date', 'date', ['notnull' => false]);
        $this->table->addColumn('time', 'time', ['notnull' => false]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addForeignKeyConstraints()
    {
        $this->table->addForeignKeyConstraint(
            'bolt_championship_pool',
            ['pool_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['day']);
        $this->table->addIndex(['date']);
        $this->table->addUniqueIndex(['pool_id', 'day']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
