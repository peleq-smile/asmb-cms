<?php

namespace Bundle\Asmb\Competition\Entity\Championship;

use Bolt\Storage\Entity\Entity;

/**
 * Entity for championship pool team.
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2019
 */
class PoolTeam extends Entity
{
    /**
     * @var integer
     */
    protected $pool_id;
    /**
     * @var integer
     */
    protected $team_id;
    /**
     * @var string
     */
    protected $team_name;
    /**
     * @var boolean
     */
    protected $team_is_club;
    /**
     * @var integer
     */
    protected $points;
    /**
     * @var integer
     */
    protected $days_played;
    /**
     * @var integer
     */
    protected $match_diff;
    /**
     * @var integer
     */
    protected $set_diff;
    /**
     * @var integer
     */
    protected $game_diff;

    /**
     * @return int
     */
    public function getPoolId()
    {
        return $this->pool_id;
    }

    /**
     * @param int $poolId
     */
    public function setPoolId($poolId)
    {
        $this->pool_id = $poolId;
    }

    /**
     * @return int
     */
    public function getTeamId()
    {
        return $this->team_id;
    }

    /**
     * @param int $teamId
     */
    public function setTeamId($teamId)
    {
        $this->team_id = $teamId;
    }

    /**
     * @return int
     */
    public function getTeamName()
    {
        return $this->team_name;
    }

    /**
     * @param int $teamName
     */
    public function setTeamName($teamName)
    {
        $this->team_name = $teamName;
    }

    /**
     * @return boolean
     */
    public function getTeamIsClub()
    {
        return $this->team_is_club;
    }

    /**
     * @param boolean $teamIsClub
     */
    public function setTeamIsClub($teamIsClub)
    {
        $this->team_is_club = $teamIsClub;
    }

    /**
     * @return int
     */
    public function getPoints()
    {
        return $this->points;
    }

    /**
     * @param int $points
     */
    public function setPoints($points)
    {
        $this->points = $points;
    }

    /**
     * @param int $points
     */
    public function addPoints($points)
    {
        $this->points += $points;
    }

    /**
     * @return int
     */
    public function getDaysPlayed()
    {
        return $this->days_played;
    }

    /**
     * @param int $daysPlayed
     */
    public function setDaysPlayed($daysPlayed)
    {
        $this->days_played = $daysPlayed;
    }

    /**
     * @param int $daysPlayed
     */
    public function addDaysPlayed($daysPlayed = 1)
    {
        $this->days_played += $daysPlayed;
    }

    /**
     * @return int
     */
    public function getMatchDiff()
    {
        return $this->match_diff;
    }

    /**
     * @param int $matchDiff
     */
    public function setMatchDiff($matchDiff)
    {
        $this->match_diff = $matchDiff;
    }

    /**
     * @param int $matchDiff
     */
    public function addMatchDiff($matchDiff)
    {
        $this->match_diff += $matchDiff;
    }

    /**
     * @return int
     */
    public function getSetDiff()
    {
        return $this->set_diff;
    }

    /**
     * @param int $setDiff
     */
    public function setSetDiff($setDiff)
    {
        $this->set_diff = $setDiff;
    }

    /**
     * @param int $setDiff
     */
    public function addSetDiff($setDiff)
    {
        $this->set_diff += $setDiff;
    }

    /**
     * @return int
     */
    public function getGameDiff()
    {
        return $this->game_diff;
    }

    /**
     * @param int $gameDiff
     */
    public function setGameDiff($gameDiff)
    {
        $this->game_diff = $gameDiff;
    }

    /**
     * @param int $gameDiff
     */
    public function addGameDiff($gameDiff)
    {
        $this->game_diff += $gameDiff;
    }
}
