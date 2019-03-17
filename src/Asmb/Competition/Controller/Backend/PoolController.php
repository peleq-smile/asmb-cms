<?php

namespace Bundle\Asmb\Competition\Controller\Backend;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolTeam;
use Bundle\Asmb\Competition\Repository\Championship\MatchRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolDayRepository;
use Bundle\Asmb\Competition\Repository\Championship\TeamRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Bundle\Asmb\Competition\Form\FormType;

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

        $c->post('/delete/{championshipId}', 'delete')
            ->assert('championshipId', '\d+')
            ->bind('pooldelete');

        $c->post('/team/add/{championshipId}/{poolId}', 'addTeams')
            ->assert('championshipId', '\d+')
            ->assert('poolId', '\d+')
            ->bind('poolteamadd');

        $c->post('/team/remove/{championshipId}/{poolId}', 'removeTeam')
            ->assert('championshipId', '\d+')
            ->assert('poolId', '\d+')
            ->bind('poolteamremove');

        $c->match('/matches/edit/{poolId}', 'editDaysAndMatches')
            ->assert('poolId', '\d+')
            ->bind('pooleditmatches');

        $c->post('/matches/save/{poolId}', 'saveDaysAndMatches')
            ->assert('poolId', '\d+')
            ->bind('poolmatchessave');

        return $c;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param                                integer    $championshipId
     */
    public function add(Request $request, $championshipId)
    {
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
        }

        return $this->redirectToRoute('championshipedit', ['id' => $championshipId]);
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

        /** @noinspection PhpUndefinedMethodInspection */
        $formOptions = [
            'category_names'  => $this->getRepository('championship_category')->findAllAsChoices(),
            'championship_id' => $pool->getChampionshipId(),
        ];

        // Generate the form
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
     * @param                                           $championshipId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Request $request, $championshipId)
    {
        $poolId = $request->get('poolId');

        try {
            $pool = $this->getRepository('championship_pool')->find($poolId);
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
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param                                           $championshipId
     * @param                                           $poolId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addTeams(Request $request, $championshipId, $poolId)
    {
        $url = $this->generateUrl('championshipedit', ['id' => $championshipId]);

        try {
            /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolRepository $poolRepository */
            $poolRepository = $this->getRepository('championship_pool');
            $pool = $poolRepository->find($poolId);

            $form = $this->buildAddTeamToPoolForm($request, $pool);

            if ($form->isSubmitted() && $form->isValid()) {
                /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolTeamRepository $poolTeamRepository */
                $poolTeamRepository = $this->getRepository('championship_pool_team');
                $poolTeams = $poolTeamRepository->findByPoolIdSortedByName($poolId);

                $teamIdInputName = 'pool' . $poolId . '_add_team_teamId';
                $teamIds = $form->get($teamIdInputName)->getData();

                $saved = true;

                // Retrieve all team names from selected team ids (optimized cause in ONE query), to be able to set
                // required team name.

                /** @var \Bundle\Asmb\Competition\Repository\Championship\TeamRepository $teamRepository */
                $teamRepository = $this->getRepository('championship_team');
                $teams = $teamRepository->findByIds($teamIds);
                /** @var \Bundle\Asmb\Competition\Entity\Championship\Team $team */
                foreach ($teams as $team) {
                    if (!isset($poolTeams[$team->getId()])) { // Save only if team is not already into pool
                        $poolTeam = new PoolTeam();
                        $poolTeam->setPoolId($poolId);
                        $poolTeam->setTeamId($team->getId());
                        // Let's retrieve name of team from id here.
                        $poolTeam->setTeamName($team->getFinalName());
                        $poolTeam->setTeamIsClub($team->isClub());
                        $poolTeam->setPosition(count($poolTeams) + 1);

                        $saved = $saved && $this->getRepository('championship_pool_team')->save($poolTeam);
                    }
                }

                if ($saved) {
                    $this->flashes()->success(Trans::__('page.add-team-pool.message.saved'));
                } else {
                    $this->flashes()->error(Trans::__('page.add-pool-team.message.duplicate-error'));
                }

                $url .= '#pool' . $pool->getId();
            }
        } catch (\Exception $e) {
            $this->flashes()->error(
                Trans::__('page.add-pool-team.message.not-saved')
            );
        }

        return $this->redirect($url);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param                                           $championshipId
     * @param                                           $poolId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @todo ne pas constuire de formulaire
     */
    public function removeTeam(Request $request, $championshipId, $poolId)
    {
        /** @var Pool $pool */
        $pool = $this->getRepository('championship_pool')->find($poolId);
        $form = $this->buildRemoveTeamFromPoolForm($request, $pool);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $teamIdInputName = 'pool' . $poolId . '_remove_team_teamId';
                $teamId = $form->get($teamIdInputName)->getData();

                /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolTeamRepository $poolTeamRepository */
                $poolTeamRepository = $this->getRepository('championship_pool_team');
                $poolTeam = $poolTeamRepository->findOneBy(
                    ['pool_id' => $poolId, 'team_id' => $teamId]
                );

                try {
                    $deleted = $poolTeamRepository->delete($poolTeam);
                    if ($deleted) {
                        $this->flashes()->success(
                            Trans::__('page.remove-team-pool.message.saved')
                        );
                    }
                } catch (\Exception $e) {
                    $this->flashes()->error(
                        Trans::__('page.remove-pool-team.message.not-saved')
                    );
                }
            } else {
                foreach ($form->getErrors() as $error) {
                    $this->flashes()->danger($error->getMessage());
                }
            }
        }

        $url = $this->generateUrl('championshipedit', ['id' => $championshipId]) . '#pool' . $pool->getId();

        return $this->redirect($url);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer                                   $poolId
     *
     * @return \Bolt\Response\TemplateResponse|\Bolt\Response\TemplateView
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function editDaysAndMatches(Request $request, $poolId)
    {
        /** @var Pool $pool */
        $pool = $this->getRepository('championship_pool')->find($poolId);
        if (!$pool) {
            $this->flashes()->error(Trans::__('general.phrase.wrong-parameter-cannot-edit'));
            $this->redirectToRoute('championship');
        }

        $completeness = $this->getRepository('championship_pool')->getEditionCompleteness($pool);

        $form = $this->buildEditPoolMatchesForm($request, $pool);
        $context = [
            'form'         => $form->createView(),
            'completeness' => $completeness,
        ];

        /** @var TeamRepository $teamRepository */
        $teamRepository = $this->getRepository('championship_team');
        $teams = $teamRepository->findByPool($pool);

        return $this->render(
            '@AsmbCompetition/championship/pool/edit-matches.twig',
            $context,
            [
                'pool'  => $pool,
                'teams' => $teams,
            ]
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer                                   $poolId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function saveDaysAndMatches(Request $request, $poolId)
    {
        /** @var Pool $pool */
        $pool = $this->getRepository('championship_pool')->find($poolId);
        if (!$pool) {
            $this->flashes()->error(Trans::__('general.phrase.wrong-parameter-cannot-edit'));
            $this->redirectToRoute('championship');
        }

        $form = $this->buildEditPoolMatchesForm($request, $pool);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var PoolDayRepository $poolDayRepository */
            $poolDayRepository = $this->getRepository('championship_pool_day');
            $poolDayRepository->savePoolDays($pool->getId(), $form->getData());

            /** @var MatchRepository $matchRepository */
            $matchRepository = $this->getRepository('championship_match');
            $matchRepository->savePoolMatches($pool->getId(), $form->getData());
        }

        return $this->redirectToRoute('pooleditmatches', ['poolId' => $pool->getId()]);
    }
}
