<?php

namespace Bundle\Asmb\Competition\Repository\Tournament;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Tournament;
use Bundle\Asmb\Competition\Entity\Tournament\Box;
use Bundle\Asmb\Competition\Entity\Tournament\Table;
use Bundle\Asmb\Competition\Helpers\DateHelper;
use Carbon\Carbon;
use PDO;

/**
 * Repository pour les "boîtes" des tableaux de tournois.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2020
 */
class BoxRepository extends Repository
{
    private $boxesByIdByTable;

    /**
     * Retourne les boîtes du tableau donné, avec les boîtes précédentes et suivantes valuées.
     *
     * @param Table $table
     *
     * @return Box[]
     */
    public function findAllByTable(Table $table)
    {
        return $this->getBoxesByIdByTable($table);
    }

    /**
     * Retourne le nombre de box sortantes pour le tableau d'id donné.
     *
     * @param int $tableId
     *
     * @return int
     */
    public function getOutBoxesCountByTableId($tableId)
    {
        $qb = $this->getLoadQuery()
            ->select('COUNT(' . $this->getAlias() . '.id) as count')
            ->resetQueryParts(['groupBy', 'join'])
            ->where('table_id = :tableId')
            ->andWhere('qualif_out IS NOT NULL')
            ->setParameter('tableId', $tableId);
        $result = $qb->execute()->fetchColumn(0);

        return (int)$result;
    }

    /**
     * Récupère et retourne les boîtes pour lesquels un score est attendu.
     *
     * @param Table $table
     * @param bool $inPastOnly
     * @return Box[]
     */
    public function findAllWithMissingScoreByTable(Table $table, $inPastOnly = true)
    {
        $boxesWithMissingScore = [];

        $boxes = $this->getBoxesByIdByTable($table);

        foreach ($boxes as $box) {
            // On teste si la rencontre n'a pas de score et a eu lieu avant aujourd'hui
            if (null !== $box->getDate()
                && (null === $box->getPlayerName() || null === $box->getScore())
                && (!$inPastOnly || $inPastOnly && $this->isBoxDatetimePastFromXhours($box))
            ) {
                $boxesWithMissingScore[$box->getId()] = $box;
            }
        }


        return $boxesWithMissingScore;
    }

    /**
     * Retourne la liste des boîtes avec vainqueur/score manquant, uniquement dans le passé ou non, tout tableau
     * confondu pour le tournoi donné.
     *
     * @param Tournament $tournament
     * @param bool $inPastOnly
     * @return Box[]
     */
    public function findAllWithMissingScoreByTournamentSortedByDay(Tournament $tournament, $inPastOnly = false)
    {
        $boxesWithMissingScore = [];

        $qb = $this->getLoadQuery();
        // Jointure sur les tableaux pour récupérer les boîtes des tableaux du tournoi donné
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_tournament_table',
            'tour_table',
            $qb->expr()->eq($this->getAlias() . '.table_id', 'tour_table.id')
        );
        $qb->addSelect('tour_table.category as table_category');
        $qb->addSelect('tour_table.name as table_name');

        // Filtre sur le tournoi
        $qb->where('tour_table.tournament_id = :tournamentId');
        $qb->setParameter('tournamentId', $tournament->getId());

        $qb->orderBy('date', 'ASC');
        $qb->addOrderBy('time', 'ASC');

        $result = $qb->execute()->fetchAll();
        if ($result) {
            $boxes = [];
            // Tout d'abord on récupère et "indexe" toutes les boîtes du tournoi
            foreach ($result as $boxData) {
                $box = $this->hydrate($boxData, $qb);
                // On valorise le nom de la table
                $tableName = $boxData['table_name'];
                if (isset(Table::$categories[$boxData['table_category']])) {
                    $tableName = Table::$categories[$boxData['table_category']] . ' - Tableau ' . $tableName;
                }
                $box->setTableName($tableName);

                $boxes[$boxData['id']] = $box;
            }
            /** @var Box $box */
            foreach ($boxes as $box) {
                // On re-parcourt ensuite chaque boîte en ne conservant que celle avec résultat manquant
                // et en leur affectant les données sur les boîtes précédentes
                if (null !== $box->getDatetime() && (null === $box->getPlayerName() || null === $box->getScore())
                    && (!$inPastOnly || $inPastOnly && $this->isBoxDatetimePastFromXhours($box))
                ) {
                    if ($box->getBoxBtmId() && isset($boxes[$box->getBoxBtmId()])) {
                        $box->setBoxBtm($boxes[$box->getBoxBtmId()]);
                    }
                    if ($box->getBoxTopId() && isset($boxes[$box->getBoxTopId()])) {
                        $box->setBoxTop($boxes[$box->getBoxTopId()]);
                    }

                    // On formate la date
                    $formattedDate = DateHelper::formatWithLocalizedDayAndMonth($box->getDatetime());
                    $boxesWithMissingScore[$formattedDate][$box->getId()] = $box;
                }
            }
        }

