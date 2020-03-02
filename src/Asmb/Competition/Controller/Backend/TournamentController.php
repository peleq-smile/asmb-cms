<?php

namespace Bundle\Asmb\Competition\Controller\Backend;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Tournament;
use Bundle\Asmb\Competition\Entity\Tournament\Box;
use Bundle\Asmb\Competition\Entity\Tournament\Table;
use Bundle\Asmb\Competition\Form\FormType;
use Silex\ControllerCollection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur pour les routes des tournois.
 *
 * @copyright 2020
 */
class TournamentController extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $c)
    {
        $c->match('/', 'index')
            ->bind('tournament');

        $c->match('/add', 'edit')
            ->bind('tournamentadd');

        $c->match('/edit/{id}', 'edit')
            ->assert('id', '\d+')
            ->bind('tournamentedit');

        $c->match('/edit/scores/{id}', 'editScores')
            ->assert('id', '\d+')
            ->bind('tournamenteditscores');

        $c->post('/delete/{id}', 'delete')
            ->assert('id', '\d*')
            ->bind('tournamentdelete');

        return $c;
    }

    /**
     * Vue en liste des tournois.
     *
     * @param Request $request
     *
     * @return Response
     * @noinspection PhpUnusedParameterInspection
     */
    public function index(Request $request)
    {
        $tournaments = $this->getRepository('tournament')->findAll();

        return $this->render(
            '@AsmbCompetition/tournament/index.twig',
            [],
            [
                'tournaments' => $tournaments,
            ]
        );
    }

    /**
     * Édition des scores (manquants) du tournoi.
     *
     * @param Request $request
     * @param integer $id
     *
     * @return \Bolt\Response\TemplateResponse|\Bolt\Response\TemplateView|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editScores(Request $request, $id)
    {
        /** @var Tournament $tournament */
        $tournament = $this->getRepository('tournament')->find($id);
        if (!$tournament) {
            return $this->redirectToRoute('tournament');
        }

        $boxesByDay = $this->getRepository('tournament_box')
            ->findAllWithMissingScoreByTournamentSortedByDay($tournament, true);

        $form = $this->buildEditScoresForm($request, $boxesByDay);
        if ($this->handleScoresFormSubmit($form, $boxesByDay)) {
            return $this->redirectToRoute('tournamenteditscores', ['id' => $id]);
        }

        return $this->render(
            '@AsmbCompetition/tournament/editScores.twig',
            [
                'form' => $form->createView(),
            ],
            [
                'tournament' => $tournament,
                'boxesByDay' => $boxesByDay,
            ]
        );
    }

    /**
     * Édition des informations du tournoi.
     *
     * @param Request $request
     * @param integer $id
     *
     * @return \Bolt\Response\TemplateResponse|\Bolt\Response\TemplateView|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Request $request, $id = null)
    {
        /** @var Tournament $tournament */
        if (null !== $id) {
            $tournament = $this->getRepository('tournament')->find($id);
            if (!$tournament) {
                return $this->redirectToRoute('tournament');
            }
        } else {
            $tournament = new Tournament();
        }

        $form = $this->buildEditForm($request, $tournament);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Tournament $tournament */
            $tournament = $form->getData();

            try {
                $this->getRepository('tournament')->save($tournament);
                $this->flashes()->success(Trans::__('general.phrase.saved-infos'));
                return $this->redirectToRoute('tournament');
            } catch (\Exception $e) {
                $this->flashes()->error(Trans::__('general.phrase.not-saved-infos'));
            }
        }

        return $this->render(
            '@AsmbCompetition/tournament/edit.twig',
            ['form' => $form->createView()],
            ['tournament' => $tournament]
        );
    }

    /**
     * Action de suppression d'un tournoi.
     *
     * @param Request $request
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @noinspection PhpUnusedParameterInspection
     */
    public function delete(Request $request, $id)
    {
        /** @var Tournament $tournament */
        $tournament = $this->getRepository('tournament')->find($id);

        try {
            $deleted = $this->getRepository('tournament')->delete($tournament);
            if ($deleted) {
                $this->flashes()->success(
                    Trans::__('page.delete-tournament.message.saved')
                );
            }
        } catch (\Exception $e) {
            $this->flashes()->error(
                Trans::__('page.delete-tournament.message.not-saved')
            );
        }

        return $this->redirectToRoute('tournament');
    }

    /**
     * @param Request $request
     * @param Box[] $boxesByDay
     * @return FormInterface
     */
    protected function buildEditScoresForm(Request $request, array $boxesByDay)
    {
        $boxes = [];
        foreach ($boxesByDay as $day => $boxesOfDay) {
            $boxes += $boxesOfDay;
        }

        $formOptions = [
            'boxes' => $boxes,
        ];

        $form = $this->createFormBuilder(FormType\TournamentBoxesScoreEditType::class, null, $formOptions)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }

    /**
     * @param Request $request
     * @param Tournament $tournament
     * @return FormInterface
     */
    protected function buildEditForm(Request $request, Tournament $tournament)
    {
        $form = $this->createFormBuilder(FormType\TournamentEditType::class, $tournament)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }

    /**
     * Gestion de la soumission du formulaire de saisie des scores.
     *
     * @param FormInterface $form
     * @param Box[][] $boxesByDay
     *
     * @return bool
     */
    protected function handleScoresFormSubmit(FormInterface $form, array $boxesByDay)
    {
        if ($form->isSubmitted() && $form->isValid()) {
            $tablesUpdated = [];

            $formData = $form->getData();
            // Ex de clé/valeur dans $formData :
            // ["box-51_score" => "5/7 6/2 6/0", "box-85_score" => null]
            foreach ($boxesByDay as $boxes) {
                foreach ($boxes as $box) {
                    if (isset($formData['box-' . $box->getId() . '_winner'])) {
                        if ('top' === $formData['box-' . $box->getId() . '_winner']) {
                            $box->setPlayerName($box->getBoxTop()->getPlayerName());
                            $box->setPlayerRank($box->getBoxTop()->getPlayerRank());
                            $box->setPlayerClub($box->getBoxTop()->getPlayerClub());

                            $tablesUpdated[$box->getTableId()] = true;
                        } elseif ('btm' === $formData['box-' . $box->getId() . '_winner']) {
                            $box->setPlayerName($box->getBoxBtm()->getPlayerName());
                            $box->setPlayerRank($box->getBoxBtm()->getPlayerRank());
                            $box->setPlayerClub($box->getBoxBtm()->getPlayerClub());

                            $tablesUpdated[$box->getTableId()] = true;
                        } else {
                            $box->setPlayerName(null);
                            $box->setPlayerRank(null);
                            $box->setPlayerClub(null);
                            $box->setScore(null);
                            $tablesUpdated[$box->getTableId()] = true;
                        }

                        if (isset($formData['box-' . $box->getId() . '_score'])) {
                            $box->setScore($formData['box-' . $box->getId() . '_score']);
                            $tablesUpdated[$box->getTableId()] = true;
                        }

                        $this->getRepository('tournament_box')->save($box);
                    }
                }
            }

            foreach (array_keys($tablesUpdated) as $tableId) {
                /** @var Table $table */
                $table = $this->getRepository('tournament_table')->find($tableId);
                $table->setUpdatedBy($this->getUser()->getId());
                $this->getRepository('tournament_table')->save($table);
            }

            return true;
        }

        return false;
    }
}
