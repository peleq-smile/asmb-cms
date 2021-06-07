<?php

namespace Bundle\Asmb\Competition\Parser\Tournament;

use Bundle\Asmb\Competition\Entity\Tournament;
use Bundle\Asmb\Competition\Entity\Tournament\Box;
use Bundle\Asmb\Competition\Entity\Tournament\Table;
use Bundle\Asmb\Competition\Repository\Tournament\BoxRepository;
use Bundle\Asmb\Competition\Repository\Tournament\TableRepository;
use Bundle\Asmb\Competition\Repository\TournamentRepository;
use Carbon\Carbon;

/**
 * Extrait les données d'un tournoi depuis la base de données.
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2020
 */
class DbParser extends AbstractParser
{
    /** @var TournamentRepository */
    protected $tournamentRepository;
    /** @var TableRepository */
    protected $tableRepository;
    /** @var BoxRepository */
    protected $boxRepository;
    /** @var integer */
    private $tournamentId;
    /** @var Tournament */
    private $tournament;

    /**
     * DbParser constructor.
     * @param TournamentRepository $tournamentRepository
     * @param TableRepository $tableRepository
     * @param BoxRepository $boxRepository
     */
    public function __construct(
        TournamentRepository $tournamentRepository, TableRepository $tableRepository, BoxRepository $boxRepository
    )
    {
        $this->tournamentRepository = $tournamentRepository;
        $this->tableRepository = $tableRepository;
        $this->boxRepository = $boxRepository;
    }

    /**
     * @return integer
     */
    public function getTournamentId()
    {
        return $this->tournamentId;
    }

    /**
     * @param integer $tournamentId
     */
    public function setTournamentId($tournamentId)
    {
        $this->tournamentId = $tournamentId;
    }

    /**
     * @return Tournament
     */
    public function getTournament(): ?Tournament
    {
        if (null === $this->tournament) {
            $this->tournament = $this->tournamentRepository->find($this->getTournamentId());
        }

        return $this->tournament;
    }

    /**
     * @param Tournament $tournament
     */
    public function setTournament(Tournament $tournament)
    {
        $this->tournament = $tournament;
    }

