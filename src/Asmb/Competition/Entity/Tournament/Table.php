<?php

namespace Bundle\Asmb\Competition\Entity\Tournament;

use Bundle\Asmb\Competition\Entity\AbstractShortNamedEntity;
use Carbon\Carbon;

/**
 * Entité représentant un tableau de tournoi.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2020
 */
class Table extends AbstractShortNamedEntity
{
    const STATUS_NEW = 0;
    const STATUS_PENDING = 1;
    const STATUS_COMPLETE = 2;

    static public $categories = [
        'D' => 'Dames',
        'M' => 'Messieurs',
        'G' => 'Garçons',
        'F' => 'Filles',
    ];

    /**
     * @var integer
     */
    protected $tournament_id;
    /**
     * @var string
     */
    protected $category;
    /**
     * @var integer
     */
    protected $status;
    /**
     * @var boolean
     */
    protected $visible;
    /**
     * @var Carbon
     */
    protected $updated_at;
    /**
     * @var integer Id d'utilisateur
     */
    protected $updated_by;
    /**
     * @var integer
     */
    protected $previous_table_id;
    /**
     * @var integer
     */
    private $nb_round;
    /**
     * @var Table
     */
    private $previous_table;
    /**
     * @var Table
     */
    private $next_table;

    /**
     * Table constructor.
     */
    public function __construct()
    {
        // Catégorie par défault : Messieurs (le + courant)
        $this->setCategory('M');
        $this->setUpdatedAt();
        $this->setStatus(self::STATUS_NEW);
    }

    /**
     * @return integer
     */
    public function getTournamentId()
    {
        return $this->tournament_id;
    }

    /**
     * @param integer $tournamentId
     */
    public function setTournamentId($tournamentId)
    {
        $this->tournament_id = $tournamentId;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status)
    {
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @param bool $visible
     */
    public function setVisible(bool $visible)
    {
        $this->visible = $visible;
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

    /**
     * Retourne un ID d'utilisateur.
     *
     * @return integer
     */
    public function getUpdatedBy()
    {
        return $this->updated_by;
    }

    /**
     * @param integer $updatedBy
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updated_by = $updatedBy;
    }

    /**
     * @return int
     */
    public function getPreviousTableId()
    {
        return $this->previous_table_id;
    }

    /**
     * @param int $previousTableId
     */
    public function setPreviousTableId($previousTableId)
    {
        $this->previous_table_id = $previousTableId;
    }

    /**
     * @return int
     */
    public function getNbRound()
    {
        return $this->nb_round;
    }

    /**
     * @param int $nbRound
     */
    public function setNbRound(int $nbRound)
    {
        $this->nb_round = $nbRound;
    }

    /**
     * @return Table
     */
    public function getPreviousTable()
    {
        return $this->previous_table;
    }

    /**
     * @param Table $previousTable
     */
    public function setPreviousTable(Table $previousTable)
    {
        $this->previous_table = $previousTable;
    }

    /**
     * @return Table
     */
    public function getNextTable()
    {
        return $this->next_table;
    }

    /**
     * @param Table $nextTable
     */
    public function setNextTable(Table $nextTable)
    {
        $this->next_table = $nextTable;
    }

    /**
     * @return string
     */
    public function getCategoryLabel()
    {
        $categoryLabel = '';
        if (isset(self::$categories[$this->category])) {
            $categoryLabel = self::$categories[$this->category];
        }
        return $categoryLabel;
    }
}
