<?php

namespace Bundle\Asmb\Competition\Nut;

use Bolt\Nut\BaseCommand;
use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Repository\Championship\PoolRepository;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Commande Nut pour rafraîchir les données des compétitions à partir de la Gestion Sportive de la FFT ou de TenUp.
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2019
 */
class RefreshCommand extends BaseCommand
{
    protected $isQuietMode = false;

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->isQuietMode = (bool) $input->getOption('quiet');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('asmb:competition:refresh')
            ->setDescription('Rafraîchir les données des compétitions')
            ->addArgument(
                'id',
                InputArgument::OPTIONAL,
                'Id (bolt) de la compétition à rafraîchir (optionnel)'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Force le rafraîchissement, sans tenir comptes des dates et/ou données déjà récupérées'
            );
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
        /** @var \Bundle\Asmb\Competition\Repository\ChampionshipRepository $championshipRepository */
        $championshipRepository = $storage->getRepository('championship');
        /** @var PoolRepository $poolRepository */
        $poolRepository = $storage->getRepository('championship_pool');
        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolRankingRepository $poolRankingRepository */
        $poolRankingRepository = $storage->getRepository('championship_pool_ranking');
        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $storage->getRepository('championship_pool_meeting');

        // Récupération des poules qui ont besoin d'être mises à jour
        $pools = $poolRepository->findAllToRefresh();
        foreach ($pools as $pool) {
            try {
                /** @var Championship $championship */
                $championship = $championshipRepository->find($pool->getChampionshipId());

                // PARSERS
                if ($championship->getFftId() && $pool->getDivisionFftId()) {
                    // Rafraichissement depuis Ten'Up
                    /** @var \Bundle\Asmb\Competition\Parser\Championship\TenupPoolRankingParser $poolRankingParser */
                    $poolRankingParser = $this->app['pool_ranking_tenup_parser'];
                    /** @var \Bundle\Asmb\Competition\Parser\Championship\TenupPoolMeetingsParser $poolMeetingsParser */
                    $poolMeetingsParser = $this->app['pool_meetings_tenup_parser'];

                    $updateFrom = 'Ten\'Up';
                } else {
                    // Rafraichissement depuis la GS
                    /** @var \Bundle\Asmb\Competition\Parser\Championship\GsPoolRankingParser $poolRankingParser */
                    $poolRankingParser = $this->app['pool_ranking_gs_parser'];
                    /** @var \Bundle\Asmb\Competition\Parser\Championship\GsPoolMeetingsParser $poolMeetingsParser */
                    $poolMeetingsParser = $this->app['pool_meetings_gs_parser'];
                    $poolMeetingsParser->setPage(0);

                    $updateFrom = 'la Gestion Sportive';
                }


                // Données CLASSEMENT
                // Récupération des données depuis la Gestion Sportive de la FFT
                $poolRankingParsed = $poolRankingParser->parse($championship, $pool);

                // Sauvegarde des classements en base
                $poolRankingRepository->saveAll($poolRankingParsed, $pool->getId());

                // Données RENCONTRES
                // On parse les différentes pages des rencontres
                $poolMeetingsParsed = $poolMeetingsParser->parse($championship, $pool);

                // On sauvegarde en base
                $poolMeetingRepository->saveAll($poolMeetingsParsed, $pool->getId());

                // On met à jour la date de mise à jour de la poule
                $pool->setUpdatedAt();
                $poolRepository->save($pool);

                if (!$this->isQuietMode) {
                    $output->writeln(
                        "<info>{$championship->getName()} {$championship->getYear()} : Poule {$pool->getCategoryName()} > {$pool->getName()} mise à jour via <comment>$updateFrom</comment>.</info>"
                    );
                }
            } catch (Exception $e) {
                $output->writeln(
                    "<comment>{$championship->getName()} {$championship->getYear()} : Poule {$pool->getCategoryName()} > {$pool->getName()} : </comment>"
                );
                $output->writeln("<error>ERREUR: {$e->getMessage()}</error>");
            }


            // On temporise entre chaque poule, pour éviter de parser plusieurs pages en peu de temps sur la FFT :-)
            sleep(2);
        }
    }
}
