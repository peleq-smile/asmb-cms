<?php

namespace Bundle\Asmb\Visitors\Nut;

use Bolt\Nut\BaseCommand;
use Bundle\Asmb\Visitors\Entity\VisitorStatistics;
use Bundle\Asmb\Visitors\Entity\VisitStatistics;
use Bundle\Asmb\Visitors\Repository\VisitorRepository;
use Bundle\Asmb\Visitors\Repository\VisitorStatisticsRepository;
use Bundle\Asmb\Visitors\Repository\VisitStatisticsRepository;
use Carbon\Carbon;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Commande Nut pour rafraîchir les données des compétitions à partir de la Gestion Sportive de la FFT.
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2020
 */
class RefreshStatisticsCommand extends BaseCommand
{
    protected $isQuietMode = false;

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->isQuietMode = (bool)$input->getOption('quiet');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('asmb:visitor-statistics:refresh')
            ->setDescription('Rafraîchir les données de statistiques des visiteurs');
    }

    /**
     * {@inheritdoc}
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Recalcul des statistiques de la saison
        $this->updateVisitStatistics();
        $this->updateVisitorStatistics();
    }

    /**
     * Met à jour les statistiques quotidiennes et mensuelles des visiteurs.
     */
    protected function updateVisitorStatistics()
    {
        /** @var VisitorRepository $visitorRepo */
        $visitorRepo = $this->app['storage']->getRepository('visitor');
        /** @var VisitorStatisticsRepository $visitorStatisticsRepo */
        $visitorStatisticsRepo = $this->app['storage']->getRepository('visitor_statistics');

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        /** @var VisitorStatistics $visitorStatistics */
        $visitorStatistics = $visitorStatisticsRepo->findOfSeason();

        // 1. On met à jour la bonne colonne de la table de statistiques
        $yesterdayVisitorsCount = $visitorRepo->findYesterdayVisitorsCount();
        $columnOfYesterday = 'dayOfMonth' . sprintf("%02d", $yesterday->day);
        $visitorStatistics->set($columnOfYesterday, $yesterdayVisitorsCount);

        // 2. Si aujourd'hui est le 1er jour du mois, hier était le dernier jour du mois précédent
        if ($today->day === 1) {
            // 2.1. On met à 0 les jours suivants dans le mois, dans le cas de mois avec moins de 31 jours
            for ($day = 1 + ($yesterday->day); $day <= 31; $day++) {
                $columnOfDay = 'dayOfMonth' . sprintf("%02d", $day);
                $visitorStatistics->set($columnOfDay, 0);
            }

            // 2.2 On màj les visiteurs du mois précédent, si on est le 1er
            $lastMonthVisitorsCount = $visitorRepo->findLastMonthVisitorsCount();
            $lastMonth = ($today->month) - 1;

            $columnOfLastMonth = 'month' . sprintf("%02d", $lastMonth);
            $visitorStatistics->set($columnOfLastMonth, $lastMonthVisitorsCount);
        }

        // 2.3 On sauvegarde les nouvelles stats
        $visitorStatisticsRepo->save($visitorStatistics);
    }

    /**
     * Met à jour les statistiques quotidiennes et mensuelles des visites.
     */
    protected function updateVisitStatistics()
    {
        /** @var VisitorRepository $visitorRepo */
        $visitorRepo = $this->app['storage']->getRepository('visitor');
        /** @var VisitStatisticsRepository $visitStatisticsRepo */
        $visitStatisticsRepo = $this->app['storage']->getRepository('visit_statistics');

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        /** @var VisitStatistics $visitStatistics */
        $visitStatistics = $visitStatisticsRepo->findOfSeason();

        // 1. On met à jour la bonne colonne de la table de statistiques
        $yesterdayVisitorsCount = $visitorRepo->findYesterdayVisitsCount();
        $columnOfYesterday = 'dayOfMonth' . sprintf("%02d", $yesterday->day);
        $visitStatistics->set($columnOfYesterday, $yesterdayVisitorsCount);

        // 2. Si aujourd'hui est le 1er jour du mois, hier était le dernier jour du mois précédent
        if ($today->day === 1) {
            // 2.1. On met à 0 les jours suivants dans le mois, dans le cas de mois avec moins de 31 jours
            for ($day = 1 + ($yesterday->day); $day <= 31; $day++) {
                $columnOfDay = 'dayOfMonth' . sprintf("%02d", $day);
                $visitStatistics->set($columnOfDay, 0);
            }

            // 2.2 On fait la somme des visites du mois
            $monthVisitsCount = 0;
            for ($day = 1; $day <= $yesterday->day; $day++) {
                $columnOfDay = 'dayOfMonth' . sprintf("%02d", $day);
                $monthVisitsCount += $visitStatistics->get($columnOfDay);
            }
            // 2.3. On met à jour la colonne du mois d'hier
            $columnOfYesterdayMonth = 'month' . sprintf("%02d", $yesterday->month);
            $visitStatistics->set($columnOfYesterdayMonth, $monthVisitsCount);
        }

        // 2.3 On sauvegarde les nouvelles stats
        $visitStatisticsRepo->save($visitStatistics);
    }
}
