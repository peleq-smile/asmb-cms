<?php

namespace Bundle\Asmb\Competition\Parser\Tournament;

use Carbon\Carbon;
use JsonSchema\Exception\RuntimeException;

/**
 * Parseur de fichiers exportés depuis JA-Tennis
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2021
 */
abstract class AbstractJaTennisParser extends AbstractParser
{
    /** @var string */
    protected $fileUrl;
    /** @var array */
    protected $fileData;

    public function __construct($fileUrl = null)
    {
        $this->fileUrl = $fileUrl;
    }

    /**
     * @param string $fileUrl
     */
    public function setFileUrl(string $fileUrl)
    {
        $this->fileUrl = $fileUrl;
    }

    /**
     * Ajoute 1 mois à la date entrante pour corriger le bug de JA Tennis sur les exports.
     *
     * @param $inputDate
     *
     * @return string
     */
    protected function add1month($inputDate): string
    {
        $outputDate = str_replace(
            ['-11-', '-10-', '-09-', '-08-', '-07-', '-06-', '-05-', '-04-', '-03-', '-02-', '-01-'],
            ['-12-', '-11-', '-10-', '-09-', '-08-', '-07-', '-06-', '-05-', '-04-', '-03-', '-02-'],
            $inputDate
        );

        return $outputDate;
    }

    /**
     * Reformate la date donnée en renvoyant la date et l'heure.
     *
     * @param string $inputDateTime Date au format "Y-m-d\TH:i:s" ou "Y-m-d"
     *
     * @return string
     */
    protected function getFormattedDateTime(string $inputDateTime): string
    {
        $formattedDateTime = $this->getFormattedDate($inputDateTime); // Donne par ex: jeu. 21 (ou chaîne vide)

        $outputTime = $this->getFormattedTime($inputDateTime);
        if (!empty($outputTime)) {
            $formattedDateTime .= " - $outputTime"; // Donne par ex: jeu. 21 - 20h30
        }

        return $formattedDateTime;
    }

    /**
     * Retourne les résultats du tournoi.
     *
     * @return array
     */
    protected function getResultData(): array
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
    protected function getSortedByNamePlayersData(): array
    {
        $sortedPlayersData = $this->getPlayersData();

        // On trie les joueurs selon leur nom
        usort($sortedPlayersData,
            function ($player1, $player2) {
                return ($player1['name']) < $player2['name'] ? -1 : 1;
            });

        return $sortedPlayersData;
    }

    /**
     * Construit le nom du joueur à partir du nom et du prénom.
     * On racourcit le prénom si trop long.
     *
     * @param $name
     * @param $firstname
     *
     * @return string
     */
    protected function buildNameWithFirstname($name, $firstname): string
    {
        if ((strlen($name) + strlen($firstname)) > 22) {
            if (strpos($firstname, '-')) {
                // cas prénom composé !!
                $firstnames = explode('-', $firstname);
                $firstname = strtoupper(substr($firstnames[0], 0, 1) .
                    substr($firstnames[1], 0, 1)) . '.';
            } else {
                $firstname = substr($firstname, 0, 1) . '.';
            }
        }
        return $name . ' ' . $firstname;
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

        $date = $box['date'] ?? '';
        $place = $box['place'] ?? '';
        $score = $box['score'] ?? '';

        if (!isset($this->planningData[$date][$place])) {
            $jId = $box['playerId'] ?? $box['jid'] ?? null;
            $this->addPlanningData($date, $score, $place, $jId, $boxBtm, $boxTop);
        }
    }
}
