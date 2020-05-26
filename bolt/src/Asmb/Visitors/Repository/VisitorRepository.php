<?php

namespace Bundle\Asmb\Visitors\Repository;

use Bolt\Storage\Repository;
use Bundle\Asmb\Visitors\Entity\Visitor;
use Bundle\Asmb\Visitors\Helpers\VisitorHelper;
use Carbon\Carbon;
use DateTime;

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
            // Nouveau visiteur : insertion
            $visitor->setIsActive(true);
            $this->insert($visitor);
        } else {
            // On commence par mettre à jour le nombre de visites du jour
            $existingVisitorDatetime = Carbon::createFromDate($existingVisitor->getDatetime());
            $diffInDaysWithLastActive = Carbon::today()->dayOfWeek - $existingVisitorDatetime->dayOfWeek;
            if ($diffInDaysWithLastActive >= 1) {
                // La dernière visite date d'avant auj, on remet le compteur à 1 pour la journée
                $existingVisitor->setDailyVisitsCount(1);
            } else {
                $diffInMinutesWithLastActive = Carbon::now()->diffInMinutes($existingVisitor->getDatetime());
                if ($diffInMinutesWithLastActive > VisitorHelper::$expirationTime) {
                    $existingVisitor->setDailyVisitsCount($existingVisitor->getDailyVisitsCount() + 1);
                }
            }

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
        $expirationDateTime = new DateTime();
        $expirationDateTime->modify('-' . VisitorHelper::$expirationTime . 'minutes');
        $expirationDateTime = $expirationDateTime->format('Y-m-d H:i:s');

        // TODO: supprimer toutes les entrées qui datent d'avant le 1er jour de la saison en cours
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

        return (false !== $visitors) ? count($visitors) : 1;
    }

    public function findYesterdayVisitorsCount()
    {
        return $this->findVisitorsCountBetweenDates(Carbon::yesterday(), Carbon::yesterday());
    }

    /**
     * Retourne le nombre de visites d'hier.
     *
     * @return int
     */
    public function findYesterdayVisitsCount()
    {
        $startDate = Carbon::yesterday()->setTime(0, 0);
        $endDate = Carbon::yesterday()->setTime(23, 59, 59, 9999);

        $qb = $this->getLoadQuery()
            ->select('SUM(dailyVisitsCount)')
            ->where('datetime >= :startDate')
            ->andWhere('datetime <= :endDate')
            ->setParameter(':startDate', $startDate->format(Carbon::DEFAULT_TO_STRING_FORMAT))
            ->setParameter(':endDate', $endDate->format(Carbon::DEFAULT_TO_STRING_FORMAT));

        $result = $qb->execute()->fetchColumn(0);

        return (int)$result;
    }

    /**
     * Retourne le nombre de visiteurs du mois dernier.
     *
     *
     * @return int
     */
    public function findLastMonthVisitorsCount()
    {
        $month = (Carbon::now()->month) - 1;
        $year = ($month === 12) ? Carbon::now()->year - 1 : Carbon::now()->year;
        $firstDayOfMonth = Carbon::createFromDate($year, $month, 1);
        $lastDayOfMonth = Carbon::createFromDate($year, $month+1, 1)->modify('-1 day');

        return $this->findVisitorsCountBetweenDates($firstDayOfMonth, $lastDayOfMonth);
    }

    /**
     * Retourne le nombre de visiteurs entre les 2 dates données.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     *
     * @return int
     */
    protected function findVisitorsCountBetweenDates(Carbon $startDate, Carbon $endDate)
    {
        $startDate->setTime(0, 0);
        $endDate->setTime(23, 59, 59, 9999);

        $qb = $this->getLoadQuery()
            ->select('COUNT(id)')
            ->where('datetime >= :startDate')
            ->andWhere('datetime <= :endDate')
            ->setParameter(':startDate', $startDate->format(Carbon::DEFAULT_TO_STRING_FORMAT))
            ->setParameter(':endDate', $endDate->format(Carbon::DEFAULT_TO_STRING_FORMAT));

        $result = $qb->execute()->fetchColumn(0);

        return (int)$result;
    }
}
