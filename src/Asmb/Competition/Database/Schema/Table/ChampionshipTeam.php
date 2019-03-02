<?php

namespace Bundle\Asmb\Competition\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table for championship teams data.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class ChampionshipTeam extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $this->table->addColumn('short_name', 'string', ['length' => 20, 'notnull' => false]);
        $this->table->addColumn('category_name', 'string', ['length' => 20, 'notnull' => false]);
        $this->table->addColumn('is_club', 'boolean', ['default' => false, 'notnull' => true]);
        $this->table->addColumn('link_fft', 'string', ['length' => 255, 'notnull' => false]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addForeignKeyConstraints()
    {
        $this->table->addForeignKeyConstraint(
            'bolt_championship_category',
            ['category_name'],
            ['name'],
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['category_name']);
        $this->table->addIndex(['is_club']);
        $this->table->addUniqueIndex(['category_name', 'name']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
