<?php

namespace Bundle\Asmb\Competition\Repository\Championship;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Championship\PoolTeam;

/**
 * Repository des équipes de poules.
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2019
 */
class PoolTeamRepository extends Repository
{
    /**
     * Retourne le nombre d'équipes pour la poule d'id donné.
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
            ->setParameter('poolId', $poolId);

        $result = (int) $qb->execute()->fetchColumn(0);

        return $result;
    }

    /**
     * Retourne les équipes de la poule d'id donné, trié par nom d'équipe.
     *
     * @param integer $poolId
     *
     * @return PoolTeam[]
     */
//    public function findByPoolIdSortedByName($poolId)
//    {
//        $poolTeamsSortedByName = [];
//
//        $poolTeams = $this->findBy(['pool_id' => $poolId], ['name', 'ASC']);
//        if (false !== $poolTeams) {
//            /** @var PoolTeam $poolTeam */
//            foreach ($poolTeams as $poolTeam) {
//                $poolTeamsSortedByName[$poolTeam->getName()] = $poolTeam;
//            }
//        }
//
//        return $poolTeamsSortedByName;
//    }

    /**
     * Retourne les équipes, par id de poule à partir des ids de poules donnés, trié par nom d'équipe FFT.
     *
     * @param array $poolIds
     *
     * @return PoolTeam[]
     */
    public function findByPoolIdsSortedByNameFft(array $poolIds)
    {
        $poolTeamsPerPoolIdSortedByName = [];

        $poolTeams = $this->findBy(['pool_id' => $poolIds], ['name_fft', 'ASC']);
        if (false !== $poolTeams) {
            /** @var PoolTeam $poolTeam */
            foreach ($poolTeams as $poolTeam) {
                $poolTeamsPerPoolIdSortedByName[$poolTeam->getPoolId()][$poolTeam->getNameFft()] = $poolTeam;
            }
        }

        return $poolTeamsPerPoolIdSortedByName;
    }

    /**
     * Retourne les équipes groupées par id de poule à partir des ids de poules donnés, trié par nom d'équipe.
     *
     * @param array $poolIds
     *
     * @return PoolTeam[]
     */
//    public function findByPoolIdsPerPoolIdSortedByName(array $poolIds)
//    {
//        $poolsGroupByPoolIdSortedByName = [];
//
//        $poolTeams = $this->findBy(['pool_id' => $poolIds], ['name', 'ASC']);
//        if (false !== $poolTeams) {
//            /** @var PoolTeam $poolTeam */
//            foreach ($poolTeams as $poolTeam) {
//                $poolsGroupByPoolIdSortedByName[$poolTeam->getPoolId()][$poolTeam->getName()] = $poolTeam;
//            }
//        }
//
//        return $poolsGroupByPoolIdSortedByName;
//    }

    /**
     * Sauvegarde les données sur les équipes des poulées données, à partir des données soumise dans le formulaire
     * correspondant.
     *
     * @param PoolTeam[] $poolTeams
     * @param array $formData
     *
     * @return bool
     */
    public function savePoolsTeamsOfChampionship(array $poolTeams, array $formData)
    {
        foreach ($poolTeams as $poolTeam) {
            $poolTeam->setName($formData["pool_team{$poolTeam->getId()}_name"]);

            // Seules les données valorisées sont soumises pour les checkboxes
            if (isset($formData["pool_team{$poolTeam->getId()}_is_club"])) {
                $poolTeam->setIsClub(true);
            } else {
                $poolTeam->setIsClub(false);
            }

            $this->save($poolTeam);
        }

        return true;
    }
}
