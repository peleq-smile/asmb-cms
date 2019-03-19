<?php

namespace Bundle\Asmb\Competition\Repository\Championship;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Championship\Match;
use Bundle\Asmb\Competition\Helpers\MatchHelper;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

/**
 * Repository for champioship matches.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class MatchRepository extends Repository
{
    private $sortedMatchesByPoolId = [];


    /**
     * As 'match' is a reserved name into Mysql, we have to use another alias.
     *
     * @return string
     */
    public function getAlias()
    {
        return 'm';
    }

    /**
     * Return matches of given pool id, grouped by day then by position.
     *
     * @param integer $poolId
     *
     * @return array
     */
    public function findByPoolIdGroupByDayAndPosition($poolId)
    {
        $groupedMatches = [];

        $qb = $this->findWithCriteria(['pool_id' => $poolId]);
        $qb->orderBy('day', 'ASC');
        $qb->orderBy('position', 'ASC');
        $result = $qb->execute()->fetchAll();

        if ($result) {
            $matches = $this->hydrateAll($result, $qb);
            /** @var Match $match */
            foreach ($matches as $match) {
                $groupedMatches['day_' . $match->getDay()][$match->getPosition()] = $match;
            }
        }

        return $groupedMatches;
    }

    /**
     * Return matches of given pool id, grouped by day then by position.
     * Sample of returned array :
     * [
     *     10 => [ // With 10 = Id of pool
     *         1 => [ // With 1 = day 1
     *             <Id of match> => Match entity instance,
     *             <Id of match> => Match entity instance,
     *             ...
     *         ],
     *         2 => [ // With 2 = day 2
     *             <Id of match> => Match entity instance,
     *             ...
     *         ],
     *         ...
     *     ],
     *     12 => [...] // With 12 = Id of pool
     * ]
     *
     * @param array $poolIds
     *
     * @return array
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function findGroupByPoolIdAndDay(array $poolIds)
    {
        $groupedMatches = [];

        $qb = $this->findWithCriteria(['pool_id' => $poolIds]);
        $qb->orderBy('day', 'ASC');
        $qb->addOrderBy('position', 'ASC');
        $result = $qb->execute()->fetchAll();

        if ($result) {
            $matches = $this->hydrateAll($result, $qb);

            // Retrieve Team entities to assign them into Match object
            /** @var \Bundle\Asmb\Competition\Repository\Championship\TeamRepository $teamRepository */
            $teamRepository = $this->getEntityManager()->getRepository('championship_team');
            $teams = $teamRepository->findByPoolId($poolIds);

            /** @var Match $match */
            foreach ($matches as $match) {
                // Add Team object into Match on-the-fly
                if (isset($teams[$match->getHomeTeamId()])) {
                    $match->setHomeTeam($teams[$match->getHomeTeamId()]);
                }

                if (isset($teams[$match->getVisitorTeamId()])) {
                    $match->setVisitorTeam($teams[$match->getVisitorTeamId()]);
                }

                $groupedMatches[$match->getPoolId()][$match->getDay()][$match->getId()] = $match;
            }
        }

        foreach ($poolIds as $poolId) {
            if (!isset($groupedMatches[$poolId])) {
                $groupedMatches[$poolId] = [];
            }
        }

        return $groupedMatches;
    }

    /**
     * Return matches data as array of given pool id, grouped by day then by position.
     *
     * @param integer|array $poolId
     *
     * @return array
     */
    public function findAllByPoolIdAsArray($poolId)
    {
        $matchesAsArray = [];

        $matches = $this->findBy(['pool_id' => $poolId], ['position', 'ASC']);

        if (false !== $matches) {
            /** @var Match $match */
            foreach ($matches as $match) {
                $matchesAsArray['day_' . $match->getDay()][$match->getPosition()] = [
                    'home_team_id'    => $match->getHomeTeamId(),
                    'visitor_team_id' => $match->getVisitorTeamId(),
                    'time'            => $match->getTime(),
                    'date'            => $match->getDate(),
                ];
            }
        }

        return $matchesAsArray;
    }

    /**
     * Save pool matches.
     *
     * @param int   $poolId
     * @param array $formData
     */
    public function savePoolMatches($poolId, array $formData)
    {
        // Base of matches data : existing ones, which were injected as data in form
        $matchesData = $formData['matches'];
        $daysData = $formData['days'];

        foreach ($formData as $key => $value) {
            if (strpos($key, "pool{$poolId}_") !== 0) {
                // We considere here only submitted data beginning with 'poolX_'
                continue;
            }

            // Match case
            if (preg_match("/^pool{$poolId}_day_(\d+)_match_(\d+)_([\d\w]+)$/", $key, $pregMatches)) {
                $day = $pregMatches[1]; // Day is second entry of this array
                $position = $pregMatches[2]; // Day key is second entry of this array
                $fieldKey = $pregMatches[3]; // Should be : home_team_id|visitor_team_id|time|date

                $matchesData["day_{$day}"][$position][$fieldKey] = $value;
            }
        }

        // Now, $matchesData is up-to-date
        $existingMatches = $this->findByPoolIdGroupByDayAndPosition($poolId);

        foreach ($matchesData as $dayKey => $matchesPerPosition) {
            foreach ($matchesPerPosition as $position => $matchData) {
                /** @var Match $match */
                // Let's check if match already exists or if it's new one
                if (isset($existingMatches[$dayKey][$position])) {
                    // Update case: retrieve existing match
                    $match = $existingMatches[$dayKey][$position];
                } else {
                    // Insert case
                    $match = new Match();
                    $match->setPoolId($poolId);
                    $match->setDay(substr($dayKey, 4));
                    $match->setPosition($position);
                }
                $match->setHomeTeamId($matchData['home_team_id']);
                $match->setVisitorTeamId($matchData['visitor_team_id']);
                $match->setTime($matchData['time']);

                /** @var \DateTime $date */
                $date = isset($matchData['date']) ? $matchData['date'] : null;
                if (null !== $date && $date->getTimestamp() === $daysData[$dayKey]->getTimestamp()) {
                    // If custom date is equal to day pool date... we force null value, because this is not a custom
                    // date in reality !
                    $date = null;
                }

                $match->setDate($date);

                try {
                    $this->save($match, true);
                } catch (UniqueConstraintViolationException $e) {
                    $existingMatch = $this->findOneBy(
                        [
                            'home_team_id'    => $match->getHomeTeamId(),
                            'visitor_team_id' => $match->getVisitorTeamId(),
                        ]
                    );
                    $this->delete($existingMatch);
                    $this->save($match, true);
                }
            }
        }
    }

    /**
     * Save all pool match scores and 
     *
     * @param array $formData
     *
     * @return void
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function saveMatchScores(array $formData)
    {
        /**
         * Format of $formData:
         * [
         *     matches => [... all Match object per pool id then day],
         *     pool{idOfPool}_day_{day}_match{idOfMatch}_score_home => null|integer,
         *     pool{idOfPool}_day_{day}_match{idOfMatch}_score_visitor => null|integer,
         *     ...
         * ]
         */
        $matches = $formData['matches'];
        $matchesToSave = [];

        // To collect id of updated pools
        $poolIds = [];

        foreach ($formData as $key => $value) {

            if (preg_match("/^pool(\d+)_day(\d+)_match(\d+)_score_(home|visitor)$/", $key, $pregMatches)) {
                $poolId = $pregMatches[1]; // Pool Id is second entry of this array
                $day = $pregMatches[2]; // Day is third entry of this array
                $matchId = $pregMatches[3]; // Match Id is fourth entry of this array
                $whichScore = $pregMatches[4]; // Match with "home" or "visitor"

                /** @var Match $match */
                $match = $matches[$poolId][$day][$matchId];
                if ('home' === $whichScore) {
                    // Home Team Score case
                    $match->setScoreHome($value);
                } else {
                    // Visitor Team Score case
                    $match->setScoreVisitor($value);
                }

                $matchesToSave[$match->getId()] = $match;

                $poolIds[$poolId] = $poolId;
                continue;
            }
        }

        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolTeamRepository $poolTeamRepository */
        $poolTeamRepository = $this->getEntityManager()->getRepository('championship_pool_team');
        $poolTeamsPerPoolId = $poolTeamRepository->findByPoolIdsGroupByPoolIdSortedByScore($poolIds);

        $this->resetRanking($poolTeamsPerPoolId);
        $poolTeamsToSave = [];

        // Finally, save all matches
        foreach ($matchesToSave as $match)
        {
            $this->save($match, true);

            if (null !== $match->getScoreHome() && null !== $match->getScoreVisitor()) {
                /** @var \Bundle\Asmb\Competition\Entity\Championship\PoolTeam $homePoolTeam */
                /** @var \Bundle\Asmb\Competition\Entity\Championship\PoolTeam $visitorPoolTeam */
                $homePoolTeam = $poolTeamsPerPoolId[$match->getPoolId()][$match->getHomeTeamId()];
                $visitorPoolTeam = $poolTeamsPerPoolId[$match->getPoolId()][$match->getVisitorTeamId()];

                if ($match->getScoreHome() > $match->getScoreVisitor()) {
                    // Add winner/loser points
                    $homePoolTeam->addPoints(MatchHelper::WINNER_POINTS);
                    $visitorPoolTeam->addPoints(MatchHelper::LOSER_POINTS);
                    // Add match diff
                    $matchDiff = $match->getScoreHome() - $match->getScoreVisitor();
                    $homePoolTeam->addMatchDiff($matchDiff);
                    $visitorPoolTeam->addMatchDiff(-1 * $matchDiff);
                } elseif ($match->getScoreHome() < $match->getScoreVisitor()) {
                    // Add winner/loser points
                    $homePoolTeam->addPoints(MatchHelper::LOSER_POINTS);
                    $visitorPoolTeam->addPoints(MatchHelper::WINNER_POINTS);
                    // Add match diff
                    $matchDiff = $match->getScoreVisitor() - $match->getScoreHome();
                    $homePoolTeam->addMatchDiff(-1 * $matchDiff);
                    $visitorPoolTeam->addMatchDiff($matchDiff);
                } else { // $match->getScoreHome() === $match->getScoreVisitor()
                    $homePoolTeam->addPoints(MatchHelper::DRAW_POINTS);
                    $visitorPoolTeam->addPoints(MatchHelper::DRAW_POINTS);
                }

                $homePoolTeam->addDaysPlayed();
                $visitorPoolTeam->addDaysPlayed();

                $poolTeamsToSave[$homePoolTeam->getId()] = $homePoolTeam;
                $poolTeamsToSave[$visitorPoolTeam->getId()] = $visitorPoolTeam;
            }
        }

        // Finally, save all new ranking pool team
        foreach ($poolTeamsToSave as $poolTeam) {
            $poolTeamRepository->save($poolTeam, true);
        }
    }

    protected function resetRanking(array $poolTeamsPerPoolId)
    {
        foreach ($poolTeamsPerPoolId as $poolTeams) {
            /** @var \Bundle\Asmb\Competition\Entity\Championship\PoolTeam $poolTeam */
            foreach ($poolTeams as $poolTeam) {
                $poolTeam->setPoints(0);
                $poolTeam->setDaysPlayed(0);
                $poolTeam->setMatchDiff(0);
                $poolTeam->setSetDiff(0);
                $poolTeam->setGameDiff(0);
            }
        }
    }
}
