<?php

namespace Bundle\Asmb\Competition\Controller\Backend;

use Bolt\Controller\Backend\BackendBase;
use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Championship\Match;
use Bundle\Asmb\Competition\Form\FormType;
use Silex\ControllerCollection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for Match routes.
 *
 * @copyright 2019
 */
class MatchController extends BackendBase
{
    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $c)
    {
        $c->match('/edit/{poolId}/{day}/{position}', 'edit')
            ->assert('poolId', '\d+')
            ->assert('day', '\d+')
            ->assert('position', '\d+')
            ->bind('matchedit');

        $c->post('/save/date/{championshipId}/{matchId}', 'saveDate')
            ->assert('championshipId', '\d+')
            ->assert('matchId', '\d+')
            ->bind('matchsavedate');

        return $c;
    }

    /**
     * @param Request $request
     * @param integer $poolId
     * @param integer $day
     * @param integer $position
     *
     * @return Response
     */
    public function edit(Request $request, $poolId, $day, $position)
    {
        /** @var \Bundle\Asmb\Competition\Entity\Championship\Pool $pool */
        $pool = $this->getRepository('championship_pool')->find($poolId);
        if (!$pool) {
            $this->flashes()->error(Trans::__('general.phrase.wrong-parameter-cannot-edit'));
        }

        $match = $this->getRepository('championship_match')
            ->findOneBy(['pool_id' => $poolId, 'day' => $day, 'position' => $position]);
        if (!$match) {
            $this->flashes()->error(Trans::__('general.phrase.wrong-parameter-cannot-edit'));
        }

        $form = $this->buildEditForm($request, $pool->getChampionshipId(), $match);

        $context = [
            'match' => $match,
            'pool'  => $pool,
            'form'  => $form->createView(),
        ];

        return $this->render(
            '@AsmbCompetition/championship/match/_modal-edit.twig',
            $context
        );
    }

    /**
     * Build championship edit form.
     *
     * @param \Symfony\Component\HttpFoundation\Request          $request
     * @param integer                                            $championshipId
     * @param \Bundle\Asmb\Competition\Entity\Championship\Match $match
     *
     * @return FormInterface
     */
    protected function buildEditForm(Request $request, $championshipId, Match $match)
    {
        /** @var \Bundle\Asmb\Competition\Entity\Championship\PoolDay $poolDay */
        $poolDay = $this->getRepository('championship_pool_day')->findOneBy(
            ['pool_id' => $match->getPoolId(), 'day' => $match->getDay()]
        );
        if (!$poolDay) {
            $this->flashes()->error(Trans::__('general.phrase.wrong-parameter-cannot-edit'));
        }

        /** @var \Bundle\Asmb\Competition\Entity\Championship $championship */
        $championship = $this->getRepository('championship')->find($championshipId);

        /** @noinspection PhpUndefinedMethodInspection */
        $formOptions = [
            'action'        => null,
            // Date of match must be different of pool day date
            'excluded_date' => $poolDay->getDate(),
            'available_years' => [$championship->getYear(), $championship->getYear() - 1],
        ];

        $form = $this->createFormBuilder(FormType\MatchEditDateType::class, $match, $formOptions)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }

    /**
     * @param Request $request
     * @param integer $championshipId
     * @param integer $matchId
     *
     * @return Response
     *
     * @deprecated
     */
    public function saveDate(Request $request, $championshipId, $matchId)
    {
        /** @var \Bundle\Asmb\Competition\Entity\Championship\Match $match */
        $match = $this->getRepository('championship_match')->find($matchId);
        if (!$match) {
            $this->flashes()->error(Trans::__('general.phrase.wrong-parameter-cannot-edit'));
        }

        $form = $this->buildEditForm($request, $championshipId, $match);

        if ($form->isSubmitted()) {
            if (! $form->isValid()) {
                $match->setDate(null);
            }
            $this->getRepository('championship_match')->save($match);
        }

        return $this->redirectToRoute('championshipedit', ['id' => $championshipId]);
    }
}
