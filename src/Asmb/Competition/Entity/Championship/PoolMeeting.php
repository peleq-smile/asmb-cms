<?php

namespace Bundle\Asmb\Competition\Entity\Championship;

use Bolt\Storage\Entity\Entity;

/**
 * Entité pour représenter une rencontre entre 2 équipes.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class PoolMeeting extends Entity
{
    /** @var int */
    protected $pool_id;
    /** @var string */
    protected $home_team_name_fft;
    /** @var string */
    protected $visitor_team_name_fft;
    /** @var int */
    protected $day;
    /** @var \DateTime */
    protected $date;
    /** @var boolean */
    protected $is_reported = false;
    /** @var \DateTime */
    protected $time;
    /** @var string */
    protected $result;
    /** @var string */
    protected $club_flag;
    /** @var array */
    protected $params_fdm_fft = [];

    // Following properties are not stored in DB, but used as simple object data.
    /** @var string */
    protected $home_team_name;
    /** @var boolean */
    protected $home_team_is_club;
    /** @var string */
    protected $visitor_team_name;
    /** @var boolean */
    protected $visitor_team_is_club;
    /** @var string */
    protected $championship_name;

    /**
     * @return mixed
     */
    public function getPoolId()
    {
        return $this->pool_id;
    }

    /**
     * @param mixed $pool_id
     */
    public function setPoolId($pool_id)
    {
        $this->pool_id = $pool_id;
    }

    /**
     * @return mixed
     */
    public function getHomeTeamNameFft()
    {
        return $this->home_team_name_fft;
    }

    /**
     * @param mixed $homeTeamNameFft
     */
    public function setHomeTeamNameFft($homeTeamNameFft)
    {
        $this->home_team_name_fft = $homeTeamNameFft;
    }

    /**
     * @return mixed
     */
    public function getVisitorTeamNameFft()
    {
        return $this->visitor_team_name_fft;
    }

    /**
     * @param mixed $visitorTeamNameFft
     */
    public function setVisitorTeamNameFft($visitorTeamNameFft)
    {
        $this->visitor_team_name_fft = $visitorTeamNameFft;
    }

    /**
     * @return mixed
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * @param mixed $day
     */
    public function setDay($day)
    {
        $this->day = $day;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return bool
     */
    public function isReported()
    {
        return $this->is_reported;
    }

    /**
     * @param bool $isReported
     */
    public function setIsReported($isReported)
    {
        $this->is_reported = $isReported;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param mixed $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @return mixed
     */
    public function getClubFlag()
    {
        return $this->club_flag;
    }

    /**
     * @param mixed $clubFlag
     */
    public function setClubFlag($clubFlag)
    {
        $this->club_flag = $clubFlag;
    }

    /**
     * @return array
     */
    public function getParamsFdmFft()
    {
        return $this->params_fdm_fft;
    }

    /**
     * @param array $paramsFdmFft
     */
    public function setParamsFdmFft(array $paramsFdmFft)
    {
        $this->params_fdm_fft = $paramsFdmFft;
    }

    /**
     * @return string
     */
    public function getHomeTeamName()
    {
        return $this->home_team_name;
    }

    /**
     * @param string $homeTeamName
     */
    public function setHomeTeamName($homeTeamName)
    {
        $this->home_team_name = $homeTeamName;
    }

    /**
     * @return bool
     */
    public function getHomeTeamIsClub()
    {
        return $this->home_team_is_club;
    }

    /**
     * @param bool $homeTeamIsClub
     */
    public function setHomeTeamIsClub($homeTeamIsClub)
    {
        $this->home_team_is_club = (bool) $homeTeamIsClub;
    }

    /**
     * @return string
     */
    public function getVisitorTeamName()
    {
        return $this->visitor_team_name;
    }

    /**
     * @param string $visitorTeamName
     */
    public function setVisitorTeamName($visitorTeamName)
    {
        $this->visitor_team_name = $visitorTeamName;
    }

    /**
     * @return bool
     */
    public function getVisitorTeamIsClub()
    {
        return $this->visitor_team_is_club;
    }

    /**
     * @param bool $visitorTeamIsClub
     */
    public function setVisitorTeamIsClub($visitorTeamIsClub)
    {
        $this->visitor_team_is_club = (bool) $visitorTeamIsClub;
    }

    /**
     * @return string
     */
    public function getChampionshipName()
    {
        return $this->championship_name;
    }

    /**
     * @param string $championshipName
     */
    public function setChampionshipName($championshipName)
    {
        $this->championship_name = $championshipName;
    }
}
