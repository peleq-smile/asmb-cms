<?php

namespace Bundle\Asmb\Competition\Entity;

use Bolt\Storage\Entity\Entity;
use Bundle\Asmb\Competition\Entity\AbstractShortNamedEntity;

/**
 * Entity for championship.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class Championship extends AbstractShortNamedEntity
{
    /**
     * @var int
     */
    protected $year;
    /**
     * @var boolean
     */
    protected $is_active;

    /**
     * @return int
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @param int $year
     */
    public function setYear($year)
    {
        $this->year = $year;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * @param boolean $isActive
     */
    public function setIsActive($isActive)
    {
        $this->is_active = $isActive;
    }
}
