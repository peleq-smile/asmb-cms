<?php

namespace Bundle\Asmb\Competition\Entity\Championship;

use Bolt\Storage\Entity\Entity;

/**
 * Entity for championship team.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class Team extends AbstractShortNamedEntity
{
    /**
     * @var string
     */
    protected $category_name;
    /**
     * @var boolean
     */
    protected $is_club;

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->category_name;
    }

    /**
     * @param string $categoryName
     */
    public function setCategoryName($categoryName)
    {
        $this->category_name = $categoryName;
    }

    /**
     * @return bool
     */
    public function isClub()
    {
        return $this->is_club;
    }

    /**
     * @param bool $isClub
     */
    public function setIsClub($isClub)
    {
        $this->is_club = $isClub;
    }
}
