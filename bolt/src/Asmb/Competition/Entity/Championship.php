<?php

namespace Bundle\Asmb\Competition\Entity;

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
     * @var string
     */
    protected $fft_id;

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year)
    {
        $this->year = $year;
    }

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(?bool $isActive)
    {
        $this->is_active = $isActive;
    }

    public function getFftId(): ?string
    {
        return $this->fft_id;
    }

    public function setFftId(?string $linkFft)
    {
        $this->fft_id = $linkFft;
    }
}
