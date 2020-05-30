<?php


namespace Bundle\Asmb\Visitors\Controller\Backend;


use Bolt\Controller\Backend\BackendBase;
use Bundle\Asmb\Visitors\Helpers\VisitorHelper;
use Bundle\Asmb\Visitors\Repository\VisitorRepository;
use Bundle\Asmb\Visitors\Repository\VisitorStatisticsRepository;
use Bundle\Asmb\Visitors\Repository\VisitStatisticsRepository;
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

        $lastMonthVisitsDataForChart = VisitorHelper::getLastMonthDataForChart($visitStatistics);
        $lastMonthVisitorsDataForChart = VisitorHelper::getLastMonthDataForChart($visitorStatistics);

        $lastSeasonVisitsDataForChart = VisitorHelper::getLastSeasonDataForChart($visitStatistics);
        $lastSeasonVisitorsDataForChart = VisitorHelper::getLastSeasonDataForChart($visitorStatistics);

        $desktopDataForChart = VisitorHelper::getDesktopDataForChart($visitorRepo->findDesktopVisitorsStats());
        $notDesktopDataForChart = VisitorHelper::getNotDesktopDataForChart($visitorRepo->findNotDesktopVisitorsStats());

        return $this->render(
            '@AsmbVisitors/statistics/index.twig',
            [],
            [
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
}