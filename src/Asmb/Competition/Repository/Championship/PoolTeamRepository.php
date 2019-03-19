<?php

namespace Bundle\Asmb\Competition\Repository\Championship;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Championship\PoolDay;
use Bundle\Asmb\Competition\Entity\Championship\PoolTeam;

/**
 * Repository for competition pool team.
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2019
 */
class PoolTeamRepository extends Repository
{
    /**
     * Return pool teams count from given pool id.
     *
     * @param integer $poolId
     *
     * @return integer
     */
    public function countByPoolId($poolId)
    {
        $qb = $this->getLoadQuery();
        $qb->select('COUNT(' . $this->getAlias() . '.id) as count')
            ->where('pool_id = :poolId')
            ->andWhere('team_id IS NOT NULL')
            ->setParameter('poolId', $poolId);

        $result = (int) $qb->execute()->fetchColumn(0);

        return $result;
    }

    /**
     * Return pool teams from given pool id.
     *
     * @param integer $poolId
     *
     * @return PoolTeam[]
     */
    public function findByPoolIdSortedByName($poolId)
    {
        $poolTeamsSortedByName = [];

        $poolTeams = $this->findBy(['pool_id' => $poolId], ['team_name', 'ASC']);
        if (false !== $poolTeams) {
            /** @var PoolTeam $poolTeam */
            foreach ($poolTeams as $poolTeam) {
                $poolTeamsSortedByName[$poolTeam->getTeamId()] = $poolTeam;
            }
        }

        return $poolTeamsSortedByName;
    }

    /**
     * Return teams group by pool id sorted by team name, from given pool ids.
     *
     * @param array $poolIds
     *
     * @return PoolTeam[]
     */
    public function findByPoolIdsGroupByPoolIdSortedByName(array $poolIds)
    {
        $poolsGroupByPoolIdSortedByName = [];

        $poolTeams = $this->findBy(['pool_id' => $poolIds], ['team_name', 'ASC']);
        if (false !== $poolTeams) {
            /** @var PoolTeam $poolTeam */
            foreach ($poolTeams as $poolTeam) {
                $poolsGroupByPoolIdSortedByName[$poolTeam->getPoolId()][$poolTeam->getTeamId()] = $poolTeam;
            }
        }

        return $poolsGroupByPoolIdSortedByName;
    }

    /**
     * Return teams group by pool id sorted by score, from given pool ids.
     *
     * @param array $poolIds
     *
     * @return PoolTeam[]
     */
    public function findByPoolIdsGroupByPoolIdSortedByScore(array $poolIds)
    {
        $poolsGroupByPoolIdSortedByName = [];

        $qb = $this->findWithCriteria(['pool_id' => $poolIds]);
        $qb->orderBy('points', 'DESC');
        $qb->addOrderBy('match_diff', 'DESC');
        $result = $qb->execute()->fetchAll();

        if ($result) {
            $poolTeams = $this->hydrateAll($result, $qb);

            /** @var PoolTeam $poolTeam */
            foreach ($poolTeams as $poolTeam) {
                $poolsGroupByPoolIdSortedByName[$poolTeam->getPoolId()][$poolTeam->getTeamId()] = $poolTeam;
            }
        }

        return $poolsGroupByPoolIdSortedByName;
    }

    /**
     * @param       $poolId
     * @param array $formData
     *
     * @return void
     */
    public function savePoolDays($poolId, array $formData)
    {
        $existingDays = $this->findByPoolId($poolId);

        foreach ($formData as $key => $value) {
            if (strpos($key, "pool{$poolId}_") !== 0) {
                // We considere here only submitted data beginning with 'poolX_'
                continue;
            }

            // Day date case (there is other data submitted, so we have to check)
            /** @see \Bundle\Asmb\Competition\Repository\Championship\MatchRepository::savePoolMatches */
            if (preg_match("/^pool{$poolId}_day_(\d+)$/", $key, $pregMatches)) {
                $day = $pregMatches[1]; // Day is second entry of this array

                /** @var PoolDay $poolDay */
                // Let's check if day already exists or if it's new one
                if (isset($existingDays["day_{$day}"])) {
                    // Update case: retrieve existing day
                    $poolDay = $existingDays["day_{$day}"];
                } else {
                    // Insert case
                    $poolDay = new PoolDay();
                    $poolDay->setPoolId($poolId);
                    $poolDay->setDay($day);
                }
                $poolDay->setDate($value);

                $this->save($poolDay, true);
            }
        }
    }

    /**
     * Return matches of given pool id, grouped by day.
     *
     * @param integer $poolId
     *
     * @return PoolDay[]
     */
    public function findByPoolId($poolId)
    {
        $daysByPoolId = [];

        $poolDays = $this->findBy(['pool_id' => $poolId]);

        if (false !== $poolDays) {
            /** @var PoolDay $poolDay */
            foreach ($poolDays as $poolDay) {
                $daysByPoolId['day_' . $poolDay->getDay()] = $poolDay;
            }
        }

        return $daysByPoolId;
    }
}
