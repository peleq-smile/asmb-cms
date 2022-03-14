<?php

namespace Bundle\Asmb\Competition\Entity\Championship;

use Bolt\Storage\Entity\Entity;
use Carbon\Carbon;

/**
 * Entité pour représenter un match d'une rencontre entre 2 équipes.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2022
 */
class PoolMeetingMatch extends Entity
{
    const TYPE_SIMPLE = 'S';
    const TYPE_DOUBLE = 'D';

    /** @var int */
    protected $pool_meeting_id;

    /** @var string */
    protected $type;

    /** @var string */
    protected $label;

    /** @var int */
    protected $position;

    /** @var string */
    protected $home_player_name;

    /** @var string */
    protected $home_player_rank;

    /** @var string */
    protected $visitor_player_name;

    /** @var string */
    protected $visitor_player_rank;

    /** @var string */
    protected $home_player2_name;

    /** @var string */
    protected $visitor_player2_name;

    /** @var string */
    protected $home_player2_rank;

    /** @var string */
    protected $visitor_player2_rank;

    /** @var string */
    protected $score;

    /** @var Carbon */
    protected $created_at;

    /** @var Carbon */
    protected $updated_at;

    /**
     * @return int
     */
    public function getPoolMeetingId()
    {
        return $this->pool_meeting_id;
    }

    /**
     * @param mixed $pool_meeting_id
     */
    public function setPoolMeetingId($pool_meeting_id)
    {
        $this->pool_meeting_id = $pool_meeting_id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return string
     */
    public function getScore(): ?string
    {
        return $this->score;
    }

    /**
     * @param string|null $score
     */
    public function setScore(?string $score): void
    {
        $this->score = $score;
    }

    /**
     * @return Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->created_at;
    }

    /**
     * @param Carbon $created_at
     */
    public function setCreatedAt(Carbon $created_at): void
    {
        $this->created_at = $created_at;
    }

    /**
     * @return string
     */
    public function getHomePlayerName(): string
    {
        return $this->home_player_name;
    }

    /**
     * @param string $home_player_name
     */
    public function setHomePlayerName(string $home_player_name): void
    {
        $this->home_player_name = $home_player_name;
    }

    /**
     * @return string
     */
    public function getHomePlayerRank(): string
    {
        return $this->home_player_rank;
    }

    /**
     * @param string $home_player_rank
     */
    public function setHomePlayerRank(string $home_player_rank): void
    {
        $this->home_player_rank = $home_player_rank;
    }

    /**
     * @return string
     */
    public function getVisitorPlayerName(): string
    {
        return $this->visitor_player_name;
    }

    /**
     * @param string $visitor_player_name
     */
    public function setVisitorPlayerName(string $visitor_player_name): void
    {
        $this->visitor_player_name = $visitor_player_name;
    }

    /**
     * @return string
     */
    public function getVisitorPlayerRank(): string
    {
        return $this->visitor_player_rank;
    }

    /**
     * @param string $visitor_player_rank
     */
    public function setVisitorPlayerRank(string $visitor_player_rank): void
    {
        $this->visitor_player_rank = $visitor_player_rank;
    }

    /**
     * @return string
     */
    public function getHomePlayer2Name(): ?string
    {
        return $this->home_player2_name;
    }

    /**
     * @param string $home_player2_name
     */
    public function setHomePlayer2Name(?string $home_player2_name): void
    {
        $this->home_player2_name = $home_player2_name;
    }

    /**
     * @return string
     */
    public function getVisitorPlayer2Name(): ?string
    {
        return $this->visitor_player2_name;
    }

    /**
     * @param string $visitor_player2_name
     */
    public function setVisitorPlayer2Name(?string $visitor_player2_name): void
    {
        $this->visitor_player2_name = $visitor_player2_name;
    }

    /**
     * @return string
     */
    public function getHomePlayer2Rank(): ?string
    {
        return $this->home_player2_rank;
    }

    /**
     * @param string $home_player2_rank
     */
    public function setHomePlayer2Rank(?string $home_player2_rank): void
    {
        $this->home_player2_rank = $home_player2_rank;
    }

    /**
     * @return string
     */
    public function getVisitorPlayer2Rank(): ?string
    {
        return $this->visitor_player2_rank;
    }

    /**
     * @param string $visitor_player2_rank
     */
    public function setVisitorPlayer2Rank(?string $visitor_player2_rank): void
    {
        $this->visitor_player2_rank = $visitor_player2_rank;
    }

    /**
     * @return Carbon
     */
    public function getUpdatedAt(): Carbon
    {
        return $this->updated_at;
    }

    /**
     * @param Carbon $updated_at
     */
    public function setUpdatedAt(Carbon $updated_at): void
    {
        $this->updated_at = $updated_at;
    }

    public function isSimple(): bool
    {
        return $this->getType() === self::TYPE_SIMPLE;
    }

    public function isDouble(): bool
    {
        return $this->getType() === self::TYPE_DOUBLE;
    }
}
