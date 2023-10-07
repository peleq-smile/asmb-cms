<?php

namespace Bundle\Asmb\Competition\Controller\Backend;

use Silex\Application;
use Bolt\Controller\Backend\BackendBase;
use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Form\FormType;
use Bundle\Asmb\Competition\Helpers\PoolHelper;
use Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolRankingRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolTeamRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * An abstract controller for competition controllers.
 *
 * @copyright 2019
 */
abstract class AbstractController extends BackendBase
{
    /** @var Pool[] */
    private $pools;

    /**
     * Retourne la route par défaut utiliser pour la gestion des permissions.
     *
     * @param Request $request
     *
     * @return string
     */
    protected function getRoleRoute(Request $request)
    {
        $roleRoute = 'competition';

        $controllerParameter = $request->get('_controller');
        if (isset($controllerParameter[1])) {
            $action = $controllerParameter[1];

            switch ($action) {
                case 'add':
                case 'edit':
                case 'delete':
                    $roleRoute = 'competition:edit';
                    break;
                default:
                    break;
            }
        }

        return $roleRoute;
    }

    /**
     * Middleware function to check whether a user is logged on.
     *
     * @param Request     $request   The Symfony Request
     * @param Application $app       The application/container
     * @param string      $roleRoute An overriding value for the route name in permission checks
     *
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function before(Request $request, Application $app, $roleRoute = null)
    {
        if (null === $roleRoute) {
            $roleRoute = $this->getRoleRoute($request);
        }

        return parent::before($request, $app, $roleRoute);
    }

    /**
     * Build add pool to a championship form.
     *
     * @param Request $request
     * @param integer $championshipId
     * @param string|null $categoryIdentifier
     *
     * @return FormInterface
     */
    protected function buildAddPoolForm(Request $request, int $championshipId, ?string $categoryIdentifier = null)
    {
        $formOptions = [
            'action'          => $this->generateUrl('pooladd', ['championshipId' => $championshipId]),
            'championship_id' => $championshipId,
            'categories'  => $this->getRepository('championship_category')->findAllAsChoices(),
            'calendarEventTypes'  => $this->getCalendarEventTypes(),
            'has_teams'       => false,
        ];

        // Generate the form
        $pool = new Pool();
        if (null !== $categoryIdentifier) {
            $pool->setCategoryIdentifier($categoryIdentifier);
        }
        $form = $this->createFormBuilder(FormType\PoolEditType::class, $pool, $formOptions)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }

    /**
     * Construction du formulaire d'édition des équipes des poules données.
     *
     * @param Request $request
     * @param Championship $championship
     * @param \Bundle\Asmb\Competition\Entity\Championship\PoolTeam[][] $poolTeamsPerPoolId
     *
     * @return FormInterface
     */
    protected function buildEditPoolsTeamsForm(Request $request, Championship $championship, array $poolTeamsPerPoolId)
    {
        $formOptions = [
            'poolTeamsPerPoolId' => $poolTeamsPerPoolId,
        ];

        // Generate the form
        $form = $this->createFormBuilder(FormType\PoolsTeamsEditType::class, $championship, $formOptions)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }

    /**
     * Retourne les équipes des poules du championnat d'id donné, trié par nom d'équipe.
     *
     * @param integer $championshipId
     *
     * @return array
     */
    protected function getPoolTeamsPerPoolId($championshipId)
    {
        $poolTeamsByPool = [];
        if (null !== $championshipId) {
            $pools = $this->getPools($championshipId);
            $poolIds = array_keys($pools);
            $poolTeamsByPool = array_fill_keys($poolIds, []);

            /** @var PoolTeamRepository $poolTeamRepository */
            $poolTeamRepository = $this->getRepository('championship_pool_team');
            $poolTeamsByPool = $poolTeamRepository->findByPoolIdsSortedByNameFft($poolIds) + $poolTeamsByPool;
        }

        return $poolTeamsByPool;
    }

    /**
     * Retourne le classement des équipes des poules du championnat d'id donné.
     *
     * @param integer $championshipId
     *
     * @return array
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    protected function getPoolRankingPerPoolId($championshipId)
    {
        $poolRankingByPool = [];
        if (null !== $championshipId) {
            $pools = $this->getPools($championshipId);
            $poolIds = array_keys($pools);
            $poolRankingByPool = array_fill_keys($poolIds, []);

            /** @var PoolRankingRepository $poolRankingRepository */
            $poolRankingRepository = $this->getRepository('championship_pool_ranking');
            $poolRankingByPool = $poolRankingRepository->findByPoolIdsSortedRanking($poolIds) + $poolRankingByPool;
        }

        return $poolRankingByPool;
    }

    /**
     * Retourne le tableau des rencontres des poules du championnat d'id donné.
     *
     * @param integer $championshipId
     *
     * @return array
     */
    protected function getPoolMeetingsPerPoolId($championshipId)
    {
        $poolMeetingsByPool = [];
        if (null !== $championshipId) {
            $pools = $this->getPools($championshipId);
            $poolIds = array_keys($pools);
            $poolMeetingsByPool = array_fill_keys($poolIds, []);

            /** @var PoolMeetingRepository $poolMeetingRepository */
            $poolMeetingRepository = $this->getRepository('championship_pool_meeting');
            $poolMeetingsByPool = $poolMeetingRepository->findGroupByPoolIdAndDay($poolIds) + $poolMeetingsByPool;
        }

        return $poolMeetingsByPool;
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
        if (null == $this->pools) {
            $this->pools = [];

            if (null !== $championshipId) {
                /** @var PoolRepository $poolRepository */
                $poolRepository = $this->getRepository('championship_pool');
                $this->pools = $poolRepository->findByChampionshipId($championshipId);
            }
        }

        return $this->pools;
    }

    /**
     * Retrieve pools of championship with given id, grouped by category names.
     *
     * @param integer $championshipId
     *
     * @return bool|mixed|object[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    protected function getPoolsPerCategoryName($championshipId)
    {
        $pools = [];

        if (null !== $championshipId) {
            /** @var PoolRepository $poolRepository */
            $poolRepository = $this->getRepository('championship_pool');
            $pools = $poolRepository->findByChampionshipIdGroupByCategory($championshipId);
        }

        return $pools;
    }

    /**
     * Retourne le nombre total de rencontres pour une poule donnée.
     *
     * @param Pool $pool
     *
     * @return int
     */
    protected function getTotalMeetingsCount(Pool $pool)
    {
        /** @var PoolTeamRepository $poolTeamRepository */
        $poolTeamRepository = $this->getRepository('championship_pool_team');
        $teamsCount = $poolTeamRepository->countByPoolId($pool->getId());
        $totalMeetingsCount = PoolHelper::getTotalMeetingsCount($teamsCount);

        return $totalMeetingsCount;
    }

    protected function getCalendarEventTypes()
    {
        $eventTypes = [];

        /** @var \Bolt\Storage\Query\Query $query */
        $query = $this->app['query'];
        /** @var \Bolt\Storage\Query\QueryResultset $queryResultSet */
        $queryResultSet = $query->getContent(
            'type_evenement_calendriers',
            [
                'order' => 'position',
            ]
        );
        /** @var \Bolt\Storage\Entity\Content $content */
        foreach ($queryResultSet as $content) {
            $eventTypeFieldValues = $content->getValues();
            $eventTypes[$eventTypeFieldValues['name']] = $eventTypeFieldValues['color'];
        }

        return $eventTypes;
    }
}