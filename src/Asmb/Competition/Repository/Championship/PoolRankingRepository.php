<?php

namespace Bundle\Asmb\Competition\Repository\Championship;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Championship\PoolRanking;
use Bundle\Asmb\Competition\Exception\PoolTeamNotFoundException;

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

    /**
     * Sauvegarde les données de classement passées en paramètre, pour la poule d'id donné.
     *
     * @param PoolRanking[] $poolRankings
     * @param int           $poolId
     *
     * @throws \Bolt\Exception\InvalidRepositoryException
     * @throws \Bundle\Asmb\Competition\Exception\PoolTeamNotFoundException
     */
    public function saveAll(array $poolRankings, $poolId)
    {
        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolTeamRepository $poolTeamRepository */
        $poolTeamRepository = $this->getEntityManager()->getRepository('championship_pool_team');

        foreach ($poolRankings as $poolRanking) {
            // On récupère le nom interne de l'équipe à partir du nom FFT
            /** @var \Bundle\Asmb\Competition\Entity\Championship\PoolTeam $poolTeam */
            $poolTeam = $poolTeamRepository->findOneBy(
                ['pool_id' => $poolId, 'name_fft' => $poolRanking->getTeamNameFft()]
            );

            if (!$poolTeam) {
                throw new PoolTeamNotFoundException(
                    sprintf(
                        'Aucune équipe trouvée avec le nom FFT %s, pour la poule d\'ID %d',
                        $poolRanking->getTeamNameFft(),
                        $poolId
                    )
                );
            }

            $poolRanking->setTeamIsClub($poolTeam->isClub());

            // Création ou mise à jour ?
            /** @var PoolRanking $existingPoolRanking */
            $existingPoolRanking = $this->findOneBy(
                [
                    'pool_id'       => $poolId,
                    'team_name_fft' => $poolRanking->getTeamNameFft(),
                ]
            );

            if (false !== $existingPoolRanking) {
                // Mise à jour : on spécifie l'id pour se mettre en mode "update"
                $poolRanking->setId($existingPoolRanking->getId());
            }
            $this->save($poolRanking, true);
        }
    }
}
