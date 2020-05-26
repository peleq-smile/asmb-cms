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
class JaTennisJsonParser extends AbstractParser
{
    /** @var string */
    protected $jsonFileUrl;
    /** @var array */
    protected $jsonData;

    /**
     * JaTennisJsonParser constructor.
     *
     * @param $jsonFileUrl
     */
    public function __construct($jsonFileUrl = null)
    {
        $this->jsonFileUrl = $jsonFileUrl;
    }

    /**
     * @param string $jsonFileUrl
     */
    public function setJsonFileUrl($jsonFileUrl)
    {
        $this->jsonFileUrl = $jsonFileUrl;
    }

    /**
     * Parse le JSON et extrait les différentes parties pour construire des tableaux PHP exploitables ensuite
     * par un template.
     *
     * @return array
     */
    public function parse()
    {
        if (null === $this->jsonFileUrl) {
            throw new RuntimeException('Url vers fichier JSON manquant.');
        }

        try {
            // On récupère le contenu JSON depuis le fichier ou l'url donnée
            $jsonFileContent = file_get_contents($this->jsonFileUrl);
            $this->jsonData = json_decode($jsonFileContent, true);

            if (null === $this->jsonData || false === $this->jsonData) {
                throw new \Exception('Le contenu JSON n\'a pas pu être extrait correctement.');
            }

            // On extrait les différentes données depuis le JSON vers un tableau PHP exploitable
            $parsedData = [
                'info' => $this->getInfoData(),
                'tables' => $this->getTablesData(),
                'planning' => $this->getSortedPlanningData(),
                'result' => $this->getResultData(),
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
     * Ajoute 1 mois à la date entrante pour corriger le bug de JA Tennis sur les exports.
     *
     * @return string
     */
    protected function add1month($inputDate)
    {
        $outputDate = str_replace(['-10-', '-09-'], ['-11-', '-10-'], $inputDate);

        return $outputDate;
    }

    /**
     * Parse et retourne les données d'infos générales du tournoi.
     *
     * @return array
     */
    protected function getInfoData()
    {
        if (null === $this->infoData) {
            $this->infoData = $this->jsonData['info'];

            // JA Tennis décale les date d'1 mois, on rectifie ici
            // Ex: "2019-09-21" devient "2019-10-21"
            $this->infoData['begin'] = $this->add1month($this->infoData['begin']);
            $this->infoData['end'] = $this->add1month($this->infoData['end']);

            // Ajout de la date de dernière màj des données
            // Ex: "2019-09-18T01:08:00"
            $generateDate = Carbon::createFromFormat('Y-m-d\TH:i:s', $this->add1month($this->jsonData['jat']['generate']));
            $generateTimeFormatted = $this->getFormattedTime($this->jsonData['jat']['generate']);

            $this->infoData['updatedAt'] = $generateDate->format('d/m/Y') . " à $generateTimeFormatted";
        }

        return $this->infoData;
    }

    /**
     * Parse et retourne les données sur les tableaux de tournoi.
     *
     * @return array
     */
    protected function getTablesData()
    {
        if (null === $this->tablesData) {
            $this->tablesData = [];

            $playersData = $this->getPlayersData();
            if (!empty($playersData)) {
                foreach ($this->jsonData['events'] as $dataEvent) {
                    foreach ($dataEvent['draws'] as $draw) {
                        $nbOut = $draw['nbOut'];

                        $boxes = $draw['boxes'];
                        $indexesRegister = $this->buildIndexesRegistry($boxes, $nbOut);

                        $name = $dataEvent['name'];
                        if (isset($draw['name'])) {
                            $name .= ' &bull; ' . $draw['name'];
                        }
                        $boxesData = [];
                        // L'export JSON de JA Tennis place les $nbOut boîtes sortantes en tête de l'entrée "boxes".
                        // On ne parcourt donc ici que les $nbOut premières boîtes, le parsing du reste se faisant
                        // ensuite récursivement.
                        for ($idx = 0; $idx < $nbOut; $idx++) {
                            $boxesData[] = $this->parseBox($name, $boxes, $idx, $indexesRegister);
                        }

                        // On profite de la boucle pour enregistrer les résultats (= vainqueurs + finalistes)
                        // Avant de trier par ordre décroissant, le vainqueur est la 1ère "boîte" (pour les tableaux
                        // dont il ressort 1 seule personne, càd où $nbOut=1.
                        if (1 === $nbOut && isset($boxesData[0])) {
                            $this->addResultDataFromFinalBox($name, $boxesData[0]);
                        }

                        krsort($boxesData);

                        $this->tablesData[] = [
                            'id' => $draw['id'],
                            'name' => $name,
                            'boxes' => $boxesData,
                        ];
                    }
                }
            }
        }

        return $this->tablesData;
    }

    /**
     * Extrait et/ou retourne les données sur les joueurs.
     *
     * @return array
     */
    protected function getPlayersData()
    {
        if (null === $this->playersData) {
            $this->playersData = [];

            if (isset($this->jsonData['players'])) {
                foreach ($this->jsonData['players'] as $playerData) {
                    // On gère les noms trop long...
                    if (strlen($playerData['name']) > 15) {
                        // dans ce cas, on affiche que la 1ère lettre du prénom
                        $name = $playerData['name'] . ' ' . substr($playerData['firstname'], 0, 1) . '.';
                    } else {
                        $name = $playerData['name'] . ' ' . $playerData['firstname'];
                    }

                    $this->playersData[$playerData['id']] = [
                        'jid' => $playerData['id'],
                        'name' => $name,
                        'rank' => $playerData['rank'],
                        'year' => isset($playerData['birth']) ? substr($playerData['birth'], 0, 4) : '',
                        'cat' => $playerData['sexe'],
                        'club' => isset($playerData['club']) ? $playerData['club'] : '',
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
     * @param int $nbOut
     *
     * @return array
     */
    protected function buildIndexesRegistry(array &$boxes, $nbOut)
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
     * @param array $boxes
     * @param        $boxIdx
     * @param array $indexesRegister
     *
     * @return array
     */
    protected function parseBox($tableName, array $boxes, $boxIdx, array $indexesRegister)
    {
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
            }
            if (isset($box['date'])) {
                $boxData['date'] = $this->getFormattedDateTime($box['date']);
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

    /**
     * Reformate la date donnée en renvoyant la date et l'heure.
     *
     * @param string $inputDateTime Date au format "Y-m-d\TH:i:s"
     *
     * @return string
     */
    protected function getFormattedDateTime($inputDateTime)
    {
        $outputDate = $this->getFormattedDate($inputDateTime);
        $outputTime = $this->getFormattedTime($inputDateTime);

        return "$outputDate - $outputTime"; // Donne par ex: jeu. 21 - 20h30
    }

    /**
     * Ajoute une donnée de planning à partir des éléments fournis.
     *
     * @param array $box
     * @param array $boxBtm
     * @param array $boxTop
     */
    protected function updatePlanningData(array $box, array $boxBtm, array $boxTop)
    {
        if (null === $this->planningData) {
            $this->planningData = [];
        }

        $date = $box['date'];
        $place = $box['place'];
        $score = isset($box['score']) ? $box['score'] : '';

        if (!isset($this->planningData[$date][$place])) {
            $jId = isset($box['playerId']) ? $box['playerId'] : null;
            $this->addPlanningData($date, $score, $place, $jId, $boxBtm, $boxTop);
        }
    }

    /**
     * Retourne les résultats du tournoi.
     *
     * @return array
     */
    protected function getResultData()
    {
        if (null === $this->resultsData) {
            $this->resultsData = [];
        }

        return $this->resultsData;
    }

    /**
     * Retourne les données sur les joueurs, triés par nom.
     * Les clés sont réinitialisées : on perd ici les id utilisés par JA-Tennis.
     */
    protected function getSortedByNamePlayersData()
    {
        $sortedPlayersData = $this->getPlayersData();

        // On trie les joueurs selon leur nom
        usort($sortedPlayersData,
            function ($player1, $player2) {
                return ($player1['name']) < $player2['name'] ? -1 : 1;
            });

        return $sortedPlayersData;
    }
}
