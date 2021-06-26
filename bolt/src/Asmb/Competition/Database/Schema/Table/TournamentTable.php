<?php

namespace Bundle\Asmb\Competition\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table bolt_tournament_table
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2020
 */
class TournamentTable extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('tournament_id', 'integer');
        $this->table->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $this->table->addColumn('short_name', 'string', ['length' => 20, 'notnull' => false]);
        // Catégorie possible : 'M' pour Messieurs, 'D' pour Dames, 'J' pour Jeunes (?)
        $this->table->addColumn('category', 'string', ['length' => 1, 'notnull' => true, 'default' => 'M']);
        $this->table->addColumn('status', 'smallint', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('visible', 'boolean', ['default' => 1, 'notnull' => true]);
        $this->table->addColumn('updated_at', 'datetime', ['notnull' => false, 'default' => null]);
        $this->table->addColumn('updated_by', 'integer', ['notnull' => false, 'default' => null]);
        $this->table->addColumn('previous_table_id', 'integer', ['notnull' => false, 'default' => null]);
        $this->table->addColumn('position', 'integer', ['notnull' => true, 'default' => 0, 'unsigned' => true]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['category','name']);
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
            'bolt_tournament',
            ['tournament_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $this->table->addForeignKeyConstraint(
            'bolt_tournament_table',
            ['previous_table_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $this->table->addForeignKeyConstraint(
            'bolt_users',
            ['updated_by'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }
}
