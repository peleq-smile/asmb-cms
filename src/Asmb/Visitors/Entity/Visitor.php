<?php

namespace Bundle\Asmb\Visitors\Entity;

use Bolt\Storage\Entity\Entity;

/**
 * Entity for visitors.
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
     * Visitor constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        // Set to current date time by default
        $this->datetime = new \DateTime();
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
}
