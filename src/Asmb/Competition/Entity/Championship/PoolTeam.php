<?php

namespace Bundle\Asmb\Competition\Entity\Championship;

use Bolt\Storage\Entity\Entity;

/**
 * Entité représentant une équipe d'une poule.
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2019
 */
class PoolTeam extends Entity
{
    /**
     * @var integer
     */
    protected $pool_id;
    /**
     * @var string
     */
    protected $name_fft;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var boolean
     */
    protected $is_club;

    /**
     * @return int
     */
    public function getPoolId()
    {
        return $this->pool_id;
    }

    /**
     * @param int $pool_id
     */
    public function setPoolId($pool_id)
    {
        $this->pool_id = $pool_id;
    }

    /**
     * @return string
     */
    public function getNameFft()
    {
        return $this->name_fft;
    }

    /**
     * @param string $name_fft
     */
    public function setNameFft($name_fft)
    {
        $this->name_fft = $name_fft;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isClub()
    {
        return $this->is_club;
    }

    /**
     * @param bool $is_club
     */
    public function setIsClub($is_club)
    {
        $this->is_club = $is_club;
    }
}
