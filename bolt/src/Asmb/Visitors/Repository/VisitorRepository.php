<?php

namespace Bundle\Asmb\Visitors\Repository;

use Bolt\Storage\Repository;
use Bundle\Asmb\Visitors\Entity\Visitor;
use Bundle\Asmb\Visitors\Helpers\VisitorHelper;
use Carbon\Carbon;
use DateTime;
use Doctrine\DBAL\Query\QueryBuilder;
use Exception as ExceptionAlias;

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
     * @throws ExceptionAlias
     */
    public function addOrUpdateVisitor(Visitor $visitor)
    {
        /** @var Visitor $existingVisitor */
        // On récupère les données visiteurs correspondantes si elles existent déjà
        $existingVisitor = $this->findOneBy(
            [
                'ip' => $visitor->getIp(),
                'browserName' => $visitor->getBrowserName(),
                'osName' => $visitor->getOsName(),
                'terminal' => $visitor->getTerminal()
            ]
        );

        if (false === $existingVisitor) {
            // Nouveau visiteur : insertion
            $visitor->setIsActive(true);
            $this->insert($visitor);
        } else {
            // On commence par mettre à jour le nombre de visites du jour
            if ($this->isLastVisitBeforeToday($existingVisitor)) {
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

    protected function isLastVisitBeforeToday(Visitor $visitor)
    {
        /** @var Carbon $visitorDatetime */
        $visitorDatetime = $visitor->getDatetime();
        $today = Carbon::today();

        $is = true;
        if ($visitorDatetime->year === $today->year
            && $visitorDatetime->month === $today->month
            && $visitorDatetime->day === $today->day
        ) {
            $is = false;
        }
        return $is;
    }

    /**
     * Clean visitors considered as expired.
     *
     * @return void
     */
    protected function cleanExpiredVisitors()
    {
        $expirationDateTime = new DateTime();
        $expirationDateTime->modify('-' . VisitorHelper::$expirationTime . 'minutes');
        $expirationDateTime = $expirationDateTime->format('Y-m-d H:i:s');

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

    public function findDayVisitorsCount(Carbon $dayDate)
    {
        return $this->findVisitorsCountBetweenDates($dayDate->copy(), $dayDate->copy());
    }

    /**
     * Retourne le nombre de visites d'hier.
     *
     * @param Carbon $dayDate
     * @return int
     */
    public function findDayVisitsCount(Carbon $dayDate)
    {
        $startDate = $dayDate->copy()->setTime(0, 0);
        $endDate = $dayDate->copy()->setTime(23, 59, 59, 9999);

        $qb = $this->getLoadQuery()
            ->select('SUM(dailyVisitsCount)')
            ->where('datetime >= :startDate')
            ->andWhere('datetime <= :endDate')
            ->setParameter(':startDate', $startDate->format(Carbon::DEFAULT_TO_STRING_FORMAT))
            ->setParameter(':endDate', $endDate->format(Carbon::DEFAULT_TO_STRING_FORMAT));

        $this->addBotExclusionWhereCondition($qb);

        $result = $qb->execute()->fetchColumn(0);

        return (int)$result;
    }

    /**
     * Retourne le nombre de visiteurs du mois dernier.
     *
     * @param int|null $month
     * @return int
     */
    public function findMonthVisitorsCount(?int $month): int
    {
        $month = $month ?? (Carbon::now()->month) - 1;

        $year = ($month === 12) ? Carbon::now()->year - 1 : Carbon::now()->year;
        $firstDayOfMonth = Carbon::createFromDate($year, $month, 1);
        $lastDayOfMonth = Carbon::createFromDate($year, $month + 1, 1)->modify('-1 day');

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

        $this->addBotExclusionWhereCondition($qb);

        $result = $qb->execute()->fetchColumn(0);

        return (int)$result;
    }

    public function findDesktopVisitorsStats()
    {
        $qb = $this->getLoadQuery()
            ->select('browserName', 'osName', 'COUNT(id) AS count')
            ->where('terminal = :terminal')
            ->setParameter(':terminal', 'Desktop')
            ->groupBy('terminal', 'browserName', 'osName')
            ->orderBy('count', 'desc')
            ->addOrderBy('browserName');

        $this->addBotExclusionWhereCondition($qb);

        $result = $qb->execute()->fetchAll();
        if (false === $result) {
            $result = [];
        }

        return $result;
    }

    public function findNotDesktopVisitorsStats()
    {
        $qb = $this->getLoadQuery()
            ->select('browserName', 'terminal', 'COUNT(id) AS count')
            ->where('terminal != :terminal')
            ->setParameter(':terminal', 'Desktop')
            ->groupBy('terminal', 'browserName', 'osName')
            ->orderBy('count', 'desc')
            ->addOrderBy('browserName');

        $this->addBotExclusionWhereCondition($qb);

        $result = $qb->execute()->fetchAll();
        if (false === $result) {
            $result = [];
        }

        return $result;
    }

    protected function addBotExclusionWhereCondition(QueryBuilder &$qb)
    {
        $qb->andWhere($qb->expr()->andX('osName != :noOs', 'browserName != :other'));
        $qb->setParameter(':noOs', VisitorHelper::$emptyValue);
        $qb->setParameter(':other', 'Other');
    }
}