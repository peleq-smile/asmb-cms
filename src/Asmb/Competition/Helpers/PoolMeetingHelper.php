<?php

namespace Bundle\Asmb\Competition\Helpers;

use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;

/**
 * Helper pour les rencontres de poules.
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2019
 */
class PoolMeetingHelper
{
    const RESULT_NONE         = 'Aucun';
    const RESULT_FLAG_VICTORY = 'V';
    const RESULT_FLAG_DEFEAT  = 'D';
    const RESULT_FLAG_DRAW    = 'N';

    /**
     * Extrait et retourne le score à partir de la rencontre donnée.
     *
     * @param PoolMeeting $meeting
     *
     * @return string
     */
    public static function getScoreFromMeeting(PoolMeeting $meeting)
    {
        $result = $meeting->getResult();

        if (self::RESULT_NONE !== $result)  {
            $score = substr($result, 2, 3);
        } else {
            $score = '';
        }

        return $score;
    }

    /**
     * Retourne VRAI si la rencontre est une victoire pour l'équipe donnée, si celle-ci fait partie du club.
     *
     * @param \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting $meeting
     *
     * @return bool
     */
    public static function isClubVictory(PoolMeeting $meeting)
    {
        $scoreFlag = self::getScoreFlagFromMeeting($meeting);

        if (
            (true === $meeting->getHomeTeamIsClub() && self::RESULT_FLAG_VICTORY === $scoreFlag)
            || (true === $meeting->getVisitorTeamIsClub() && self::RESULT_FLAG_DEFEAT === $scoreFlag)
        ) {
            $isClubVictory = true;
        } else {
            $isClubVictory = false;
        }

        return $isClubVictory;
    }

    /**
     * Extrait et retourne l'indicateur de score à partir de la rencontre donnée.
     *
     * @param PoolMeeting $meeting
     *
     * @return string
     */
    protected static function getScoreFlagFromMeeting(PoolMeeting $meeting)
    {
        $result = $meeting->getResult();
        $scoreFlag = substr($result, 0, 1);

        return $scoreFlag;
    }

    /**
     * Retourne VRAI si la rencontre est une défaite pour l'équipe donnée, si celle-ci fait partie du club.
     *
     * @param \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting $meeting
     *
     * @return bool
     */
    public static function isClubDefeat(PoolMeeting $meeting)
    {
        $scoreFlag = self::getScoreFlagFromMeeting($meeting);

        if (
            (true === $meeting->getHomeTeamIsClub() && self::RESULT_FLAG_DEFEAT === $scoreFlag)
            || (true === $meeting->getVisitorTeamIsClub() && self::RESULT_FLAG_VICTORY === $scoreFlag)
        ) {
            $isClubDefeat = true;
        } else {
            $isClubDefeat = false;
        }

        return $isClubDefeat;
    }

    /**
     * Retourne VRAI si la rencontre est une égalité pour l'équipe donnée, si celle-ci fait partie du club.
     *
     * @param \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting $meeting
     *
     * @return bool
     */
    public static function isClubDraw(PoolMeeting $meeting)
    {
        $scoreFlag = self::getScoreFlagFromMeeting($meeting);

        if (
            self::RESULT_FLAG_DRAW === $scoreFlag &&
            (true === $meeting->getHomeTeamIsClub() || true === $meeting->getVisitorTeamIsClub())
        ) {
            $isClubDraw = true;
        } else {
            $isClubDraw = false;
        }

        return $isClubDraw;
    }
}
