<?php

namespace Bundle\Asmb\Visitors\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table for visitors data.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2018
 */
class Visitor extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id',            'integer',  ['autoincrement' => true]);
        $this->table->addColumn('ip',            'string',   ['length' => 45, 'notnull' => true]);
        $this->table->addColumn('httpUserAgent', 'string',   ['length' => 255]);
        $this->table->addColumn('datetime',      'datetime', []);
    }

    /**
     * Define the columns that require indexing.
     */
    protected function addIndexes()
    {
        $this->table->addUniqueIndex(['ip', 'httpUserAgent']);
        $this->table->addIndex(['ip', 'httpUserAgent']);
    }

    /**
     * Set the table's primary key.
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }

}
