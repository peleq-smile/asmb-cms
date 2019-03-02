<?php

namespace Bundle\Asmb\Competition\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table for championship matches data.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class ChampionshipMatch extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('pool_id', 'integer');
        $this->table->addColumn('day', 'smallint', ['notnull' => true]);
        $this->table->addColumn('date', 'date', ['notnull' => false]);
        $this->table->addColumn('time', 'time', ['notnull' => false]);
        $this->table->addColumn('position', 'integer', ['notnull' => true]);
        $this->table->addColumn('home_team_id', 'integer', ['notnull' => false]);
        $this->table->addColumn('visitor_team_id', 'integer', ['notnull' => false]);
        $this->table->addColumn('score_home', 'smallint', ['notnull' => false]);
        $this->table->addColumn('score_visitor', 'smallint', ['notnull' => false]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['pool_id']);
        $this->table->addIndex(['day']);
        $this->table->addIndex(['date']);
        $this->table->addIndex(['time']);
        $this->table->addIndex(['position']);
        $this->table->addIndex(['home_team_id']);
        $this->table->addIndex(['visitor_team_id']);
        $this->table->addUniqueIndex(['pool_id', 'home_team_id', 'visitor_team_id']);
        $this->table->addUniqueIndex(['pool_id', 'day', 'position']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
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

        $this->table->addForeignKeyConstraint(
            'bolt_championship_team',
            ['home_team_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );

        $this->table->addForeignKeyConstraint(
            'bolt_championship_team',
            ['visitor_team_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }
}
