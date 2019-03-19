<?php

namespace Bundle\Asmb\Competition\Controller\Backend;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity;
use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Form\FormType;
use Bundle\Asmb\Competition\Repository\Championship\MatchRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolDayRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Silex\ControllerCollection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for Championship routes.
 *
 * @copyright 2019
 */
class ChampionshipController extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $c)
    {
        $c->match('/', 'index')
            ->bind('championship');

        $c->match('/edit/{id}', 'edit')
            ->assert('id', '\d*')
            ->bind('championshipedit');

        $c->match('/edit/scores/{id}', 'editScores')
            ->assert('id', '\d*')
            ->bind('championshipeditscores');

        $c->post('/delete/{id}', 'delete')
            ->assert('id', '\d*')
            ->bind('championshipdelete');

        return $c;
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function index(Request $request)
    {
        $championships = $this->getRepository('championship')->findAll();
        /** @var \Bundle\Asmb\Competition\Repository\Championship\TeamRepository $teamsRepo */
        $teamsRepo = $this->getRepository('championship_team');
        $teamsByCategoryName = $teamsRepo->findAllGroupByCategoryName();

        return $this->render(
            '@AsmbCompetition/championship/index.twig',
            [],
            [
                'championships'       => $championships,
                'teamsByCategoryName' => $teamsByCategoryName,
            ]
        );
    }

    /**
     * Build championship edit form.
     *
     * @param \Symfony\Component\HttpFoundation\Request    $request
     * @param \Bundle\Asmb\Competition\Entity\Championship $championship
     *
     * @return FormInterface
     */
    protected function buildEditForm(Request $request, Entity\Championship $championship)
    {
        $form = $this->createFormBuilder(FormType\ChampionshipEditType::class, $championship)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string                                    $id
     *
     * @return \Bolt\Response\TemplateResponse|\Bolt\Response\TemplateView|\Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function edit(Request $request, $id = '')
    {
        // Retrieve championship
        /** @var Championship $championship */
        $championship = $this->getRepository('championship')->find($id);
        if (!$championship) {
            $championship = new Entity\Championship();
        }

        // FORM 1: championship edit infos
        $editForm = $this->buildEditForm($request, $championship);
        if ($this->handleEditFormSubmit($editForm)) {
            // We don't want to POST again data, so we redirect to current in GET route in case of submitted form
            // with success
            return $this->redirectToRoute('championshipedit', ['id' => $championship->getId()]);
        }

        // Render
        $context = [
            'editForm' => $editForm->createView(),
        ];

        if (null !== $championship->getId()) {
            // FORM 2: add pool to championship
            $addPoolForm = $this->buildAddPoolForm($request, $championship->getId());

            // FORM 3: add team to pool forms (one per pool)
            $addTeamFormViews = [];
            $pools = $this->getPools($championship->getId());
            foreach ($pools as $pool) {
                $addTeamFormViews[$pool->getId()]
                    = $this->buildAddTeamToPoolForm($request, $pool)->createView();
            }

            /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolRepository $poolRepository */
            $poolRepository = $this->getRepository('championship_pool');
            $completenessByPoolId = $poolRepository->getEditionCompletenesses($pools);

            $context += [
                'addPoolForm'          => $addPoolForm->createView(),
                'addTeamsForms'        => $addTeamFormViews,
                'completenessByPoolId' => $completenessByPoolId,
            ];

            // TODO bouger ça ?
            $this->checkChampionshipActionState($championship, $completenessByPoolId);
        } // ELSE: add new championship case

        return $this->render(
            '@AsmbCompetition/championship/edit.twig',
            $context,
            [
                'championship'        => $championship,
                'poolsByCategoryName' => $this->getPoolsGroupByCategoryName($championship->getId()),
                'teamsByPool'         => $this->getPoolTeamsGroupByPoolId($championship->getId()),
            ]
        );
    }

    /**
     * Check if current champioship should switch on "Edit Score Mode" instead of simple "Edit Mode".
     *
     * @param \Bundle\Asmb\Competition\Entity\Championship $championship
     * @param array                                        $completenessByPoolId
     *
     * @return void
     */
    private function checkChampionshipActionState(Championship $championship, array $completenessByPoolId)
    {
        $isEditScoreMode = true;

        foreach ($completenessByPoolId as $completeness) {
            if ($completeness < 100) {
                $isEditScoreMode = false;
                break;
            }
        }

        if ($isEditScoreMode !== $championship->isEditScoreMode()) {
            $championship->setIsEditScoreMode($isEditScoreMode);
            $this->getRepository('championship')->save($championship, true);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param                                           $id
     *
     * @return \Bolt\Response\TemplateResponse|\Bolt\Response\TemplateView|\Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function editScores(Request $request, $id)
    {
        // Retrieve championship
        /** @var Championship $championship */
        $championship = $this->getRepository('championship')->find($id);
        if (!$championship) {
            $this->flashes()->error(Trans::__('general.phrase.wrong-parameter-cannot-edit'));
            $this->redirectToRoute('championship');
        }

        $pools = $this->getPools($championship->getId());
        // Get match data to edit scores
        $matchesByPoolId = $this->getMatchesByPoolIdByDay($pools);

        $editScoresForm = $this->buildEditPoolMatchScoresForm($request, $matchesByPoolId);
        if ($this->handleEditPoolMatchScoresFormSubmit($editScoresForm)) {
            // We don't want to POST again data, so we redirect to current in GET route in case of submitted form
            // with success
            return $this->redirectToRoute('championshipeditscores', ['id' => $championship->getId()]);
        }

        // Render
        $context = [
            'editScoresForm' => $editScoresForm->createView()
        ];

        return $this->render(
            '@AsmbCompetition/championship/edit-scores.twig',
            $context,
            [
                'championship'        => $championship,
                'poolsByCategoryName' => $this->getPoolsGroupByCategoryName($championship->getId()),
                'poolTeamsByPoolId'   => $this->getPoolTeamsGroupByPoolId($championship->getId()),
                'matchesByPoolId'     => $matchesByPoolId,
            ]
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array                                     $matches
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function buildEditPoolMatchScoresForm(Request $request, array $matches)
    {
        $formData = [
            'matches' => $matches,
        ];

        // Generate the form
        $form = $this->createFormBuilder(FormType\MatchEditScoreType::class, $formData)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }

     /**
     * Handle championship edit scores form submission.
     *
     * @param FormInterface $form
     *
     * @return boolean
     */
    protected function handleEditPoolMatchScoresFormSubmit(FormInterface $form)
    {
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            /** @var MatchRepository $matchRepository */
            $matchRepository = $this->getRepository('championship_match');
            try {
                $saved = $matchRepository->saveMatchScores($formData);
                if ($saved) {
                    $this->flashes()->success(Trans::__('page.edit-championship-scores.message.saved'));
                }

                return true;
            } catch (\Exception $e) {
                $this->flashes()->error(Trans::__('page.edit-championship-scores.message.not-saved'));
            }
            return true;
        }

        return false;
    }

    public function delete(Request $request, $id = null)
    {
        // TODO delete championship
        return new Response('OK');
    }

    /**
     * Handle championship edit infos form submission.
     *
     * @param FormInterface $form
     *
     * @return boolean
     */
    protected function handleEditFormSubmit(FormInterface $form)
    {
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Bundle\Asmb\Competition\Database\Schema\Table\Championship $data */
            $championship = $form->getData();
            try {
                $saved = $this->getRepository('championship')->save($championship);
                if ($saved) {
                    $this->flashes()->success(
                        Trans::__('page.edit-championship.message.saved', ['%name%' => $championship->getName()])
                    );
                }

                return true;
            } catch (UniqueConstraintViolationException $e) {
                $this->flashes()->error(
                    Trans::__('page.edit-championship.message.duplicate-error', ['%name%' => $championship->getName()])
                );
            } catch (\Exception $e) {
                $this->flashes()->error(
                    Trans::__('page.edit-championship.message.not-saved', ['%name%' => $championship->getName()])
                );
            }
        }

        return false;
    }

    /**
     * Handle add team to pool form submission.
     *
     * @param FormInterface                                     $form
     * @param \Bundle\Asmb\Competition\Entity\Championship\Pool $pool
     *
     * @return boolean
     * @deprecated
     */
    protected function handleAddTeamToPoolFormSubmit(FormInterface $form, Pool $pool)
    {
        if ($form->isSubmitted() && $form->isValid()) {
            $teamId = $form->get('team')->getData();
            $pool->addTeam($teamId);

            try {
                $saved = $this->getRepository('championship_pool')->save($pool);
                if ($saved) {
                    $this->flashes()->success(
                        Trans::__('page.add-team-pool.message.saved')
                    );
                }

                return true;
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

        return false;
    }
}
