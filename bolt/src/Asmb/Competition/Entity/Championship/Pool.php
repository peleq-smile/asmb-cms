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
    protected $category_identifier;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $championship_fft_id;
    /**
     * @var string
     */
    protected $division_fft_id;
    /**
     * @var string
     */
    protected $fft_id;
    /**
     * @var Carbon
     */
    protected $updated_at;
    /**
     * @var string
     */
    protected $calendar_color;

    /**
     * @var integer
     */
    private $completeness;

    public function getChampionshipId(): ?string
    {
        return $this->championship_id;
    }

    public function setChampionshipId(?string $championshipId)
    {
        $this->championship_id = $championshipId;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position)
    {
        $this->position = $position;
    }

    public function getCategoryIdentifier(): ?string
    {
        return $this->category_identifier;
    }

    public function setCategoryIdentifier(?string $categoryIdentifier)
    {
        $this->category_identifier = $categoryIdentifier;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getDivisionFftId(): ?string
    {
        return $this->division_fft_id;
    }

    public function setDivisionFftId(?string $divisionFftId)
    {
        $this->division_fft_id = $divisionFftId;
    }

    public function getChampionshipFftId(): ?string
    {
        return $this->championship_fft_id;
    }

    public function setChampionshipFftId(?string $championshipFftId)
    {
        $this->championship_fft_id = $championshipFftId;
    }

    public function getFftId(): ?string
    {
        return $this->fft_id;
    }

    public function setFftId(?string $fftId)
    {
        $this->fft_id = $fftId;
    }

    public function getCompleteness(): ?int
    {
        return $this->completeness;
    }

    public function setCompleteness(?int $completeness)
    {
        $this->completeness = $completeness;
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?Carbon $updateAt = null)
    {
        if (null === $updateAt) {
            $updateAt = new Carbon();
        }

        $this->updated_at = $updateAt;
    }

    public function getCalendarColor(): ?string
    {
        return $this->calendar_color;
    }

    public function setCalendarColor(?string $calendarColor)
    {
        $this->calendar_color = $calendarColor;
    }
}
