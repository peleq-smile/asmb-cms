<?php

namespace Bundle\Asmb\Competition\Entity;

use Bolt\Storage\Entity\Entity;
use Bundle\Asmb\Competition\Entity\Championship\AbstractShortNamedEntity;

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
}
