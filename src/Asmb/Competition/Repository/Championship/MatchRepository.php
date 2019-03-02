<?php

namespace Bundle\Asmb\Competition\Repository\Championship;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Championship\Match;
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
     *     'pool_1' => [ // With 1 = Id of pool
     *         'day_1' => [
     *             <Id of match> => Match entity instance,
     *             <Id of match> => Match entity instance,
     *             ...
     *         ],
     *         'day_2' => [
     *             <Id of match> => Match entity instance,
     *             ...
     *         ],
     *         ...
     *     ],
     *     'pool_2' => [...] // With 2 = Id of pool
     * ]
     *
     * @param array $poolIds
     *
     * @return array
     */
    public function findGroupByPoolIdAndDay(array $poolIds)
    {
        $groupedMatches = [];

        $qb = $this->findWithCriteria(['pool_id' => $poolIds]);
        $qb->orderBy('day', 'ASC');
        $qb->orderBy('position', 'ASC');
        $result = $qb->execute()->fetchAll();

        if ($result) {
            $matches = $this->hydrateAll($result, $qb);
            /** @var Match $match */
            foreach ($matches as $match) {
                $groupedMatches['pool_' . $match->getPoolId()]['day_' . $match->getDay()][$match->getId()] = $match;
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
}
