<?php

namespace Bundle\Asmb\Competition\Entity\Championship;

use Bolt\Storage\Entity\Entity;
use Carbon\Carbon;

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
    /** @var Carbon */
    protected $date;
    /** @var Carbon */
    protected $report_date;
    /** @var boolean */
    protected $is_reported = 0;
    /** @var Carbon */
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
    /** @var integer */
    protected $championship_id;
    /** @var string */
    protected $championship_name;
    /** @var string */
    protected $championship_short_name;
    /** @var string */
    protected $category_name;
    /** @var string */
    protected $category_identifier;
    /** @var string */
    protected $competition_record_title;
    /** @var string */
    protected $competition_record_slug;
    /** @var Carbon */
    protected $updated_at;

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
     * @return Carbon
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param Carbon $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getReportDate()
    {
        return $this->report_date;
    }

    /**
     * @param mixed $reportDate
     */
    public function setReportDate($reportDate)
    {
        $this->report_date = $reportDate;
    }

    /**
     * @return int
     */
    public function getIsReported()
    {
        return (int) $this->is_reported;
    }

    /**
     * @param int $isReported
     */
    public function setIsReported($isReported)
    {
        $this->is_reported = (int) $isReported;
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
     * @return integer
     */
    public function getChampionshipId()
    {
        return $this->championship_id;
    }

    /**
     * @param integer $championshipId
     */
    public function setChampionshipId($championshipId)
    {
        $this->championship_id = $championshipId;
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

    /**
     * @return string
     */
    public function getChampionshipShortName()
    {
        return $this->championship_short_name;
    }

    /**
     * @param string $championshipShortName
     */
    public function setChampionshipShortName($championshipShortName)
    {
        $this->championship_short_name = $championshipShortName;
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
    public function getCategoryIdentifier()
    {
        return $this->category_identifier;
    }

    /**
     * @param string $categoryIdentifier
     */
    public function setCategoryIdentifier($categoryIdentifier)
    {
        $this->category_identifier = $categoryIdentifier;
    }

    /**
     * @return string
     */
    public function getCompetitionRecordTitle()
    {
        return $this->competition_record_title;
    }

    /**
     * @param string $competitionRecordTitle
     */
    public function setCompetitionRecordTitle($competitionRecordTitle)
    {
        $this->competition_record_title = $competitionRecordTitle;
    }

    /**
     * @return string
     */
    public function getCompetitionRecordSlug()
    {
        return $this->competition_record_slug;
    }

    /**
     * @param string $competitionRecordSlug
     */
    public function setCompetitionRecordSlug($competitionRecordSlug)
    {
        $this->competition_record_slug = $competitionRecordSlug;
    }

    /**
     * @return Carbon
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @param Carbon $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;
    }

    /**
     * @return Carbon
     */
    public function getFinalDate()
    {
        if (null !== $this->report_date) {
            return $this->report_date;
        }

        return $this->date;
    }
}
