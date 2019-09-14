<?php

namespace Bundle\Asmb\Competition\Repository\Championship;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Helpers\PoolMeetingHelper;
use Carbon\Carbon;

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

    /**
     * Récupère et retourne les rencontres du moment concernant le club, en filtrant sur $pastDays jours en arrière
     * et $futureDays en avant.
     *
     * @param int  $pastDays
     * @param int  $futureDays
     * @param bool $onlyActiveChampionship
     * @param bool $withReportDates
     *
     * @return PoolMeeting[]
     */
    public function findClubMeetingsOfTheMoment(
        $pastDays,
        $futureDays,
        $onlyActiveChampionship = true,
        $withReportDates = true
    ) {
        $clubMeetingsOfTheMoment = [];

        $qb = $this->getLoadQuery();

        // Récupération de l'id + du nom (court et long) du championnat
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_championship_pool',
            'pool',
            $qb->expr()->eq($this->getAlias() . '.pool_id', 'pool.id')
        );
        $qb->addSelect('championship.id as championship_id');
        $qb->addSelect('championship.name as championship_name');
        $qb->addSelect('championship.short_name as championship_short_name');

        $qb->innerJoin(
            'pool',
            'bolt_championship',
            'championship',
            $qb->expr()->eq('pool.championship_id', 'championship.id')
        );
        // On profite de la jointure sur la Pool pour récupérer la date de dernière mise à jour de chaque poule
        $qb->addSelect('pool.updated_at as updated_at');

        if ($onlyActiveChampionship) {
            // Filtre sur les poules des championnats ACTIFS uniquement
            $qb->where('championship.is_active = true');
        }

        // Filtre sur la date des rencontres (éventuellement en prenant en compte les dates de report)
        if ($withReportDates) {
            $qb->addSelect("IFNULL({$this->getAlias()}.report_date, {$this->getAlias()}.date) as final_date");
        } else {
            $qb->addSelect("{$this->getAlias()}.date as final_date");
        }
        $qb->having("final_date >= (CURDATE() - INTERVAL $pastDays DAY)");
        $qb->andHaving("final_date <= (CURDATE() + INTERVAL $futureDays DAY)");

        // Si $pastDays est >0, alors on veut les derniers résultats donc des rencontres avec le score.
        if ($pastDays > 0) {
            $qb->andHaving("{$this->getAlias()}.result IS NOT NULL OR final_date <> CURDATE()");
            $qb->andHaving("{$this->getAlias()}.result <> :noneResult OR final_date <> CURDATE()");
            $qb->setParameter(':noneResult', PoolMeetingHelper::RESULT_NONE);
        }
        if ($futureDays > 0) {
            $qb->andWhere("({$this->getAlias()}.result IS NULL OR {$this->getAlias()}.result = :noneResult)");
            $qb->setParameter(':noneResult', PoolMeetingHelper::RESULT_NONE);
        }

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

        $qb->orderBy('championship_name');
        $qb->addOrderBy('final_date');
        $qb->addOrderBy('time');

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

                if ($meeting->getHomeTeamIsClub() || $meeting->getVisitorTeamIsClub()) {
                    // Ajout à la volée de données sur le Championnat concerné
                    $meeting->setChampionshipId((int) $result[$idx]['championship_id']);
                    $meeting->setChampionshipName($result[$idx]['championship_name']);
                    $meeting->setChampionshipShortName($result[$idx]['championship_short_name']);

                    // Mise à jour de la date avec la date de report éventuelle
                    if ($withReportDates && null !== $result[$idx]['final_date']) {
                        $date = Carbon::createFromFormat('Y-m-d', $result[$idx]['final_date']);
                        $date->setTime(0, 0, 0);
                        $meeting->setDate($date);
                    }

                    // Mise à jour de la date de dernière mise à jour à partir de cette de la poule
                    $updatedAt = $result[$idx]['updated_at'];
                    if ($updatedAt) {
                        $meeting->setUpdatedAt(Carbon::createFromFormat('Y-m-d H:i:s', $updatedAt));
                    }

                    // On ne veut que les rencontres qui concernent le club
                    $clubMeetingsOfTheMoment[] = $meeting;
                }
            }
        }

        return $clubMeetingsOfTheMoment;
    }

    /**
     * Sauvegarde les rencontres données en paramètre, pour la poule d'id donné.
     *
     * @param PoolMeeting[] $poolMeetings
     * @param int           $poolId
     */
    public function saveAll(array $poolMeetings, $poolId)
    {
        foreach ($poolMeetings as $poolMeeting) {
            // Création ou mise à jour ? On vérifie sur l'id de poule + le nom des 2 équipes
            /** @var PoolMeeting $existingPoolRanking */
            $existingPoolMeeting = $this->findOneBy(
                [
                    'pool_id'               => $poolId,
                    'home_team_name_fft'    => $poolMeeting->getHomeTeamNameFft(),
                    'visitor_team_name_fft' => $poolMeeting->getVisitorTeamNameFft(),
                ]
            );

            if (false !== $existingPoolMeeting) {
                // Mise à jour : on spécifie l'id pour se mettre en mode "update"
                $poolMeeting->setId($existingPoolMeeting->getId());
                // Si une heure existe, on la conserve
                $poolMeeting->setTime($existingPoolMeeting->getTime());
                // Si une date de report existe, on la conserve
                $poolMeeting->setReportDate($existingPoolMeeting->getReportDate());
            }
            $this->save($poolMeeting, true);
        }
    }
}
