<?php

namespace Bundle\Asmb\Competition\Repository\Championship;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Helpers\PoolHelper;
use Bundle\Asmb\Competition\Helpers\PoolMeetingHelper;
use Bundle\Asmb\Competition\Helpers\PoolTeamHelper;

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
     * Retourne toutes les poules nécessitant un rafraîchissement des données depuis la FFT.
     * Une poule est considérée comme "à rafraîchir" si elle appartient à une compétition au statut ACTIF et s'il existe
     * au moins 1 rencontre passée ou du jour-même sans résultat.
     *
     * @return Pool[]
     */
    public function findAllToRefresh()
    {
        $pools = [];

        $qb = $this->getLoadQuery();

        // Filtre sur les poules des championnats ACTIFS uniquement
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_championship',
            'championship',
            $qb->expr()->eq("{$this->getAlias()}.championship_id", 'championship.id')
        );
        $qb->where('championship.is_active = true');

        // Filtre sur les poules ayant au moins 1 rencontre passée (ou du jour même) sans résultat
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_championship_pool_meeting',
            'pool_meeting',
            $qb->expr()->eq("{$this->getAlias()}.id", 'pool_meeting.pool_id')
        );
        // On exclut les rencontres avec les "fausses équipes"
        $exemptTeamPrefix = PoolTeamHelper::EXEMPT_TEAM_PREFIX;
        $qb->andWhere("pool_meeting.home_team_name_fft NOT LIKE '$exemptTeamPrefix%'");
        $qb->andWhere("pool_meeting.visitor_team_name_fft NOT LIKE '$exemptTeamPrefix%'");

        // On prend en compte les éventuelles dates de report
        $qb->andWhere('IFNULL(pool_meeting.report_date, pool_meeting.date) <= CURDATE()');

        // On exclut les rencontres ayant déjà un résultat
        $qb->andWhere('(pool_meeting.result IS NULL OR pool_meeting.result = :noneResult)');
        $qb->setParameter(':noneResult', PoolMeetingHelper::RESULT_NONE);

        $qb->groupBy("{$this->getAlias()}.id");

        $result = $qb->execute()->fetchAll();
        if ($result) {
            $pools = $this->hydrateAll($result, $qb);
        }

        return $pools;
    }
}
