<?php

namespace Bundle\Asmb\Competition\Entity\Tournament;

use Bolt\Storage\Entity\Entity;
use Carbon\Carbon;

/**
 * Entité représentant une "boîte" dans un tableau de tournoi.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2020
 */
class Box extends Entity
{
    /**
     * @var integer
     */
    protected $table_id;
    /**
     * @var Carbon
     */
    protected $date;
    /**
     * @var Carbon
     */
    protected $time;
    /**
     * @var string
     */
    protected $score;
    /**
     * @var string
     */
    protected $player_name;
    /**
     * @var string
     */
    protected $player_rank;
    /**
     * @var string
     */
    protected $player_club;
    /**
     * @var integer
     */
    protected $box_top_id;
    /**
     * @var integer
     */
    protected $box_btm_id;
    /**
     * @var integer
     */
    protected $qualif_in;
    /**
     * @var integer
     */
    protected $qualif_out;
    /**
     * @var Box
     */
    private $boxTop;
    /**
     * @var Box
     */
    private $boxBtm;
    /**
     * @var string
     */
    private $tableName;
    /**
     * @var string
     */
    private $tournamentName;

    /**
     * @return integer
     */
    public function getTableId()
    {
        return $this->table_id;
    }

    /**
     * @param integer $tableId
     */
    public function setTableId($tableId)
    {
        $this->table_id = $tableId;
    }

    /**
     * @return \DateTime|null
     */
    public function getDatetime()
    {
        $datetime = null;
        if (null !== $this->getDate()) {
            $datetime = $this->getDate();
            if (null !== $this->getTime()) {
                $datetime->setTime(
                    $this->getTime()->format('H'),
                    $this->getTime()->format('i')
                );
            }
        }
        return $datetime;
    }

    /**
     * @return \DateTime
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
     * @return \DateTime
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
     * @return string
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param string $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }

    /**
     * @return string
     */
    public function getPlayerName()
    {
        return $this->player_name;
    }

    /**
     * @param string $playerName
     */
    public function setPlayerName($playerName)
    {
        $this->player_name = $playerName;
    }

    /**
     * @return string
     */
    public function getPlayerRank()
    {
        return $this->player_rank;
    }

    /**
     * @param string $playerRank
     */
    public function setPlayerRank($playerRank)
    {
        $this->player_rank = $playerRank;
    }

    /**
     * @return string
     */
    public function getPlayerClub()
    {
        return $this->player_club;
    }

    /**
     * @param string $playerClub
     */
    public function setPlayerClub($playerClub)
    {
        $this->player_club = $playerClub;
    }

    /**
     * @return int
     */
    public function getBoxTopId()
    {
        return $this->box_top_id;
    }

    /**
     * @param int $boxTopId
     */
    public function setBoxTopId($boxTopId)
    {
        $this->box_top_id = $boxTopId;
    }

    /**
     * @return int
     */
    public function getBoxBtmId()
    {
        return $this->box_btm_id;
    }

    /**
     * @param int $boxBtmId
     */
    public function setBoxBtmId($boxBtmId)
    {
        $this->box_btm_id = $boxBtmId;
    }

    /**
     * @return int
     */
    public function getQualifIn()
    {
        return $this->qualif_in;
    }

    /**
     * @param int $qualifIn
     */
    public function setQualifIn($qualifIn)
    {
        $this->qualif_in = $qualifIn;
    }

    /**
     * @return int
     */
    public function getQualifOut()
    {
        return $this->qualif_out;
    }

    /**
     * @param int $qualifOut
     */
    public function setQualifOut($qualifOut)
    {
        $this->qualif_out = $qualifOut;
    }

    /**
     * @return Box
     */
    public function getBoxTop()
    {
        return $this->boxTop;
    }

    /**
     * @param Box $boxTop
     */
    public function setBoxTop($boxTop)
    {
        $this->boxTop = $boxTop;
    }

    /**
     * @return Box
     */
    public function getBoxBtm()
    {
        return $this->boxBtm;
    }

    /**
     * @param Box $boxBtm
     */
    public function setBoxBtm($boxBtm)
    {
        $this->boxBtm = $boxBtm;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName(string $tableName)
    {
        $this->tableName = $tableName;
    }
    /**
     * @return string
     */
    public function getTournamentName()
    {
        return $this->tournamentName;
    }

    /**
     * @param string $tournamentName
     */
    public function setTournamentName(string $tournamentName)
    {
        $this->tournamentName = $tournamentName;
    }
}
