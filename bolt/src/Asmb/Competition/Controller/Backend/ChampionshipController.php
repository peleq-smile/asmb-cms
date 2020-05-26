<?php

namespace Bundle\Asmb\Competition\Controller\Backend;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Form\FormType;
use Silex\ControllerCollection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur pour les routes des compétitions par poules.
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
            ->bind('competition');

        $c->match('/', 'index')
            ->bind('championship');

        $c->match('/edit/{id}', 'edit')
            ->assert('id', '\d*')
            ->bind('championshipedit');

        $c->match('/edit/{id}/{categoryName}', 'edit')
            ->assert('id', '\d*')
            ->assert('categoryName', '[\+\w]+')
            ->bind('championshipeditwithcategoryname');

        $c->match('/view/{id}', 'view')
            ->assert('id', '\d+')
            ->bind('championshipview');

        $c->post('/delete/{id}', 'delete')
            ->assert('id', '\d+')
            ->bind('championshipdelete');

        return $c;
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @noinspection PhpUnusedParameterInspection
     */
    public function index(Request $request)
    {
        $championships = $this->getRepository('championship')->findAll();

        return $this->render(
            '@AsmbCompetition/championship/index.twig',
            [],
            [
                'championships' => $championships,
            ]
        );
    }

    /**
     * Build championship edit form.
     *
     * @param Request $request
     * @param Championship $championship
     *
     * @return FormInterface
     */
    protected function buildEditForm(Request $request, Championship $championship)
    {
        $form = $this->createFormBuilder(FormType\ChampionshipEditType::class, $championship)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }

    /**
     * @param Request $request
     * @param string                                    $id
     * @param string|null                               $categoryName
     *
     * @return \Bolt\Response\TemplateResponse|\Bolt\Response\TemplateView|\Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function edit(Request $request, $id = '', $categoryName = null)
    {
        /** @var Championship $championship */
        $championship = $this->getRepository('championship')->find($id);
        if (!$championship) {
            $championship = new Championship();
        }

        // FORM 1: édition des infos de base d'un championnat
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
            $poolTeamsPerPoolId = $this->getPoolTeamsPerPoolId($championship->getId());

            // FORM 2: ajout d'une poule
            $addPoolForm = $this->buildAddPoolForm($request, $championship->getId(), $categoryName);
            $context += [
                'addPoolForm' => $addPoolForm->createView(),
            ];

            if (! empty($poolTeamsPerPoolId)) {
                // FORM 3: édition des équipes (nom interne + est du club), par poule
                $editPoolsTeamsForm = $this->buildEditPoolsTeamsForm($request, $championship, $poolTeamsPerPoolId);
                if ($this->handleEditPoolsTeamsFormSubmit($request, $editPoolsTeamsForm)) {
                    // We don't want to POST again data, so we redirect to current in GET route in case of submitted form
                    // with success
                    return $this->redirectToRoute('championshipedit', ['id' => $championship->getId()]);
                }

                $context += [
                    'editPoolsTeamsForm' => $editPoolsTeamsForm->createView(),
                ];
            }
        } else { // ELSE: add new championship case
            $poolTeamsPerPoolId = [];
        }

        return $this->render(
            '@AsmbCompetition/championship/edit.twig',
            $context,
            [
                'championship'         => $championship,
                'poolsPerCategoryName' => $this->getPoolsPerCategoryName($championship->getId()),
                'poolTeamsPerPoolId'   => $poolTeamsPerPoolId,
            ]
        );
    }

    /**
     * Visualisation des classements et rencontres des poules du championnat d'id donné.
     *
     * @param Request $request
     * @param integer                                   $id
     *
     * @return \Bolt\Response\TemplateResponse|\Bolt\Response\TemplateView
     * @throws \Bolt\Exception\InvalidRepositoryException
     * @noinspection PhpUnusedParameterInspection
     */
    public function view(Request $request, $id)
    {
        /** @var Championship $championship */
        $championship = $this->getRepository('championship')->find($id);
        if (!$championship) {
            $this->flashes()->error(Trans::__('general.phrase.wrong-parameter-cannot-view'));
            $this->redirectToRoute('championship');
        }

        return $this->render(
            '@AsmbCompetition/championship/view.twig',
            [],
            [
                'championship'          => $championship,
                'poolsPerCategoryName'  => $this->getPoolsPerCategoryName($championship->getId()),
                'poolRankingPerPoolId'  => $this->getPoolRankingPerPoolId($championship->getId()),
                'poolMeetingsPerPoolId' => $this->getPoolMeetingsPerPoolId($championship->getId()),
            ]
        );
    }

    /**
     * Action de suppression d'un championnat.
     *
     * @param Request $request
     * @param integer                                   $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @noinspection PhpUnusedParameterInspection
     */
    public function delete(Request $request, $id)
    {
        /** @var Championship $championship */
        $championship = $this->getRepository('championship')->find($id);

        try {
            $deleted = $this->getRepository('championship')->delete($championship);
            if ($deleted) {
                $this->flashes()->success(
                    Trans::__('page.delete-championship.message.saved')
                );
            }
        } catch (\Exception $e) {
            $this->flashes()->error(
                Trans::__('page.delete-championship.message.not-saved')
            );
        }

        return $this->redirectToRoute('championship');
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
            } catch (\Exception $e) {
                $this->flashes()->error(
                    Trans::__('page.edit-championship.message.not-saved', ['%name%' => $championship->getName()])
                );
                $this->flashes()->error($e->getMessage());
            }
        }

        return false;
    }

    /**
     * Gère la soumission du formulaire d'édition des équipes des poules.
     *
     * @param FormInterface $form
     *
     * @return boolean
     */
    protected function handleEditPoolsTeamsFormSubmit(Request $request, FormInterface $form)
    {
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Championship $data */
            $championship = $form->getData();
            try {
                /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolTeamRepository $poolTeamRepository */
                $poolTeamRepository = $this->getRepository('championship_pool_team');
                $submittedFormData = $request->get('pools_teams_edit');
                $poolIds = array_keys($this->getPools($championship->getId()));
                $poolTeams = $poolTeamRepository->findBy(['pool_id' => $poolIds]);
                $saved = $poolTeamRepository->savePoolsTeamsOfChampionship($poolTeams, $submittedFormData);

                if ($saved) {
                    $this->flashes()->success(
                        Trans::__('page.edit-championship.message.saved', ['%name%' => $championship->getName()])
                    );
                }

                return true;
            } catch (\Exception $e) {
                $this->flashes()->error(
                    Trans::__('page.edit-championship.message.not-saved', ['%name%' => $championship->getName()])
                    . "\n" . $e->getMessage()
                );
            }
        }

        return false;
    }
}
