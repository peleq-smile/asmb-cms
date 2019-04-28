<?php

namespace Bundle\Asmb\Competition\Extension;

use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Helpers\PoolMeetingHelper;

/**
 * Déclaration de filtres Twig.
 */
trait TwigFiltersTrait
{
    /**
     * Extrait et retourne le score à partir de la rencontre donnée.
     *
     * @param \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting $meeting
     * @param bool                                                     $decorated
     *
     * @return mixed|string
     */
    public function extractScoreFromResult(PoolMeeting $meeting, $decorated = false)
    {
        $score = PoolMeetingHelper::getScoreFromMeeting($meeting);
        $score = str_replace('/', ' / ', $score);

        if (!empty($score) && $decorated) {
            $stateClass = '';

            if (PoolMeetingHelper::isClubVictory($meeting)) {
                $stateClass = ' victory';
            } elseif (PoolMeetingHelper::isClubDefeat($meeting)) {
                $stateClass = ' defeat';
            } elseif (PoolMeetingHelper::isClubDraw($meeting)) {
                $stateClass = ' draw';
            }
            $score = new \Twig_Markup('<span class="score' . $stateClass . '">' . $score . '</span>', 'utf-8');
        }

        return $score;
    }

    /**
     * Retourne le lien (HTML) vers la feuille de matchs de la Gestion Sportive (FFT).
     *
     * @param \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting $meeting
     * @param string                                                   $faIcon
     *
     * @return string
     */
    public function getMatchesSheetLink(PoolMeeting $meeting, $faIcon = 'eye')
    {
        $link = '';

        $paramsFdmFft = $meeting->getParamsFdmFft();
        if (isset($paramsFdmFft['efm_iid'], $paramsFdmFft['pha_iid'], $paramsFdmFft['pou_iid'], $paramsFdmFft['ren_iid'])) {
            // TODO : en faire un param global
            $url = "http://www.gs.applipub-fft.fr/fftfr/match.do?dispatch=load&pou_iid={$paramsFdmFft['pou_iid']}"
                . "&ren_iid={$paramsFdmFft['ren_iid']}"
                . "&efm_iid={$paramsFdmFft['efm_iid']}"
                . "&pha_iid={$paramsFdmFft['pha_iid']}";

            $link = new \Twig_Markup(
                '<a href="' . $url . '" class="btn btn-xs btn-link" target="_blank">'
                . '<i class="fa fa-' . $faIcon . ' "></i></a>',
                'utf-8'
            );
        }

        return $link;
    }

    /**
     * Formate la date de la rencontre donnée de sorte à avoir une date du type "Dimanche 31 mars".
     *
     * @param \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting $meeting
     * @param bool                                                     $short
     *
     * @return string
     */
    public function getFormattedDate(PoolMeeting $meeting, $short = false)
    {
        /** @var \Carbon\Carbon $meetingDate */
        $meetingDate = $meeting->getDate();
        $dayOfMonth = (int) $meetingDate->format('d');

        $formatDay = $short ? '%a' : '%A';

        if (1 === $dayOfMonth) {
            $formattedDate = $meetingDate->formatLocalized("$formatDay %eer %B");
        } else {
            $formattedDate = $meetingDate->formatLocalized("$formatDay %e %B");
        }

        return ucfirst($formattedDate);
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFilters()
    {
        return [
            'matchesSheetLink' => 'getMatchesSheetLink',
            'formattedDate'    => 'getFormattedDate',
            'score'            => 'extractScoreFromResult',
        ];
    }
}
