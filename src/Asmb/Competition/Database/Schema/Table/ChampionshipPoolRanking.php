<?php

namespace Bundle\Asmb\Competition\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table for championship matches data.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class ChampionshipPoolRanking extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('pool_id', 'integer', ['notnull' => true]);
        $this->table->addColumn('team_name_fft', 'string', ['length' => 255, 'notnull' => true]);
        $this->table->addColumn('team_is_club', 'boolean', ['default' => false, 'notnull' => true]);
        $this->table->addColumn('points', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('days_played', 'integer', ['notnull' => true, 'default' => 0, 'unsigned' => true]);
        $this->table->addColumn('match_diff', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('set_diff', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('game_diff', 'integer', ['notnull' => true, 'default' => 0]);
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
        $this->table->addUniqueIndex(['pool_id', 'team_name_fft']);
        $this->table->addIndex(['team_is_club']);
        $this->table->addIndex(['points', 'days_played', 'match_diff', 'set_diff', 'game_diff']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
