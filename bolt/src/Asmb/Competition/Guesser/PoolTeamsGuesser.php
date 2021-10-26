<?php

namespace Bundle\Asmb\Competition\Guesser;

use Bundle\Asmb\Competition\Helpers\PoolTeamHelper;

/**
 * Service de déduction des noms des équipes en interne et de l'équipe qui fait partie du club.
 *
 * @copyright 2019
 */
class PoolTeamsGuesser
{
    /**
     * Déduit les noms des équipes à utiliser en interne à partir des noms FFT, ainsi que l'équipe
     * faisant partie du club.
     *
     * @param \Bundle\Asmb\Competition\Entity\Championship\PoolTeam[] $poolTeams
     *
     * @return void
     */
    public function guess(array $poolTeams)
    {
        foreach ($poolTeams as $poolTeam) {
            if (strpos($poolTeam->getNameFft(), PoolTeamHelper::ASMB_TEAM_PREFIX) === 0) {
                // L'équipe fait partie du club : on la flague comme telle, mais ne donne pas de nom personnalisé.
                $poolTeam->setIsClub(true);
            } else {
                // L'équipe ne fait pas partie du club, on lui attribue un nom interne plus court.
                $name = $this->buildNameFromNameFft($poolTeam->getNameFft());
                $poolTeam->setName($name);
            }
        }
    }

    /**
     * Génère le nom interne final à partir du nom FFT actuel.
     * Ex: "TC B. GOULAINE 1" devient "TcB.Goulaine1"
     *
     * @param string $nameFft
     *
     * @return string
     */
    protected function buildNameFromNameFft($nameFft)
    {
        if (strpos($nameFft, PoolTeamHelper::EXEMPT_TEAM_PREFIX) === 0) {
            // L'équipe est une "fausse" équipe pour faire un nombre pair d'équipes dans la poule.
            // On lui donne le nom de "Exempt" + numéro
            $name = str_replace(PoolTeamHelper::EXEMPT_TEAM_PREFIX, 'Exempt', $nameFft);
            $name = str_replace(' ', '', $name);
        } else {
            $name = strtolower($nameFft);
            $name = ucwords($name);
            $name = str_ireplace([' ', '\'', 'tennis', 'nantes'], ['', '', '', ''], $name);
        }

        return $name;
    }
}
