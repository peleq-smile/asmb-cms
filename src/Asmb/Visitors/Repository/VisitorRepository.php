<?php

namespace Bundle\Asmb\Visitors\Repository;

use Bolt\Storage\Repository;
use Bundle\Asmb\Visitors\Entity\Visitor;
use Bundle\Asmb\Visitors\Helpers\VisitorHelper;
use Carbon\Carbon;

/**
 * Repository for visitors.
 *
 * @author    Perrine Léquipé <perrine.lequipe@gmail.com>
 * @copyright 2018
 */
class VisitorRepository extends Repository
{
    /**
     * Add or update given visitor into visitors table.
     *
     * @param Visitor $visitor
     *
     * @return void
     * @throws \Exception
     */
    public function addOrUpdateVisitor(Visitor $visitor)
    {
        $existingVisitor = false;

        /** @var Visitor $existingVisitor */
        // On récupère d'abord l'utilisateur par son nom (si connecté)
        if (null !== $visitor->getUsername()) {
            $existingVisitor = $this->findOneBy(
                [
                    'username' => $visitor->getUsername(),
                ]
            );
        }
        // Ou sinon par IP et support utilisé
        if (false === $existingVisitor) {
            $existingVisitor = $this->findOneBy(
                [
                    'ip'             => $visitor->getIp(),
                    'browserName'    => $visitor->getBrowserName(),
                    'browserVersion' => $visitor->getBrowserVersion(),
                    'osName'         => $visitor->getOsName(),
                    'terminal'       => $visitor->getTerminal()
                ]
            );
        }

        if (false === $existingVisitor) {
            // New visitor : insert case
            $visitor->setIsActive(true);
            $this->insert($visitor);
        } else {
            // Already registered visitor : update case
            $existingVisitor->copyFieldFromVisitor($visitor);
            $existingVisitor->setUsername($visitor->getUsername());
            $existingVisitor->setIp($visitor->getIp());
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
     * @throws \Exception
     */
    protected function cleanExpiredVisitors()
    {
        $expirationDateTime = new \DateTime();
        $expirationDateTime->modify('-' . VisitorHelper::$expirationTime . 'minutes');
        $expirationDateTime = $expirationDateTime->format('Y-m-d H:i:s');

        //        $query = $this->getEntityManager()->createQueryBuilder()
        //            ->delete($this->getTableName())
        //            ->where('datetime < :expirationDate')
        //            ->setParameter(':expirationDate', $expirationDateTime);

        $query = $this->getEntityManager()->createQueryBuilder()
            ->update($this->getTableName())
            ->set('isActive', ':isActive')
            ->where('datetime < :expirationDate')
            ->setParameter(':expirationDate', $expirationDateTime)
            ->setParameter(':isActive', 0);

        $query->execute();
    }

    /**
     * Retourne le nombre de visiteurs actifs.
     *
     * @return int
     */
    public function countCurrentVisitors()
    {
        $visitors = $this->findBy(['isActive' => 1]);
        $count = (false !== $visitors) ? count($visitors) : 1;

        return $count;
    }

    public function findYesterdayVisitorsCount()
    {
        $yesterdayStart = Carbon::now()->modify('-1day')
            ->setTime(0, 0)
            ->format(Carbon::DEFAULT_TO_STRING_FORMAT);;
        $yesterdayEnd = Carbon::now()->modify('-1day')
            ->setTime(23, 59, 59)
            ->format(Carbon::DEFAULT_TO_STRING_FORMAT);;

        // TODO à supprimer
        $yesterdayStart = Carbon::now()->setTime(0, 0)
            ->format(Carbon::DEFAULT_TO_STRING_FORMAT);
        $yesterdayEnd = Carbon::now()->setTime(23, 59, 59)
            ->format(Carbon::DEFAULT_TO_STRING_FORMAT);

        $qb = $this->getLoadQuery()
            ->select('COUNT(id)')
            ->where('datetime >= :yesterdayStart')
            ->where('datetime <= :yesterdayEnd')
            ->setParameter(':yesterday', $yesterdayStart)
            ->setParameter(':yesterdayEnd', $yesterdayEnd);

        $result = $qb->execute()->fetchColumn(0);

        return (int)$result;
    }
}
