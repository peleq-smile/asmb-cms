<?php

namespace Bundle\Asmb\Competition\Repository;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Tournament;
use PDO;

/**
 * Repository pour les tournois.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2020
 */
class TournamentRepository extends Repository
{
    /**
     * Retourne tous les championnats, par ordre d'année décroissante puis nom croissant.
     *
     * @return bool|mixed|object[]
     */
    public function findAll()
    {
        $tournaments = [];

        $qb = $this->findWithCriteria([]);
        $qb->addOrderBy('year', 'DESC');
        $qb->addOrderBy('id', 'DESC');

        // AJOUT DES TABLEAUX du tournoi
        $qb->addSelect("tour_table.id AS table_id");
        $qb->addSelect("tour_table.name AS table_name");
        $qb->addSelect("tour_table.status AS table_status");
        $qb->addSelect("tour_table.visible AS table_visible");
        $qb->addSelect("tour_table.position AS table_position");
        $qb->leftJoin(
            $this->getAlias(),
            'bolt_tournament_table',
            'tour_table',
            $qb->expr()->eq($this->getAlias() . '.id', 'tour_table.tournament_id')
        );
        $qb->addOrderBy('table_position');
        $qb->addOrderBy('table_id');

        $result = $qb->execute()->fetchAll();
        if ($result) {
            $tournaments = [];

            foreach ($result AS $row) {
                $tournamentId = $row['id'];
                if (!isset($tournaments[$tournamentId])) {
                    /** @var Tournament $tournament */
                    $tournament = $this->hydrate($row, $qb);

                    $tournaments[$tournamentId] = $tournament;
                }

                $tournament = $tournaments[$tournamentId];

                // MISE À JOUR DES INFOS SUR LES TABLEAUX
                $tournamentTables = $tournament->getTables();
                if (isset($row['table_id'])) {
                    $tournamentTable = new Tournament\Table();
                    $tournamentTable->setId($row['table_id']);
                    $tournamentTable->setTournamentId($tournamentId);
                    $tournamentTable->setName($row['table_name']);
                    $tournamentTable->setStatus($row['table_status']);
                    $tournamentTable->setVisible($row['table_visible']);

                    $tournamentTables[] = $tournamentTable;
                    $tournament->setTables($tournamentTables);
                }
            }
        }

        return $tournaments;
    }

    /**
     * Retourne les infos des joueurs sous forme d'un tableau de la sorte :
     * [
     *   "LEQUIPE_Perrine_30/2" => [
     *     "name" => "LEQUIPE Perrine"
     *     "rank" => "30/2"
     *     "club" => "ASMB"
     *     "cat"  => "D"
     *   ],
     *   ...
     * ]
     *
     * @param Tournament $tournament
     * @param bool $visibleTablesOnly
     * @return array
     */
    public function findAllPlayersData(Tournament $tournament, $visibleTablesOnly = false)
    {
        $qb = $this->getLoadQuery();
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_tournament_table',
            'tour_table',
            $qb->expr()->eq($this->getAlias() . '.id', 'tour_table.tournament_id')
        );
        $qb->innerJoin(
            'tour_table',
            'bolt_tournament_box',
            'tour_box',
            $qb->expr()->eq('tour_table.id', 'tour_box.table_id')
        );
        $qb->where('tournament_id = :tournamentId');
        $qb->setParameter('tournamentId', $tournament->getId());

        if ($visibleTablesOnly) {
            $qb->andWhere('tour_table.visible = 1');
        }
        $qb->andWhere('tour_box.player_name IS NOT NULL');

        $qb->resetQueryPart('select');
        $qb->select(
            'REPLACE(REPLACE(CONCAT(tour_box.player_name,"_",tour_box.player_rank)," ","_"),"/","-") AS player_uniq_id'
        );
        $qb->addSelect('tour_box.player_name AS name');
        $qb->addSelect('tour_box.player_rank AS rank');
        $qb->addSelect('tour_box.player_club AS club');
        $qb->addSelect('tour_table.category AS cat');

        $qb->groupBy(['name', 'rank', 'club', 'cat']);

        $playersData = [];

        $statement = $qb->execute();
        while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
            $playersData[$row['player_uniq_id']] = [
                'jid' => $row['player_uniq_id'],
                'name' => $row['name'],
                'rank' => $row['rank'],
                'club' => $row['club'],
                'cat' => $row['cat'],
            ];
        }

        return $playersData;
    }
}
