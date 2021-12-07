<?php

namespace Bundle\Asmb\Competition\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table for championships data.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class Championship extends BaseTable
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
        $this->table->addColumn('is_active', 'boolean', ['default' => 0, 'notnull' => true]);
        $this->table->addColumn('fft_id', 'string', ['length' => 12, 'notnull' => false]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addUniqueIndex(['year', 'name']);
        $this->table->addIndex(['is_active']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
