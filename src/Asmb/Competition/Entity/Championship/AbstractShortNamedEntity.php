<?php

namespace Bundle\Asmb\Competition\Entity\Championship;

use Bolt\Storage\Entity\Entity;

/**
 * Entity for championship.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class AbstractShortNamedEntity extends Entity
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $short_name;

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
     * @return string
     */
    public function getShortName()
    {
        return $this->short_name;
    }

    /**
     * @param string $shortName
     */
    public function setShortName($shortName)
    {
        $this->short_name = $shortName;
    }

    /**
     * Get final name to display.
     *
     * @return string
     */
    public function getFinalName()
    {
        $finalName = $this->getShortName();

        if (empty($finalName)) {
            $finalName = $this->getName();
        }

        return $finalName;
    }
}
