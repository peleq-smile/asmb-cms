<?php

namespace Bundle\Asmb\Competition\Parser\Tournament;

use Bolt\Filesystem\Exception\RuntimeException;
use Carbon\Carbon;

/**
 * Parseur de JS exporté depuis JA-Tennis
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2021
 */
class JaTennisJsParser extends AbstractJaTennisParser
{
    private static $rowContexts = [
        '{//j' => 'player',
        '{//e' => 'event',
        '{//t' => 'draw',
        '{//b' => 'box',
    ];

    private static $infoKeysMapping = [
        'Nom' => 'name',
        'Deb' => 'begin',
        'Fin' => 'end',
        'Gen' => 'updatedAt',
    ];

    private static $playersKeysMapping = [
        'Nom' => 'name',
        'Pre' => 'firstname',
        'Cls' => 'rank',
        'Clu' => 'club',
        'Sex' => 'cat',
    ];

    private static $drawsKeysMapping = [
        'Typ' => 'type',
        'Qua' => 'nbOut',
        'Col' => 'nbColumn',
    ];

    private static $boxesKeysMapping = [
        'Sco' => 'score',
        'Dat' => 'date',
        'QS' => 'qualif',
        'Cou' => 'place'
    ];

    /**
     * Parse le JS et extrait les différentes parties pour construire des tableaux PHP exploitables ensuite
     * par un template.
     *
     * @return array
     */
    public function parse(): array
    {
        if (null === $this->fileUrl) {
            throw new RuntimeException('Url vers fichier JS manquant.');
        }

        try {
            // On récupère le contenu JS depuis le fichier ou l'url donnée
            $jsFileContent = file_get_contents($this->fileUrl);
            $this->extractFileData($jsFileContent);

            if (empty($this->infoData)) {
                throw new \Exception('Le contenu du JS n\'a pas pu être extrait correctement.');
            }

            // On extrait les différentes données depuis le JS vers un tableau PHP exploitable
            $parsedData = [
                'info' => $this->getInfoData(),
                'tables' => $this->getTablesData(),
                'planning' => $this->getSortedPlanningData(),
                'result' => $this->getResultData(),
                'playersById' => $this->getPlayersData(),
                'players' => $this->getSortedByNamePlayersData(),
            ];
        } catch (\Exception $e) {
            $parsedData = [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }

        return $parsedData;
    }

    /**
     * Parse et retourne les données d'infos générales du tournoi.
     *
     * @return array
     */
    protected function getInfoData(): array
    {
        if (isset($this->infoData['updatedAt'])) {
            // Ajout de la date de dernière màj des données
            // Ex: "2019-09-18 01:08"
            $generateDate = Carbon::createFromFormat('Y-m-d H:i', $this->infoData['updatedAt']);
            $this->infoData['updatedAt'] = $this->getUpdatedAtFormatted($generateDate);
        }

        return $this->infoData;
    }

    /**
     * Parse et retourne les données sur les tableaux de tournoi.
     *
     * @return array
     */
    protected function getTablesData(): array
    {
        return $this->tablesData;
    }

    protected function getBoxCountForPoolType(int $nbPlayers): int
    {
        if (3 === $nbPlayers) {
            // 3 joueurs => 3 matchs en tout
            return 3;
        } else {
            return $this->getBoxCountForPoolType($nbPlayers - 1) + $nbPlayers - 1;
        }
    }

    /**
     * Extrait et/ou retourne les données sur les joueurs.
     *
     * @return array
     */
    protected function getPlayersData(): array
    {
        return $this->playersData;
    }

    /**
     * Exemple de $box :
     * [
     *     "position" =>  0,
     *     "playerId" =>  "j57",
     *     "score"    =>  "6/0 6/2",
     *     "date"     =>  "2019-02-21T20:30:00",
     *     "place"    =>  "Court A",
     * ]
     *
     * @param string $tableName Ex: "Messieurs senior NC à 30/2"
     * @param array $boxes
     * @param        $boxIdx
     * @param array $indexesRegister
     *
     * @return array
     */
    protected function parseBox(string $tableName, array $boxes, $boxIdx, array $indexesRegister): array
    {
        $boxData = [];

        if (isset($boxes[$boxIdx])) {
            $boxData = $boxes[$boxIdx];
            $origBoxData = $boxData;

            if (isset($boxData['jid'])) {
                $jId = $boxData['jid'];
                $boxData['name'] = $this->playersData[$jId]['name'];
                $boxData['rank'] = $this->playersData[$jId]['rank'];
            } // sinon... c'est la merde un peu

            if (isset($boxData['date'])) {
                $origBoxDate = $boxData['date'];
                $boxData['date'] = $this->getFormattedDateTime($origBoxDate);
            }

            /** @noinspection DuplicatedCode */
            if (isset($indexesRegister[$boxIdx]['idxBtm'])) {
                $boxData['prevBtm'] = $this->parseBox(
                    $tableName,
                    $boxes,
                    $indexesRegister[$boxIdx]['idxBtm'],
                    $indexesRegister
                );
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
            if (isset($indexesRegister[$boxIdx]['idxTop'])) {
                $boxData['prevTop'] = $this->parseBox(
                    $tableName,
                    $boxes,
                    $indexesRegister[$boxIdx]['idxTop'],
                    $indexesRegister
                );

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
            if (isset($origBoxData['date'], $boxData['prevBtm'], $boxData['prevTop'])) {
                $this->updatePlanningData($origBoxData, $boxData['prevBtm'], $boxData['prevTop']);
            }

            // On ajoute les données de match sur les joueurs
            if (isset($origBoxData['date'])) {
                $jId = $jId ?? null;
                $looserPlayer = $looserPlayer ?? null;
                $this->addMatchesDataOnPlayersData($jId, $looserPlayer, $tableName, $origBoxData['date'], $boxData);
            }
        }

        return $boxData;
    }

    protected function buildPoolBoxesData(string $tableName, array $boxes, int $nbPlayers): array
    {
        $boxesData = [];
        $boxCount = $this->getBoxCountForPoolType($nbPlayers);

        // On fait une première boucle pour récupérer tous les joueurs et construire une matrice de poule
        $players = [];
        $idxPlayer = 1;

        $reverseBoxes = array_reverse($boxes);
        foreach ($reverseBoxes as $box) {
            if (!isset($box['jid'])) {
                continue;
            }

            $playerId = $box['jid'];
            if (!in_array($playerId, $players)) {
                $players[$idxPlayer++] = $playerId;
            }
        }

        // On initialise la matrice avec tous les joueurs
        foreach ($players as $playerIdRow) {
            $boxesData[$playerIdRow] = [];

            foreach ($players as $playerIdCol) {
                $boxesData[$playerIdRow][$playerIdCol] = [];
            }
        }

        // On remplit notre "matrice" de poule initialisée, en se basant sur la donnée 'boxId' qui correspont à la
        // position de la case dans la matrice
        $boxId = ($nbPlayers * $nbPlayers) - 1;
        foreach ($boxesData as $playerIdRow => &$boxDataOnRow) {
            foreach ($boxDataOnRow as $playerIdCol => &$boxData) {
                $looserPlayer = null;
                if (isset($boxes[$boxId]['score']) || isset($boxes[$boxId]['date'])) {
                    $boxData['jid'] = $boxes[$boxId]['jid'] ?? '';
                    $boxData['score'] = $boxes[$boxId]['score'] ?? '';
                    $boxData['place'] = $boxes[$boxId]['place'] ?? '';

                    if (isset($boxes[$boxId]['jid'])) {
                        $winnerPlayerId = $boxes[$boxId]['jid'];
                        $boxData['name'] = $this->playersData[$winnerPlayerId]['name'];
                        $boxData['rank'] = $this->playersData[$winnerPlayerId]['rank'];

                        $looserPlayerId = ($playerIdRow === $winnerPlayerId) ? $playerIdCol : $playerIdRow;
                        $looserPlayer = $this->playersData[$looserPlayerId];
                    }

                    if (isset($boxes[$boxId]['date'])) {
                        $origBoxDate = $boxes[$boxId]['date'];
                        $boxData['date'] = $this->getFormattedDateTime($origBoxDate);

                        // On ajoute les données de match sur les joueurs
                        $this->addMatchesDataOnPlayersData($playerIdRow, $looserPlayer, $tableName, $origBoxDate, $boxData);

                        // On a une date : on enregistre des données sur le planning ici
                        $place = $boxData['place'];
                        $score = $boxData['score'];

                        //TODO refactoriser tout ça !!!!
                        if (isset($winnerPlayerId, $looserPlayerId)) {
                            // si vainqueur, on le place en 1er
                            $player1 = [
                                'jid' => $winnerPlayerId,
                                'name' => $this->playersData[$winnerPlayerId]['name'],
                                'rank' => $this->playersData[$winnerPlayerId]['rank'],
                                'club' => $this->playersData[$winnerPlayerId]['club'] ?? '',
                                'qualif' => '',
                            ];
                            $player2 = [
                                'jid' => $looserPlayerId,
                                'name' => $looserPlayer['name'],
                                'rank' => $looserPlayer['rank'],
                                'club' => $looserPlayer['club'] ?? '',
                                'qualif' => '',
                            ];
                        } else {
                            // sinon le joueur 1 est le joueur de la ligne
                            $player1Id = $playerIdRow;
                            $player1 = [
                                'jid' => $player1Id,
                                'name' => $this->playersData[$player1Id]['name'],
                                'rank' => $this->playersData[$player1Id]['rank'],
                                'club' => $this->playersData[$player1Id]['club'] ?? '',
                                'qualif' => '',
                            ];
                            // et le joueur 2 est le joueur de la colonne
                            $player2Id = $playerIdCol;
                            $player2 = [
                                'jid' => $player2Id,
                                'name' => $this->playersData[$player2Id]['name'],
                                'rank' => $this->playersData[$player2Id]['rank'],
                                'club' => $this->playersData[$player2Id]['club'] ?? '',
                                'qualif' => '',
                            ];
                        }

                        $this->planningData[$origBoxDate][$place] = [
                            'table' => $tableName,
                            'player1' => $player1,
                            'player2' => $player2,
                            'score' => $score,
                        ];
                    }
                }
                $boxId--;
                unset($winnerPlayerId, $looserPlayerId);
            }
        }

        return $boxesData;
    }

    /**
     * Lit le fichier ligne par ligne pour peupler les données correspondantes.
     *
     * @param string $jsFilecontent
     */
    private function extractFileData(string $jsFilecontent)
    {
        $rows = explode("\n", $jsFilecontent);

        // on ignore toutes les lignes avant la chaîne "function InitTournoi(){"
        $fromIdx = array_search('function InitTournoi(){', $rows) + 1;
        $rows = array_slice($rows, $fromIdx);

        $context = null;
        $contextId = null;
        $eventId = null;
        $drawId = null;
        $eventName = ''; // épreuve actuellement parsée
        $indexesRegister = []; // enregistrement des précédences des "boîtes"
        $boxesByDraw = [];
        $boxId = null;
        $cursorIdx = null;
        foreach ($rows as $row) {
            $row = trim($row, " ,");

            // Gestion du changement de contexte de la ligne lue
            if (strpos($row, '{//') === 0) {
                foreach (self::$rowContexts as $rowStart => $availableContext) {
                    if (strpos($row, $rowStart) === 0) {
                        // on extrait d'une part le contexte et l'ID de la donnée courante
                        $context = $availableContext;
                        $contextId = substr($row, 4); // 4 = longueur de '{//' + 1 lettre
                        break;
                    }
                }
                continue;
            }

            $idxOfDots = strpos($row, ':');
            if (!$idxOfDots) {
                continue;
            }
            $rowKey = substr($row, 0, $idxOfDots); // Ex: 'Nom', 'Deb', 'Pre' etc.

            switch ($context) {
                case 'player':
                    // CONTEXTE "JOUEUR"
                    $playerId = $contextId;
                    if (isset(self::$playersKeysMapping[$rowKey])) {
                        $data = $this->extractDataFromRow($row, $rowKey);
                        if ('Nom' === $rowKey) {
                            $this->playersData['j' . $playerId] = [
                                'jid' => 'j' . $playerId,
                                'name' => $data,
                            ];
                        } elseif ('Pre' === $rowKey) {
                            $this->playersData['j' . $playerId]['name'] =
                                $this->buildNameWithFirstname($this->playersData['j' . $playerId]['name'], $data);
                        } elseif (isset(self::$playersKeysMapping[$rowKey])) {
                            $this->playersData['j' . $playerId][self::$playersKeysMapping[$rowKey]] = $data;
                        }
                    }
                    break;
                case 'event':
                    // CONTEXTE "ÉPREUVE"
                    $eventId = $contextId;
                    if ('Nom' === $rowKey) {
                        $eventName = $this->extractDataFromRow($row, $rowKey);
                    }
                    break;
                case 'draw':
                    // CONTEXTE "TABLEAU"
                    $drawId = 'e' . $eventId . 'd' . $contextId;
                    if ('Nom' === $rowKey) {
                        $boxId = -1;
                        $cursorIdx = 0; // Curseur d'index transverse pour les box
                        $tableName = $this->extractDataFromRow($row, $rowKey);
                        if (!empty($eventName)) {
                            $tableName = $eventName . ' &bull; ' . $tableName;
                        }
                        $this->tablesData[$drawId] = [
                            'id' => $drawId,
                            'name' => $tableName,
                        ];
                    } elseif (isset(self::$drawsKeysMapping[$rowKey])) {
                        $this->tablesData[$drawId][self::$drawsKeysMapping[$rowKey]] = $this->extractDataFromRow($row, $rowKey);
                    }
                    break;
                case 'box':
                    // CONTEXTE "BOITE"
                    $isPool = (int)($this->tablesData[$drawId]['type']) === 2; // le tableau courant, est-il une poule ?
                    if ('Jou' === $rowKey) {
                        if ($isPool) {
                            $boxId = $contextId;
                        } else {
                            $boxId++;
                            $indexesRegister[$drawId][$boxId] = [];
                        }
                        $boxesByDraw[$drawId][$boxId]['table'] = $tableName ?? '';
                        $boxesByDraw[$drawId][$boxId]['boxId'] = $contextId; // nécessaire pour les poules !
                        $boxesByDraw[$drawId][$boxId]['jid'] = 'j' . $this->extractDataFromRow($row, $rowKey);
                    } elseif (isset(self::$boxesKeysMapping[$rowKey])) {
                        if ('Sco' === $rowKey && !$isPool) {
                            $nbOut = (int)$this->tablesData[$drawId]['nbOut'];
                            $indexesRegister[$drawId][$boxId] = [
                                'idxBtm' => ($cursorIdx++) + $nbOut,
                                'idxTop' => ($cursorIdx++) + $nbOut,
                            ];
                        }
                        $boxesByDraw[$drawId][$boxId][self::$boxesKeysMapping[$rowKey]] = $this->extractDataFromRow($row, $rowKey);
                    }
                    break;
                default:
                    // Pas de contexte = contexte pour récupérer les infos générales du tournoi
                    if (isset(self::$infoKeysMapping[$rowKey])) {
                        $this->infoData[self::$infoKeysMapping[$rowKey]] = $this->extractDataFromRow($row, $rowKey);
                    }
                    break;
            }
        }

        if (isset($boxesByDraw)) {
            $this->buildTablesData($boxesByDraw, $indexesRegister);
        }
    }

    /**
     * Alimente les données sur tous les tableaux à partir de ce qui a été lu dans le JS.
     *
     * @param array $boxesByDraw
     * @param array $indexesRegister
     */
    private function buildTablesData(array $boxesByDraw, array $indexesRegister)
    {
        foreach ($boxesByDraw as $drawId => $boxes) {
            $nbOut = (int)$this->tablesData[$drawId]['nbOut'];
            $isPool = (2 === (int)$this->tablesData[$drawId]['type']);
            $name = $this->tablesData[$drawId]['name'];
            $nbColumn = (int)$this->tablesData[$drawId]['nbColumn'];

            $boxesData = [];
            if ($isPool) {
                $boxesData = $this->buildPoolBoxesData($name, $boxes, $nbColumn);
            } else {
                for ($idx = 0; $idx < $nbOut; $idx++) {
                    $boxesData[] = $this->parseBox($name, $boxes, $idx, $indexesRegister[$drawId]);
                }
                // On profite de la boucle pour enregistrer les résultats (= vainqueurs + finalistes)
                // Avant de trier par ordre décroissant, le vainqueur est la 1ère "boîte" (pour les tableaux
                // dont il ressort 1 seule personne, càd où $nbOut=1 + qui contiennent "final" dans leur nom
                if (1 === $nbOut && isset($boxesData[0]) && stripos($name, 'final') !== false) {
                    $this->addResultDataFromFinalBox($name, $boxesData[0]);
                }
                krsort($boxesData);
            }

            $this->tablesData[$drawId] = [
                'id' => $drawId,
                'name' => $name,
                'isPool' => $isPool,
                'boxes' => $boxesData,
            ];
        }
    }

    /**
     * Retourne la donnée sur une ligne à partir d'une clé.
     * Ex, la ligne suivante :
     * Nom:"Trophee Philippe Le Gouic 2021"
     * doit retourner la chaîne 'Trophee Philippe Le Gouic 2021' à partir de la clé $rowKey 'Nom'
     *
     * @param string $row
     * @param string $rowKey
     *
     * @return string
     */
    private function extractDataFromRow(string $row, string $rowKey): ?string
    {
        $offset = strlen($rowKey) + 1; // on zappe le début de la ligne contenant la $rowKey + ':'
        $value = substr($row, $offset);

        // cas des string : on retire les guillemets
        $value = trim($value, '"');

        // cas des dates : on doit la renvoyer au format Y-m-d et rajouter le mois manquant avec JA-Tennis !
        if (strpos($value, 'new Date') === 0) {
            // on ajoute 1 au moins ici pour corriger le bug de JA-Tennis qui enlève 1 au mois.
            // on doit le faire avant de parser la date, sinon on peut avoir des soucis avec les 30/31 du mois !
            $explodedValue = explode(',', $value);
            $explodedValue[1] = (int)$explodedValue[1] + 1;
            $value = implode(',', $explodedValue);

            if (substr_count($value, ',') === 4) {
                // cas avec heure
                $value = Carbon::createFromFormat('(Y,m,d,H,i)', substr($value, strlen('new Date')))
                    ->format('Y-m-d H:i');
            } elseif (substr_count($value, ',') === 3) {
                // cas sans heure
                $value = Carbon::createFromFormat('(Y,m,d,H)', substr($value, strlen('new Date')))
                    ->format('Y-m-d H:00');
            } elseif (substr_count($value, ',') === 2) {
                // cas sans heure
                $value = Carbon::createFromFormat('(Y,m,d)', substr($value, strlen('new Date')))
                    ->format('Y-m-d');
            }
        }

        return $value;
    }
}
