<?php

namespace Bundle\Asmb\Competition\Repository\Championship;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Helpers\PoolMeetingHelper;
use Bundle\Asmb\Competition\Helpers\PoolTeamHelper;
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
     * Retourne les rencontres des poules d'ids donnés, groupé par id de poule puis par jour de rencontre.
     * Exemple de tableau retourné :
     * [
     *     10 => [ // Avec 10 = Id de la poule
     *         1 => [ // Avec 1 = jour 1
     *             <Id of match> => entité PoolMeeting,
     *             <Id of match> => entité PoolMeeting,
     *             ...
     *         ],
     *         2 => [ // Avec 2 = day 2
     *             <Id of match> => entité PoolMeeting,
     *             ...
     *         ],
     *         ...
     *     ],
     *     12 => [...] // Avec 12 = Id de la poule
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
            $qb->expr()->andX(
                $qb->expr()->eq($this->getAlias() . '.pool_id', 'pt_home.pool_id'),
                $qb->expr()->eq($this->getAlias() . '.home_team_name_fft', 'pt_home.name_fft')
            )
        );
        // ÉQUIPE VISITEUR
        $qb->addSelect("pt_visitor.name as visitor_team_name");
        $qb->addSelect("pt_visitor.is_club as visitor_team_is_club");
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_championship_pool_team',
            'pt_visitor',
            $qb->expr()->andX(
                $qb->expr()->eq($this->getAlias() . '.pool_id', 'pt_visitor.pool_id'),
                $qb->expr()->eq($this->getAlias() . '.visitor_team_name_fft', 'pt_visitor.name_fft')
            )
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
     * @param int $pastDays
     * @param int $futureDays
     * @param bool $onlyActiveChampionship
     * @param bool $withReportDates
     *
     * @return PoolMeeting[]
     */
    public function findClubMeetingsOfTheMoment(
        int  $pastDays,
        int  $futureDays,
        bool $onlyActiveChampionship = true,
        bool $withReportDates = true
    )
    {
        $clubMeetingsOfTheMoment = [];

        $qb = $this->getLoadQuery();
        $qb->addSelect("{$this->getAlias()}.time as time");
        $qb->addSelect("{$this->getAlias()}.is_reported as isReported");

        // Récupération de l'id + du nom (court et long) du championnat
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_championship_pool',
            'pool',
            $qb->expr()->eq($this->getAlias() . '.pool_id', 'pool.id')
        );
        // Jointure sur la catégorie pour avoir le nom
        $qb->innerJoin(
            'pool',
            'bolt_championship_category',
            'category',
            $qb->expr()->eq('pool.category_identifier', 'category.identifier')
        );
        $qb->addSelect('category.name as category_name');

        // On profite de la jointure sur la Pool pour récupérer la date de dernière mise à jour de chaque poule
        $qb->addSelect('pool.updated_at as updated_at');

        $qb->innerJoin(
            'pool',
            'bolt_championship',
            'championship',
            $qb->expr()->eq('pool.championship_id', 'championship.id')
        );
        $qb->addSelect('championship.id as championship_id');
        $qb->addSelect('championship.name as championship_name');
        $qb->addSelect('championship.short_name as championship_short_name');

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
            $qb->expr()->andX(
                $qb->expr()->eq($this->getAlias() . '.pool_id', 'pt_home.pool_id'),
                $qb->expr()->eq($this->getAlias() . '.home_team_name_fft', 'pt_home.name_fft')
            )
        );

        // ÉQUIPE VISITEUR
        $qb->addSelect("pt_visitor.name as visitor_team_name");
        $qb->addSelect("pt_visitor.is_club as visitor_team_is_club");
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_championship_pool_team',
            'pt_visitor',
            $qb->expr()->andX(
                $qb->expr()->eq($this->getAlias() . '.pool_id', 'pt_visitor.pool_id'),
                $qb->expr()->eq($this->getAlias() . '.visitor_team_name_fft', 'pt_visitor.name_fft')
            )
        );

        $qb->orderBy('final_date');
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
                    $meeting->setChampionshipId((int)$result[$idx]['championship_id']);
                    $meeting->setChampionshipName($result[$idx]['championship_name']);
                    $meeting->setChampionshipShortName($result[$idx]['championship_short_name']);

                    // Ajout à la volée de données sur la Poule concernée
                    $meeting->setCategoryName($result[$idx]['category_name']);

                    // Mise à jour de la date avec la date de report éventuelle
                    if ($withReportDates && null !== $result[$idx]['final_date']) {
                        $date = Carbon::createFromFormat('Y-m-d', $result[$idx]['final_date']);
                        $date->setTime(0, 0);
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
     * @param int $poolId
     */
    public function saveAll(array $poolMeetings, int $poolId)
    {
        foreach ($poolMeetings as $poolMeeting) {
            // Création ou mise à jour ? On vérifie sur l'id de poule + le nom des 2 équipes
            /** @var PoolMeeting $existingPoolRanking */
            $existingPoolMeeting = $this->findOneBy(
                [
                    'pool_id' => $poolId,
                    'home_team_name_fft' => $poolMeeting->getHomeTeamNameFft(),
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
                // Si l'indicateur "reporté" existe, on le conserve
                $poolMeeting->setIsReported($existingPoolMeeting->getIsReported());
            } else {
                $poolMeeting->setIsReported(false);
            }
            $this->save($poolMeeting, true);
        }
    }

    /**
     * @param Carbon $fromDate
     * @param Carbon|null $toDate
     *
     * @return PoolMeeting[]
     */
    public function findHomeMeetingsBetweenDate(Carbon $fromDate, Carbon $toDate)
    {
        $homeMeetings = [];

        $qb = $this->getLoadQuery();

        // Filtre sur les rencontres du club à domicile + récupération du "nom interne"
        $qb->addSelect('team_home.name as team_home_name');
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_championship_pool_team',
            'team_home',
            $qb->expr()->andX(
                $qb->expr()->eq($this->getAlias() . '.pool_id', 'team_home.pool_id'),
                $qb->expr()->eq($this->getAlias() . '.home_team_name_fft', 'team_home.name_fft'),
                $qb->expr()->eq('team_home.is_club', true)
            )
        );

        // Récupération du nom interne de l'équipe extérieure
        $qb->addSelect('team_visitor.name as team_visitor_name');
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_championship_pool_team',
            'team_visitor',
            $qb->expr()->andX(
                $qb->expr()->eq($this->getAlias() . '.pool_id', 'team_visitor.pool_id'),
                $qb->expr()->eq($this->getAlias() . '.visitor_team_name_fft', 'team_visitor.name_fft')
            )
        );

        // Prise en compte de la date de report en priorité, sinon la date définie dans la GS
        $qb->addSelect("IFNULL({$this->getAlias()}.report_date, {$this->getAlias()}.date) as final_date");

        $qb->having($qb->expr()->gte('final_date', ':fromDate'));
        $qb->setParameter('fromDate', $fromDate);

        $qb->andHaving($qb->expr()->lte('final_date', ':toDate'));
        $qb->setParameter('toDate', $toDate);

        $qb->orderBy('final_date');
        $qb->addOrderBy('time');

        $result = $qb->execute()->fetchAll();

        if ($result) {
            $homeMeetings = $this->hydrateAll($result, $qb);
            /** @var PoolMeeting $homeMeeting */
            foreach ($homeMeetings as $idx => $homeMeeting) {
                if ($homeMeeting->getIsReported() && null === $homeMeeting->getReportDate()) {
                    // on ignore les rencontres reportées dont on ignore la date !
                    unset($homeMeetings[$idx]);
                    continue;
                }
                $homeMeeting->setHomeTeamIsClub(true);
                $homeMeeting->setHomeTeamName($result[$idx]['team_home_name']);
                $homeMeeting->setVisitorTeamName($result[$idx]['team_visitor_name']);
            }
        }

        return $homeMeetings;
    }

    /**
     * Retourne les rencontres de poules concernant l'équipe du club, pour la poule d'id donné.
     *
     * @return PoolMeeting[]
     */
    public function findClubMeetingsOfPool(int $poolId)
    {
        $clubMeetings = [];

        $qb = $this->getLoadQuery();

        // Récupération des rencontres du club à domicile + récupération du "nom interne"
        $qb->addSelect('team_home.name as team_home_name');
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_championship_pool_team',
            'team_home',
            $qb->expr()->andX(
                $qb->expr()->eq($this->getAlias() . '.pool_id', 'team_home.pool_id'),
                $qb->expr()->eq($this->getAlias() . '.home_team_name_fft', 'team_home.name_fft')
            )
        );

        // Récupération des rencontres du club à l'extérieur + récupération du "nom interne"
        $qb->addSelect('team_visitor.name as team_visitor_name');
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_championship_pool_team',
            'team_visitor',
            $qb->expr()->andX(
                $qb->expr()->eq($this->getAlias() . '.pool_id', 'team_visitor.pool_id'),
                $qb->expr()->eq($this->getAlias() . '.visitor_team_name_fft', 'team_visitor.name_fft')
            )
        );

        // On exclut les rencontres avec les "fausses équipes"
        $exemptTeamPrefix = PoolTeamHelper::EXEMPT_TEAM_PREFIX;
        $qb->andWhere($this->getAlias() . ".home_team_name_fft NOT LIKE '$exemptTeamPrefix%'");
        $qb->andWhere($this->getAlias() . ".visitor_team_name_fft NOT LIKE '$exemptTeamPrefix%'");

        // Filtre sur la poule donnée
        $qb->andWhere($qb->expr()->eq($this->getAlias() . '.pool_id', $poolId));

        // Filtre sur l'équipe domicile ou extérieure : l'une des 2 doit être du club
        $qb->andWhere("(team_home.is_club = :isClub OR team_visitor.is_club = :isClub)");
        $qb->setParameter(':isClub', true);

        // Prise en compte de la date de report en priorité, sinon la date définie dans la GS
        $qb->addSelect("IFNULL({$this->getAlias()}.report_date, {$this->getAlias()}.date) as final_date");

        // On garde la donnée comme quoi l'équipe du club est à domicile ou à l'extérieur
        $qb->addSelect("IF(team_home.is_club, 1, 0) as team_home_is_club");

        $qb->orderBy('final_date');
        $qb->addOrderBy('time');

        $result = $qb->execute()->fetchAll();

        if ($result) {
            $clubMeetings = $this->hydrateAll($result, $qb);
            /** @var PoolMeeting $clubMeeting */
            foreach ($clubMeetings as $idx => $clubMeeting) {
                if ($result[$idx]['team_home_is_club']) {
                    $clubMeeting->setHomeTeamIsClub(true);
                    $clubMeeting->setVisitorTeamIsClub(false);
                    $clubMeeting->setHomeTeamName($result[$idx]['team_home_name']);
                } else {
                    $clubMeeting->setVisitorTeamIsClub(true);
                    $clubMeeting->setHomeTeamIsClub(false);
                    $clubMeeting->setVisitorTeamName($result[$idx]['team_visitor_name']);
                }
            }
        }

        return $clubMeetings;
    }
}
