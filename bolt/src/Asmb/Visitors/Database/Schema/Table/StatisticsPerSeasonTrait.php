<?php
namespace Bundle\Asmb\Visitors\Database\Schema\Table;

/**
 * Trait pour la création de tables en base de statistiques sur les visites/visiteurs du site.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2020
 */
trait StatisticsPerSeasonTrait
{
    /**
     * {@inheritdoc}
     */
    protected function addPerSeasonColumns()
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);

        // Pour contenir par exemple '2019-2020'
        $this->table->addColumn('season', 'string', ['length' => 9, 'notnull' => true]);

        $this->table->addColumn('dayOfMonth01', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth02', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth03', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth04', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth05', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth06', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth07', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth08', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth09', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth10', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth11', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth12', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth13', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth14', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth15', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth16', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth17', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth18', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth19', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth20', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth21', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth22', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth23', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth24', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth25', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth26', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth27', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth28', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth29', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth30', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('dayOfMonth31', 'integer', ['notnull' => true, 'default' => 0]);

        $this->table->addColumn('month09', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('month10', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('month11', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('month12', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('month01', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('month02', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('month03', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('month04', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('month05', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('month06', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('month07', 'integer', ['notnull' => true, 'default' => 0]);
        $this->table->addColumn('month08', 'integer', ['notnull' => true, 'default' => 0]);

        $this->table->addColumn('updated_at', 'datetime', ['notnull' => false, 'default' => null]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addUniqueIndex(['season']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }
}
