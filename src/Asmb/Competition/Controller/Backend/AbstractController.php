<?php

namespace Bundle\Asmb\Competition\Controller\Backend;

use Bolt\Controller\Backend\BackendBase;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Form\FormType;
use Bundle\Asmb\Competition\Repository\Championship\MatchRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolDayRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolTeamRepository;
use Bundle\Asmb\Competition\Repository\Championship\TeamRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * An abstract controller for competition controllers.
 *
 * @copyright 2019
 */
abstract class AbstractController extends BackendBase
{
    /**
     * Build add pool to a championship form.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer                                   $championshipId
     *
     * @return FormInterface
     */
    protected function buildAddPoolForm(Request $request, $championshipId)
    {
        // TODO : gérer la position des poules : forcer les valeurs de 1 à N après un save()

        /** @noinspection PhpUndefinedMethodInspection */
        $formOptions = [
            'action'          => $this->generateUrl('pooladd', ['championshipId' => $championshipId]),
            'championship_id' => $championshipId,
            'category_names'  => $this->getRepository('championship_category')->findAllAsChoices(),
        ];

        // Generate the form
        $pool = new Pool();
        $form = $this->createFormBuilder(FormType\PoolEditType::class, $pool, $formOptions)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }

    /**
     * Build add team to pool form.
     *
     * @param \Symfony\Component\HttpFoundation\Request         $request
     * @param \Bundle\Asmb\Competition\Entity\Championship\Pool $pool
     *
     * @return FormInterface
     */
    protected function buildAddTeamToPoolForm(Request $request, Pool $pool)
    {
        /** @var \Bundle\Asmb\Competition\Repository\Championship\TeamRepository $teamRepository */
        $teamRepository = $this->getRepository('championship_team');

        $formOptions = [
            'action'          => $this->generateUrl(
                'poolteamadd',
                [
                    'championshipId' => $pool->getChampionshipId(),
                    'poolId'         => $pool->getId(),
                ]
            ),
            'championship_id' => $pool->getChampionshipId(),
            'available_teams' => $teamRepository->findByCategoryNameAsChoices(
                $pool->getCategoryName()
            ),
        ];

        // Generate the form
        $form = $this->createFormBuilder(FormType\PoolAddTeamType::class, $pool, $formOptions)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }

    /**
     * Build remove team from pool form.
     *
     * @param \Symfony\Component\HttpFoundation\Request         $request
     * @param \Bundle\Asmb\Competition\Entity\Championship\Pool $pool
     *
     * @return FormInterface
     */
    protected function buildRemoveTeamFromPoolForm(Request $request, Pool $pool)
    {
        $formOptions = [
            'csrf_protection' => false,
        ];

        // Generate the form
        $form = $this->createFormBuilder(FormType\PoolRemoveTeamType::class, $pool, $formOptions)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request         $request
     * @param \Bundle\Asmb\Competition\Entity\Championship\Pool $pool
     *
     * @return FormInterface
     */
    protected function buildEditPoolMatchesForm(Request $request, Pool $pool)
    {
        /** @var PoolDayRepository $poolDayRepository */
        $poolDayRepository = $this->getRepository('championship_pool_day');
        /** @var MatchRepository $matchRepository */
        $matchRepository = $this->getRepository('championship_match');
        /** @var \Bundle\Asmb\Competition\Entity\Championship $championship */
        $championship = $this->getRepository('championship')->find($pool->getChampionshipId());

        $matchesData = $matchRepository->findAllByPoolIdAsArray($pool->getId());
        $daysData = $poolDayRepository->findDateByPoolId($pool->getId());

        $formData = [
            'matches' => $matchesData,
            'days'    => $daysData,
        ];

        /** @noinspection PhpUndefinedMethodInspection */
        $formOptions = [
            'action'          => $this->generateUrl(
                'poolmatchessave',
                [
                    'championshipId' => $pool->getChampionshipId(),
                    'poolId'         => $pool->getId(),
                ]
            ),
            'pool_id'         => $pool->getId(),
            'available_teams' => $this->getRepository('championship_team')
                ->findByPoolIdAsChoices($pool),
            'available_years' => [$championship->getYear(), $championship->getYear() - 1],
        ];

        // Generate the form
        $form = $this->createFormBuilder(FormType\PoolEditMatchesType::class, $formData, $formOptions)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }

    /**
     * Retrieve teams by pool id, for championship with given id.
     *
     * @param integer $championshipId
     *
     * @return array
     */
    protected function getPoolTeamsGroupByPoolId($championshipId)
    {
        $teamsByPool = [];
        if (null !== $championshipId) {
            $pools = $this->getPools($championshipId);
            $poolIds = array_keys($pools);
            $teamsByPool = array_fill_keys($poolIds, []);

            /** @var PoolTeamRepository $poolTeamRepository */
            $poolTeamRepository = $this->getRepository('championship_pool_team');
            $teamsByPool = $poolTeamRepository->findByPoolIdsGroupByPoolIdSortedByName($poolIds) + $teamsByPool;
        }

        return $teamsByPool;
    }

    /**
     * Retrieve teams by pool id, for championship with given id.
     *
     * @param integer $championshipId
     *
     * @return array
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    protected function getTeamsByPoolIdOLD($championshipId)
    {
        $teamsByPool = [];

        if (null !== $championshipId) {
            /** @var TeamRepository $teamRepository */
            $teamRepository = $this->getRepository('championship_team');

            foreach ($this->getPools($championshipId) as $pool) {
                $teamsByPool[$pool->getId()] = $teamRepository->findByPool($pool);
            }
        }

        return $teamsByPool;
    }

    /**
     * Retrieve pools of championship with given id.
     *
     * @param integer $championshipId
     *
     * @return Pool[]
     */
    protected function getPools($championshipId)
    {
        $pools = [];

        if (null !== $championshipId) {
            /** @var PoolRepository $poolRepository */
            $poolRepository = $this->getRepository('championship_pool');
            $pools = $poolRepository->findByChampionshipId($championshipId);
        }

        return $pools;
    }

    /**
     * Retrieve pools of championship with given id, grouped by category names.
     *
     * @param integer $championshipId
     *
     * @return bool|mixed|object[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    protected function getPoolsByCategoryName($championshipId)
    {
        $pools = [];

        if (null !== $championshipId) {
            /** @var PoolRepository $poolRepository */
            $poolRepository = $this->getRepository('championship_pool');
            $pools = $poolRepository->findByChampionshipIdGroupByCategoryName($championshipId);
        }

        return $pools;
    }

    /**
     * Retrieve matches of given pools, grouped by pool id and day.
     *
     * @param Pool[] $pools
     *
     * @return bool|mixed|object[]
     */
    protected function getMatchesByPoolIdByDay(array $pools)
    {
        /** @var MatchRepository $matchRepository */
        $matchRepository = $this->getRepository('championship_match');

        $poolIds = array_keys($pools);

        return $matchRepository->findGroupByPoolIdAndDay($poolIds);
    }

    /**
     * Retrieve teams scores of given pools, grouped by pool id.
     *
     * @param Pool[] $pools
     *
     * @return bool|mixed|object[]
     */
    protected function getTeamScoresByPoolId(array $pools)
    {
        $teamScoresByPoolId = [];

        foreach ($pools as $pool) {
            $teamsScores = $pool->getTeams();

            natsort($teamsScores);
            $teamScoresByPoolId[$pool->getId()] = $teamsScores;
            // TODO
        }

        return $teamScoresByPoolId;
    }
}
