<?php

namespace Bundle\Asmb\Competition\Entity\Championship;

use Bolt\Storage\Entity\Entity;

/**
 * Entity for championship pool day.
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2019
 */
class PoolDay extends Entity
{
    protected $pool_id;
    protected $day;
    protected $date;
    protected $time;

    /**
     * @return mixed
     */
    public function getPoolId()
    {
        return $this->pool_id;
    }

    /**
     * @param mixed $pool_id
     */
    public function setPoolId($pool_id)
    {
        $this->pool_id = $pool_id;
    }

    /**
     * @return mixed
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * @param mixed $day
     */
    public function setDay($day)
    {
        $this->day = $day;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param mixed $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }
}
