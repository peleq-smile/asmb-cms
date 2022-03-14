<?php

namespace Bundle\Asmb\Competition\Parser\Tournament;

use Carbon\Carbon;
use JsonSchema\Exception\RuntimeException;

/**
 * Parseur de JSON exporté depuis JA-Tennis
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2019
 */
class JaTennisJsonParser extends AbstractJaTennisParser
{
    /**
     * Parse le JSON et extrait les différentes parties pour construire des tableaux PHP exploitables ensuite
     * par un template.
     *
     * @return array
     */
    public function parse(): array
    {
        if (null === $this->fileUrl) {
            throw new RuntimeException('Url vers fichier JSON manquant.');
        }

        try {
            // On récupère le contenu JSON depuis le fichier ou l'url donnée
            $jsonFileContent = file_get_contents($this->fileUrl);

            // Corrections à l'arrache du JSON exporté par JA-Tennis !
            $fixedJsonFileContent = str_replace(',team:', ',"team":', $jsonFileContent);

            $this->fileData = json_decode($fixedJsonFileContent, true);

            if (null === $this->fileData || false === $this->fileData) {
                throw new \Exception('Le contenu JSON n\'a pas pu être extrait correctement.');
            }

            // On extrait les différentes données depuis le JSON vers un tableau PHP exploitable
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

        // On retire les joueurs qui n'ont pas joué, si le tournoi est terminé !
        $now = Carbon::now()->format('Y-m-d');
        if ($this->infoData['end'] < $now) {
            foreach ($parsedData['players'] as $idxPlayer => $playerData) {
                if (empty($playerData['matches'])) {
                    unset($parsedData['players'][$idxPlayer]);
                }
            }
        }

        // On tri les données de résultat par clé
        krsort($parsedData['result']);

        return $parsedData;
    }

    /**
     * Parse et retourne les données d'infos générales du tournoi.
     *
     * @return array
     */
    protected function getInfoData(): array
    {
        if (null === $this->infoData) {
            $this->infoData = $this->fileData['info'];

            // JA Tennis décale les date d'1 mois, on rectifie ici
            // Ex: "2019-09-21" devient "2019-10-21"
            $this->infoData['begin'] = $this->add1month($this->infoData['begin']);
            $this->infoData['end'] = $this->add1month($this->infoData['end']);

            // Ajout de la date de dernière màj des données
            // Ex: "2019-09-18T01:08:00"
            $generateDate = Carbon::createFromFormat('Y-m-d\TH:i:s', $this->add1month($this->fileData['jat']['generate']));
            $generateTimeFormatted = $this->getFormattedTime($this->fileData['jat']['generate']);

            $this->infoData['updatedAt'] = $generateDate->format('d/m/Y') . " à $generateTimeFormatted";
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
        if (null === $this->tablesData) {
            $this->tablesData = [];

            $playersData = $this->getPlayersData();
            if (!empty($playersData)) {
                foreach ($this->fileData['events'] as $dataEvent) {
                    foreach ($dataEvent['draws'] as $idxDraw => $draw) {
                        $nbOut = $draw['nbOut'];
                        $name = $dataEvent['name'];

                        // TODOpeleq à retirer -- pour surcharger les noms pas top de l'asmb cup ds JA tennis
                        if ($name === 'ASMB Cup 2022' || $name === 'ASMB Cup') {
                            if ('F' === $dataEvent['sexe']) {
                                $name = 'ASMB Cup Dames';
                            } elseif ('H' === $dataEvent['sexe']) {
                                $name = 'ASMB Cup Messieurs';
                            } else {
                                $name = 'ASMB Cup 2022';
                            }
                        }

                        $boxes = $draw['boxes'];
                        $indexesRegister = $this->buildIndexesRegistry($boxes, $nbOut);

                        if (isset($draw['name'])) {
                            $name .= ' &bull; ' . $draw['name'];
                        }

                        // S'agit-il d'une poule ?
                        $isPool = ($draw['type'] === 2);
                        $boxesData = [];

                        if ($isPool) {
                            $boxesData = $this->buildPoolBoxesData($name, $boxes, $draw['nbColumn']);
                        } else {
                            // L'export JSON de JA Tennis place les $nbOut boîtes sortantes en tête de l'entrée "boxes".
                            // On ne parcourt donc ici que les $nbOut premières boîtes, le parsing du reste se faisant
                            // ensuite récursivement.
                            for ($idx = 0; $idx < $nbOut; $idx++) {
                                $boxesData[] = $this->parseBox($name, $boxes, $idx, $indexesRegister);
                            }

                            // On profite de la boucle pour enregistrer les résultats (= vainqueurs + finalistes)
                            // Avant de trier par ordre décroissant, le vainqueur est la 1ère "boîte" (pour les tableaux
                            // dont il ressort 1 seule personne et qui est le dernier de l'épreuve)
                            if (1 === $nbOut && isset($boxesData[0]) && $idxDraw === (count($dataEvent['draws']) - 1)) {
                                $this->addResultDataFromFinalBox($name, $boxesData[0]);
                            }

                            krsort($boxesData);
                        }

                        $this->tablesData[] = [
                            'id' => $draw['id'],
                            'name' => $name,
                            'isPool' => $isPool,
                            'boxes' => $boxesData,
                            'sexe' => $dataEvent['sexe']
                        ];
                    }
                }
            }
        }

        return $this->tablesData;
    }

    protected function buildPoolBoxesData(string $tableName, array $boxes, int $nbPlayers): array
    {
        $boxesData = [];
        $boxCount = $this->getBoxCountForPoolType($nbPlayers);

        // On fait une première boucle pour récupérer tous les joueurs et construire une matrice de poule
        $players = [];
        $idxPlayer = 1;
        for ($i = count($boxes) - 1; $i >= 0; $i--) {
            // les X dernières "boîtes" correspondent en qq sorte à la colonne des noms des joueurs (X = nb de joueurs
            // de la poule)
            if (!isset($boxes[$i]['playerId'])) {
                continue;
            }
            // utilisation d'un for pour parcourir à l'envers, sans utiliser krsort
            $playerId = $boxes[$i]['playerId'];
            if (!in_array($playerId, $players)) {
                $players[$idxPlayer++] = $playerId;

                // on initialise également les données de classement
                $this->resultsData['pool'][$tableName][$playerId]['matchCount'] = 0;
                $this->resultsData['pool'][$tableName][$playerId]['points'] = 0;
                $this->resultsData['pool'][$tableName][$playerId]['setsDiff'] = 0;
                $this->resultsData['pool'][$tableName][$playerId]['gamesDiff'] = 0;
            }
        }

        // On initialise la matrice avec tous les joueurs
        foreach ($players as $playerIdRow) {
            $boxesData[$playerIdRow] = [];

            foreach ($players as $playerIdCol) {
                $boxesData[$playerIdRow][$playerIdCol] = [];
            }
        }

        // On repasse dans toutes les "boxes" pour remplir la matrice avec les résultats, en ne prenant cette fois-ci
        // que les $boxCount premières, et dans l'ordre inverse de JA-Tennis !
        $firstReverseBoxes = array_slice($boxes, -0, $boxCount);
        krsort($firstReverseBoxes);

        $idxCol = 2; // = $idxRow + 1
        $idxRow = 1;
        foreach ($firstReverseBoxes as $box) {
            $boxData = [];
            $looserPlayer = null;
            $playerIdRow = $players[$idxRow];
            $playerIdCol = $players[$idxCol];

            if (isset($box['date']) || isset($box['score'])) {
                $place = $box['place'] ?? '';
                $score = $box['score'] ?? '';
                $date = $box['date'] ?? null;
                $boxData['date'] = !empty($date) ? $this->getFormattedDateTime($box['date']) : null;
                $boxData['score'] = null;

                if (!empty($score)) {
                    $boxData['score'] = $score;
                    if (isset($box['playerId'])) {
                        $winnerPlayerId = $box['playerId'];
                        $boxData['jid'] = $winnerPlayerId;
                        $boxData['name'] = $this->playersData[$winnerPlayerId]['name'];
                        $boxData['shortName'] = $this->playersData[$winnerPlayerId]['shortName'];
                        $boxData['rank'] = $this->playersData[$winnerPlayerId]['rank'];

                        $looserPlayerId = ($playerIdRow === $winnerPlayerId) ? $playerIdCol : $playerIdRow;
                        $looserPlayer = $this->playersData[$looserPlayerId];

                        // On compte les points du joueur dans la poule pour le classement (on utilise $resultsData)
                        // 3 points une victoire, 1 point pour une défaite en 3set
                        $this->resultsData['pool'][$tableName][$winnerPlayerId]['points'] += 3;

                        if (substr_count($score, ' ') === 2 && strpos($score, ' 1/0') > 1) {
                            // score en 3 sets
                            $this->resultsData['pool'][$tableName][$looserPlayerId]['points'] += 1;
                            $this->resultsData['pool'][$tableName][$winnerPlayerId]['setsDiff'] += 1;
                            $this->resultsData['pool'][$tableName][$looserPlayerId]['setsDiff'] -= 1;

                            // extraction des jeux
                            $winnerGames = intval($score[0]) + intval($score[4]) + intval($score[8]);
                            $looserGames = intval($score[2]) + intval($score[6]) + intval($score[10]);
                        } else {
                            // score en 2 sets
                            $this->resultsData['pool'][$tableName][$winnerPlayerId]['setsDiff'] += 2;
                            $this->resultsData['pool'][$tableName][$looserPlayerId]['setsDiff'] -= 2;

                            // extraction des jeux
                            $winnerGames = intval($score[0]) + intval($score[4]);
                            $looserGames = intval($score[2]) + intval($score[6]);
                        }
                        $this->resultsData['pool'][$tableName][$looserPlayerId]['matchCount'] += 1;
                        $this->resultsData['pool'][$tableName][$winnerPlayerId]['matchCount'] += 1;
                        $this->resultsData['pool'][$tableName][$winnerPlayerId]['gamesDiff'] += ($winnerGames - $looserGames);
                        $this->resultsData['pool'][$tableName][$looserPlayerId]['gamesDiff'] += ($looserGames - $winnerGames);
                    }
                }

                // On ajoute les données de match sur les joueurs
                $this->addMatchesDataOnPlayersData($playerIdRow, $looserPlayer, $tableName, $date, $boxData);

                if (!empty($date)) {
                    // On a une date : on enregistre des données sur le planning ici
                    // $date est non formattée ici, c'est ce qu'on veut pour trier

                    //TODO refactoriser tout ça !!!!
                    if (isset($winnerPlayerId, $looserPlayerId)) {
                        // si vainqueur, on le place en 1er
                        $player1 = [
                            'jid' => $winnerPlayerId,
                            'name' => $this->playersData[$winnerPlayerId]['name'],
                            'rank' => $this->playersData[$winnerPlayerId]['rank'],
                            'club' => $this->playersData[$winnerPlayerId]['club'],
                            'qualif' => '',
                        ];
                        $player2 = [
                            'jid' => $looserPlayerId,
                            'name' => $looserPlayer['name'],
                            'rank' => $looserPlayer['rank'],
                            'club' => $looserPlayer['club'],
                            'qualif' => '',
                        ];
                    } else {
                        // sinon le joueur 1 est le joueur de la ligne
                        $player1Id = $playerIdRow;
                        $player1 = [
                            'jid' => $player1Id,
                            'name' => $this->playersData[$player1Id]['name'],
                            'rank' => $this->playersData[$player1Id]['rank'],
                            'club' => $this->playersData[$player1Id]['club'],
                            'qualif' => '',
                        ];
                        // et le joueur 2 est le joueur de la colonne
                        $player2Id = $playerIdCol ;
                        $player2 = [
                            'jid' => $player2Id,
                            'name' => $this->playersData[$player2Id]['name'],
                            'rank' => $this->playersData[$player2Id]['rank'],
                            'club' => $this->playersData[$player2Id]['club'],
                            'qualif' => '',
                        ];
                    }

                    $indexIntoPlanning = $place . '_' . ($box['position'] ?? '');
                    $this->planningData[$date][$indexIntoPlanning] = [
                        'table' => $tableName,
                        'player1' => $player1,
                        'player2' => $player2,
                        'score' => $score,
                    ];
                }
            }
            $boxesData[$playerIdRow][$playerIdCol] = $boxData;

            if ($idxCol === ($idxRow+1)) {
                $idxRow = 1;
                $idxCol++;
            } else {
                $idxRow++;
            }

            unset($winnerPlayerId, $looserPlayerId);
        }

        // on tri le tableau de classement selon les points, puis le setsDiff et le gamesDiff
        $points = $setsDiff = $gamesDiff = [];
        foreach ($this->resultsData['pool'][$tableName] as $playerId => $resultsData) {
            $points[$playerId]  = $resultsData['points'];
            $setsDiff[$playerId]  = $resultsData['setsDiff'];
            $gamesDiff[$playerId]  = $resultsData['gamesDiff'];
        }
        array_multisort(
            $points, SORT_DESC,
            $setsDiff, SORT_DESC,
            $gamesDiff, SORT_DESC,
            $this->resultsData['pool'][$tableName]
        );

        return $boxesData;
    }

    /**
     * Extrait et/ou retourne les données sur les joueurs.
     *
     * @return array
     */
    protected function getPlayersData(): array
    {
        if (null === $this->playersData) {
            $this->playersData = [];

            if (isset($this->fileData['players'])) {
                foreach ($this->fileData['players'] as $playerData) {
                    $name = $playerData['name'];
                    $team = null; // par défaut, match en simple
                    if (isset($playerData['firstname'])) {
                        $name = $this->buildNameWithFirstname($name, $playerData['firstname']);
                    } elseif (isset($playerData['team'])) {
                        // gestion des doubles
                        $team = $playerData['team']; // tableau à 2 entrées, contenant les IDs des 2 joueurs
                    }

                    $nameParts = explode(' ', $name);
                    $namePartsCountButLast = count($nameParts) - 1;
                    $namePartsButLast = implode(' ', array_slice($nameParts, 0, $namePartsCountButLast));
                    $shortName = $namePartsButLast . ' ' . $nameParts[$namePartsCountButLast][0] . '.';

                    $this->playersData[$playerData['id']] = [
                        'jid' => $playerData['id'],
                        'name' => $name,
                        'shortName' => $shortName,
                        'rank' => $playerData['rank'] ?? '',
                        'cat' => $playerData['sexe'],
                        'club' => $playerData['club'] ?? '',
                        'team' => $team,
                    ];
                }
            }
        }

        return $this->playersData;
    }

    /**
     * Parcourt toutes les "boîtes" d'un évènement afin de construire un registre d'index, afin de connaître,
     * pour chaque boîte, quelles sont les boîtes précédentes haute et basse.
     *
     * @param array $boxes
     * @param int   $nbOut
     *
     * @return array
     */
    protected function buildIndexesRegistry(array &$boxes, $nbOut): array
    {
        $indexesRegister = [];
        $cursorIdx = 0; // Curseur d'index transverse

        foreach ($boxes as $idxBox => &$box) {
            // JA Tennis semble décaler les date d'1 mois, on doit rectifier ça ici
            if (isset($box['date'])) {
                $box['date'] = $this->add1month($box['date']);
            }

            if (isset($box['score'])) {
                // Présence d'un score dans cette boîte = on récupère les index des boîtes précédentes (btm et top)
                $indexesRegister[$idxBox] = [
                    'idxBtm' => ($cursorIdx++) + $nbOut,
                    'idxTop' => ($cursorIdx++) + $nbOut,
                ];
            } else {
                // Pas de précédence : on enregistre tout de même l'info avec un tableau vide
                $indexesRegister[$idxBox] = [];
            }
        }

        return $indexesRegister;
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
     * @param array  $boxes
     * @param        $boxIdx
     * @param array  $indexesRegister
     *
     * @return array
     */
    protected function parseBox($tableName, array $boxes, $boxIdx, array $indexesRegister): array
    {
        $today = Carbon::today()->setTime(23,59,59)->format('Y-m-d\TH:i:s');
        $tomorrow = Carbon::tomorrow()->setTime(23,59,59)->format('Y-m-d\TH:i:s');

        $boxData = [];

        if (isset($boxes[$boxIdx])) {
            $box = $boxes[$boxIdx];

            $boxData = [
                'table' => $tableName,
            ];

            // ça peut arriver qu'il n'y ait pas de joueur indiqué... :-/
            if (isset($box['playerId'])) {
                $jId = $box['playerId'];
                $boxData['jid'] = $jId;
                $boxData['name'] = $this->playersData[$box['playerId']]['name'];
                $boxData['rank'] = $this->playersData[$box['playerId']]['rank'];
                $boxData['club'] = $this->playersData[$box['playerId']]['club'] ?? '';
            }
            if (isset($box['date'])) {
                // TODOPeleq revoir tout ça !!
                $boxData['date'] = '?';

                if (!empty($box['date']) && $box['date'] <= $today) {
                    $boxData['date'] = $this->getFormattedDateTime($box['date']);
                } elseif (!empty($box['date']) && $box['date'] <= $tomorrow) {
                    $boxData['date'] = $this->getFormattedDate($box['date']);
                }
            }
            if (isset($box['place'])) {
                $boxData['place'] = $box['place'];
            }
            if (isset($box['score'])) {
                $boxData['score'] = $box['score'];
            }
            if (isset($box['qualifIn']) && $box['qualifIn'] >= 1) {
                $boxData['qualif'] = $box['qualifIn'];
            }
            if (isset($box['qualifOut']) && $box['qualifOut'] >= 1) {
                $boxData['qualif'] = $box['qualifOut'];
            }
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
                        'club' => $boxData['prevBtm']['club'] ?? '',
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
                        'club' => $boxData['prevTop']['club'] ?? '',
                    ];
                }
            }

            // On a une date : on enregistre des données sur le planning ici
            if (isset($box['date'], $boxData['prevBtm'], $boxData['prevTop'])) {
                $this->updatePlanningData($box, $boxData['prevBtm'], $boxData['prevTop']);
            }

            // On ajoute les données de match sur les joueurs
            if (isset($box['date'])) {
                $jId = $jId ?? null;
                $looserPlayer = $looserPlayer ?? null;
                $this->addMatchesDataOnPlayersData($jId, $looserPlayer, $tableName, $box['date'], $boxData);

            }
        }

        return $boxData;
    }
}