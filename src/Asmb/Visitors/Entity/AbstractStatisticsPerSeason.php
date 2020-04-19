<?php
namespace Bundle\Asmb\Visitors\Entity;

use Bolt\Storage\Entity\Entity;
use Carbon\Carbon;

abstract class AbstractStatisticsPerSeason extends Entity
{
    /**
     * @var string
     */
    protected $season;

    protected $dayOfMonth01 = 0;
    protected $dayOfMonth02 = 0;
    protected $dayOfMonth03 = 0;
    protected $dayOfMonth04 = 0;
    protected $dayOfMonth05 = 0;
    protected $dayOfMonth06 = 0;
    protected $dayOfMonth07 = 0;
    protected $dayOfMonth08 = 0;
    protected $dayOfMonth09 = 0;
    protected $dayOfMonth10 = 0;
    protected $dayOfMonth11 = 0;
    protected $dayOfMonth12 = 0;
    protected $dayOfMonth13 = 0;
    protected $dayOfMonth14 = 0;
    protected $dayOfMonth15 = 0;
    protected $dayOfMonth16 = 0;
    protected $dayOfMonth17 = 0;
    protected $dayOfMonth18 = 0;
    protected $dayOfMonth19 = 0;
    protected $dayOfMonth20 = 0;
    protected $dayOfMonth21 = 0;
    protected $dayOfMonth22 = 0;
    protected $dayOfMonth23 = 0;
    protected $dayOfMonth24 = 0;
    protected $dayOfMonth25 = 0;
    protected $dayOfMonth26 = 0;
    protected $dayOfMonth27 = 0;
    protected $dayOfMonth28 = 0;
    protected $dayOfMonth29 = 0;
    protected $dayOfMonth30 = 0;
    protected $dayOfMonth31 = 0;
    protected $month09 = 0;
    protected $month10 = 0;
    protected $month11 = 0;
    protected $month12 = 0;
    protected $month01 = 0;
    protected $month02 = 0;
    protected $month03 = 0;
    protected $month04 = 0;
    protected $month05 = 0;
    protected $month06 = 0;
    protected $month07 = 0;
    protected $month08 = 0;
    /**
     * @var Carbon
     */
    protected $updated_at;

    /**
     * @return mixed
     */
    public function getSeason()
    {
        return $this->season;
    }

