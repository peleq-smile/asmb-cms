<?php

namespace Bundle\Asmb\Competition\Parser\Championship;

use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Helpers\PoolMeetingHelper;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Service d'extraction des données FFT pour la récupération des rencontres dans une poule.
 *
 * @copyright 2019
 */
class TenupPoolMeetingsParser extends AbstractTenupJsonParser
{
    protected function doParse(Championship $championship, Pool $pool, ?PoolMeeting $poolMeeting, array $jsonData): array
    {
        $poolMeetings = [];

        $jsonDataRows = [];
        if (isset($jsonData['results']['components']['calendrier']['rows'])) {
            $jsonDataRows = $jsonData['results']['components']['calendrier']['rows'];
        } elseif (isset($jsonData['results']['components']['calendrier']['component_data']['rows'])) {
            $jsonDataRows = $jsonData['results']['components']['calendrier']['component_data']['rows'];
        }

        foreach ($jsonDataRows as $day => $rowByDays) {
            if (is_numeric($day)) {
                foreach ($rowByDays as $row) {
                    $poolMeeting = new PoolMeeting();
                    $poolMeeting->setPoolId($pool->getId());
                    $poolMeeting->setDay($day);
                    $poolMeeting->setDate($this->parseDateFromJson($row['date']));
                    $poolMeeting->setHomeTeamNameFft($row['team_home']['name']);
                    $poolMeeting->setVisitorTeamNameFft($row['team_visitor']['name']);

                    // On construit la donnée "résultat" telle qu'on a l'habitude avec la GS
                    $result = null;
                    $paramsFdmFft = []; //TODOpeleq à supprimer qd FDM interne
                    // La rencontre a été saisie dans Ten'Up
                    if (true === $row['team_home']['win']) {
                        // Victoire de l'équipe visitée
                        $result = PoolMeetingHelper::RESULT_FLAG_VICTORY;
                    } elseif (true === $row['team_visitor']['win']) {
                        // Défaite de l'équipe visitée
                        $result = PoolMeetingHelper::RESULT_FLAG_DEFEAT;
                    } elseif ('T' === $row['statut_feuille_match'] &&
                        $row['team_home']['score'] === $row['team_visitor']['score']
                    ) {
                        // Égalité
                        $result = PoolMeetingHelper::RESULT_FLAG_DRAW;
                    }
                    if (null !== $result) {
                        $score = $row['team_home']['score'] . '/' . $row['team_visitor']['score'];
                        $result .= ' ' . $score;

                        // Cas forfait ou disqualification
                        if ($row['team_home']['forfeit'] || $row['team_visitor']['forfeit']) {
                            $result .= ' ' . PoolMeetingHelper::RESULT_WO;
                        } elseif ($row['team_home']['disqualifie'] || $row['team_visitor']['disqualifie']) {
                            $result .= ' ' . PoolMeetingHelper::RESULT_FLAG_DISQ;
                        }

                        // on sauvegarde ici l'uri de la feuille de match (seulement si un résultat existe)
                        if (null !== $row['statut_feuille_match']) { //TODOpeleq à supprimer qd FDM interne
                            $paramsFdmFft = ['feuille_match_url' => $row['feuille_match_url']];
                        }
                    }
                    $poolMeeting->setResult($result);
                    $poolMeeting->setParamsFdmFft($paramsFdmFft); //TODOpeleq à supprimer qd FDM interne

                    if (isset($row['feuille_match'][0])) {
                        $poolMeeting->setMatchesSheetFftId($row['feuille_match'][0]);
                    }

                    $poolMeetings[] = $poolMeeting;
                }
            }
        }

        return $poolMeetings;
    }

    /**
     * Parse et retourne une date à partir de celle fournie en entrée.
     */
    protected function parseDateFromJson($inputDate): ?Carbon
    {
        preg_match('#\d\d/\d\d/\d\d#', $inputDate, $matches);
        if (!empty($matches)) {
            $dateParsed = $matches[0];

            try {
                $outputDate = Carbon::createFromFormat('d/m/y', $dateParsed);
            } catch (InvalidArgumentException $e) {
                $outputDate = null;
            }
        } else {
            $outputDate = null;
        }

        return $outputDate;
    }
}