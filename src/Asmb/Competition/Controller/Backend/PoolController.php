<?php

namespace Bundle\Asmb\Competition\Controller\Backend;

use Bolt\Exception\InvalidRepositoryException;
use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Entity\Championship\PoolRanking;
use Bundle\Asmb\Competition\Exception\PoolTeamNotFoundException;
use Bundle\Asmb\Competition\Form\FormType;
use Bundle\Asmb\Competition\Helpers\CalendarHelper;
use Bundle\Asmb\Competition\Parser\PoolMeetingsParser;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

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
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param                                integer    $championshipId
     */
    public function add(Request $request, $championshipId)
    {
        $url = $this->generateUrl('championshipedit', ['id' => $championshipId]);

        $form = $this->buildAddPoolForm($request, $championshipId);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Pool $pool */
            $pool = $form->getData();
            $position = $pool->getPosition();
            if (null === $position || !$position) {
                // Set position to count of pool of same championship + 1
                /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolRepository $poolRepository */
                $poolRepository = $this->getRepository('championship_pool');
                $countOfPools = $poolRepository->countByChampionshipIdAndCategoryName(
                    $championshipId,
                    $pool->getCategoryName()
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
            } catch (UniqueConstraintViolationException $e) {
                $this->flashes()->error(
                    Trans::__('page.add-pool.message.duplicate-error')
                );
            } catch (\Exception $e) {
                $this->flashes()->error(
                    Trans::__('page.add-pool.message.not-saved')
                );
            }

            $url = $this->generateUrl(
                'championshipeditwithcategoryname',
                ['id' => $championshipId, 'categoryName' => $pool->getCategoryName()]
            );
            $url .= '#pool' . $pool->getId();
        }

        return $this->redirect($url);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
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

        /** @noinspection PhpUndefinedMethodInspection */
        $formOptions = [
            'category_names'  => $this->getRepository('championship_category')->findAllAsChoices(),
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
            } catch (UniqueConstraintViolationException $e) {
                $this->flashes()->error(
                    Trans::__('page.edit-pool.message.duplicate-error', ['%name%' => $pool->getName()])
                );
            } catch (\Exception $e) {
                $this->flashes()->error(
                    Trans::__('page.edit-pool.message.saving-team', ['%name%' => $pool->getName()])
                );
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
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int                                       $poolId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Request $request, $poolId)
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
        } catch (\Exception $e) {
            $this->flashes()->error(
                Trans::__('page.delete-pool.message.not-saved')
            );
        }

        return $this->redirectToRoute('championshipedit', ['id' => $championshipId]);
    }

    /**
     * Récupération des équipes de la poule d'id donné depuis la FFT.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer                                   $championshipId
     * @param integer                                   $poolId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function fetchTeams(Request $request, $championshipId, $poolId)
    {
        $url = $this->generateUrl('championshipedit', ['id' => $championshipId]) . "#pool{$poolId}";

        try {
            /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolRepository $poolRepository */
            $poolRepository = $this->getRepository('championship_pool');
            $pool = $poolRepository->find($poolId);

            /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolTeamRepository $poolTeamRepository */
            $poolTeamRepository = $this->getRepository('championship_pool_team');

            // Récupération des données depuis la Gestion Sportive de la FFT
            /** @var \Bundle\Asmb\Competition\Parser\PoolTeamsParser $poolTeamsParser */
            $poolTeamsParser = $this->app['pool_teams_parser'];
            /** @var \Bundle\Asmb\Competition\Entity\Championship\PoolTeam[] $poolTeams */
            $poolTeams = $poolTeamsParser->parse($pool);

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
                } catch (\Exception $e) {
                    $this->flashes()->warning(
                        Trans::__('page.pool-teams.message.not-saved', ['%poolTeam%' => $poolTeam->getNameFft()])
                    );
                }
            }
        } catch (\Exception $e) {
            $this->flashes()->error(Trans::__('page.pool-teams.message.not-fetched'));
        }

        return $this->redirect($url);
    }

    /**
     * Récupération du classement et des rencontres de la poule d'id donné depuis la FFT.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer                                   $championshipId
     * @param integer                                   $poolId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function fetchRankingAndMeetings(Request $request, $championshipId, $poolId)
    {
        $url = $this->generateUrl('championshipview', ['id' => $championshipId]);

        try {
            /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolRepository $poolRepository */
            $poolRepository = $this->getRepository('championship_pool');
            /** @var \Bundle\Asmb\Competition\Entity\Championship\Pool $pool */
            $pool = $poolRepository->find($poolId);

            // Données CLASSEMENT
            // Récupération des données depuis la Gestion Sportive de la FFT
            /** @var \Bundle\Asmb\Competition\Parser\PoolRankingParser $poolRankingParser */
            $poolRankingParser = $this->app['pool_ranking_parser'];
            $poolRankingParsed = $poolRankingParser->parse($pool);
            // Sauvegarde des classements en base
            $this->savePoolRanking($pool, $poolRankingParsed);

            // Données RENCONTRES
            // Récupération des données depuis la Gestion Sportive de la FFT
            // On commence par compter le nombre de page à parser
            $pageCount = ceil($this->getTotalMeetingsCount($pool) / PoolMeetingsParser::MAX_PER_PAGE);

            /** @var \Bundle\Asmb\Competition\Parser\PoolMeetingsParser $poolMatchesParser */
            $poolMeetingsParser = $this->app['pool_meetings_parser'];
            $poolMeetingsParsed = $poolMeetingsParser->parse($pool, $pageCount);
            // Sauvegarde des rencontres en base
            $this->saveMeetings($pool, $poolMeetingsParsed);

            $pool->setUpdatedAt();
            $poolRepository->save($pool);

            $this->flashes()->success(
                Trans::__('page.pool-ranking-and-meetings.message.fetched', ['%pool_name%' => $pool->getName()])
            );
        } catch (\Exception $e) {
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
     * @param \Bundle\Asmb\Competition\Entity\Championship\Pool $pool
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
     * @param \Bundle\Asmb\Competition\Entity\Championship\Pool $pool
     * @param PoolMeeting[]                                     $poolMeetings
     */
    protected function saveMeetings(Pool $pool, array $poolMeetings)
    {
        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $this->getRepository('championship_pool_meeting');
        $poolMeetingRepository->saveAll($poolMeetings, $pool->getId());
    }
}
