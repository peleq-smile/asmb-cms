<?php

namespace Bundle\Asmb\Competition\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table bolt_tournament
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2020
 */
class Tournament extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('year', 'smallint', ['notnull' => true]);
        $this->table->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $this->table->addColumn('short_name', 'string', ['length' => 20, 'notnull' => false]);
        $this->table->addColumn('from_date', 'date', ['notnull' => true]);
        $this->table->addColumn('to_date', 'date', ['notnull' => true]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addUniqueIndex(['year', 'name']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
