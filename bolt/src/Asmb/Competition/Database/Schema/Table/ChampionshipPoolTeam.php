<?php

namespace Bundle\Asmb\Competition\Database\Schema\Table;

use Bolt\Storage\Database\Schema\Table\BaseTable;

/**
 * Table des équipes de poules.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class ChampionshipPoolTeam extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('pool_id', 'integer', ['notnull' => true]);
        $this->table->addColumn('name_fft', 'string', ['length' => 255, 'notnull' => true]);
        $this->table->addColumn('name', 'string', ['length' => 20, 'notnull' => false]);
        $this->table->addColumn('is_club', 'boolean', ['default' => 0, 'notnull' => true]);
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
        $this->table->addUniqueIndex(['name_fft', 'pool_id']);
        $this->table->addUniqueIndex(['name', 'pool_id']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
