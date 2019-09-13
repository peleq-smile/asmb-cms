<?php

namespace Bundle\Asmb\Competition\Parser;

use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Entity\Championship\PoolRanking;
use Bundle\Asmb\Competition\Helpers\CalendarHelper;

/**
 * Service d'extraction des données FFT pour la récupération des rencontres dans une poule.
 *
 * @copyright 2019
 */
class PoolMeetingsParser extends AbstractGsParser
{
    /**
     * Requete pour la récupération du tableau des rencontres de chaque équipe
     *
     * @var string
     */
    private $xpathMeetingsContext = "//table[@class='L1']/tr/td/table[1]/tr[position() >= 2]";

    /**
     * {@inheritdoc}
     */
    protected function doParse(Pool $pool)
    {
        $nodesMeetings = $this->xpath->query($this->xpathMeetingsContext);

        /** @var \DOMElement $node */
        $poolMeetings = [];
        foreach ($nodesMeetings as $node) {
            $homeTeamNameFft = $this->getTextContentFromXpath("td[3]//nobr", $node);
            $visitorTeamNameFft = $this->getTextContentFromXpath("td[4]//nobr", $node);
            $date = $this->getDateContentFromXpath("td[5]", $node);
            $day = $this->getTextContentFromXpath("td[6]", $node);
            $result = $this->getTextContentFromXpath("td[7]", $node);

            // Extraction du lien vers la feuille de match
            $matchesSheetLinkNode = $this->xpath->query("td[9]//a", $node)->item(0);
            $matchesSheetParams = $this->getMeetingsSheetParams($pool->getFftId(), $matchesSheetLinkNode);

            $poolMeeting = new PoolMeeting();
            $poolMeeting->setPoolId($pool->getId());
            $poolMeeting->setDate($date);
            $poolMeeting->setDay($day);
            $poolMeeting->setHomeTeamNameFft($homeTeamNameFft);
            $poolMeeting->setVisitorTeamNameFft($visitorTeamNameFft);
            $poolMeeting->setResult($result);
            $poolMeeting->setParamsFdmFft($matchesSheetParams);

            $poolMeetings[] = $poolMeeting;
        }

        return $poolMeetings;
    }

    /**
     * Retourne les paramètres FFT à utiliser pour construire ultérieurement le lien vers la feuille de match.
     *
     * @param int         $fftId
     * @param \DOMElement|null $matchesSheetLinkNode
     *
     * @return array
     */
    protected function getMeetingsSheetParams($fftId, \DOMElement $matchesSheetLinkNode = null)
    {
        $matchesSheetParams = [];

        /**
         * Exemple de contenu du <td> contenant le lien vers la feuille de match :
         * <a href="javascript:openFDM('6642514','5714939','150239','0');">
         *     <img src="config/match-smaller.gif">
         * </a>
         */
        if (null !== $matchesSheetLinkNode && $matchesSheetLinkNode->hasAttribute('href')) {
            $href = $matchesSheetLinkNode->getAttribute('href');

            $regexMeetings = [];
            // Cette expr. régulière permet d'extraire les 4 paramètres de la fonction JS openFDM()
            // Au cas où la fonction changerait de nom, on décide d'extraire juste ce qu'il y a entre parenthèses.
            if (preg_match("#[^\(]+\('(\d+)','(\d+)','(\d+)','\d+'\)#", $href, $regexMeetings)) {
                $renId = $regexMeetings[1];
                $emfId = $regexMeetings[2];
                $phaId = $regexMeetings[3];

                $matchesSheetParams = [
                    'pou_iid' => $fftId,
                    'ren_iid' => $renId,
                    'efm_iid' => $emfId,
                    'pha_iid' => $phaId,
                ];
            }
        }

        return $matchesSheetParams;
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        // TODO déporter dans de la conf

        return 'http://www.gs.applipub-fft.fr/fftfr/pouleRencontres.do?dispatch=load&pou_iid=$fftId$&pagerPage=$page$';
    }
}
