<?php

namespace Bundle\Asmb\Competition\Repository\Championship;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Championship\PoolRanking;

/**
 * Repository de l'entité PoolRaking, pour le classement des équipes dans une poule.
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2019
 */
class PoolRankingRepository extends Repository
{
    /**
     * Retourne les classements des équipes, par id de poule à partir des ids de poules donnés, trié selon leur
     * classement.
     *
     * @param array $poolIds
     *
     * @return PoolRanking[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function findByPoolIdsSortedRanking(array $poolIds)
    {
        $poolRankingsPerPoolId = [];

        $qb = $this->findWithCriteria(['pool_id' => $poolIds]);
        $qb->orderBy('points', 'DESC');
        $qb->addOrderBy('match_diff', 'DESC');
        $qb->addOrderBy('set_diff', 'DESC');
        $qb->addOrderBy('game_diff', 'DESC');
        $qb->addOrderBy('days_played', 'ASC');

        // On veut le nom de l'équipe donné en interne (donc dans la table des PoolTeam)
        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolRankingRepository $poolTeamRepository */
        $poolTeamRepository = $this->getEntityManager()->getRepository('championship_pool_team');
        $poolTeamAlias = $poolTeamRepository->getAlias();

        $qb->addSelect("$poolTeamAlias.name as team_name");
        $qb->innerJoin(
            $this->getAlias(),
            'bolt_championship_pool_team',
            $poolTeamAlias,
            $qb->expr()->eq($this->getAlias() . '.pool_id', "$poolTeamAlias.pool_id")
            . ' AND ' . $qb->expr()->eq($this->getAlias() . '.team_name_fft', "$poolTeamAlias.name_fft")
        );

        $result = $qb->execute()->fetchAll();

        if ($result) {
            $poolRankings = $this->hydrateAll($result, $qb);

            /** @var PoolRanking $poolRanking */
            foreach ($poolRankings as $idx => $poolRanking) {
                // Ajout à la volée du nom interne de l'équipe
                $poolRanking->setTeamName($result[$idx]['team_name']);
                $poolRankingsPerPoolId[$poolRanking->getPoolId()][] = $poolRanking;
            }
        }

        return $poolRankingsPerPoolId;
    }
}
