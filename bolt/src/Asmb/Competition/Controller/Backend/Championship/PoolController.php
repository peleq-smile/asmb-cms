<?php

namespace Bundle\Asmb\Competition\Controller\Backend\Championship;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Controller\Backend\AbstractController;
use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Entity\Championship\PoolRanking;
use Bundle\Asmb\Competition\Form\FormType;
use Bundle\Asmb\Competition\Parser\Championship\TenupMatchesSheetParser;
use Bundle\Asmb\Competition\Parser\Championship\TenupPoolMeetingsParser;
use Exception;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for Pool routes.
 *
 * @copyright 2019
 */
class PoolController extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    protected function getRoleRoute(Request $request)
    {
        return 'competition:edit';
    }

    public function addRoutes(ControllerCollection $c)
    {
        $c->post('/add/{championshipId}', 'add')
            ->assert('championshipId', '\d+')
            ->bind('pooladd');

        $c->match('/edit/{poolId}', 'edit')
            ->assert('poolId', '\d+')
            ->bind('pooledit');

        $c->match('/delete/{poolId}', 'delete')
            ->assert('poolId', '\d+')
            ->bind('pooldelete');

        $c->match('/fetch/teams/{championshipId}/{poolId}', 'fetchTeams')
            ->assert('championshipId', '\d+')
            ->assert('poolId', '\d+')
            ->bind('poolfetchteams');

        $c->match('/fetch/{championshipId}/{poolId}', 'fetchRankingAndMeetings')
            ->assert('championshipId', '\d+')
            ->assert('poolId', '\d+')
            ->bind('poolfetch');

        return $c;
    }

    /**
     * @param Request $request
     * @param integer $championshipId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function add(Request $request, int $championshipId)
    {
        $url = $this->generateUrl('championshipedit', ['id' => $championshipId]);

        $form = $this->buildAddPoolForm($request, $championshipId);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Pool $pool */
            $pool = $form->getData();
            $position = $pool->getPosition();
            if (!$position) {
                // Set position to count of pool of same championship + 1
                /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolRepository $poolRepository */
                $poolRepository = $this->getRepository('championship_pool');
                $countOfPools = $poolRepository->countByChampionshipIdAndCategory(
                    $championshipId,
                    $pool->getCategoryIdentifier()
                );
                $pool->setPosition($countOfPools + 1);
            }

            try {
                $saved = $this->getRepository('championship_pool')->save($pool);
                if ($saved) {
                    $this->flashes()->success(
                        Trans::__('page.add-pool.message.saved')
                    );
                }
            } catch (Exception $e) {
                $this->flashes()->error(
                    Trans::__('page.add-pool.message.not-saved')
                );
            }

            $url = $this->generateUrl(
                'championshipeditwithcategory',
                ['id' => $championshipId, 'categoryIdentifier' => $pool->getCategoryIdentifier()]
            );
            $url .= '#pool' . $pool->getId();
        }

        return $this->redirect($url);
    }

    /**
     * @param Request $request
     * @param                                           $poolId
     *
     * @return \Bolt\Response\TemplateResponse|\Bolt\Response\TemplateView|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Request $request, $poolId)
    {
        $pool = $this->getRepository('championship_pool')->find($poolId);

        if (!$pool) {
            $this->flashes()->error(Trans::__('general.phrase.wrong-parameter-cannot-edit'));
            $this->redirectToRoute('championship');
        }

        // On vérifie si la poule a déjà ses équipes synchronisées ou non.
        // Si c'est le cas, on empêche la modification de l'Id de Gestion Sportive FFT
        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolTeamRepository $poolTeamRepository */
        $poolTeamRepository = $this->getRepository('championship_pool_team');
        $teamsCount = $poolTeamRepository->countByPoolId($pool->getId());

        $formOptions = [
            'categories'  => $this->getRepository('championship_category')->findAllAsChoices(),
            'calendarEventTypes'  => $this->getCalendarEventTypes(),
            'championship_id' => $pool->getChampionshipId(),
            'has_teams'       => ($teamsCount > 0),
        ];

        // Génération du formulaire d'édition de la poule
        $form = $this->createFormBuilder(FormType\PoolEditType::class, $pool, $formOptions)
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Pool $pool */
            $pool = $form->getData();

            try {
                $this->getRepository('championship_pool')->save($pool);

                $this->flashes()->success(
                    Trans::__('page.edit-pool.message.saved', ['%name%' => $pool->getName()])
                );

                return $this->redirectToRoute('championshipedit', ['id' => $pool->getChampionshipId()]);
            } catch (Exception $e) {
                $this->flashes()->error($e->getMessage());
            }
        }

        $context = [
            'form' => $form->createView(),
        ];

        return $this->render(
            '@AsmbCompetition/championship/pool/edit.twig',
            $context,
            [
                'pool' => $pool,
            ]
        );
    }

    /**
     * @param Request $request
     * @param int $poolId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @noinspection PhpUnusedParameterInspection
     */
    public function delete(Request $request, int $poolId)
    {
        /** @var Pool $pool */
        $pool = $this->getRepository('championship_pool')->find($poolId);
        $championshipId = $pool->getChampionshipId();

        try {
            $deleted = $this->getRepository('championship_pool')->delete($pool);
            if ($deleted) {
                $this->flashes()->success(
                    Trans::__('page.delete-pool.message.saved')
                );
            }
        } catch (Exception $e) {
            $this->flashes()->error(
                Trans::__('page.delete-pool.message.not-saved')
            );
        }

        return $this->redirectToRoute('championshipedit', ['id' => $championshipId]);
    }

    /**
     * Récupération des équipes de la poule d'id donné depuis la FFT.
     *
     * @param Request $request
     * @param integer $championshipId
     * @param integer                                   $poolId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @noinspection PhpUnusedParameterInspection
     */
    public function fetchTeams(Request $request, int $championshipId, int $poolId): Response
    {
        $url = $this->generateUrl('championshipedit', ['id' => $championshipId]) . "#pool$poolId";

        try {
            /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolRepository $poolRepository */
            $poolRepository = $this->getRepository('championship_pool');
            /** @var Pool $pool */
            $pool = $poolRepository->find($poolId);
            $championshipRepository = $this->getRepository('championship');
            /** @var Championship $championship */
            $championship = $championshipRepository->find($pool->getChampionshipId());

            /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolTeamRepository $poolTeamRepository */
            $poolTeamRepository = $this->getRepository('championship_pool_team');

            // Récupération des données depuis Ten'Up ou la GS
            /** @var \Bundle\Asmb\Competition\Entity\Championship\PoolTeam[] $poolTeams */
            if ($pool->getChampionshipFftId() && $pool->getDivisionFftId()) {
                /** @var \Bundle\Asmb\Competition\Parser\Championship\TenupPoolTeamsParser $poolTeamsParser */
                $poolTeamsParser = $this->app['pool_teams_tenup_parser'];
            } else {
                /** @var \Bundle\Asmb\Competition\Parser\Championship\GsPoolTeamsParser $poolTeamsParser */
                $poolTeamsParser = $this->app['pool_teams_gs_parser'];
            }
            $poolTeams = $poolTeamsParser->parse($championship, $pool);

            foreach ($poolTeams as $poolTeam) {
                // On sauvegarde dans un premier temps toutes les équipes
                $poolTeamRepository->save($poolTeam, true);
            }

            // Valorisation des noms des équipes utilisées en interne
            /** @var \Bundle\Asmb\Competition\Guesser\PoolTeamsGuesser $poolTeamsGuesser */
            $poolTeamsGuesser = $this->app['pool_teams_guesser'];
            $poolTeamsGuesser->guess($poolTeams);

            foreach ($poolTeams as $poolTeam) {
                try {
                    $poolTeamRepository->save($poolTeam, true);
                } catch (Exception $e) {
                    $this->flashes()->warning(
                        Trans::__('page.pool-teams.message.not-saved', ['%poolTeam%' => $poolTeam->getNameFft()])
                    );
                }
            }
        } catch (Exception $e) {
            $this->flashes()->error(Trans::__('page.pool-teams.message.not-fetched'));
        }

        return $this->redirect($url);
    }

    /**
     * Récupération du classement et des rencontres de la poule d'id donné depuis la FFT.
     *
     * @param Request $request
     * @param integer                                   $championshipId
     * @param integer                                   $poolId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @noinspection PhpUnusedParameterInspection
     */
    public function fetchRankingAndMeetings(Request $request, int $championshipId, int $poolId)
    {
        $url = $this->generateUrl('championshipview', ['id' => $championshipId]);

        try {
            /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolRepository $poolRepository */
            $poolRepository = $this->getRepository('championship_pool');
            /** @var Pool $pool */
            $pool = $poolRepository->find($poolId);

            $championshipRepository = $this->getRepository('championship');
            /** @var Championship $championship */
            $championship = $championshipRepository->find($pool->getChampionshipId());

            // Données CLASSEMENT
            // Récupération des données depuis Ten'up ou la Gestion Sportive de la FFT
            if ($pool->getChampionshipFftId() && $pool->getDivisionFftId()) {
                // Récupération depuis Ten'Up
                /** @var \Bundle\Asmb\Competition\Parser\Championship\TenupPoolRankingParser $poolRankingParser */
                $poolRankingParser = $this->app['pool_ranking_tenup_parser'];
            } else {
                // Récupération depuis la GS
                /** @var \Bundle\Asmb\Competition\Parser\Championship\GsPoolRankingParser $poolRankingParser */
                $poolRankingParser = $this->app['pool_ranking_gs_parser'];
            }
            $poolRankingParsed = $poolRankingParser->parse($championship, $pool);

            // Sauvegarde des classements en base
            $this->savePoolRanking($pool, $poolRankingParsed);

            // Données RENCONTRES
            if ($pool->getChampionshipFftId() && $pool->getDivisionFftId()) {
                // Récupération depuis Ten'Up
                /** @var TenupPoolMeetingsParser $poolMeetingsParser */
                $poolMeetingsParser = $this->app['pool_meetings_tenup_parser'];

                /** @var TenupMatchesSheetParser $matchesSheetParser */
                $matchesSheetParser = $this->app['pool_matches_sheet_tenup_parser'];

            } else {
                // Récupération depuis la GS
                /** @var \Bundle\Asmb\Competition\Parser\Championship\GsPoolMeetingsParser $poolMeetingsParser */
                $poolMeetingsParser = $this->app['pool_meetings_gs_parser'];
                $poolMeetingsParser->setPage(0);
            }
            $poolMeetingsParsed = $poolMeetingsParser->parse($championship, $pool);
            // Sauvegarde des rencontres en base
            $this->saveMeetings($pool, $poolMeetingsParsed);

            // On parse les feuilles de match (cas Ten'up seulement)
            if (isset($matchesSheetParser)) {
                $poolMeetingMatchRepository = $this->getRepository('championship_pool_meeting_match');
                $matchesSheetsParsed = [];
                /** @var Championship\PoolMeeting $poolMeeting */
                foreach ($poolMeetingsParsed as $poolMeeting) {
                    if (!empty($poolMeeting->getMatchesSheetFftId())) {
                        $matchesSheetsParsed = $matchesSheetParser->parse($championship, $pool, $poolMeeting);
                    }
                    // On sauvegarde en base, pour chaque rencontre
                    $poolMeetingMatchRepository->saveAll($matchesSheetsParsed, $poolMeeting->getId());
                }
            }

            $pool->setUpdatedAt();
            $poolRepository->save($pool);

            $this->flashes()->success(
                Trans::__('page.pool-ranking-and-meetings.message.fetched', ['%pool_name%' => $pool->getName()])
            );
        } catch (Exception $e) {
            /** @noinspection PhpUndefinedVariableInspection */
            $this->flashes()->error(
                Trans::__('page.pool-ranking-and-meetings.message.not-fetched', ['%pool_name%' => $pool->getName()])
            );
            $this->flashes()->error($e->getMessage());
        }

        return $this->redirect($url);
    }

    /**
     * Sauvegarde les classements données en paramètre.
     *
     * @param Pool $pool
     * @param PoolRanking[]                                     $poolRankings
     */
    protected function savePoolRanking(Pool $pool, array $poolRankings)
    {
        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolRankingRepository $poolRankingRepository */
        $poolRankingRepository = $this->getRepository('championship_pool_ranking');
        try {
            $poolRankingRepository->saveAll($poolRankings, $pool->getId());
        } catch (Exception $e) {
            $this->flashes()->error(Trans::__('page.pool-ranking.message.not-fetched'));
            $this->flashes()->error($e->getMessage());
        }
    }

    /**
     * Sauvegarde les rencontres donnés en paramètre.
     *
     * @param Pool $pool
     * @param PoolMeeting[]                                     $poolMeetings
     */
    protected function saveMeetings(Pool $pool, array $poolMeetings)
    {
        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $this->getRepository('championship_pool_meeting');
        $poolMeetingRepository->saveAll($poolMeetings, $pool->getId());
    }
}
