<?php

namespace Bundle\Asmb\Competition\Parser\Tournament;

use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Tournament\Box;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use Symfony\Component\Debug\Exception\ContextErrorException;

/**
 * Classe abstraite pour les parseurs de données de tournois.
 *
 * @property string url
 * @copyright 2020
 */
abstract class AbstractParser
{
    /** @var array */
    protected $infoData;
    /** @var array */
    protected $playersData;
    /** @var array */
    protected $tablesData;
    /** @var array */
    protected $planningData;
    /** @var array */
    protected $resultsData;

    abstract protected function getInfoData();

    abstract protected function getTablesData();

    abstract protected function getResultData();

    abstract protected function getPlayersData();

    abstract protected function getSortedByNamePlayersData();

    abstract public function parse();

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
            $outputFormat = '%a %eer %b';
        } else {
            $outputFormat = '%a %e %b';
        }

        $cleanedDate = str_replace(
            ['.', ' ', '1er', 'é', 'û'],
            ['', '', '1', 'e', 'u'],
            $inputDate->formatLocalized($outputFormat)
        ); // Donne par ex: jeu21fevr

        return $cleanedDate;
    }

    /**
     * Reformate la date donnée en renvoyant la date uniquement
     *
     * @param string|Carbon $inputDateTime Date au format "Y-m-d\TH:i:s" ou "Y-m-d H:i" ou "Y-m-d" ou objet Carbon
     *
     * @return string
     */
    public function getFormattedDate($inputDateTime)
    {
        $formattedDate = '';

        // Exemple de date en entrée: 2019-02-21T20:30:00 OU 2019-02-21
        if ($inputDateTime instanceof Carbon) {
            $carbonDate = $inputDateTime;
        } elseif (19 === strlen($inputDateTime)) {
            $carbonDate = Carbon::createFromFormat('Y-m-d\TH:i:s', $inputDateTime);
        } elseif(16 === strlen($inputDateTime)) {
            $carbonDate = Carbon::createFromFormat('Y-m-d H:i', $inputDateTime);
        } elseif(10 === strlen($inputDateTime)) {
            $carbonDate = Carbon::createFromFormat('Y-m-d', $inputDateTime);
        }

        if (isset($carbonDate)) {
            if ($carbonDate->daysInMonth == 1) {
                $outputFormat = '%a %eer %b';
            } else {
                $outputFormat = '%a %e %b';
            }
            $formattedDate = $carbonDate->formatLocalized($outputFormat); // Donne par ex: jeu. 21 févr.
        }

        return $formattedDate;
    }

    /**
     * Reformate la date donnée en renvoyant l'heure uniquement.
     *
     * @param string $inputDateTime Date au format "Y-m-d\TH:i:s" ou "Y-m-d"
     *
     * @return string
     */
    public function getFormattedTime(string $inputDateTime)
    {
        $formattedTime = '';

        // Exemple de date en entrée: 2019-02-21T20:30:00
        if (19 === strlen($inputDateTime)) {
            $carbonDate = Carbon::createFromFormat('Y-m-d\TH:i:s', $inputDateTime);
        } elseif(16 === strlen($inputDateTime)) {
            $carbonDate = Carbon::createFromFormat('Y-m-d H:i', $inputDateTime);
        }

        if (isset($carbonDate)) {
            if ($carbonDate->minute > 0) {
                $outputFormat = '%Hh%M';
            } else {
                $outputFormat = '%Hh';
            }

            $formattedTime = $carbonDate->formatLocalized($outputFormat); // Donne par ex: "19h" ou "20h30"
        }

        return $formattedTime;
    }

    protected function getUpdatedAtFormatted(Carbon $datetime): string
    {
        $generateTimeFormatted = $this->getFormattedTime($datetime->format('Y-m-d\TH:i:s'));

        return $datetime->format('d/m/Y') . " à $generateTimeFormatted";
    }

    protected function addMatchesDataOnPlayersData($jId, $looserPlayer, $tableName, $boxDatetime, $boxData)
    {
        if (null !== $jId && null !== $looserPlayer) {
            // cas où le match a eu lieu

            // $boxDatetime en tant que clé va permettre de trier par ordre de date
            // cas victoire
            $this->playersData[$jId]['matches'][$tableName][$boxDatetime] = [
                'player' => $looserPlayer, // le joueur $jId a joué contre $looserPlayer
                'victory' => true, // cas victoire ici
                'score' => isset($boxData['score']) ? $boxData['score'] : '',
                'date' => $boxData['date'], // ici, la date joliement formatée !
            ];

            // cas défaite
            $this->playersData[$looserPlayer['jid']]['matches'][$tableName][$boxDatetime] = [
                'player' => [
                    'jid' => $boxData['jid'],
                    'name' => $boxData['name'],
                    'rank' => $boxData['rank'],
                ],
                'victory' => false, // cas défaite ici
                'score' => isset($boxData['score']) ? $boxData['score'] : '',
                'date' => $boxData['date'], // ici, la date joliement formatée !
            ];
        } else {
            // cas où le match n'a pas eu lieu : on regarde dans chacune des 2 boîtes précédentes
            if (isset($boxData['prevBtm']['jid'])) {
                $this->playersData[$boxData['prevBtm']['jid']]['matches'][$tableName][$boxDatetime] = [
                    'player' => [
                        'jid' => $boxData['prevTop']['jid'] ?? '',
                        'name' => $boxData['prevTop']['name'] ?? '',
                        'rank' => $boxData['prevTop']['rank'] ?? '',
                    ],
                    'victory' => null,
                    'score' => '',
                    'date' => $boxData['date'], // ici, la date joliement formatée !
                ];
            }

            if (isset($boxData['prevTop']['jid'])) {
                $this->playersData[$boxData['prevTop']['jid']]['matches'][$tableName][$boxDatetime] = [
                    'player' => [
                        'jid' => $boxData['prevBtm']['jid'] ?? '',
                        'name' => $boxData['prevBtm']['name'] ?? '',
                        'rank' => $boxData['prevBtm']['rank'] ?? '',
                    ],
                    'victory' => null,
                    'score' => '',
                    'date' => $boxData['date'], // ici, la date joliement formatée !
                ];
            }
        }
    }

    /**
     * Retourne les données sur les planning par jour du tournoi, triées correctement.
     *
     * @return array
     */
    protected function getSortedPlanningData(): array
    {
        $sortedPlanningData = [];

        if (null !== $this->planningData) {
            // On trie puis reformatte les dates/heures
            ksort($this->planningData);

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

    protected function addPlanningData($date, $score, $place, $jId, $boxBtm, $boxTop)
    {
        if (null !== $jId && isset($boxBtm['jid']) && $jId === $boxBtm['jid']) {
            // Ici, le vainqueur de la rencontre est dans la boîte du bas
            $boxPlayer1 = $boxBtm;
            $boxPlayer2 = $boxTop;
        } else {
            // Sinon le vainqueur est dans la boîte du haut ou bien le score n'est pas encore connu.
            $boxPlayer1 = $boxTop;
            $boxPlayer2 = $boxBtm;
        }

        // Si pas encore de donnée sur cette rencontre, à cette date + heure + lieu : on ajoute le 1er joueurs
        $this->planningData[$date][$place] = [
            'table' => $boxPlayer1['table'] ?? '',
            'player1' => [
                'jid' => $boxPlayer1['jid'] ?? '',
                'name' => $boxPlayer1['name'] ?? '',
                'rank' => $boxPlayer1['rank'] ?? '',
                'club' => $boxPlayer1['club'] ?? '',
                'qualif' => $boxPlayer1['qualif'] ?? '',
            ],
            'player2' => [
                'jid' => $boxPlayer2['jid'] ?? '',
                'name' => $boxPlayer2['name'] ?? '',
                'rank' => $boxPlayer2['rank'] ?? '',
                'club' => $boxPlayer2['club'] ?? '',
                'qualif' => $boxPlayer2['qualif'] ?? '',
            ],
            'score' => $score,
        ];
    }

    /**
     * Ajoute des données de résultat à partir de la "boîte" finale donnée pour le tableau donné.
     *
     * @param string $tableName Nom du tableau
     * @param array $finalBox Dernière "boîte" du tableau
     *
     * @return void
     */
    protected function addResultDataFromFinalBox(string $tableName, array $finalBox)
    {
        // On teste s'il on connaît au moins les finalistes, sans cela => pas de résultat
        if (isset($finalBox['prevBtm']['name'], $finalBox['prevTop']['name'])) {
            if (isset($finalBox['name'], $finalBox['rank'], $finalBox['score'])) {
                // On connaît le vainqueur
                $this->resultsData[$tableName] = [
                    'winner' => [
                        'jid' => $finalBox['jid'],
                        'name' => $finalBox['name'],
                        'rank' => $finalBox['rank'],
                        'club' => $finalBox['club'] ?? '',
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
                        'jid' => $boxFinalist['jid'],
                        'name' => $boxFinalist['name'],
                        'rank' => $boxFinalist['rank'],
                        'club' => $boxFinalist['club'] ?? '',
                    ];
                }
            } else {
                // On a seulement les finalistes
                $this->resultsData[$tableName] = [
                    'finalists' => [
                        [
                            'jid' => $finalBox['prevBtm']['jid'],
                            'name' => $finalBox['prevBtm']['name'],
                            'rank' => $finalBox['prevBtm']['rank'],
                            'club' => $finalBox['prevBtm']['club'] ?? '',
                        ],
                        [
                            'jid' => $finalBox['prevTop']['jid'],
                            'name' => $finalBox['prevTop']['name'],
                            'rank' => $finalBox['prevTop']['rank'],
                            'club' => $finalBox['prevTop']['club'] ?? '',
                        ],
                    ],
                ];
            }
        }
    }
}
