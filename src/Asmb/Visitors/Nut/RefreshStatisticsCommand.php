<?php

namespace Bundle\Asmb\Visitors\Nut;

use Bolt\Nut\BaseCommand;
use Bundle\Asmb\Visitors\Entity\VisitorStatistics;
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
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Bolt\Storage\EntityManagerInterface $storage */
        $storage = $this->app['storage'];

        // REPOSITORIES
        /** @var \Bundle\Asmb\Visitors\Repository\VisitorRepository $visitorRepo */
        $visitorRepo = $storage->getRepository('visitor');
        /** @var \Bundle\Asmb\Visitors\Repository\VisitorStatisticsRepository $visitorStatisticsRepo */
        $visitorStatisticsRepo = $storage->getRepository('visitor_statistics');

        /** @var VisitorStatistics $visitorStatistics */
        $visitorStatistics = $visitorStatisticsRepo->findStatisticsOfSeason();

        // Recalcul des statistiques de la saison
        $today = Carbon::now();
        $yesterday = Carbon::now()->modify('-1 day');
        $currentDay = $today->day;

        // 1. On met à jour la bonne colonne de la table de statistiques
        $yesterdayVisitorsCount = $visitorRepo->findYesterdayVisitorsCount();
        $columnOfYesterday = 'dayOfMonth' . sprintf("%02d", $yesterday->day);

        $visitorStatistics->set($columnOfYesterday, $yesterdayVisitorsCount);

        // 2. Si aujourd'hui est le 1er jour du mois, hier était le dernier jour
        if ($currentDay === 1) {
            for ($day = 1 + ($yesterday->day); $day <= 31; $day++) {
                $columnOfDay = 'dayOfMonth' . sprintf("%02d", $day);
                // 2.1. On met à 0 les jours suivants dans le mois, dans le cas de mois avec moins de 31 jours
                $visitorStatistics->set($columnOfDay, 0);
            }
        }

        // 2.2 On fait la somme des visiteurs du mois
        $monthVisitorsCount = 0;
        for ($day = 1; $day <= $yesterday->day; $day++) {
            $columnOfDay = 'dayOfMonth' . sprintf("%02d", $day);
            $monthVisitorsCount += $visitorStatistics->get($columnOfDay);
        }
        // 2.3. On met à jour la colonne du mois d'hier
        $columnOfYesterdayMonth = 'month' . sprintf("%02d", $yesterday->month);
        $visitorStatistics->set($columnOfYesterdayMonth, $monthVisitorsCount);

        $visitorStatisticsRepo->save($visitorStatistics, true);
    }
}
