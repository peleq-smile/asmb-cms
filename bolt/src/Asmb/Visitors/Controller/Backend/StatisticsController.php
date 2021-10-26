<?php

namespace Bundle\Asmb\Visitors\Controller\Backend;

use Bolt\Controller\Backend\BackendBase;
use Bundle\Asmb\Visitors\Entity\VisitorStatistics;
use Bundle\Asmb\Visitors\Entity\VisitStatistics;
use Bundle\Asmb\Visitors\Helpers\VisitorHelper;
use Bundle\Asmb\Visitors\Repository\VisitorRepository;
use Bundle\Asmb\Visitors\Repository\VisitorStatisticsRepository;
use Bundle\Asmb\Visitors\Repository\VisitStatisticsRepository;
use Carbon\Carbon;
use Silex\ControllerCollection;

class StatisticsController extends BackendBase
{
    public function addRoutes(ControllerCollection $c)
    {
        $c->match('/statistics', 'index')
            ->bind('statistics');

        return $c;
    }


    public function index()
    {
        /** @var VisitorStatisticsRepository $visitorStatisticsRepo */
        $visitorStatisticsRepo = $this->app['storage']->getRepository('visitor_statistics');
        /** @var VisitStatisticsRepository $visitStatisticsRepo */
        $visitStatisticsRepo = $this->app['storage']->getRepository('visit_statistics');
        /** @var VisitorRepository $visitorRepo */
        $visitorRepo = $this->app['storage']->getRepository('visitor');

        $visitorStatistics = $visitorStatisticsRepo->findOfSeason();
        $visitStatistics = $visitStatisticsRepo->findOfSeason();

        // Stats du jour
        $todayVisitsCount = $visitorRepo->findDayVisitsCount(Carbon::today());
        $todayVisitorsCount = $visitorRepo->findDayVisitorsCount(Carbon::today());
        // On met à jour (en mémoire seulement) les stats du mois courant
        $this->updateCurrentMonthAndDayStats($visitStatistics, $visitorStatistics, $todayVisitsCount);

        // Couleur des graphiques
        $visitColor = '#6d78ad';
        $visitorColor = '#fcc26c';

        $lastMonthVisitsDataForChart = VisitorHelper::getLastMonthDataForChart($visitStatistics, $visitColor, $todayVisitsCount);
        $lastMonthVisitorsDataForChart = VisitorHelper::getLastMonthDataForChart($visitorStatistics, $visitorColor, $todayVisitorsCount);

        $lastSeasonVisitsDataForChart = VisitorHelper::getLastSeasonDataForChart($visitStatistics, $visitColor);
        $lastSeasonVisitorsDataForChart = VisitorHelper::getLastSeasonDataForChart($visitorStatistics, $visitorColor);

        $desktopDataForChart = VisitorHelper::getDesktopDataForChart($visitorRepo->findDesktopVisitorsStats());
        $notDesktopDataForChart = VisitorHelper::getNotDesktopDataForChart($visitorRepo->findNotDesktopVisitorsStats());

        return $this->render(
            '@AsmbVisitors/statistics/index.twig',
            [],
            [
                'visitColor' => $visitColor,
                'visitorColor' => $visitorColor,
                'maxSimultaneous' => $visitorStatistics->get('maxSimultaneous'),
                'lastMonthVisitorsJsonData' => json_encode($lastMonthVisitorsDataForChart, JSON_NUMERIC_CHECK),
                'lastMonthVisitsJsonData' => json_encode($lastMonthVisitsDataForChart, JSON_NUMERIC_CHECK),
                'lastSeasonVisitorsJsonData' => json_encode($lastSeasonVisitorsDataForChart, JSON_NUMERIC_CHECK),
                'lastSeasonVisitsJsonData' => json_encode($lastSeasonVisitsDataForChart, JSON_NUMERIC_CHECK),
                'desktopDataForChart' => json_encode($desktopDataForChart, JSON_NUMERIC_CHECK),
                'notDesktopDataForChart' => json_encode($notDesktopDataForChart, JSON_NUMERIC_CHECK),
                'desktopVisitorTotalCount' => VisitorHelper::$desktopVisitorTotalCount,
                'notDesktopVisitorTotalCount' => VisitorHelper::$notDesktopVisitorTotalCount,
            ]
        );
    }

    protected function updateCurrentMonthAndDayStats(VisitStatistics $visitStatistics, VisitorStatistics $visitorStatistics, int $todayVisitsCount)
    {
        /** @var VisitorRepository $visitorRepo */
        $visitorRepo = $this->app['storage']->getRepository('visitor');

        $columnOfMonth = 'month' . sprintf("%02d", Carbon::today()->month);

        // On récupère le nombre de visiteurs du mois en cours et on l'assigne au mois de la saison en cours
        $monthVisitorCount = $visitorRepo->findMonthVisitorsCount(Carbon::today()->month);
        $visitorStatistics->set($columnOfMonth, $monthVisitorCount);

        // On compte le nombre de visites du mois en cours
        $monthVisitsCount = $todayVisitsCount; // On démarre du nombre de visites d'aujourd'hui !
        for ($day = 1; $day <= Carbon::today()->day; $day++) {
            $columnOfDay = 'dayOfMonth' . sprintf("%02d", $day);
            $monthVisitsCount += $visitStatistics->get($columnOfDay);
        }
        $visitStatistics->set($columnOfMonth, $monthVisitsCount);
    }
}