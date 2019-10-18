<?php

namespace Bundle\Asmb\Competition\Parser;

use Carbon\Carbon;
use JsonSchema\Exception\RuntimeException;

/**
 * Parseur de JSON exporté depuis JA-Tennis
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2019
 */
class JaTennisJsonParser
{
    /** @var string */
    protected $jsonFileUrl;
    /** @var array */
    protected $jsonData;
    /** @var array */
    protected $playersData;
    /** @var array */
    protected $tablesData;
    /** @var array */
    protected $planningData;
    /** @var array */
    protected $resultsData;

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
                'info'     => $this->getInfoData(),
                'tables'   => $this->getTablesData(),
                'planning' => $this->getSortedPlanningData(),
                'result'   => $this->getResultData(),
                'players'  => $this->getSortedByNamePlayersData(),
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
    protected function getInfoData()
    {
        $info = $this->jsonData['info'];
        
        // JA Tennis semble décaler les date d'1 mois, on rectifie ici       
        $beginDate = Carbon::createFromFormat('Y-m-d', $info['begin']);
        $beginDate->modify('+1 month'); // Ex: "2019-09-21" devient "2019-10-21"
        $info['begin'] = $beginDate->format('Y-m-d');
        
        $endDate = Carbon::createFromFormat('Y-m-d', $info['end']);
        $endDate->modify('+1 month');
        $info['end'] = $endDate->format('Y-m-d');
        
        // Ajout de la date de dernière màj des données
        // Ex: "2019-09-18T01:08:00"
        $generateDate = Carbon::createFromFormat('Y-m-d\TH:i:s', $this->jsonData['jat']['generate']);
        $generateTimeFormatted = $this->getFormattedTime($this->jsonData['jat']['generate']);
        $info['updatedAt'] = $generateDate->format('d/m/Y') . " à $generateTimeFormatted";
        
        return $info;
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
                            'id'    => $draw['id'],
                            'name'  => $name,
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
                        'jid'  => $playerData['id'],
                        'name' => $name,
                        'rank' => $playerData['rank'],
                        'year' => isset($playerData['birth']) ? substr($playerData['birth'], 0, 4) : '',
                        'sexe' => $playerData['sexe'],
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
     * @param int   $nbOut
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
                $boxDate = Carbon::createFromFormat('Y-m-d\TH:i:s', $box['date']);
                $boxDate->modify('+1 month');
                $box['date'] = $boxDate->format('Y-m-d\TH:i:s');
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
                        'jid'  => $boxData['prevBtm']['jid'],
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
                        'jid'  => $boxData['prevTop']['jid'],
                        'name' => $boxData['prevTop']['name'],
                        'rank' => $boxData['prevTop']['rank'],
                    ];
                }
            }

            // On a une date : on enregistre des données sur le planning ici
            if (isset($box['date'], $boxData['prevBtm'], $boxData['prevTop'])) {
                $this->addPlanningData($box, $boxData['prevBtm'], $boxData['prevTop']);
            }

            // On ajoute les données de match sur les joueurs
            if (isset($box['date'])) {
                if (isset($jId, $looserPlayer)) {
                    // cas où le match a eu lieu
                    
                    // $box['date'] en tant que clé va permettre de trier par ordre de date
                    // cas victoire
                    $this->playersData[$jId]['matches'][$tableName][$box['date']] = [
                        'player'  => $looserPlayer,
                        'victory' => true, // cas victoire ici
                        'score'   => isset($boxData['score']) ? $boxData['score'] : '',
                        'date'    => $boxData['date'], // ici, la date joliement formatée !
                    ];

                    // cas défaite
                    $this->playersData[$looserPlayer['jid']]['matches'][$tableName][$box['date']] = [
                        'player'  => [
                            'jid'  => $boxData['jid'],
                            'name' => $boxData['name'],
                            'rank' => $boxData['rank'],
                        ],
                        'victory' => false, // cas défaite ici
                        'score'   => isset($boxData['score']) ? $boxData['score'] : '',
                        'date'    => $boxData['date'], // ici, la date joliement formatée !
                    ];
                } else {
                    
                }
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
     * Reformate la date donnée en renvoyant la date uniquement
     *
     * @param string|Carbon $inputDateTime Date au format "Y-m-d\TH:i:s" ou objet Carbon
     *
     * @return string
     */
    public function getFormattedDate($inputDateTime)
    {
        // Exemple de date en entrée: 2019-02-21T20:30:00
        if ($inputDateTime instanceof Carbon) {
            $carbonDate = $inputDateTime;          
        } else {
            $carbonDate = Carbon::createFromFormat('Y-m-d\TH:i:s', $inputDateTime);
        }
        
        if ($carbonDate->daysInMonth == 1) {
            $outputFormat = '%a %eer';
        } else {
            $outputFormat = '%a %e';
        }

        return $carbonDate->formatLocalized($outputFormat); // Donne par ex: jeu. 21
    }

    /**
     * Reformate la date donnée en renvoyant la date uniquement
     *
     * @param Carbon $inputDate
     *
     * @return string
     */
    public function getFormattedCleanedDate(Carbon $inputDate)
    {        
        if ($inputDate->daysInMonth == 1) {
            $outputFormat = '%a %eer';
        } else {
            $outputFormat = '%a %e';
        }

        $cleanedDate = str_replace(
            ['.', ' ', '1er'],
            ['', '', '1'],
            $inputDate->formatLocalized($outputFormat)
        ); // Donne par ex: jeu21

        return $cleanedDate;
    }

    /**
     * Reformate la date donnée en renvoyant l'heure uniquement.
     *
     * @param string $inputDateTime Date au format "Y-m-d\TH:i:s"
     *
     * @return string
     */
    protected function getFormattedTime($inputDateTime)
    {
        // Exemple de date en entrée: 2019-02-21T20:30:00
        $carbonDate = Carbon::createFromFormat('Y-m-d\TH:i:s', $inputDateTime);
        if ($carbonDate->minute > 0) {
            $outputFormat = '%Hh%M';
        } else {
            $outputFormat = '%Hh';
        }

        return $carbonDate->formatLocalized($outputFormat); // Donne par ex: "19h" ou "20h30"
    }

    /**
     * Ajoute une donnée de planning à partir des éléments fournis.
     *
     * @param array $box
     * @param array $boxBtm
     * @param array $boxTop
     */
    protected function addPlanningData(array $box, array $boxBtm, array $boxTop)
    {
        if (null === $this->planningData) {
            $this->planningData = [];
        }

        $date = $box['date'];
        $place = $box['place'];
        $score = isset($box['score']) ? $box['score'] : '';

        if (!isset($this->planningData[$date][$place])) {
            if (isset($box['playerId']) && isset($boxBtm['jid']) && $box['playerId'] === $boxBtm['jid']) {
                // Ici, le vainqueur de la rencontre est dans la boîte du bas
                $boxPlayer1 = $boxBtm;
                $boxPlayer2 = $boxTop;
            } else {
                // Sinon le vainqueur est dans la boîte du haut ou bien le score n'est pas encore connu.
                $boxPlayer1 = $boxTop;
                $boxPlayer2 = $boxBtm;
            }

            // Pas encore de donnée sur cette rencontre, à cette date + heure + lieu : on ajoute le 1er joueur
            $this->planningData[$date][$place] = [
                'table'   => $boxPlayer1['table'],
                'player1' => [
                    'jid'    => $boxPlayer1['jid'] ?? '',
                    'name'   => $boxPlayer1['name'] ?? '',
                    'rank'   => $boxPlayer1['rank'] ?? '',
                    'qualif' => $boxPlayer1['qualif'] ?? '',
                ],
                'player2' => [
                    'jid'    => $boxPlayer2['jid'] ?? '',
                    'name'   => $boxPlayer2['name'] ?? '',
                    'rank'   => $boxPlayer2['rank'] ?? '',
                    'qualif' => $boxPlayer2['qualif'] ?? '',
                ],
                'score'   => $score,
            ];
        }
    }

    /**
     * Ajoute des données de résultat à partir de la "boîte" finale donnée pour le tableau donné.
     *
     * @param string $tableName Nom du tableau
     * @param array  $finalBox  Dernière "boîte" du tableau
     *
     * @return void
     */
    protected function addResultDataFromFinalBox($tableName, $finalBox)
    {
        // On teste s'il on connaît au moins les finalistes, sans cela => pas de résultat
        if (isset($finalBox['prevBtm']['name'], $finalBox['prevTop']['name'])) {
            if (isset($finalBox['name'], $finalBox['rank'], $finalBox['score'])) {
                // On connaît le vainqueur
                $this->resultsData[$tableName] = [
                    'winner' => [
                        'jid'   => $finalBox['jid'],
                        'name'  => $finalBox['name'],
                        'rank'  => $finalBox['rank'],
                        'score' => $finalBox['score'],
                    ],
                ];

                if ($finalBox['prevBtm']['name'] !== $finalBox['name']) {
                    $boxFinalist = $finalBox['prevBtm'];
                } elseif ($finalBox['prevTop']['name'] !== $finalBox['name']) {
                    $boxFinalist = $finalBox['prevTop'];
                }
                if (isset($boxFinalist)) {
                    $this->resultsData[$tableName]['finalist'] = [
                        'jid'  => $boxFinalist['jid'],
                        'name' => $boxFinalist['name'],
                        'rank' => $boxFinalist['rank'],
                    ];
                }
            } else {
                // On a seulement les finalistes
                $this->resultsData[$tableName] = [
                    'finalists' => [
                        [
                            'jid'  => $finalBox['prevBtm']['jid'],
                            'name' => $finalBox['prevBtm']['name'],
                            'rank' => $finalBox['prevBtm']['rank'],
                        ],
                        [
                            'jid'  => $finalBox['prevTop']['jid'],
                            'name' => $finalBox['prevTop']['name'],
                            'rank' => $finalBox['prevTop']['rank'],
                        ],
                    ],
                ];
            }
        }
    }

    /**
     * Retourne les données sur les planning par jour du tournoi, triées correctement.
     *
     * @return array
     */
    protected function getSortedPlanningData()
    {
        $sortedPlanningData = [];

        if (null !== $this->planningData) {
            // On trie puis reformatte les dates/heures
            ksort($this->planningData);

            $sortedPlanningData = [];
            foreach ($this->planningData as $dateTime => $planningDataByPlace) {
                ksort($planningDataByPlace);
                foreach ($planningDataByPlace as $place => $planningData) {
                    $formattedDate = $this->getFormattedDate($dateTime);
                    $formattedTime = $this->getFormattedTime($dateTime);
                    $sortedPlanningData[$formattedDate][$formattedTime][$place] = $planningData;
                }
            }
        }

        return $sortedPlanningData;
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
