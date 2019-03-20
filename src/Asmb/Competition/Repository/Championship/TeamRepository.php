<?php

namespace Bundle\Asmb\Competition\Repository\Championship;

use Bolt\Storage\Repository;
use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolTeam;
use Bundle\Asmb\Competition\Entity\Championship\Team;

/**
 * Repository for champioship teams.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class TeamRepository extends Repository
{
    /**
     * Return all teams group by category name.
     *
     * @return bool|mixed|object[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function findAllGroupByCategoryName()
    {
        $teamsGroupedByCategoryName = [];

        $categories = $this->getEntityManager()->getRepository('championship_category')
            ->findBy([], ['position', 'ASC']);

        // Init $teamsGroupedByCategoryName with category name SORTED BY POSITION
        /** @var \Bundle\Asmb\Competition\Entity\Championship\Category $category */
        foreach ($categories as $category) {
            $teamsGroupedByCategoryName[$category->getName()] = [];
        }

        $teams =  $this->findBy([], ['name', 'ASC']);
        /** @var \Bundle\Asmb\Competition\Entity\Championship\Team $team */
        foreach ($teams as $team) {
            $teamsGroupedByCategoryName[$team->getCategoryName()][] = $team;
        }

        return $teamsGroupedByCategoryName;
    }

    /**
     * @param string $categoryName
     *
     * @return array
     */
    public function findByCategoryNameAsChoices($categoryName)
    {
        $asChoices = [];
        $teams = $this->findBy(['category_name' => $categoryName], ['short_name', 'ASC']);

        /** @var \Bundle\Asmb\Competition\Entity\Championship\Team $team */
        foreach ($teams as $team) {
            $asChoices[$team->getId()] = $team->getShortName();
        }

        return $asChoices;
    }

    /**
     * @param \Bundle\Asmb\Competition\Entity\Championship\Pool $pool
     *
     * @return array
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function findByPoolAsChoices(Pool $pool)
    {
        $asChoices = [];
        $teams = $this->findByPoolId($pool->getId());

        /** @var \Bundle\Asmb\Competition\Entity\Championship\Team $team */
        foreach ($teams as $team) {
            $asChoices[$team->getId()] = $team->getShortName();
        }

        return $asChoices;
    }

    /**
     * Find teams for given pool id(s).
     *
     * @param integer|array $poolId
     *
     * @return bool|mixed|Team[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function findByPoolId($poolId)
    {
        $teams = [];

        $poolIds = is_array($poolId) ? $poolId : [$poolId];

        /** @var PoolTeamRepository $poolTeamRepository */
        $poolTeamRepository = $this->getEntityManager()->getRepository('championship_pool_team');
        $poolTeams = $poolTeamRepository->findBy(['pool_id' => $poolIds]);

        if (false !== $poolTeams) {
            $teamIds = array_map(function(PoolTeam $poolTeam) { return $poolTeam->getTeamId(); }, $poolTeams);

            $teams = $this->findBy(['id' => $teamIds], ['short_name', 'ASC']);
            $teams = (false !== $teams) ? $teams : [];

            // Let's add team id as key of result array
            $teamIds = array_map(function(Team $team) { return $team->getId(); }, $teams);
            $teams = array_combine($teamIds, $teams);
        }

        return $teams;
    }

    /**
     * Return teams from given team ids.
     *
     * @param array $teamIds
     *
     * @return array
     */
    public function findByIds($teamIds)
    {
        $teamNamesById = [];

        $teams = $this->findBy(['id' => $teamIds]);
        if (false !== $teams) {
            /** @var Team $team */
            foreach ($teams as $team) {
                $teamNamesById[$team->getId()] = $team;
            }
        }

        return $teamNamesById;
    }
}