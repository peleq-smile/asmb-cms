<?php

namespace Bundle\Asmb\Competition\Controller\Backend\Tournament;

use Bolt\Response\TemplateResponse;
use Bolt\Response\TemplateView;
use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Controller\Backend\AbstractController;
use Bundle\Asmb\Competition\Entity\Tournament;
use Bundle\Asmb\Competition\Entity\Tournament\Box;
use Bundle\Asmb\Competition\Entity\Tournament\Table;
use Bundle\Asmb\Competition\Form\FormType;
use Bundle\Asmb\Competition\Repository\Tournament\BoxRepository;
use Exception;
use Silex\ControllerCollection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contrôleur des tableaux de tournois.
 *
 * @copyright 2020
 */
class TableController extends AbstractController
{
    /** @var Tournament\Box[] */
    private $savedBoxesByPrefix = [];

    /**
     * {@inheritdoc}
     */
    protected function getRoleRoute(Request $request)
    {
        return 'competition:edit';
    }

    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $c)
    {
        $c->match('/{tableId}', 'index')
            ->assert('tableId', '\d+')
            ->bind('tournamenttable');

        $c->match('/edit/{tournamentId}/{tableId}', 'edit')
            ->assert('tournamentId', '\d+')
            ->assert('tableId', '\d*')
            ->bind('tournamenttableedit');

        $c->match('/delete/{tableId}', 'delete')
            ->assert('tableId', '\d+')
            ->bind('tournamenttabledelete');

        $c->match('/boxes/add/{tableId}', 'addBoxes')
            ->assert('tableId', '\d+')
            ->bind('tournamenttableboxesadd');

        return $c;
    }

    /**
     * @param Request $request
     * @param integer $tableId
     *
     * @return TemplateResponse|TemplateView|RedirectResponse
     */
    public function index(Request $request, $tableId)
    {
        /** @var Table $table */
        $table = $this->getRepository('tournament_table')->findAndFetchSiblingTables($tableId);

        if (!$table) {
            return $this->redirectToRoute('tournament');
        }

        // Remplissage auto des joueurs des qualifiés entrants, si des données sont manquantes
        $this->getRepository('tournament_box')->updatePlayerDataFromQualifOut($table);

        // Détection des Q entrants en double
        $duplicatesQualifIn = $this->getRepository('tournament_box')->findDuplicatesQualifIn($tableId);
        foreach (array_keys($duplicatesQualifIn) as $q) {
            $this->flashes()->danger(
                Trans::__('page.tournament.table.index.danger.duplicate-qualif_in', ['%idQualifIn%' => $q])
            );
        }

        // Récupération de l'auteur de la dernière modification
        $updatedByUser = null;
        if (null !== $table->getUpdatedBy()) {
            $updatedByUser = $this->getUser($table->getUpdatedBy());
        }

        /** @var Tournament $tournament */
        $tournament = $this->getRepository('tournament')->find($table->getTournamentId());
        /** @var Tournament\Box[] $boxes */
        $boxes = $this->getRepository('tournament_box')->findAllByTable($table);

        // Construction d'un formulaire de saisie des scores des rencontres passées
        $boxesWithMissingScore = $this->getRepository('tournament_box')->findAllWithMissingScoreByTable($table);
        $scoresForm = $this->buildScoresForm($request, $boxesWithMissingScore);

        if ($this->handleScoresFormSubmit($scoresForm, $boxesWithMissingScore, $table)) {
            return $this->redirectToRoute('tournamenttable', ['tableId' => $table->getId()]);
        }

        return $this->render(
            '@AsmbCompetition/tournament/table/index.twig',
            [
                'scoresForm' => $scoresForm->createView(),
            ],
            [
                'tournament' => $tournament,
                'table' => $table,
                'boxes' => $boxes,
                'boxesWithMissingScore' => $boxesWithMissingScore,
                'duplicatesQualifIn' => $duplicatesQualifIn,
                'updated_by_user' => $updatedByUser,
            ]
        );
    }

    /**
     * @param Request      $request
     * @param integer      $tournamentId
     * @param integer|null $tableId
     *
     * @return TemplateResponse|TemplateView|RedirectResponse
     */
    public function edit(Request $request, $tournamentId, $tableId = null)
    {
        if (null !== $tableId && $tableId) {
            /** @var Table $table */
            $table = $this->getRepository('tournament_table')->find($tableId);
        } else {
            $table = new Table();
            $table->setTournamentId($tournamentId);
        }

        $form = $this->buildEditForm($request, $table);
        if ($form->isSubmitted() && $form->isValid()) {
            $table->setUpdatedBy($this->getUser()->getId());
            $this->getRepository('tournament_table')->save($table);

            return $this->redirectToRoute('tournamenttable', ['tableId' => $table->getId()]);
        }

        /** @var Tournament $tournament */
        $tournament = $this->getRepository('tournament')->find($tournamentId);

        return $this->render(
            '@AsmbCompetition/tournament/table/edit.twig',
            [
                'form' => $form->createView()
            ],
            [
                'tournament' => $tournament,
                'table' => $table
            ]
        );
    }

    /**
     * @param Request $request
     * @param integer $tableId
     *
     * @return RedirectResponse
     */
    public function delete(Request $request, $tableId)
    {
        /** @var Table $table */
        $table = $this->getRepository('tournament_table')->find($tableId);
        $tournamentId = $table->getTournamentId();

        try {
            $deleted = $this->getRepository('tournament_table')->delete($table);
            if ($deleted) {
                $this->flashes()->success(
                    Trans::__('page.delete-table.message.saved')
                );
            }
        } catch (Exception $e) {
            $this->flashes()->error(
                Trans::__('page.delete-table.message.not-saved')
            );
        }

        return $this->redirectToRoute('tournament', ['id' => $tournamentId]);
    }

    /**
     * Action permettant de saisir des nouvelles entrées dans un tableau.
     *
     * @param Request $request
     * @param integer $tableId
     *
     * @return TemplateResponse|TemplateView|RedirectResponse
     */
    public function addBoxes(Request $request, $tableId)
    {
        /** @var Table $table */
        $table = $this->getRepository('tournament_table')->find($tableId);
        if (!$table) {
            return $this->redirectToRoute('tournament');
        }

        /** @var Tournament $tournament */
        $tournament = $this->getRepository('tournament')->find($table->getTournamentId());

        $nbOut = $request->get('out'); // Nombre de qualifié(es) sortant(es) du tableau
        $nbRound = $request->get('round'); // Nombre de tour (maximum) dans le tableau

        $addBoxesForm = $this->buildAddBoxesForm($request, $tournament, $table, $nbOut, $nbRound);
        if ($this->handleAddBoxesFormSubmit($addBoxesForm)) {
            return $this->redirectToRoute('tournamenttable', ['tableId' => $tableId]);
        }

        // Render
        $context = [
            'form' => $addBoxesForm->createView()
        ];

        return $this->render(
            '@AsmbCompetition/tournament/table/boxes/add.twig',
            $context,
            [
                'tournament' => $tournament,
                'table' => $table,
                'nbOut' => $nbOut,
                'nbRound' => $nbRound,
            ]
        );
    }

    /**
     * Construit un formulaire permettant de saisir les scores des rencontres passées.
     *
     * @param Request $request
     * @param Box[]   $boxes
     *
     * @return FormInterface
     */
    protected function buildScoresForm(Request $request, array $boxes)
    {
        $formOptions = [
            'boxes' => $boxes,
        ];

        return $this->createFormBuilder(FormType\TournamentBoxesScoreEditType::class, null, $formOptions)
            ->getForm()
            ->handleRequest($request);
    }

    /**
     * Gestion de la soumission du formulaire de saisie des scores.
     *
     * @param FormInterface $form
     * @param Box[]         $boxes
     * @param Table         $table
     *
     * @return bool
     */
    protected function handleScoresFormSubmit(FormInterface $form, array $boxes, Table $table)
    {
        if ($form->isSubmitted() && $form->isValid()) {
            $tableIsUpdated = false;

            $formData = $form->getData();
            // Ex de clé/valeur dans $formData :
            // ["box-51_score" => "5/7 6/2 6/0", "box-85_score" => null]
            foreach ($boxes as $box) {
                if (isset($formData['box-' . $box->getId() . '_winner'])) {
                    if ('top' === $formData['box-' . $box->getId() . '_winner']) {
                        $box->setPlayerName($box->getBoxTop()->getPlayerName());
                        $box->setPlayerRank($box->getBoxTop()->getPlayerRank());
                        $box->setPlayerClub($box->getBoxTop()->getPlayerClub());

                        $tableIsUpdated = true;
                    } elseif ('btm' === $formData['box-' . $box->getId() . '_winner']) {
                        $box->setPlayerName($box->getBoxBtm()->getPlayerName());
                        $box->setPlayerRank($box->getBoxBtm()->getPlayerRank());
                        $box->setPlayerClub($box->getBoxBtm()->getPlayerClub());

                        $tableIsUpdated = true;
                    } else {
                        $box->setPlayerName(null);
                        $box->setPlayerRank(null);
                        $box->setPlayerClub(null);
                        $box->setScore(null);
                        $tableIsUpdated = true;
                    }

                    if (isset($formData['box-' . $box->getId() . '_score'])) {
                        $box->setScore($formData['box-' . $box->getId() . '_score']);
                        $tableIsUpdated = true;
                    }

                    $this->getRepository('tournament_box')->save($box);
                }
            }

            if ($tableIsUpdated) {
                $table->setUpdatedBy($this->getUser()->getId());
                $this->getRepository('tournament_table')->save($table);
            }

            return true;
        }

        return false;
    }

    /**
     * Construction du formulaire d'ajout de boîtes dans un tableau de tournoi.
     *
     * @param Request    $request
     * @param Tournament $tournament Tournoi
     * @param Table      $table      Tableau
     * @param integer    $nbOut      Nombre de qualifié(es) sortant(es) du tableau à ajouter
     * @param integer    $nbRound    Nombre de tour (maximum) dans le tableau
     *
     * @return FormInterface
     */
    protected function buildAddBoxesForm(Request $request, Tournament $tournament, Table $table, $nbOut, $nbRound)
    {
        // Nombre de qualifié(es) déjà existants dans le tableau (permette d'auto-valuer les "Q[X]" sortants à créer)
        $existingNbOut = $this->getRepository('tournament_box')->getOutBoxesCountByTableId($table->getId());

        $formOptions = [
            'tournament' => $tournament,
            'tableId' => $table->getId(),
            'nbOutToAdd' => $nbOut,
            'existingNbOut' => $existingNbOut,
            'nbRound' => $nbRound,
            'fromDate' => $tournament->getFromDate(),
            'toDate' => $tournament->getToDate(),
        ];

        return $this->createFormBuilder(FormType\TournamentBoxesAddType::class, null, $formOptions)
            ->getForm()
            ->handleRequest($request);
    }

    /**
     * Gestion de la soumission du formulaire d'ajout de boîtes dans le tableau.
     *
     * @param FormInterface $form
     *
     * @return bool
     */
    protected function handleAddBoxesFormSubmit(FormInterface $form)
    {
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array $formData */
            $formData = $form->getData();
            $repository = $this->getRepository('tournament_box');

            $nbOutToAdd = $form->getConfig()->getOption('nbOutToAdd');
            $nbRound = $form->getConfig()->getOption('nbRound');

            for ($idxOut = 1; $idxOut <= $nbOutToAdd; $idxOut++) {
                $prefixElement = 'box' . $idxOut;
                $this->handleBoxesSaving($repository, $formData, $prefixElement, $nbRound);
            }

            // Mise à jour de la date de mise à jour du tableau
            /** @var Table $table */
            $table = $this->getRepository('tournament_table')->find($formData['boxes_table_id']);
            $table->setStatus(Table::STATUS_PENDING);
            $table->setUpdatedBy($this->getUser()->getId());
            $this->getRepository('tournament_table')->save($table);

            return true;
        }

        $errors = $form->getErrors(true);

        return false;
    }

    /**
     * Sauvegarde des boîtes à ajouter au tableau.
     *
     * @param BoxRepository $repository
     * @param               $formData
     * @param               $prefixElement
     * @param               $idxRound
     */
    protected function handleBoxesSaving(BoxRepository $repository, $formData, $prefixElement, $idxRound)
    {
        $prefixBoxTop = $prefixElement . '-1';
        $prefixBoxBtm = $prefixElement . '-2';

        if ($idxRound > 1) {
            $this->handleBoxesSaving($repository, $formData, $prefixBoxTop, $idxRound - 1);
            $this->handleBoxesSaving($repository, $formData, $prefixBoxBtm, $idxRound - 1);
        }

        $boxHasData = false;

        $box = new Tournament\Box();
        $box->setTableId($formData['boxes_table_id']);

        if (isset($formData[$prefixElement . '_qualif_in'])) {
            $qualifIn = str_ireplace('q', '', $formData[$prefixElement . '_qualif_in']);
            $box->setQualifIn($qualifIn);
            $boxHasData = true;
        }
        if (isset($formData[$prefixElement . '_qualif_out'])) {
            $qualifOut = substr($formData[$prefixElement . '_qualif_out'], 1);
            $box->setQualifOut($qualifOut);
            $boxHasData = true;
        }

        if (isset($formData[$prefixElement . '_date']) || isset($formData[$prefixElement . '_time'])) {
            // On accepte uniquement l'un ou l'autre, en cas d'erreur de saisie et pour ne pas perdre de données
            if (isset($formData[$prefixElement . '_date'])) {
                $box->setDate($formData[$prefixElement . '_date']);
            }
            if (isset($formData[$prefixElement . '_time'])) {
                $box->setTime($formData[$prefixElement . '_time']);
            }
            $boxHasData = true;
        } elseif (isset($formData[$prefixElement . '_player_name'])) {
            $box->setPlayerName($formData[$prefixElement . '_player_name']);
            $boxHasData = true;

            if (isset($formData[$prefixElement . '_player_rank'])) {
                $box->setPlayerRank($formData[$prefixElement . '_player_rank']);
            }
            if (isset($formData[$prefixElement . '_player_club'])) {
                $box->setPlayerClub($formData[$prefixElement . '_player_club']);
            }
        }

        if ($idxRound > 1) {
            if (isset($this->savedBoxesByPrefix[$prefixBoxTop])) {
                $boxTopId = $this->savedBoxesByPrefix[$prefixBoxTop]->getId();
                $box->setBoxTopId($boxTopId);
                $boxHasData = true;
            }
            if (isset($this->savedBoxesByPrefix[$prefixBoxBtm])) {
                $boxBtmId = $this->savedBoxesByPrefix[$prefixBoxBtm]->getId();
                $box->setBoxBtmId($boxBtmId);
                $boxHasData = true;
            }
        }

        if ($boxHasData) {
            $repository->save($box);
            $this->savedBoxesByPrefix[$prefixElement] = $box;
        }
    }

    /**
     * Construit le formulaire d'édition des infos d'un tableau
     *
     * @param Request $request
     * @param Table   $table
     *
     * @return FormInterface
     */
    protected function buildEditForm(Request $request, Table $table)
    {
        $otherTables = $this->getRepository('tournament_table')->findAllOtherTablesOfTournament($table);
        $formOptions = [
            'otherTables' => $otherTables,
        ];

        $form = $this->createFormBuilder(FormType\TournamentTableEditType::class, $table, $formOptions)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }
}
