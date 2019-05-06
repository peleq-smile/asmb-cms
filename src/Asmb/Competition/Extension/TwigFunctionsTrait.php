<?php

namespace Bundle\Asmb\Competition\Extension;

use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Helpers\PoolMeetingHelper;
use Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolRankingRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolRepository;

/**
 * Déclaration de fonctions Twig.
 */
trait TwigFunctionsTrait
{
    /**
     * Retourne les dernières rencontres du club.
     *
     * @param integer $pastDays
     *
     * @return \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getLastMeetings($pastDays)
    {
        $lastMeetings = [];

        if ($pastDays > 0) {
            $pastDays = -1 * $pastDays;
            $lastMeetings = $this->getLastOrNextMeetings($pastDays);
        }

        return $lastMeetings;
    }

    /**
     * Retourne les prochaines rencontres du club.
     *
     * @param integer $futureDays
     *
     * @return \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getNextMeetings($futureDays)
    {
        return $this->getLastOrNextMeetings($futureDays);
    }

    /**
     * Retourne les rencontres du moment, dans le passé ou le futur selon que $pastOrFutureDays soit négatif (passé)
     * ou positif (futur).
     *
     * @param integer $pastOrFutureDays
     *
     * @return \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    protected function getLastOrNextMeetings($pastOrFutureDays)
    {
        /** @var PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $this->getStorage()->getRepository('championship_pool_meeting');
        $pastDays = ($pastOrFutureDays < 0) ? (-1 * $pastOrFutureDays) : 0;
        $futureDays = ($pastOrFutureDays > 0) ? $pastOrFutureDays : 0;
        $meetingsOfTheMoment = $poolMeetingRepository->findClubMeetingsOfTheMoment($pastDays, $futureDays);

        return $meetingsOfTheMoment;
    }

    /**
     * @param integer $championshipId
     *
     * @return \Bundle\Asmb\Competition\Entity\Championship
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getChampionship($championshipId)
    {
        /** @var \Bundle\Asmb\Competition\Repository\ChampionshipRepository $championshipRepository */
        $championshipRepository = $this->getStorage()->getRepository('championship');
        $championship = $championshipRepository->find($championshipId);

        return $championship;
    }

    /**
     * Retourne les poules de la compétation d'id donné, groupées par nom de catégorie, avec éventuellement un filtre
     * sur les catégories à prendre en compte.
     *
     * @param integer $championshipId
     * @param array   $categoryNames
     *
     * @return array
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getPoolsPerCategoryName($championshipId, array $categoryNames)
    {
        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolRepository $poolRepository */
        $poolRepository = $this->getStorage()->getRepository('championship_pool');
        $poolsPerCategoryName = $poolRepository->findByChampionshipIdGroupByCategoryName(
            $championshipId,
            $categoryNames
        );

        return $poolsPerCategoryName;
    }

    /**
     * Retourne le classement des équipes des poules du championnat d'id donné.
     *
     * @param integer $championshipId
     * @param array   $categoryNames
     *
     * @return \Bundle\Asmb\Competition\Entity\Championship\PoolRanking[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getPoolRankingPerPoolId($championshipId, array $categoryNames)
    {
        $pools = $this->getPools($championshipId, $categoryNames);
        $poolIds = array_keys($pools);
        $poolRankingByPool = array_fill_keys($poolIds, []);

        /** @var PoolRankingRepository $poolRankingRepository */
        $poolRankingRepository = $this->getStorage()->getRepository('championship_pool_ranking');
        $poolRankingByPool = $poolRankingRepository->findByPoolIdsSortedRanking($poolIds) + $poolRankingByPool;

        return $poolRankingByPool;
    }

    /**
     * Retourne le tableau des rencontres des poules du championnat d'id donné.
     *
     * @param integer $championshipId
     * @param array   $categoryNames
     *
     * @return array
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getPoolMeetingsPerPoolId($championshipId, array $categoryNames)
    {
        $pools = $this->getPools($championshipId, $categoryNames);
        $poolIds = array_keys($pools);
        $poolMeetingsByPool = array_fill_keys($poolIds, []);

        /** @var PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $this->getStorage()->getRepository('championship_pool_meeting');
        $poolMeetingsByPool = $poolMeetingRepository->findGroupByPoolIdAndDay($poolIds) + $poolMeetingsByPool;

        return $poolMeetingsByPool;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        return [
            'getLastMeetings'          => 'getLastMeetings',
            'getNextMeetings'          => 'getNextMeetings',
            //            'getChampionship'          => 'getChampionship', //TODO retirer ?
            'getPoolsPerCategoryName'  => 'getPoolsPerCategoryName',
            'getPoolRankingPerPoolId'  => 'getPoolRankingPerPoolId',
            'getPoolMeetingsPerPoolId' => 'getPoolMeetingsPerPoolId',
        ];
    }

    /**
     * @return \Bolt\Storage\EntityManagerInterface
     */
    protected function getStorage()
    {
        /** @var \Bolt\Application $app */
        $app = $this->getContainer();
        /** @var \Bolt\Storage\EntityManagerInterface $storage */
        $storage = $app['storage'];

        return $storage;
    }

    /**
     * Recupère les poules à partir de l'id de compétition donné.
     *
     * @param integer $championshipId
     * @param array   $categoryNames
     *
     * @return Pool[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    protected function getPools($championshipId, array $categoryNames)
    {
        /** @var PoolRepository $poolRepository */
        $poolRepository = $this->getStorage()->getRepository('championship_pool');
        $pools = $poolRepository->findByChampionshipId($championshipId);

        return $pools;
    }
}
