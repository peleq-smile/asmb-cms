<?php

namespace Bundle\Asmb\Competition\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table for championship pools data.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class ChampionshipPool extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('championship_id', 'integer');
        $this->table->addColumn('position', 'integer');
        $this->table->addColumn('category_name', 'string', ['length' => 20, 'notnull' => false]);
        $this->table->addColumn('name', 'string', ['length' => 20, 'notnull' => true]);
        $this->table->addColumn('division_fft_id', 'string', ['length' => 12, 'notnull' => false]);
        $this->table->addColumn('fft_id', 'string', ['length' => 12, 'notnull' => true]);
        $this->table->addColumn('updated_at', 'datetime', ['notnull' => false, 'default' => null]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addForeignKeyConstraints()
    {
        $this->table->addForeignKeyConstraint(
            'bolt_championship',
            ['championship_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
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
        $this->table->addIndex(['championship_id']);
        $this->table->addIndex(['position']);
        $this->table->addIndex(['category_name']);
        $this->table->addUniqueIndex(['championship_id', 'name', 'category_name']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
