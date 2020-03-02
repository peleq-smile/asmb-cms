<?php

namespace Bundle\Asmb\Competition\Repository\Tournament;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Tournament\Table;
use PDO;

/**
 * Repository pour les tableaux de tournois.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2020
 */
class TableRepository extends Repository
{
    /**
     * @return string
     */
    public function getAlias()
    {
        // L'expression 'table' est réservé, on surcharge l'alias du Repo pour
        // éviter les erreurs de syntaxe SQL
        return 'to_table';
    }

    public function findAndFetchSiblingTables($id)
    {
        /** @var Table $table */
        $table = $this->find($id);

        if ($table) {
            if (null !== $table->getPreviousTableId()) {
                /** @var Table $previousTable */
                $previousTable = $this->find($table->getPreviousTableId());
                if ($previousTable) {
                    $table->setPreviousTable($previousTable);
                }
            }

            /** @var Table $nextTable */
            $nextTable = $this->findOneBy(['previous_table_id' => $table->getId()]);
            if ($nextTable) {
                $table->setNextTable($nextTable);
            }
        }

        return $table;
    }


    /**
     * {@inheritDoc}
     */
    public function save($entity, $silent = null)
    {
        /** @var Table $entity */
        $entity->setUpdatedAt(); // Mise à jour auto de la date de dernière mise à jour du tableau

        // On veut voir s'il manque des données à saisir (données de résultats)
        /** @var BoxRepository $boxRepository */
        $boxRepository = $this->getEntityManager()->getRepository('tournament_box');
        $outBoxes = $boxRepository->getOutBoxesCountByTableId($entity->getId()); // Tableau non vide ?
        $boxesWithMissingScore = $boxRepository->findAllWithMissingScoreByTable($entity, false);

        if ($outBoxes > 0 && empty($boxesWithMissingScore)) {
            $entity->setStatus(Table::STATUS_COMPLETE);
        } elseif ($outBoxes > 0 && !empty($boxesWithMissingScore)) {
            $entity->setStatus(Table::STATUS_PENDING);
        } else {
            $entity->setStatus(Table::STATUS_NEW);
        }

        return parent::save($entity, $silent);
    }

    /**
     * @param Table $table
     *
     * @return Table[]
     */
    public function findAllOtherTablesOfTournament(Table $table)
    {
        $otherTables = [];

        $tableId = ($table->getId()) ? $table->getId() : 0;

        $qb = $this->getLoadQuery()
            ->where('tournament_id = :tournamentId')
            ->andWhere('id != :tableId')
            ->setParameter('tableId', $tableId)
            ->setParameter('tournamentId', $table->getTournamentId());

        $result = $qb->execute()->fetchAll();
        if ($result) {
            $otherTables = $this->hydrateAll($result, $qb);
        }

        return $otherTables;
    }
}
