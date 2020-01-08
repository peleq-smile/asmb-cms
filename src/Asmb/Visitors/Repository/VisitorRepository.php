<?php

namespace Bundle\Asmb\Visitors\Repository;

use Bolt\Storage\Repository;
use Bundle\Asmb\Visitors\Entity\Visitor;

/**
 * Repository for visitors.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2018
 */
class VisitorRepository extends Repository
{
    /**
     * Expiration time of a visitor, in seconds.
     *
     * @var int
     */
    static public $expirationTime = 120;

    /**
     * Add or update given visitor into visitors table.
     *
     * @param \Bundle\Asmb\Visitors\Entity\Visitor $visitor
     *
     * @return void
     */
    public function addOrUpdateVisitor(Visitor $visitor)
    {
        $existingVisitor = $this->findOneBy(
            [
                'ip'            => $visitor->getIp(),
                'httpUserAgent' => $visitor->getHttpUserAgent(),
            ]
        );

        if (false === $existingVisitor) {
            // New visitor : insert case
            $visitor->setIsActive(true);
            $this->insert($visitor);
        } else {
            // Already registered visitor : update case
            $existingVisitor->setDatetime($visitor->getDatetime());
            $existingVisitor->setIsActive(true);
            $this->update($existingVisitor);
        }

        $this->cleanExpiredVisitors();
    }

    /**
     * Clean visitors considered as expired.
     *
     * @return void
     */
    protected function cleanExpiredVisitors()
    {
        $expirationDateTime = new \DateTime();
        $expirationDateTime->modify('-' . self::$expirationTime . 'seconds');
        $expirationDateTime = $expirationDateTime->format('Y-m-d H:i:s');

        $query = $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getTableName())
            ->where('datetime < :expirationDate')
            ->setParameter(':expirationDate', $expirationDateTime);

        $query->execute();
    }
}
