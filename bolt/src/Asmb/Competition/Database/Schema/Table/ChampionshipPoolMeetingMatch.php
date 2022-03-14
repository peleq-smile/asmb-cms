<?php

namespace Bundle\Asmb\Competition\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2022
 */
class ChampionshipPoolMeetingMatch extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('pool_meeting_id', 'integer', ['notnull' => true]);
        $this->table->addColumn('type', 'string', ['length' => 2, 'notnull' => true]);
        $this->table->addColumn('label', 'string', ['length' => 20, 'notnull' => true]);
        $this->table->addColumn('position', 'smallint', ['notnull' => true]);
        $this->table->addColumn('home_player_name', 'string', ['length' => 100, 'notnull' => true]);
        $this->table->addColumn('home_player_rank', 'string', ['length' => 10, 'notnull' => true]);
        $this->table->addColumn('visitor_player_name', 'string', ['length' => 100, 'notnull' => true]);
        $this->table->addColumn('visitor_player_rank', 'string', ['length' => 10, 'notnull' => true]);
        $this->table->addColumn('home_player2_name', 'string', ['length' => 100, 'notnull' => false]);
        $this->table->addColumn('home_player2_rank', 'string', ['length' => 10, 'notnull' => false]);
        $this->table->addColumn('visitor_player2_name', 'string', ['length' => 100, 'notnull' => false]);
        $this->table->addColumn('visitor_player2_rank', 'string', ['length' => 10, 'notnull' => false]);
        $this->table->addColumn('score', 'string', ['length' => 30, 'notnull' => false]);
        $this->table->addColumn('created_at', 'datetime', ['notnull' => true]);
        $this->table->addColumn('updated_at', 'datetime', ['notnull' => true]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['pool_meeting_id']);
        $this->table->addIndex(['position']);
        $this->table->addUniqueIndex(['pool_meeting_id', 'label']);
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
            'bolt_championship_pool_meeting',
            ['pool_meeting_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }
}
