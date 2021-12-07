<?php

namespace Bundle\Asmb\Competition\Parser\Championship;

use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolRanking;

/**
 * Service d'extraction des données FFT pour la récupération du classement dans une poule.
 *
 * @copyright 2019
 */
class GsPoolRankingParser extends AbstractGsParser
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
    protected function doParse(Pool $pool): array
    {
        $nodesRanking = $this->xpath->query($this->xpathRankingContext);

        /** @var \DOMElement $node */
        $poolRankings = [];
        foreach ($nodesRanking as $node) {
            $teamNameFft = $this->getTextContentFromXpath( "td[1]//nobr", $node);
            $points = (int) $this->getTextContentFromXpath( "td[3]", $node);
            $daysPlayed = (int) $this->getTextContentFromXpath( "td[4]", $node);
            $matchDiff = (int) $this->getTextContentFromXpath( "td[5]", $node);
            $setDiff = (int) $this->getTextContentFromXpath( "td[6]", $node);
            $gameDiff = (int) $this->getTextContentFromXpath( "td[7]", $node);

            $poolRanking = new PoolRanking();
            $poolRanking->setTeamNameFft($teamNameFft);
            $poolRanking->setPoolId($pool->getId());
            $poolRanking->setPoints($points);
            $poolRanking->setDaysPlayed($daysPlayed);
            $poolRanking->setMatchDiff($matchDiff);
            $poolRanking->setSetDiff($setDiff);
            $poolRanking->setGameDiff($gameDiff);

            $poolRankings[] = $poolRanking;
        }

        return $poolRankings;
    }
}
