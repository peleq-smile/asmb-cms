<?php

namespace Bundle\Asmb\Competition\Repository\Championship;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Helpers\PoolHelper;

/**
 * Repository for champioship pools.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class PoolRepository extends Repository
{
    /**
     * Return all pools of given championship id group by category name.
     *
     * @param integer $championshipId
     *
     * @return bool|mixed|object[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function findByChampionshipIdGroupByCategoryName($championshipId)
    {
        $poolsGroupedByCategoryName = [];

        $categories = $this->getEntityManager()->getRepository('championship_category')
            ->findBy([], ['position', 'ASC']);

        // Init $poolsGroupedByCategoryName with category name SORTED BY POSITION
        /** @var \Bundle\Asmb\Competition\Entity\Championship\Category $category */
        foreach ($categories as $category) {
            $poolsGroupedByCategoryName[$category->getName()] = [];
        }

        /** @var \Bundle\Asmb\Competition\Entity\Championship\Pool $pool */
        foreach ($this->findByChampionshipId($championshipId) as $pool) {
            $poolsGroupedByCategoryName[$pool->getCategoryName()][] = $pool;
        }

        return $poolsGroupedByCategoryName;
    }

    /**
     * Return all pools of given championship id, sorted by name.
     *
     * @param integer $championshipId
     *
     * @return bool|Pool[]
     */
    public function findByChampionshipId($championshipId)
    {
        $poolsSortedByName = [];

        $pools = $this->findBy(['championship_id' => $championshipId], ['position', 'ASC']);

        if (false !== $pools) {
            // Let's add pool id as key of result array
            $poolIds = array_map(function(Pool $pool) { return $pool->getId(); }, $pools);

            $poolsSortedByName = array_combine($poolIds, $pools);
        }

        return $poolsSortedByName;
    }

    /**
     * Return count of pools for championship with given id.
     *
     * @param integer $championshipId
     * @param string  $categoryName
     *
     * @return int
     */
    public function countByChampionshipIdAndCategoryName($championshipId, $categoryName)
    {
        $qb = $this->getLoadQuery()
            ->select('COUNT(' . $this->getAlias() . '.id) as count')
            ->resetQueryParts(['groupBy', 'join'])
            ->where('championship_id = :championshipId')
            ->andWhere('category_name = :categoryName')
            ->setParameter('championshipId', $championshipId)
            ->setParameter('categoryName', $categoryName)
        ;
        $result = $qb->execute()->fetchColumn(0);

        return (int) $result;
    }

    /**
     * Check if given Pool has all their matches filled.
     *
     * @param \Bundle\Asmb\Competition\Entity\Championship\Pool $pool
     *
     * @return bool
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function checkEditionIsComplete(Pool $pool)
    {
        $isComplete = (100 === $this->getEditionCompleteness($pool));

        return $isComplete;
    }

    /**
     * Calculate completeness of given Pool, according to filled match count.
     *
     * @param \Bundle\Asmb\Competition\Entity\Championship\Pool $pool
     *
     * @return int
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getEditionCompleteness(Pool $pool)
    {
        $completeness = 0;

        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolTeamRepository $poolTeamRepository */
        $poolTeamRepository = $this->getEntityManager()->getRepository('championship_pool_team');
        $teamsCount = $poolTeamRepository->countByPoolId($pool->getId());

        $daysCount = PoolHelper::getDaysCount($teamsCount);
        $matchesCountPerDay = PoolHelper::getMatchesCountPerDay($teamsCount);

        $completeMatchesCountToCheck = $daysCount * $matchesCountPerDay;

        if ($completeMatchesCountToCheck > 0) {
            /** @var \Bundle\Asmb\Competition\Repository\Championship\MatchRepository $matchRepository */
            $matchRepository = $this->em->getRepository('championship_match');
            $qb = $matchRepository->getLoadQuery();
            $qb->select('COUNT(' . $matchRepository->getAlias() . '.id) as count')
                ->where('pool_id = :poolId')
                ->andWhere('home_team_id IS NOT NULL')
                ->andWhere('visitor_team_id IS NOT NULL')
                ->setParameter('poolId', $pool->getId());

            $result = (int) $qb->execute()->fetchColumn(0);

            $completeness = (int) (100 * ($result / $completeMatchesCountToCheck));
        }

        return $completeness;
    }

    /**
     * @param Pool[] $pools
     *
     * @return array
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getEditionCompletenesses(array $pools)
    {
        $completenesses = [];

        foreach ($pools as $pool) {
            $completenesses[$pool->getId()] = 0;
        }
        $poolIds = array_keys($completenesses);

        if (!empty($poolIds)) {
            /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolTeamRepository $poolTeamRepository */
            $poolTeamRepository = $this->getEntityManager()->getRepository('championship_pool_team');
            $poolTeamAlias = $poolTeamRepository->getAlias();
            $matchAlias = 'm';

            $qb = $poolTeamRepository->getLoadQuery();
            // SELECT
            $qb->select("$poolTeamAlias.pool_id as pool_id");
            $qb->addSelect("COUNT(DISTINCT $matchAlias.id) as filled_match_count");
            // Expected match count depends on teams count into each pool : if it's even or odd
            $qb->addSelect("(IF(COUNT(DISTINCT $poolTeamAlias.team_id) % 2 = 0, 
                                COUNT(DISTINCT $poolTeamAlias.team_id) - 1, 
                                COUNT(DISTINCT $poolTeamAlias.team_id))
                            ) * floor(COUNT(DISTINCT $poolTeamAlias.team_id) / 2) as expected_match_count");
            // JOIN
            $qb->leftJoin(
                $poolTeamAlias,
                'bolt_championship_match',
                $matchAlias,
                $qb->expr()->eq("$matchAlias.pool_id", "$poolTeamAlias.pool_id")
            );
            // WHERE
            $qb->where($qb->expr()->in("$poolTeamAlias.pool_id", $poolIds));
            $qb->andWhere("$matchAlias.home_team_id IS NOT NULL");
            $qb->andWhere("$matchAlias.visitor_team_id IS NOT NULL");
            // GROUP BY
            $qb->groupBy("$poolTeamAlias.pool_id");

            $result = $qb->execute()->fetchAll();

            foreach ($result as $row) {
                $completenesses[$row['pool_id']] = (int) (100 * ($row['filled_match_count'] / $row['expected_match_count']));
            }
        }

        return $completenesses;
    }

    /**
     * @param \Bundle\Asmb\Competition\Entity\Championship\Pool $pool
     *
     * @return integer|boolean
     */
    public function findPoolWithLteTeamCount(Pool $pool)
    {
        $qb = $this->getLoadQuery();
        $qb->select("other_pool.id as other_pool_id");
        $qb->addSelect("count(distinct other_pt.team_id) as other_pool_team_count");

        $qb->leftJoin(
            $this->getAlias(),
            'bolt_championship_pool_team',
            'orig_pt',
            $qb->expr()->eq("orig_pt.pool_id", "{$this->getAlias()}.id")
        );
        $qb->leftJoin(
            $this->getAlias(),
            'bolt_championship_pool',
            'other_pool',
            $qb->expr()->eq("other_pool.championship_id", "{$this->getAlias()}.championship_id")
        );
        $qb->leftJoin(
            'other_pool',
            'bolt_championship_pool_team',
            'other_pt',
            $qb->expr()->eq("other_pt.pool_id", "other_pool.id")
        );

        $qb->where($qb->expr()->eq("{$this->getAlias()}.id", $pool->getId()));
        $qb->andWhere($qb->expr()->neq("other_pool.id", $pool->getId()));
        $qb->groupBy('other_pool_id');
        $qb->having($qb->expr()->lte("other_pool_team_count", 'count(distinct orig_pt.team_id)'));

        $otherPoolId = $qb->execute()->fetchColumn();

        return $otherPoolId;
    }
}