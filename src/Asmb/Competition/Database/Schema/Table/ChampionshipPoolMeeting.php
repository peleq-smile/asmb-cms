<?php

namespace Bundle\Asmb\Competition\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table for championship matches data.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class ChampionshipPoolMeeting extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('pool_id', 'integer');
        $this->table->addColumn('home_team_name_fft', 'string', ['length' => 255, 'notnull' => true]);
        $this->table->addColumn('visitor_team_name_fft', 'string', ['length' => 255, 'notnull' => true]);
        $this->table->addColumn('day', 'smallint', ['notnull' => true]);
        $this->table->addColumn('date', 'date', ['notnull' => false]);
        $this->table->addColumn('is_reported', 'boolean', ['default' => false, 'notnull' => true]);
        $this->table->addColumn('time', 'time', ['notnull' => false]);
        $this->table->addColumn('result', 'string', ['length' => 20, 'notnull' => false]);
        $this->table->addColumn('club_flag', 'smallint', ['default' => 0, 'notnull' => true]);
        $this->table->addColumn('params_fdm_fft', 'json', []);
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['pool_id']);
        $this->table->addIndex(['day']);
        $this->table->addIndex(['date', 'time']);
        $this->table->addIndex(['club_flag']);
        $this->table->addUniqueIndex(['pool_id', 'home_team_name_fft', 'visitor_team_name_fft']);
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
    }
}
