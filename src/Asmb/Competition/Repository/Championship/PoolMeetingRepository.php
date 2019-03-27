<?php

namespace Bundle\Asmb\Competition\Repository\Championship;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Helpers\MatchHelper;

/**
 * Repository pour les rencontres entre équipes.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class PoolMeetingRepository extends Repository
{
    /**
     * Return meetings of given pool id, grouped by day then by position.
     * Sample of returned array :
     * [
     *     10 => [ // With 10 = Id of pool
     *         1 => [ // With 1 = day 1
     *             <Id of match> => PoolMeeting entity instance,
     *             <Id of match> => PoolMeeting entity instance,
     *             ...
     *         ],
     *         2 => [ // With 2 = day 2
     *             <Id of match> => PoolMeeting entity instance,
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
        $groupedMeetings = [];

        $qb = $this->findWithCriteria(['pool_id' => $poolIds]);
        $qb->orderBy('day', 'ASC');

        // On veut le nom des équipes donné en interne (donc dans la table des PoolTeam) + savoir si c'est une
        // équipe du club
        // ÉQUIPE DOMICILE
        $qb->addSelect("pt_home.name as home_team_name");
        $qb->addSelect("pt_home.is_club as home_team_is_club");
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_championship_pool_team',
            'pt_home',
            $qb->expr()->eq($this->getAlias() . '.pool_id', 'pt_home.pool_id')
            . ' AND ' . $qb->expr()->eq($this->getAlias() . '.home_team_name_fft', 'pt_home.name_fft')
        );
        // ÉQUIPE VISITEUR
        $qb->addSelect("pt_visitor.name as visitor_team_name");
        $qb->addSelect("pt_visitor.is_club as visitor_team_is_club");
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_championship_pool_team',
            'pt_visitor',
            $qb->expr()->eq($this->getAlias() . '.pool_id', 'pt_visitor.pool_id')
            . ' AND ' . $qb->expr()->eq($this->getAlias() . '.visitor_team_name_fft', 'pt_visitor.name_fft')
        );

        $result = $qb->execute()->fetchAll();

        if ($result) {
            $meetings = $this->hydrateAll($result, $qb);

            /** @var PoolMeeting $meeting */
            foreach ($meetings as $idx => $meeting) {
                // Ajout à la volée du nom interne des équipes + indicateur "fait partie du club"
                $meeting->setHomeTeamName($result[$idx]['home_team_name']);
                $meeting->setHomeTeamIsClub($result[$idx]['home_team_is_club']);
                $meeting->setVisitorTeamName($result[$idx]['visitor_team_name']);
                $meeting->setVisitorTeamIsClub($result[$idx]['visitor_team_is_club']);

                $groupedMeetings[$meeting->getPoolId()][$meeting->getDay()][$meeting->getId()] = $meeting;
            }
        }

        foreach ($poolIds as $poolId) {
            if (!isset($groupedMeetings[$poolId])) {
                $groupedMeetings[$poolId] = [];
            }
        }

        return $groupedMeetings;
    }
}
