<?php

namespace Bundle\Asmb\Competition\Controller;

use Bolt\Controller\Base;
use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Repository\Championship\PoolRepository;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur qui retourne une ou plusieurs feuilles de matchs.
 *
 * @copyright 2022
 */
class PoolMatchesSheetController extends Base
{
    public function addRoutes(ControllerCollection $c)
    {
        $c->match('/{matchesSheetFftId}', 'showOne')
            ->assert('matchesSheetFftId', '\d+')
            ->bind('matches_sheet');

//        $c->match('-asmb/{poolFftId}/{matchSheetFftId}', 'indexClub')
//            ->assert('poolFftId', '\d+')
//            ->assert('matchSheetFftId', '\d*')
//            ->bind('matches_sheets_asmb');
//
//        $c->match('/{poolFftId}/{matchSheetFftId}', 'index')
//            ->assert('poolFftId', '\d+')
//            ->assert('matchSheetFftId', '\d*')
//            ->bind('matches_sheet');

        return $c;
    }

    /**
     * Feuille(s) de matchs toute équipe confondue de la poule donnée.
     *
     * @param Request $request
     *
     * @return Response
     * @noinspection PhpUnusedParameterInspection
     */
    public function showOne(Request $request, string $matchesSheetFftId)
    {
        // REPOSITORIES
        /** @var \Bundle\Asmb\Competition\Repository\ChampionshipRepository $championshipRepository */
        $championshipRepository = $this->getRepository('championship');
        /** @var PoolRepository $poolRepository */
        $poolRepository = $this->getRepository('championship_pool');
        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $this->getRepository('championship_pool_meeting');
        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolMeetingMatchRepository $poolMeetingMatchRepository */
        $poolMeetingMatchRepository = $this->getRepository('championship_pool_meeting_match');

        /** @var Championship\PoolMeeting $poolMeeting */
        $poolMeeting = $poolMeetingRepository->findOneByWithClubTeamNames(['matches_sheet_fft_id' => $matchesSheetFftId]);

        /** @var Pool $pool */
        $pool = $poolRepository->find($poolMeeting->getPoolId());
        /** @var Championship $championship */
        $championship = $championshipRepository->find($pool->getChampionshipId());

        $matchesSheets = $poolMeetingMatchRepository->findBy(
            ['pool_meeting_id' => $poolMeeting->getId()]
        );

        return $this->render(
            '@AsmbCompetition/championship/matches_sheet/index.twig',
            [],
            [
                'headTitle' => $championship->getFullName() . ' > ' . $pool->getName(),
                'matchesSheets' => $matchesSheets,
                'poolMeeting' => $poolMeeting,
                'pool' => $pool,
                'championship' => $championship,
            ]
        );
    }
}
