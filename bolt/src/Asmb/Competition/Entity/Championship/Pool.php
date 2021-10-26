<?php

namespace Bundle\Asmb\Competition\Entity\Championship;

use Bolt\Storage\Entity\Entity;
use Carbon\Carbon;

/**
 * Entity for championship pool.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class Pool extends Entity
{
    /**
     * @var string
     */
    protected $championship_id;
    /**
     * @var integer
     */
    protected $position;
    /**
     * @var string
     */
    protected $category_name;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $fft_id;
    /**
     * @var Carbon
     */
    protected $updated_at;

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
    public function getFftId()
    {
        return $this->fft_id;
    }

    /**
     * @param $linkFft
     */
    public function setFftId($linkFft)
    {
        $this->fft_id = $linkFft;
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

    /**
     * @return string
     * @deprecated
     * @todo déporter en fonction twig
     */
    public function getLinkFft()
    {
        return "http://www.gs.applipub-fft.fr/fftfr/pouleClassement.do?dispatch=load&pou_iid={$this->getFftId()}";
    }

    /**
     * @return Carbon
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @param Carbon|null $updateAt
     *
     * @return void
     */
    public function setUpdatedAt($updateAt = null)
    {
        if (null === $updateAt) {
            $updateAt = new Carbon();
        }

        $this->updated_at = $updateAt;
    }
}
