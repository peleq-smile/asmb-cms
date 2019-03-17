<?php

namespace Bundle\Asmb\Competition\Entity\Championship;

use Bolt\Storage\Entity\Entity;

/**
 * Entity for championship pool.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class Pool extends AbstractShortNamedEntity
{
    /**
     * @var string
     */
    protected $championship_id;
    /**
     * @var string
     */
    protected $category_name;
    /**
     * @var string
     */
    protected $link_fft;
    /**
     * @var integer
     */
    protected $position;
    /**
     * @var integer
     */
    private $completeness;

    /**
     * @return string
     */
    public function getChampionshipId()
    {
        return $this->championship_id;
    }

    /**
     * @param string $championshipId
     */
    public function setChampionshipId($championshipId)
    {
        $this->championship_id = $championshipId;
    }

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
     * @return string
     */
    public function getLinkFft()
    {
        return $this->link_fft;
    }

    /**
     * @param $linkFft
     */
    public function setLinkFft($linkFft)
    {
        $this->link_fft = $linkFft;
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

    /**
     * @return int|null
     */
    public function getCompleteness()
    {
        return $this->completeness;
    }

    /**
     * @param $completeness
     */
    public function setCompleteness($completeness)
    {
        $this->completeness = $completeness;
    }
}
