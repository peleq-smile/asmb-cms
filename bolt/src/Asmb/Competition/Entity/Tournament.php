<?php

namespace Bundle\Asmb\Competition\Entity;

use Bundle\Asmb\Competition\Entity\Tournament\Table;
use Carbon\Carbon;

/**
 * Entité représentant un tournoi.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2020
 */
class Tournament extends AbstractShortNamedEntity
{
    /**
     * @var int
     */
    protected $year;
    /**
     * @var \Datetime
     */
    protected $from_date;
    /**
     * @var \Datetime
     */
    protected $to_date;
    /**
     * @var Table[]
     */
    private $tables;
    /**
     * @var boolean
     */
    protected $is_internal;
    /**
     * @var boolean
     */
    protected $display_times;

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
        $now = Carbon::now();
        return ($this->getFromDate() <= $now && $now <= $this->getToDate());
    }

    /**
     * @return \Datetime
     */
    public function getFromDate()
    {
        return $this->from_date;
    }

    /**
     * @param \Datetime $from_date
     */
    public function setFromDate(\Datetime $from_date)
    {
        $this->from_date = $from_date;
    }

    /**
     * @return Carbon|null
     */
    public function getToDate(): ?\DateTime
    {
        return $this->to_date;
    }

    /**
     * @param \Datetime $to_date
     */
    public function setToDate(\Datetime $to_date): void
    {
        $this->to_date = $to_date;
    }

    /**
     * @return Table[]
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * @param Table[] $tables
     */
    public function setTables($tables)
    {
        $this->tables = $tables;
    }

    /**
     * @return boolean
     */
    public function isInternal()
    {
        return $this->is_internal;
    }

    public function setIsInternal($isInternal)
    {
        $this->is_internal = $isInternal;
    }

    /**
     * @return boolean
     */
    public function getDisplayTimes()
    {
        return $this->display_times;
    }

    public function setDisplayTimes($displayTimes)
    {
        $this->display_times = $displayTimes;
    }
}
