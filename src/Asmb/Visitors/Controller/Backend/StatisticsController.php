<?php


namespace Bundle\Asmb\Visitors\Controller\Backend;


use Bolt\Controller\Backend\BackendBase;
use Bundle\Asmb\Visitors\Entity\VisitorStatistics;
use Bundle\Asmb\Visitors\Helpers\VisitorHelper;
use Carbon\Carbon;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Response;

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
        $visitorStatistics = $this->getRepository('visitor_statistics')->findStatisticsOfSeason();
        $lastMonthDataForChart = VisitorHelper::getLastMonthDataForChart($visitorStatistics);

        return $this->render(
            '@AsmbVisitors/statistics/index.twig',
            [],
            [
                'lastMonthJsonData' => json_encode($lastMonthDataForChart, JSON_NUMERIC_CHECK),
            ]
        );
    }
}