    /**
     * @param mixed $season
     */
    public function setSeason($season): void
    {
        $this->season = $season;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth01()
    {
        return $this->dayOfMonth01;
    }

    /**
     * @param mixed $dayOfMonth01
     */
    public function setDayOfMonth01($dayOfMonth01): void
    {
        $this->dayOfMonth01 = $dayOfMonth01;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth02()
    {
        return $this->dayOfMonth02;
    }

    /**
     * @param mixed $dayOfMonth02
     */
    public function setDayOfMonth02($dayOfMonth02): void
    {
        $this->dayOfMonth02 = $dayOfMonth02;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth03()
    {
        return $this->dayOfMonth03;
    }

    /**
     * @param mixed $dayOfMonth03
     */
    public function setDayOfMonth03($dayOfMonth03): void
    {
        $this->dayOfMonth03 = $dayOfMonth03;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth04()
    {
        return $this->dayOfMonth04;
    }

    /**
     * @param mixed $dayOfMonth04
     */
    public function setDayOfMonth04($dayOfMonth04): void
    {
        $this->dayOfMonth04 = $dayOfMonth04;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth05()
    {
        return $this->dayOfMonth05;
    }

    /**
     * @param mixed $dayOfMonth05
     */
    public function setDayOfMonth05($dayOfMonth05): void
    {
        $this->dayOfMonth05 = $dayOfMonth05;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth06()
    {
        return $this->dayOfMonth06;
    }

    /**
     * @param mixed $dayOfMonth06
     */
    public function setDayOfMonth06($dayOfMonth06): void
    {
        $this->dayOfMonth06 = $dayOfMonth06;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth07()
    {
        return $this->dayOfMonth07;
    }

    /**
     * @param mixed $dayOfMonth07
     */
    public function setDayOfMonth07($dayOfMonth07): void
    {
        $this->dayOfMonth07 = $dayOfMonth07;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth08()
    {
        return $this->dayOfMonth08;
    }

    /**
     * @param mixed $dayOfMonth08
     */
    public function setDayOfMonth08($dayOfMonth08): void
    {
        $this->dayOfMonth08 = $dayOfMonth08;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth09()
    {
        return $this->dayOfMonth09;
    }

    /**
     * @param mixed $dayOfMonth09
     */
    public function setDayOfMonth09($dayOfMonth09): void
    {
        $this->dayOfMonth09 = $dayOfMonth09;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth10()
    {
        return $this->dayOfMonth10;
    }

    /**
     * @param mixed $dayOfMonth10
     */
    public function setDayOfMonth10($dayOfMonth10): void
    {
        $this->dayOfMonth10 = $dayOfMonth10;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth11()
    {
        return $this->dayOfMonth11;
    }

    /**
     * @param mixed $dayOfMonth11
     */
    public function setDayOfMonth11($dayOfMonth11): void
    {
        $this->dayOfMonth11 = $dayOfMonth11;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth12()
    {
        return $this->dayOfMonth12;
    }

    /**
     * @param mixed $dayOfMonth12
     */
    public function setDayOfMonth12($dayOfMonth12): void
    {
        $this->dayOfMonth12 = $dayOfMonth12;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth13()
    {
        return $this->dayOfMonth13;
    }

    /**
     * @param mixed $dayOfMonth13
     */
    public function setDayOfMonth13($dayOfMonth13): void
    {
        $this->dayOfMonth13 = $dayOfMonth13;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth14()
    {
        return $this->dayOfMonth14;
    }

    /**
     * @param mixed $dayOfMonth14
     */
    public function setDayOfMonth14($dayOfMonth14): void
    {
        $this->dayOfMonth14 = $dayOfMonth14;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth15()
    {
        return $this->dayOfMonth15;
    }

    /**
     * @param mixed $dayOfMonth15
     */
    public function setDayOfMonth15($dayOfMonth15): void
    {
        $this->dayOfMonth15 = $dayOfMonth15;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth16()
    {
        return $this->dayOfMonth16;
    }

    /**
     * @param mixed $dayOfMonth16
     */
    public function setDayOfMonth16($dayOfMonth16): void
    {
        $this->dayOfMonth16 = $dayOfMonth16;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth17()
    {
        return $this->dayOfMonth17;
    }

    /**
     * @param mixed $dayOfMonth17
     */
    public function setDayOfMonth17($dayOfMonth17): void
    {
        $this->dayOfMonth17 = $dayOfMonth17;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth18()
    {
        return $this->dayOfMonth18;
    }

    /**
     * @param mixed $dayOfMonth18
     */
    public function setDayOfMonth18($dayOfMonth18): void
    {
        $this->dayOfMonth18 = $dayOfMonth18;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth19()
    {
        return $this->dayOfMonth19;
    }

    /**
     * @param mixed $dayOfMonth19
     */
    public function setDayOfMonth19($dayOfMonth19): void
    {
        $this->dayOfMonth19 = $dayOfMonth19;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth20()
    {
        return $this->dayOfMonth20;
    }

    /**
     * @param mixed $dayOfMonth20
     */
    public function setDayOfMonth20($dayOfMonth20): void
    {
        $this->dayOfMonth20 = $dayOfMonth20;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth21()
    {
        return $this->dayOfMonth21;
    }

    /**
     * @param mixed $dayOfMonth21
     */
    public function setDayOfMonth21($dayOfMonth21): void
    {
        $this->dayOfMonth21 = $dayOfMonth21;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth22()
    {
        return $this->dayOfMonth22;
    }

    /**
     * @param mixed $dayOfMonth22
     */
    public function setDayOfMonth22($dayOfMonth22): void
    {
        $this->dayOfMonth22 = $dayOfMonth22;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth23()
    {
        return $this->dayOfMonth23;
    }

    /**
     * @param mixed $dayOfMonth23
     */
    public function setDayOfMonth23($dayOfMonth23): void
    {
        $this->dayOfMonth23 = $dayOfMonth23;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth24()
    {
        return $this->dayOfMonth24;
    }

    /**
     * @param mixed $dayOfMonth24
     */
    public function setDayOfMonth24($dayOfMonth24): void
    {
        $this->dayOfMonth24 = $dayOfMonth24;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth25()
    {
        return $this->dayOfMonth25;
    }

    /**
     * @param mixed $dayOfMonth25
     */
    public function setDayOfMonth25($dayOfMonth25): void
    {
        $this->dayOfMonth25 = $dayOfMonth25;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth26()
    {
        return $this->dayOfMonth26;
    }

    /**
     * @param mixed $dayOfMonth26
     */
    public function setDayOfMonth26($dayOfMonth26): void
    {
        $this->dayOfMonth26 = $dayOfMonth26;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth27()
    {
        return $this->dayOfMonth27;
    }

    /**
     * @param mixed $dayOfMonth27
     */
    public function setDayOfMonth27($dayOfMonth27): void
    {
        $this->dayOfMonth27 = $dayOfMonth27;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth28()
    {
        return $this->dayOfMonth28;
    }

    /**
     * @param mixed $dayOfMonth28
     */
    public function setDayOfMonth28($dayOfMonth28): void
    {
        $this->dayOfMonth28 = $dayOfMonth28;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth29()
    {
        return $this->dayOfMonth29;
    }

    /**
     * @param mixed $dayOfMonth29
     */
    public function setDayOfMonth29($dayOfMonth29): void
    {
        $this->dayOfMonth29 = $dayOfMonth29;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth30()
    {
        return $this->dayOfMonth30;
    }

    /**
     * @param mixed $dayOfMonth30
     */
    public function setDayOfMonth30($dayOfMonth30): void
    {
        $this->dayOfMonth30 = $dayOfMonth30;
    }

    /**
     * @return mixed
     */
    public function getDayOfMonth31()
    {
        return $this->dayOfMonth31;
    }

    /**
     * @param mixed $dayOfMonth31
     */
    public function setDayOfMonth31($dayOfMonth31): void
    {
        $this->dayOfMonth31 = $dayOfMonth31;
    }

    /**
     * @return mixed
     */
    public function getMonth09()
    {
        return $this->month09;
    }

    /**
     * @param mixed $month09
     */
    public function setMonth09($month09): void
    {
        $this->month09 = $month09;
    }

    /**
     * @return mixed
     */
    public function getMonth10()
    {
        return $this->month10;
    }

    /**
     * @param mixed $month10
     */
    public function setMonth10($month10): void
    {
        $this->month10 = $month10;
    }

    /**
     * @return mixed
     */
    public function getMonth11()
    {
        return $this->month11;
    }

    /**
     * @param mixed $month11
     */
    public function setMonth11($month11): void
    {
        $this->month11 = $month11;
    }

    /**
     * @return mixed
     */
    public function getMonth12()
    {
        return $this->month12;
    }

    /**
     * @param mixed $month12
     */
    public function setMonth12($month12): void
    {
        $this->month12 = $month12;
    }

    /**
     * @return mixed
     */
    public function getMonth01()
    {
        return $this->month01;
    }

    /**
     * @param mixed $month01
     */
    public function setMonth01($month01): void
    {
        $this->month01 = $month01;
    }

    /**
     * @return mixed
     */
    public function getMonth02()
    {
        return $this->month02;
    }

    /**
     * @param mixed $month02
     */
    public function setMonth02($month02): void
    {
        $this->month02 = $month02;
    }

    /**
     * @return mixed
     */
    public function getMonth03()
    {
        return $this->month03;
    }

    /**
     * @param mixed $month03
     */
    public function setMonth03($month03): void
    {
        $this->month03 = $month03;
    }

    /**
     * @return mixed
     */
    public function getMonth04()
    {
        return $this->month04;
    }

    /**
     * @param mixed $month04
     */
    public function setMonth04($month04): void
    {
        $this->month04 = $month04;
    }

    /**
     * @return mixed
     */
    public function getMonth05()
    {
        return $this->month05;
    }

    /**
     * @param mixed $month05
     */
    public function setMonth05($month05): void
    {
        $this->month05 = $month05;
    }

    /**
     * @return mixed
     */
    public function getMonth06()
    {
        return $this->month06;
    }

    /**
     * @param mixed $month06
     */
    public function setMonth06($month06): void
    {
        $this->month06 = $month06;
    }

    /**
     * @return mixed
     */
    public function getMonth07()
    {
        return $this->month07;
    }

    /**
     * @param mixed $month07
     */
    public function setMonth07($month07): void
    {
        $this->month07 = $month07;
    }

    /**
     * @return mixed
     */
    public function getMonth08()
    {
        return $this->month08;
    }

    /**
     * @param mixed $month08
     */
    public function setMonth08($month08): void
    {
        $this->month08 = $month08;
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
}