        return $boxesWithMissingScore;
    }

    /**
     * Récupère les "Q" entrants qui sont en double (les Q0 ne sont pas pris en compte).
     * @param $tableId
     *
     * @return array
     */
    public function findDuplicatesQualifIn($tableId)
    {
        $qb = $this->getLoadQuery();
        $qb->select('qualif_in');
        $qb->addSelect('COUNT(qualif_in) AS qualif_in_count');
        $qb->where('table_id = :tableId');
        $qb->setParameter('tableId', $tableId);
        $qb->andWhere('qualif_in > 0');
        $qb->groupBy('qualif_in');
        $qb->having('qualif_in_count > 1');
        $qb->orderBy('qualif_in');

        $duplicatesQualifIn = $qb->execute()->fetchAll(PDO::FETCH_KEY_PAIR);
        if (!$duplicatesQualifIn) {
            $duplicatesQualifIn = [];
        }

        return $duplicatesQualifIn;
    }

    public function save($entity, $silent = null)
    {
        /** @var Box $entity */

        // On s'assure d'avoir un score bien formaté
        if (null !== $entity->getScore()) {
            $cleanedScore = trim(str_replace('  ', ' ', $entity->getScore()));
            $entity->setScore($cleanedScore);
        }

        // On s'assure d'avoir un classement de joueur correct
        if (null !== $entity->getPlayerRank()) {
            $cleanedRank = trim(str_replace('//', '/', $entity->getPlayerRank()));
            $entity->setPlayerRank($cleanedRank);
        }

        // Mise à jour des qualifiés entrants en fonction de la précédence des tableaux
        if (null !== $entity->getQualifOut()) {
            // Une boîte avec qualifié(e) sortant(e) est enregistré : on cherche la boîte entrante correspondante
            // (si elle existe)

            $boxToUpdate = $this->findBoxWithQualifInEqualToBoxQualifOut($entity);
            if (null !== $boxToUpdate) {
                $boxToUpdate->setPlayerName($entity->getPlayerName());
                $boxToUpdate->setPlayerRank($entity->getPlayerRank());
                $boxToUpdate->setPlayerClub($entity->getPlayerClub());

                $this->save($boxToUpdate, true);
            }
        }

        return parent::save($entity, $silent);
    }

    public function updatePlayerDataFromQualifOut(Table $table)
    {
        if (null !== $table->getPreviousTableId()) {
            $qb = $this->getLoadQuery();

            $qb->innerJoin(
                $this->getAlias(),
                'bolt_tournament_box',
                'tour_previous_box',
                $qb->expr()->andX(
                    $qb->expr()->eq('tour_previous_box.table_id', $table->getPreviousTableId()),
                    $qb->expr()->eq($this->getAlias() . '.qualif_in', 'tour_previous_box.qualif_out')
                )
            );
            $qb->addSelect('tour_previous_box.player_name AS player_name');
            $qb->addSelect('tour_previous_box.player_club AS player_club');
            $qb->addSelect('tour_previous_box.player_rank AS player_rank');

            $qb->where($this->getAlias() . '.table_id = :tableId');
            $qb->andWhere($this->getAlias() . '.qualif_in IS NOT NULL');
            $qb->andWhere($this->getAlias() . '.player_name IS NULL');

            $qb->setParameter('tableId', $table->getId());

            $result = $qb->execute()->fetchAll();

            if ($result) {
                foreach ($result as $boxData) {
                    $boxToUpdate = $this->hydrate($boxData, $qb);
                    $this->update($boxToUpdate, ['qualif_in', 'qualif_out', 'table_id']);
                }
            }
        }
    }

    /**
     * @param Table $table
     *
     * @return Box[]
     */
    protected function getBoxesByIdByTable(Table $table)
    {
        if (!isset($this->boxesByIdByTable[$table->getId()])) {
            $boxesById = [];

            /** @var Box[] $boxes */
            $boxes = $this->findBy(['table_id' => $table->getId()]);
            if (false !== $boxes) {
                foreach ($boxes as $box) {
                    $boxesById[$box->getId()] = $box;
                }
            }

            // On refait un tour pour valuer les boîtes top et bottom précédentes
            foreach ($boxesById as &$box) {
                if (null !== $box->getBoxBtmId() && isset($boxesById[$box->getBoxBtmId()])) {
                    /** @var Box $boxBtm */
                    $boxBtm = $boxesById[$box->getBoxBtmId()];
                    $box->setBoxBtm($boxBtm);
                }
                if (null !== $box->getBoxTopId() && isset($boxesById[$box->getBoxTopId()])) {
                    /** @var Box $boxTop */
                    $boxTop = $boxesById[$box->getBoxTopId()];
                    $box->setBoxTop($boxTop);
                }
            }

            $this->boxesByIdByTable[$table->getId()] = $boxesById;
        }

        return $this->boxesByIdByTable[$table->getId()];
    }

    /**
     * @param Box $box
     *
     * @return Box|null
     */
    protected function findBoxWithQualifInEqualToBoxQualifOut(Box $box)
    {
        $boxWithQualifInEqualToBoxQualifOut = null;

        $qb = $this->getLoadQuery();
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_tournament_table',
            'tour_table',
            $qb->expr()->eq($this->getAlias() . '.table_id', 'tour_table.id')
        );

        $qb->where('qualif_in = :qualifIn');
        $qb->andWhere('previous_table_id = :previousTableId');
        $qb->setParameter('qualifIn', $box->getQualifOut());
        $qb->setParameter('previousTableId', $box->getTableId());

        $result = $qb->execute()->fetch();
        if ($result) {
            $boxWithQualifInEqualToBoxQualifOut = $this->hydrate($result, $qb);
        }

        return $boxWithQualifInEqualToBoxQualifOut;
    }

    /**
     * Teste si la boîte donnée a une date+heure passée du nombre d'heure donné.
     *
     * @param Box $box
     * @param float $xHours
     * @return bool
     */
    protected function isBoxDatetimePastFromXhours(Box $box, $xHours = 1.5)
    {
        $xHoursAgo = Carbon::now()->addHours(-1 * $xHours);

        return $box->getDatetime() <= $xHoursAgo;
    }
}
