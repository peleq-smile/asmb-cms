<?php

namespace Bundle\Asmb\Competition\Extension;

use Bolt\Config;
use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Helpers\PoolMeetingHelper;
use Carbon\Carbon;

/**
 * Déclaration de filtres Twig.
 */
trait TwigFiltersTrait
{
    /**
     * Extrait et retourne le score à partir de la rencontre donnée.
     */
    public function extractScoreFromResult(PoolMeeting $meeting, $decorated = false)
    {
        $score = PoolMeetingHelper::getScoreFromMeeting($meeting);
        $score = str_replace('/', ' / ', $score);

        if (!empty($score) && $decorated) {
            $stateClass = '';
            $scoreTitle = '';

            if (PoolMeetingHelper::isClubVictory($meeting)) {
                $stateClass = ' victory';
            } elseif (PoolMeetingHelper::isClubDefeat($meeting)) {
                $stateClass = ' defeat';
            } elseif (PoolMeetingHelper::isClubDraw($meeting)) {
                $stateClass = ' draw';
            }

            // Cas report sans date connue ?
            if ($meeting->getIsReported() && null === $meeting->getReportDate()) {
                $score = new \Twig_Markup(
                    '<span class="score"><em>' . Trans::__('general.phrase.reported') . '</em></span>', 'utf-8'
                );
            } else {
                // Cas d'un forfait ?
                $woLength = strlen(PoolMeetingHelper::RESULT_WO);

                if (PoolMeetingHelper::RESULT_WO === substr($meeting->getResult(), -1 * $woLength)) {
                    // Cas d'un forfait
                    $stateClass .= ' with-wo';
                    $scoreTitle = Trans::__('general.phrase.with-wo');
                }

                if ($scoreTitle) {
                    $score = new \Twig_Markup(
                        '<span class="score' . $stateClass . '" title="' . $scoreTitle . '">' . $score . '</span>', 'utf-8'
                    );
                } else {
                    $score = new \Twig_Markup(
                        '<span class="score' . $stateClass . '">' . $score . '</span>', 'utf-8'
                    );
                }
            }
        }

        return $score;
    }

    public function getFftMatchesSheetUrl(Pool $pool, PoolMeeting $poolMeeting): ?string
    {
        $url = null;

        if (!empty($poolMeeting->getMatchesSheetFftId())) {
            $config = $this->getConfigParameter('tenup');
            $url = str_replace(
                ['{$championship}', '{$matchSheet}'],
                [$pool->getChampionshipFftId(), $poolMeeting->getMatchesSheetFftId()],
                $config['url_match_sheet']
            );
        }

        return $url;
    }

    /**
     * Retourne le lien (HTML) vers la feuille de matchs de la Gestion Sportive ou de Ten'Up (FFT).
     */
    public function getMatchesSheetLink(PoolMeeting $meeting, $withThisContent = '<i class="fa fa-eye"></i>')
    {
        $paramsFdmFft = $meeting->getParamsFdmFft();

        if ($meeting->getMatchesSheetFftId() && false) { //TODOpeleq retirer condition "false"
            $url = '/competition/feuille-de-match/' . $meeting->getMatchesSheetFftId();
        } elseif (isset($paramsFdmFft['feuille_match_url'])) {
            $config = $this->getConfigParameter('tenup');
            $url = $config['url_base'] . $paramsFdmFft['feuille_match_url'];
        } elseif (isset($paramsFdmFft['efm_iid'], $paramsFdmFft['pha_iid'], $paramsFdmFft['pou_iid'], $paramsFdmFft['ren_iid'])) {
            $config = $this->getConfigParameter('gs');
            $url = str_replace(
                ['{$pou}', '{$ren}', '{$efm}', '{$pha}'],
                [
                    $paramsFdmFft['pou_iid'], $paramsFdmFft['ren_iid'], $paramsFdmFft['efm_iid'], $paramsFdmFft['pha_iid']
                ],
                $config['url_match_sheet']
            );
        }

        if (isset($url)) {
            $date = $meeting->getFinalDate() ? $meeting->getFinalDate()->format('d/m/Y') : '?';
            $title = Trans::__('general.phrase.go-to-matches-sheet', ['%date%' => $date]);
            $linkContent = '<a href="' . $url . '" class="btn btn-xs btn-link" target="_blank" title="' . $title . '">';
            $linkContent .= $withThisContent . '</a>';
        }
        $link = (isset($linkContent)) ? new \Twig_Markup($linkContent, 'utf-8') : '';

        return $link;
    }

    /**
     * Formate la date de la rencontre donnée de sorte à avoir une date du type "Dimanche 31 mars".
     */
    public function getFormattedDate(PoolMeeting $meeting, $short = false)
    {
        $meetingDate = $meeting->getFinalDate();
        $dayOfMonth = (int)$meetingDate->format('d');

        $formatDay = $short ? '%a' : '%A';
        $formatMonth = $short ? '%b' : '%B';

        if (1 === $dayOfMonth) {
            $formattedDate = $meetingDate->formatLocalized("$formatDay %eer $formatMonth");
        } else {
            $formattedDate = $meetingDate->formatLocalized("$formatDay %e $formatMonth");
        }

        return ucfirst($formattedDate);
    }

    /**
     * Formate la date de la rencontre donnée de sorte à avoir une date du type "Dim 31/03".
     */
    public function getShorterFormattedDate(PoolMeeting $meeting)
    {
        $meetingDate = $meeting->getFinalDate();
        $localizedDayOfWeek = substr($meetingDate->formatLocalized('%a'), 0, 2); // ex: "lu", "ma", ...
        $formattedDate = $localizedDayOfWeek . ' ' . $meetingDate->format('d/m');

        return ucfirst($formattedDate);
    }

    /**
     * Construit et retourne l'url vers la poule du championnat donné, sur la Gestion Sportive.
     */
    public function getChampionshipPoolGsUrl(Pool $pool): ?string
    {
        $url = null;
        $linksPattern = $this->getConfigParameter('gs');

        if (isset($linksPattern['url_pool_ranking'])) {
            $url = $linksPattern['url_pool_ranking'];
            $url = str_replace(['{$pool}'], [$pool->getFftId()], $url);
        }

        return $url;
    }

    /**
     * Construit et retourne l'url vers la poule du championnat donné, sur Ten'Up.
     */
    public function getChampionshipPoolTenupUrl(Pool $pool): ?string
    {
        $url = null;
        $linksPattern = $this->getConfigParameter('tenup');

        if (isset($linksPattern['url_pool'])) {
            $url = $linksPattern['url_pool'];
            $url = str_replace(
                ['{$championship}', '{$division}', '{$pool}'],
                [$pool->getChampionshipFftId(), $pool->getDivisionFftId(), $pool->getFftId()],
                $url
            );
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFilters(): array
    {
        return [
            'championshipPoolGsUrl' => 'getChampionshipPoolGsUrl',
            'championshipPoolTenupUrl' => 'getChampionshipPoolTenupUrl',
            'matchesSheetLink' => 'getMatchesSheetLink',
            'fftMatchesSheetUrl' => 'getFftMatchesSheetUrl',
            'formattedDate' => 'getFormattedDate',
            'shorterFormattedDate' => 'getShorterFormattedDate',
            'score' => 'extractScoreFromResult',
        ];
    }

    private function getConfigParameter(string $key)
    {
        $app = $this->getContainer();
        /** @var Config $config */
        $config = $app['config'];

        return $config->get('general/' . $key);
    }
}
