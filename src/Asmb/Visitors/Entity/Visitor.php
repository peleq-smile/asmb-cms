<?php

namespace Bundle\Asmb\Visitors\Entity;

use Bolt\Storage\Entity\Entity;
use Bundle\Asmb\Visitors\Helpers\VisitorHelper;

/**
 * Entité pour un visiteur.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2018
 */
class Visitor extends Entity
{
    /**
     * @var string
     */
    protected $ip;
    /**
     * @var string
     */
    protected $httpUserAgent;
    /**
     * @var \Datetime
     */
    protected $datetime;
    /**
     * @var boolean
     */
    protected $isActive;
    /**
     * @var string
     */
    protected $username;
    /**
     * @var string
     */
    protected $browserName;
    /**
     * @var string
     */
    protected $browserVersion;
    /**
     * @var string
     */
    protected $osName;
    /**
     * @var string
     */
    protected $terminal ;
    /**
     * @var string
     */
    protected $geolocalization;
    /**
     * @var integer
     */
    protected $dailyVisitsCount = 1;

    /**
     * Visitor constructor.
     *
     * @param array $data
     * @throws \Exception
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        // Set to current date time by default
        $this->datetime = new \DateTime();

        $this->browserName = VisitorHelper::$emptyValue;
        $this->browserVersion = VisitorHelper::$emptyValue;
        $this->osName = VisitorHelper::$emptyValue;
        $this->terminal = VisitorHelper::$emptyValue;
        $this->geolocalization = VisitorHelper::$emptyValue;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getHttpUserAgent()
    {
        return $this->httpUserAgent;
    }

    /**
     * @param string $httpUserAgent
     */
    public function setHttpUserAgent($httpUserAgent)
    {
        $this->httpUserAgent = $httpUserAgent;
    }

    /**
     * @return \Datetime
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * @param \Datetime $datetime
     */
    public function setDatetime($datetime)
    {
        $this->datetime = $datetime;
    }

    /**
     * @param boolean $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getBrowserName()
    {
        return $this->browserName;
    }

    /**
     * @param string $browserName
     */
    public function setBrowserName($browserName)
    {
        $this->browserName = $browserName;
    }

    /**
     * @return string
     */
    public function getBrowserVersion()
    {
        return $this->browserVersion;
    }

    /**
     * @param string $browserVersion
     */
    public function setBrowserVersion($browserVersion)
    {
        $this->browserVersion = $browserVersion;
    }
    /**
     * @return string
     */
    public function getOsName()
    {
        return $this->osName;
    }

    /**
     * @param string $osName
     */
    public function setOsName($osName)
    {
        $this->osName = $osName;
    }

    /**
     * @return string
     */
    public function getTerminal()
    {
        return $this->terminal;
    }

    /**
     * @param string $terminal
     */
    public function setTerminal($terminal)
    {
        $this->terminal = $terminal;
    }

    /**
     * @return string
     */
    public function getGeolocalization(): string
    {
        return $this->geolocalization;
    }

    /**
     * @param string $geolocalization
     */
    public function setGeolocalization(string $geolocalization): void
    {
        $this->geolocalization = $geolocalization;
    }

    /**
     * @return int
     */
    public function getDailyVisitsCount(): int
    {
        return $this->dailyVisitsCount;
    }

    /**
     * @param int $dailyVisitsCount
     */
    public function setDailyVisitsCount(int $dailyVisitsCount): void
    {
        $this->dailyVisitsCount = $dailyVisitsCount;
    }

    /**
     * Copie les données du visiteur donné dans le visiteur actuel.
     *
     * @param Visitor $visitor
     */
    public function copyFieldFromVisitor(Visitor $visitor)
    {
        foreach ($this->getFields() as $field) {
            if (! in_array($field, ['id', 'datetime', 'dailyVisitsCount'])) {
                $this->set($field, $visitor->get($field));
            }
        }
    }
}
