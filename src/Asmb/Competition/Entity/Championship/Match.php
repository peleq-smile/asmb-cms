<?php

namespace Bundle\Asmb\Competition\Entity\Championship;

use Bolt\Storage\Entity\Entity;

/**
 * Entity for championship match.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2019
 */
class Match extends Entity
{
    protected $pool_id;
    protected $home_team_id;
    protected $visitor_team_id;
    protected $day;
    protected $date;
    protected $time;
    protected $score_home;
    protected $score_visitor;
    protected $position;

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
    public function getHomeTeamId()
    {
        return $this->home_team_id;
    }

    /**
     * @param mixed $home_team_id
     */
    public function setHomeTeamId($home_team_id)
    {
        $this->home_team_id = $home_team_id;
    }

    /**
     * @return mixed
     */
    public function getVisitorTeamId()
    {
        return $this->visitor_team_id;
    }

    /**
     * @param mixed $visitor_team_id
     */
    public function setVisitorTeamId($visitor_team_id)
    {
        $this->visitor_team_id = $visitor_team_id;
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
    public function getScoreHome()
    {
        return $this->score_home;
    }

    /**
     * @param mixed $score_home
     */
    public function setScoreHome($score_home)
    {
        $this->score_home = $score_home;
    }

    /**
     * @return mixed
     */
    public function getScoreVisitor()
    {
        return $this->score_visitor;
    }

    /**
     * @param mixed $score_visitor
     */
    public function setScoreVisitor($score_visitor)
    {
        $this->score_visitor = $score_visitor;
    }

    /**
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param mixed $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }
}
