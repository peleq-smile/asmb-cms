<?php

namespace Bundle\Asmb\Competition\Parser;

use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolTeam;

/**
 * Service d'extraction des données FFT pour la récupération des équipes de poules.
 *
 * @copyright 2019
 */
class PoolTeamsParser extends AbstractGsParser
{
    /**
     * Requete pour la récupération du tableau de classement de chaque équipe
     *
     * @var string
     */
    private $xpathRankingContext = "//table[@class='L1']/tr/td/table[1]/tr[position() >= 2]";

    /**
     * {@inheritdoc}
     */
    protected function doParse(Pool $pool)
    {
        $nodesRanking = $this->xpath->query($this->xpathRankingContext);

        /** @var \DOMElement $node */
        $poolTeams = [];
        foreach ($nodesRanking as $node) {
            $teamName = $this->getTextContentFromXpath( "td[1]//nobr", $node);

            $poolTeam = new PoolTeam();
            $poolTeam->setPoolId($pool->getId());
            $poolTeam->setNameFft($teamName);

            $poolTeams[] = $poolTeam;
        }

        return $poolTeams;
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        // TODO déporter dans de la conf

        return 'http://www.gs.applipub-fft.fr/fftfr/pouleClassement.do?dispatch=load&pou_iid=$fftId$';
    }
}