    /**
     * Extrait les différentes parties pour construire des tableaux PHP exploitables ensuite
     * par le template.
     *
     * @return array
     */
    public function parse()
    {
        try {
            // On extrait les différentes données depuis la base de données pour construire un tableau PHP exploitable
            $parsedData = [
                'tables' => $this->getTablesData(),
                'planning' => $this->getSortedPlanningData(),
                'result' => $this->getResultData(),
                'players' => $this->getSortedByNamePlayersData(),
                'info' => $this->getInfoData(),
            ];
        } catch (\Exception $e) {
            $parsedData = [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }

        return $parsedData;
    }

    protected function getInfoData()
    {
        if (null === $this->infoData) {
            $this->infoData = [];
        }

        if (!isset($this->infoData['begin'])) {
            $tournament = $this->getTournament();
            if ($tournament) {
                $this->infoData['begin'] = $tournament->getFromDate()->format('Y-m-d');
                $this->infoData['end'] = $tournament->getToDate()->format('Y-m-d');
            }
        }

        return $this->infoData;
    }

    protected function getTablesData()
    {
        if (null === $this->tablesData) {
            $this->tablesData = [];

            $this->getPlayersData();

            // On récupère tout d'abord tous les tableaux du tournoi (par position)
            $tables = $this->tableRepository->findBy(
                [
                    'tournament_id' => $this->getTournamentId(),
                    'visible' => true
                ],
                [
                    'position',
                    'asc'
                ]
            );
            /** @var Table $table */
            foreach ($tables as $table) {
                $tableName = $table->getCategoryLabel() . ' &bull; ' . $table->getName();

                // On récupère les boîtes de chaque tableau
                $boxes = $this->boxRepository->findAllByTable($table);

                $boxesData = [];
                /** @var Box $box */
                foreach ($boxes as $box) {
                    // On ne garde directement que les données avec qualifié sortant, les données suivantes sont
                    // ajoutées récursivement et dans le bon ordre
                    if ($box->getQualifOut() > 0) {
                        $boxesData[] = $this->buildBoxData(
                            $tableName,
                            $boxes,
                            $box
                        );
                    }
                }

                // On profite de la boucle pour enregistrer les résultats (= vainqueurs + finalistes)
                if (count($boxesData) === 1) {
                    $this->addResultDataFromFinalBox($tableName, $boxesData[0]);
                }

                $this->tablesData[] = [
                    'id' => $table->getId(),
                    'name' => $tableName,
                    'boxes' => $boxesData,
                ];


                if (!isset($tournamentUpdatedAt) || $table->getUpdatedAt() > $tournamentUpdatedAt) {
                    $tournamentUpdatedAt = $table->getUpdatedAt();
                }

            }
            // On met à jour la date de dernière mise à jour du tournoi avec la date de mise à jour la + récente
            // parmi les tableaux
            if (isset($tournamentUpdatedAt)) {
                $this->infoData['updatedAt'] = $this->getUpdatedAtFormatted($tournamentUpdatedAt);
            }
        }

        return $this->tablesData;
    }

    protected function getResultData()
    {
        if (null === $this->resultsData) {
            $this->resultsData = [];
        }

        return $this->resultsData;
    }

    protected function getPlayersData()
    {
        if (null === $this->playersData) {
            if ($this->getTournament()) {
                $this->playersData = $this->tournamentRepository->findAllPlayersData($this->getTournament(), true);
            } else {
                $this->playersData = [];
            }
        }

        return $this->playersData;
    }

    protected function getSortedByNamePlayersData()
    {
        // Les joueurs sont déjà triés par nom
        return $this->getPlayersData();
    }

    protected function buildBoxData($tableName, array $allBoxes, Box $box)
    {
        $boxData = [
            'table' => $tableName,
        ];

        if (null !== $box->getPlayerName()) {
            $jId = $this->getPlayerUniqIdFromBox($box);
            $boxData['jid'] = $jId;
            $boxData['name'] = $box->getPlayerName();
            $boxData['rank'] = $box->getPlayerRank();
            $boxData['club'] = $box->getPlayerClub();
        }

        if (null !== $box->getDatetime()) {
            if ($box->getDatetime()->format('H') !== '00') {
                $boxData['date'] = $box->getDatetime()->formatLocalized('%a %d %b %H:%M');
            } else {
                // si l'horaire du match est "00:00", on n'affiche pas l'heure
                $boxData['date'] = $box->getDatetime()->formatLocalized('%a %d %b');
            }
        }
        if (null !== $box->getScore()) {
            $boxData['score'] = $box->getScore();
        }
        if (null !== $box->getQualifIn() && $box->getQualifIn() > 0) {
            $boxData['qualif'] = $box->getQualifIn();
        }
        if (null !== $box->getQualifOut() && $box->getQualifOut() > 0) {
            $boxData['qualif'] = $box->getQualifOut();
        }

        if (null !== $box->getBoxBtm()) {
            $boxData['prevBtm'] = $this->buildBoxData(
                $tableName,
                $allBoxes,
                $box->getBoxBtm()
            );

            // Si le nom du joueur de la boîte courante est différent de celui de la boîte du bas, cela signifie que
            // le joueur dans la boîte du bas a perdu la rencontre
            if (isset($boxData['name'], $boxData['prevBtm']['name'])
                && $boxData['prevBtm']['name'] !== $boxData['name']
            ) {
                $looserPlayer = [
                    'jid' => $boxData['prevBtm']['jid'],
                    'name' => $boxData['prevBtm']['name'],
                    'rank' => $boxData['prevBtm']['rank'],
                ];
            }
        }

        if (null !== $box->getBoxTop()) {
            $boxData['prevTop'] = $this->buildBoxData(
                $tableName,
                $allBoxes,
                $box->getBoxTop()
            );

            // Si le nom du joueur de la boîte courante est différent de celui de la boîte du haut, cela signifie que
            // le joueur dans la boîte du haut a perdu la rencontre
            if (isset($boxData['name'], $boxData['prevTop']['name'])
                && $boxData['prevTop']['name'] !== $boxData['name']
            ) {
                $looserPlayer = [
                    'jid' => $boxData['prevTop']['jid'],
                    'name' => $boxData['prevTop']['name'],
                    'rank' => $boxData['prevTop']['rank'],
                ];
            }
        }

        // On a une date : on enregistre des données sur le planning ici
        if (isset($boxData['date'], $boxData['prevBtm'], $boxData['prevTop'])) {
            $this->updatePlanningData($box, $boxData['prevBtm'], $boxData['prevTop']);
        }

        // On ajoute les données de match sur les joueurs
        if (null !== $box->getDatetime()) {
            $jId = $jId ?? null;
            $looserPlayer = $looserPlayer ?? null;
            $boxDatetime = $this->getBoxDatetimeFormattedForSort($box->getDatetime());
            $this->addMatchesDataOnPlayersData($jId, $looserPlayer, $tableName, $boxDatetime, $boxData);
        }

        return $boxData;
    }

    /**
     * Ajoute une donnée de planning à partir des éléments fournis.
     *
     * @param Box $box
     * @param array $boxBtm
     * @param array $boxTop
     */
    protected function updatePlanningData(Box $box, array $boxBtm, array $boxTop)
    {
        if (null === $this->planningData) {
            $this->planningData = [];
        }

        $date = $this->getBoxDatetimeFormattedForSort($box->getDatetime());
        $score = (null !== $box->getScore()) ? $box->getScore() : '';
        $place = $box->getId(); // Tentons d'utiliser cette donnée...

        if (!isset($this->planningData[$date][$box->getId()])) {
            $jId = $this->getPlayerUniqIdFromBox($box);
            $this->addPlanningData($date, $score, $place, $jId, $boxBtm, $boxTop);
        }
    }

    protected function getBoxDatetimeFormattedForSort(Carbon $datetime)
    {
        if ($datetime->hour > 0) {
            $datetimeFormatted = $datetime->format('Y-m-d\TH:i:s');
        } else {
            $datetimeFormatted = $datetime->format('Y-m-d');
        }

        return $datetimeFormatted;
    }

    protected function getPlayerUniqIdFromBox(Box $box)
    {
        $jId = null;

        if (null !== $box->getPlayerName() && null !== $box->getPlayerName()) {
            /**
             * Avoir la même règle que dans la requête SQL de récupération des joueurs, ici :
             * @see TournamentRepository::findAllPlayersData()
             */
            $jId = str_replace([' ', '/'], ['_', '-'], $box->getPlayerName() . '_' . $box->getPlayerRank());
        }

        return $jId;
    }
}
