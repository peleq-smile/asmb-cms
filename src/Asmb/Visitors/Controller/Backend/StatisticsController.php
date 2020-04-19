<?php


namespace Bundle\Asmb\Visitors\Controller\Backend;


use Bolt\Controller\Backend\BackendBase;
use Bundle\Asmb\Visitors\Helpers\VisitorHelper;
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
        /** @var \Bundle\Asmb\Visitors\Repository\VisitorStatisticsRepository $visitorStatisticsRepo */
        $visitorStatisticsRepo = $this->app['storage']->getRepository('visitor_statistics');
        /** @var \Bundle\Asmb\Visitors\Repository\VisitStatisticsRepository $visitStatisticsRepo */
        $visitStatisticsRepo = $this->app['storage']->getRepository('visit_statistics');

        $visitorStatistics = $visitorStatisticsRepo->findOfSeason();
        $visitStatistics = $visitStatisticsRepo->findOfSeason();

        $lastMonthVisitorsDataForChart = VisitorHelper::getLastMonthDataForChart($visitorStatistics, 'visiteurs');
        $lastMonthVisitsDataForChart = VisitorHelper::getLastMonthDataForChart($visitStatistics, 'visites');

        /** @var \Symfony\Component\Console\Application $nut Nut Console Application */
        $nut = $this->app['nut'];

        return $this->render(
            '@AsmbVisitors/statistics/index.twig',
            [],
            [
                'lastMonthVisitorsJsonData' => json_encode($lastMonthVisitorsDataForChart, JSON_NUMERIC_CHECK),
                'lastMonthVisitsJsonData'   => json_encode($lastMonthVisitsDataForChart, JSON_NUMERIC_CHECK),
            ]
        );
    }
}