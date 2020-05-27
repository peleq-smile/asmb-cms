<?php

namespace Bundle\Asmb\Visitors\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table sur les visiteurs du site.
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
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('ip', 'string', ['length' => 45, 'notnull' => true]);
        $this->table->addColumn('datetime', 'datetime', []);
        $this->table->addColumn('isActive', 'boolean', ['default' => true]);
        $this->table->addColumn('username', 'string', ['length' => 32, 'notnull' => false]);
        $this->table->addColumn('browserName', 'string', ['length' => 20, 'notnull' => true]);
        $this->table->addColumn('browserVersion', 'string', ['length' => 20, 'notnull' => true]);
        $this->table->addColumn('osName', 'string', ['length' => 20, 'notnull' => true]);
        $this->table->addColumn('terminal', 'string', ['length' => 20, 'notnull' => true]);
        $this->table->addColumn('geolocalization', 'string', ['length' => 50, 'notnull' => true]);
        $this->table->addColumn('dailyVisitsCount', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('httpUserAgent', 'string',   ['length' => 255]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addUniqueIndex(['ip', 'browserName', 'browserVersion', 'osName', 'terminal']);
        $this->table->addIndex(['isActive']);
        $this->table->addIndex(['browserName']);
        $this->table->addIndex(['osName']);
        $this->table->addIndex(['terminal']);
        $this->table->addIndex(['geolocalization']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }

}
