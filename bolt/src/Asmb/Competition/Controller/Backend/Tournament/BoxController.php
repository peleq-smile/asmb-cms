<?php

namespace Bundle\Asmb\Competition\Controller\Backend\Tournament;

use Bolt\Response\TemplateResponse;
use Bolt\Response\TemplateView;
use Bundle\Asmb\Competition\Controller\Backend\AbstractController;
use Bundle\Asmb\Competition\Entity\Tournament;
use Bundle\Asmb\Competition\Entity\Tournament\Box;
use Bundle\Asmb\Competition\Entity\Tournament\Table;
use Bundle\Asmb\Competition\Form\FormType;
use Silex\ControllerCollection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contrôleur des boîtes de tableaux de tournois.
 *
 * @copyright 2020
 */
class BoxController extends AbstractController
{
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
        $c->match('/edit/{boxId}', 'edit')
            ->assert('boxId', '\d+')
            ->bind('tournamentboxedit');

        $c->match('/delete/{boxId}', 'delete')
            ->assert('boxId', '\d+')
            ->bind('tournamentboxdelete');

        return $c;
    }

    /**
     * @param Request $request
     * @param integer $boxId
     *
     * @return TemplateResponse|TemplateView|RedirectResponse
     */
    public function edit(Request $request, $boxId)
    {
        /** @var Box $box */
        $box = $this->getRepository('tournament_box')->find($boxId);
        /** @var Table $table */
        $table = $this->getRepository('tournament_table')->find($box->getTableId());
        /** @var Tournament $tournament */
        $tournament = $this->getRepository('tournament')->find($table->getTournamentId());

        $form = $this->buildEditForm($request, $box, $tournament);
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de la sauvegarde du vainqueur
            if (null !== $request->get('tournament_box_edit') &&
                isset($request->get('tournament_box_edit')['winner'])
            ) {
                if ('top' === $request->get('tournament_box_edit')['winner']) {
                    $box->setPlayerName($box->getBoxTop()->getPlayerName());
                    $box->setPlayerRank($box->getBoxTop()->getPlayerRank());
                    $box->setPlayerClub($box->getBoxTop()->getPlayerClub());
                } elseif ('btm' === $request->get('tournament_box_edit')['winner']) {
                    $box->setPlayerName($box->getBoxBtm()->getPlayerName());
                    $box->setPlayerRank($box->getBoxBtm()->getPlayerRank());
                    $box->setPlayerClub($box->getBoxBtm()->getPlayerClub());
                } else {
                    $box->setPlayerName(null);
                    $box->setPlayerRank(null);
                    $box->setPlayerClub(null);
                    $box->setScore(null);
                }
            }
            $this->getRepository('tournament_box')->save($box);

            $table->setUpdatedBy($this->getUser()->getId());
            $this->getRepository('tournament_table')->save($table);

            return $this->redirectToRoute('tournamenttable', ['tableId' => $box->getTableId()]);
        }

        return $this->render(
            '@AsmbCompetition/tournament/box/edit.twig',
            [
                'form' => $form->createView()
            ],
            [
                'tournament' => $tournament,
                'table' => $table,
                'box' => $box,
            ]
        );
    }

    /**
     * @param Request $request
     * @param integer $boxId
     *
     * @return RedirectResponse
     * @noinspection PhpUnusedParameterInspection
     */
    public function delete(Request $request, $boxId)
    {
        //TODO

        return $this->redirectToRoute('tournament');
    }

    /**
     * Construit le formulaire d'édition des infos d'une boîte d'un tableau.
     *
     * @param Request $request
     * @param Box $box
     * @param Tournament $tournament
     *
     * @return FormInterface
     */
    protected function buildEditForm(Request $request, Box $box, Tournament $tournament)
    {
        $formOptions = [
            'fromDate' => $tournament->getFromDate(),
            'toDate' => $tournament->getToDate(),
        ];

        // On récupère les boîtes précédentes si nécessaire
        if (null === $box->getBoxTop() && null !== $box->getBoxTopId()) {
            $boxTop = $this->getRepository('tournament_box')->find($box->getBoxTopId());
            $box->setBoxTop($boxTop);
        }
        if (null === $box->getBoxBtm() && null !== $box->getBoxBtmId()) {
            $boxBtm = $this->getRepository('tournament_box')->find($box->getBoxBtmId());
            $box->setBoxBtm($boxBtm);
        }

        $form = $this->createFormBuilder(FormType\TournamentBoxEditType::class, $box, $formOptions)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }
}
