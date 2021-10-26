<?php

namespace Bundle\Asmb\Competition\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table bolt_tournament_box
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2020
 */
class TournamentBox extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('table_id', 'integer');
        $this->table->addColumn('date', 'date', ['notnull' => false, 'default' => null]);
        $this->table->addColumn('time', 'time', ['notnull' => false, 'default' => null]);
        $this->table->addColumn('score', 'string', ['length' => 20, 'notnull' => false]);
        $this->table->addColumn('qualif_in', 'smallint', ['notnull' => false, 'default' => null]);
        $this->table->addColumn('player_name', 'string', ['length' => 30, 'notnull' => false]);
        $this->table->addColumn('player_rank', 'string', ['length' => 5, 'notnull' => false]);
        $this->table->addColumn('player_club', 'string', ['length' => 20, 'notnull' => false]);
        $this->table->addColumn('box_top_id', 'integer', ['notnull' => false, 'default' => null]);
        $this->table->addColumn('box_btm_id', 'integer', ['notnull' => false, 'default' => null]);
        $this->table->addColumn('qualif_out', 'smallint', ['notnull' => false, 'default' => null]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['player_name', 'player_rank', 'player_club']);
        $this->table->addIndex(['date']);
        $this->table->addIndex(['time']);
        $this->table->addIndex(['qualif_in']);
        $this->table->addIndex(['qualif_out']);
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
            'bolt_tournament_table',
            ['table_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $this->table->addForeignKeyConstraint(
            'bolt_tournament_box',
            ['box_top_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $this->table->addForeignKeyConstraint(
            'bolt_tournament_box',
            ['box_btm_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }
}
