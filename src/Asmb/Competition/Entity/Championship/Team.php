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
     * @var string
     */
    protected $link_fft;

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

    /**
     * @return string
     */
    public function getLinkFft()
    {
        return $this->link_fft;
    }

    /**
     * @param string $linkFft
     */
    public function setLinkFft($linkFft)
    {
        $this->link_fft = $linkFft;
    }
}
