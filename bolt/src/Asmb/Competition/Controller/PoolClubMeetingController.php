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
 * Contrôleur de toutes les jounées de rencontres des équipes du club.
 *
 * @copyright 2022
 */
class PoolClubMeetingController extends Base
{
    public function addRoutes(ControllerCollection $c)
    {
        $c->match('/', 'index')->bind('club_meeting');

        return $c;
    }

    /**
     * Point d'entrée des tests.
     *
     * @param Request $request
     *
     * @return Response
     * @noinspection PhpUnusedParameterInspection
     */
    public function index(Request $request)
    {
        $clubMeetings = [];
        $nbMaxDaysByChampionship = [];

        // REPOSITORIES
        /** @var \Bundle\Asmb\Competition\Repository\ChampionshipRepository $championshipRepository */
        $championshipRepository = $this->getRepository('championship');
        /** @var PoolRepository $poolRepository */
        $poolRepository = $this->getRepository('championship_pool');
        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $this->getRepository('championship_pool_meeting');

        $championships = $championshipRepository->findBy(['is_active' => true]);

        /** @var Championship $championship */
        foreach ($championships as $championship) {
            $championshipTitle = $championship->getName() . ' ' . $championship->getYear();
            $nbMaxDaysByChampionship[$championshipTitle] = 1;

            $poolsByCategory = $poolRepository->findByChampionshipIdGroupByCategory($championship->getId());

            foreach ($poolsByCategory as $categoryName => $pools) {
                /** @var Pool $pool */
                foreach ($pools as $pool) {
                    $poolMeetings = $poolMeetingRepository->findClubMeetingsOfPool($pool->getId());

                    foreach ($poolMeetings as $poolMeeting) {
                        $clubTeamName = $poolMeeting->getHomeTeamName() ?? $poolMeeting->getVisitorTeamName();
                        $day = $poolMeeting->getDay();
                        $clubMeetings[$championshipTitle][$categoryName][$clubTeamName][$day] = $poolMeeting;
                    }

                    $nbMaxDaysByChampionship[$championshipTitle] = max($nbMaxDaysByChampionship[$championshipTitle], count($poolMeetings));
                }
            }
        }

        return $this->render(
            '@AsmbCompetition/championship/index.twig',
            [],
            [
                'headTitle' => 'Dates des rencontres par équipe',
                'clubMeetings' => $clubMeetings,
                'nbMaxDaysByChampionship' => $nbMaxDaysByChampionship,
            ]
        );
    }
}
