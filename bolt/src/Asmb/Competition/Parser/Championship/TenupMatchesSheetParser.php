<?php

namespace Bundle\Asmb\Competition\Parser\Championship;

use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Helpers\PoolMeetingHelper;
use Carbon\Carbon;

/**
 * Service d'extraction des données FFT pour la récupération des rencontres dans une poule.
 *
 * @copyright 2019
 */
class TenupMatchesSheetParser extends AbstractTenupJsonParser
{
    protected function doParse(Championship $championship, Pool $pool, ?PoolMeeting $poolMeeting, array $jsonData): ?array
    {
        $poolMeetingMatches = [];

        $matchesRows = $jsonData['components']['matchs_in_feuille']['rows'];

        foreach ($matchesRows as $matchesRow) {
            $poolMeetingMatch = new Championship\PoolMeetingMatch();
            $poolMeetingMatch->setCreatedAt(new Carbon());
            $poolMeetingMatch->setUpdatedAt(new Carbon());
            $poolMeetingMatch->setPoolMeetingId($poolMeeting->getId());
            $poolMeetingMatch->setType($matchesRow['type_match']);
            $poolMeetingMatch->setLabel($matchesRow['label']);
            $poolMeetingMatch->setPosition($matchesRow['numero']);

            $homePlayerInfos = $matchesRow['teams']['equipe_1']['joueurs']['joueur_1'];
            $poolMeetingMatch->setHomePlayerName(ucwords(strtolower($homePlayerInfos['prenom'])) . ' ' . strtoupper($homePlayerInfos['nom']));
            $poolMeetingMatch->setHomePlayerRank($homePlayerInfos['classements']['simple']);
            if (isset($matchesRow['teams']['equipe_1']['joueurs']['joueur_2'])) { // cas des doubles !
                $homePlayerInfos = $matchesRow['teams']['equipe_1']['joueurs']['joueur_2'];
                $poolMeetingMatch->setHomePlayer2Name(ucwords(strtolower($homePlayerInfos['prenom'])) . ' ' . strtoupper($homePlayerInfos['nom']));
                $poolMeetingMatch->setHomePlayer2Rank($homePlayerInfos['classements']['simple']);
            }

            $visitorPlayerInfos = $matchesRow['teams']['equipe_2']['joueurs']['joueur_1'];
            $poolMeetingMatch->setVisitorPlayerName(ucwords(strtolower($visitorPlayerInfos['prenom'])) . ' ' . strtoupper($visitorPlayerInfos['nom']));
            $poolMeetingMatch->setVisitorPlayerRank($visitorPlayerInfos['classements']['simple']);
            if (isset($matchesRow['teams']['equipe_2']['joueurs']['joueur_2'])) { // cas des doubles !
                $visitorPlayerInfos = $matchesRow['teams']['equipe_2']['joueurs']['joueur_2'];
                $poolMeetingMatch->setVisitorPlayer2Name(ucwords(strtolower($visitorPlayerInfos['prenom'])) . ' ' . strtoupper($visitorPlayerInfos['nom']));
                $poolMeetingMatch->setVisitorPlayer2Rank($visitorPlayerInfos['classements']['simple']);
            }

            if (true === $matchesRow['teams']['equipe_1']['win']) {
                $score = PoolMeetingHelper::RESULT_FLAG_VICTORY;
            } else {
                $score = PoolMeetingHelper::RESULT_FLAG_DEFEAT;
            }

            foreach ($matchesRow['teams']['equipe_1']['scores'] as $idxScore => $rowScore) {
                if (null !== $rowScore['value'] && isset($matchesRow['teams']['equipe_2']['scores'][$idxScore]['value'])) {
                    $score .= ' ' . (int) $rowScore['value'] . '/' . (int) $matchesRow['teams']['equipe_2']['scores'][$idxScore]['value'];
                }
            }

            if ('A' === $matchesRow['teams']['equipe_1']['type_lose'] || 'A' === $matchesRow['teams']['equipe_2']['type_lose']) {
                // cas "Abandon"
                $score .= ' Ab.';
            }

            $poolMeetingMatch->setScore(trim($score));

            $poolMeetingMatches[] = $poolMeetingMatch;
        }

        return $poolMeetingMatches;
    }
}
