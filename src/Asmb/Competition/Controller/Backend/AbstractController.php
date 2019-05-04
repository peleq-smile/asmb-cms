<?php

namespace Bundle\Asmb\Competition\Controller\Backend;

use Bolt\Controller\Backend\BackendBase;
use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Form\FormType;
use Bundle\Asmb\Competition\Helpers\CalendarHelper;
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
    /** @var array */
    private $config;

    /**
     * AbstractController constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Build add pool to a championship form.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer                                   $championshipId
     * @param string|null                               $categoryName
     *
     * @return FormInterface
     */
    protected function buildAddPoolForm(Request $request, $championshipId, $categoryName = null)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $formOptions = [
            'action'          => $this->generateUrl('pooladd', ['championshipId' => $championshipId]),
            'championship_id' => $championshipId,
            'category_names'  => $this->getRepository('championship_category')->findAllAsChoices(),
            'has_teams'       => false,
        ];

        // Generate the form
        $pool = new Pool();
        if (null !== $categoryName) {
            $pool->setCategoryName($categoryName);
        }
        $form = $this->createFormBuilder(FormType\PoolEditType::class, $pool, $formOptions)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }

    /**
     * Construction du formulaire d'édition des équipes des poules données.
     *
     * @param \Symfony\Component\HttpFoundation\Request                 $request
     * @param \Bundle\Asmb\Competition\Entity\Championship              $championship
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
     * Retourne les rencontres du moment, dans le passé ou le futur selon que $pastOrFutureDays soit négatif (passé)
     * ou positif (futur).
     *
     * @param int  $pastOrFutureDays
     * @param bool $onlyActiveChampionship
     * @param bool $withReportDates
     *
     * @return \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting[]
     */
    protected function getPastOrFutureMeetings(
        $pastOrFutureDays,
        $onlyActiveChampionship = true,
        $withReportDates = true
    ) {
        /** @var PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $this->getRepository('championship_pool_meeting');
        $pastDays = ($pastOrFutureDays < 0) ? (-1 * $pastOrFutureDays) : 0;
        $futureDays = ($pastOrFutureDays > 0) ? $pastOrFutureDays : 0;
        $meetingsOfTheMoment = $poolMeetingRepository
            ->findClubMeetingsOfTheMoment($pastDays, $futureDays, $onlyActiveChampionship, $withReportDates);

        return $meetingsOfTheMoment;
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
            $pools = $poolRepository->findByChampionshipIdGroupByCategoryName($championshipId);
        }

        return $pools;
    }

    /**
     * Retourne le nombre total de rencontres pour une poule donnée.
     *
     * @param \Bundle\Asmb\Competition\Entity\Championship\Pool $pool
     *
     * @return int
     */
    protected function getTotalMeetingsCount(Pool $pool)
    {
        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolTeamRepository $poolTeamRepository */
        $poolTeamRepository = $this->getRepository('championship_pool_team');
        $teamsCount = $poolTeamRepository->countByPoolId($pool->getId());
        $totalMeetingsCount = CalendarHelper::getTotalMeetingsCount($teamsCount);

        return $totalMeetingsCount;
    }

    /**
     * Retourne la valeur du paramètre de config demandé.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getAsmbConfig($key)
    {
        return $this->config[$key];
    }
}
