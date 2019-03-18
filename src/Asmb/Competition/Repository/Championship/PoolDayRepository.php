<?php

namespace Bundle\Asmb\Competition\Repository\Championship;

use Bolt\Storage\Repository;
use Bundle\Asmb\Competition\Entity\Championship\PoolDay;

/**
 * Repository for competition pool day.
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2019
 */
class PoolDayRepository extends Repository
{
    /**
     * Return date per day for given pool id.
     *
     * @param integer $poolId
     *
     * @return array
     */
    public function findDateByPoolId($poolId)
    {
        $daysByPoolId = [];
        /** @var PoolDay $poolDay */
        foreach ($this->findByPoolId($poolId) as $dayKey => $poolDay) {
            $daysByPoolId[$dayKey] = $poolDay->getDate();
        }

        return $daysByPoolId;
    }

    /**
     * Return PoolDay per day for given pool id.
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
}
