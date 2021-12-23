<?php

namespace Bundle\Asmb\Competition\Entity\Championship;

use Bolt\Storage\Entity\Entity;

/**
 * Entity for championship.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class Category extends Entity
{
    /**
     * @var string
     */
    protected $identifier;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var integer
     */
    protected $position;

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
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
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }
}
