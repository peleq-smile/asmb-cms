<?php

namespace Bundle\Asmb\Competition\Extension;

use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolRankingRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolRepository;
use Carbon\Carbon;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Déclaration de fonctions Twig.
 */
trait TwigFunctionsTrait
{
    /**
     * Retourne les dernières rencontres du club.
     *
     * @param integer $pastDays
     *
     * @return \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getLastMeetings($pastDays)
    {
        $lastMeetings = [];

        if ($pastDays > 0) {
            $pastDays = -1 * $pastDays;
            $lastMeetings = $this->getLastOrNextMeetings($pastDays);
        }

        return $lastMeetings;
    }

    /**
     * Retourne les prochaines rencontres du club.
     *
     * @param integer $futureDays
     *
     * @return \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getNextMeetings($futureDays)
    {
        return $this->getLastOrNextMeetings($futureDays);
    }

    /**
     * Retourne les rencontres du moment, dans le passé ou le futur selon que $pastOrFutureDays soit négatif (passé)
     * ou positif (futur).
     *
     * @param integer $pastOrFutureDays
     *
     * @return \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    protected function getLastOrNextMeetings($pastOrFutureDays)
    {
        /** @var PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $this->getStorage()->getRepository('championship_pool_meeting');
        $pastDays = ($pastOrFutureDays < 0) ? (-1 * $pastOrFutureDays) : 0;
        $futureDays = ($pastOrFutureDays > 0) ? $pastOrFutureDays : 0;
        $meetingsOfTheMoment = $poolMeetingRepository->findClubMeetingsOfTheMoment($pastDays, $futureDays);

        return $meetingsOfTheMoment;
    }

    /**
     * @param integer $championshipId
     *
     * @return \Bundle\Asmb\Competition\Entity\Championship
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getChampionship($championshipId)
    {
        /** @var \Bundle\Asmb\Competition\Repository\ChampionshipRepository $championshipRepository */
        $championshipRepository = $this->getStorage()->getRepository('championship');
        $championship = $championshipRepository->find($championshipId);

        return $championship;
    }

    /**
     * Retourne les poules de la compétation d'id donné, groupées par nom de catégorie, avec éventuellement un filtre
     * sur les catégories à prendre en compte.
     *
     * @param integer $championshipId
     * @param array   $categoryNames
     *
     * @return array
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getPoolsPerCategoryName($championshipId, array $categoryNames)
    {
        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolRepository $poolRepository */
        $poolRepository = $this->getStorage()->getRepository('championship_pool');
        $poolsPerCategoryName = $poolRepository->findByChampionshipIdGroupByCategoryName(
            $championshipId,
            $categoryNames
        );

        return $poolsPerCategoryName;
    }

    /**
     * Retourne le classement des équipes des poules du championnat d'id donné.
     *
     * @param integer $championshipId
     * @param array   $categoryNames
     *
     * @return \Bundle\Asmb\Competition\Entity\Championship\PoolRanking[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getPoolRankingPerPoolId($championshipId, array $categoryNames)
    {
        $pools = $this->getPools($championshipId, $categoryNames);
        $poolIds = array_keys($pools);
        $poolRankingByPool = array_fill_keys($poolIds, []);

        /** @var PoolRankingRepository $poolRankingRepository */
        $poolRankingRepository = $this->getStorage()->getRepository('championship_pool_ranking');
        $poolRankingByPool = $poolRankingRepository->findByPoolIdsSortedRanking($poolIds) + $poolRankingByPool;

        return $poolRankingByPool;
    }

    /**
     * Retourne le tableau des rencontres des poules du championnat d'id donné.
     *
     * @param integer $championshipId
     * @param array   $categoryNames
     *
     * @return array
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getPoolMeetingsPerPoolId($championshipId, array $categoryNames)
    {
        $pools = $this->getPools($championshipId, $categoryNames);
        $poolIds = array_keys($pools);
        $poolMeetingsByPool = array_fill_keys($poolIds, []);

        /** @var PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $this->getStorage()->getRepository('championship_pool_meeting');
        $poolMeetingsByPool = $poolMeetingRepository->findGroupByPoolIdAndDay($poolIds) + $poolMeetingsByPool;

        return $poolMeetingsByPool;
    }

    /**
     * @param \Bolt\Legacy\Content $competitionRecord
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function renderTournament($competitionRecord)
    {
        // Récupération de l'url donnée dans le contenu "Compétition"
        $jsonFileUrl = $competitionRecord->get('tournoi_url_json');

        // Chemin vers le fichier .html dont on veut vérifier l'existence / générer le contenu / récupérer le contenu
        $htmlFilePath = $this->getHtmlFilePath($jsonFileUrl);
        $htmlFile = $this->getFile($htmlFilePath);

        if ($htmlFile->exists()) {
            $tournamentContent = $htmlFile->read();
        } else {
            $jsonFileAbsoluteUrl = $this->getFileUrl($jsonFileUrl);

            $parser = $this->getJaTennisParser();
            $parser->setJsonFileUrl($jsonFileAbsoluteUrl);
            $parsedData = $parser->parse();

            // Règle d'affichage du tournoi:
            // - Tournoi terminé : on affiche la page de résultat
            // - Tournoi à venir : on affiche la page de planning avec le 1er jour du tournoi
            // - Tournoi en cours : on affiche la page du jour J ou du prochain jour de tournoi

            $now = Carbon::now();

            $beginDate = Carbon::createFromFormat('Y-m-d', $parsedData['info']['begin']);
            $beginDate->setTime(0, 0, 0);
            $endDate = Carbon::createFromFormat('Y-m-d', $parsedData['info']['end']);
            $endDate->setTime(0, 0, 0);

            $display = '#res';
            $planningDayFormatted = '';
            if ($now <= $beginDate) {
                // Tournoi à venir ou 1er du tournoi : on affiche le planning du 1er jour
                $planningDayFormatted = $parser->getFormattedCleanedDate($beginDate);
                $display = '#pla';
            } elseif ($now <= $endDate) {
                // Tournoi en cours : on affiche le planning du jour (si des matchs ont lieu) ou le prochain jour
                $planningDay = $now;

                while (!isset($parsedData['planning'][$parser->getFormattedDate($planningDay)]) && $planningDay <= $endDate) {
                    $planningDay->addDay(1);
                }

                $planningDayFormatted = $parser->getFormattedCleanedDate($planningDay);
                $display = '#pla';
            }

            $context = [
                'parsedData' => $parsedData,
                'display'    => $display,
                'plaDay'     => $planningDayFormatted,
            ];

            /** @var $twig \Twig\Environment */
            $twig = $this->container['twig'];
            $tournamentContent = $twig->render('@AsmbCompetition/tournament/ja_tennis_tournament.twig', $context);

            // On génère le .html pour la prochaine fois
            $htmlFilePath = str_replace($htmlFile->getMountPoint(), '', $htmlFilePath);
            $htmlFile->setPath($htmlFilePath);
            if (!isset($parsedData['error']) && '#res' === $display) {
                // On ne sauvegarde pas la version HTML si le tournoi est en cours, afin d'éviter d'avoir des données
                // non à jour.
                $htmlFile->write($tournamentContent);
            }
        }

        return $tournamentContent;
    }

    /**
     * @return \Bundle\Asmb\Competition\Parser\JaTennisJsonParser
     */
    protected function getJaTennisParser()
    {
        return $this->container['ja_tennis_parser'];
    }

    /**
     * Retourne le chemin du fichier .html à vérifier l'existence / à générer.
     *
     * @param string $jsonFileUrl
     *
     * @return string
     */
    protected function getHtmlFilePath($jsonFileUrl)
    {
        $basename = basename($jsonFileUrl);

        // On voudra générer un .html dans "files/tournois/html" et avec l'extension .html au lieu de .json
        $htmlFilePath = 'tournois/html/' . str_replace('.json', '.html', $basename);

        return $htmlFilePath;
    }

    /**
     * Retourne l'url absolue du fichier dont le chemin est passé en paramètre, selon qu'il soit relatif ou non.
     *
     * @param $uri
     *
     * @return string
     */
    protected function getFileUrl($uri)
    {
        if (0 === strpos($uri, '/')) {
            $scheme = $this->getUrlGenerator()->getContext()->getScheme();
            $host = $this->getUrlGenerator()->getContext()->getHost();
            $url = "$scheme://$host$uri";
        } else {
            $url = $uri;
        }

        return $url;
    }

    /**
     * Retourne le fichier dont le chemin est passé en paramètre.
     *
     * @param string $filePath
     *
     * @return \Bolt\Filesystem\Handler\File
     */
    protected function getFile($filePath)
    {
        /** @var \Bolt\Filesystem\Manager $fileManager */
        $fileManager = $this->container['filesystem'];
        /** @var \Bolt\Filesystem\Handler\File $file */
        $path = ltrim(str_replace('files', '', $filePath), '/');
        $file = $fileManager->getFilesystem('files')->getFile($path);

        return $file;
    }

    /**
     * @return \Symfony\Component\Routing\Generator\UrlGeneratorInterface
     */
    protected function getUrlGenerator()
    {
        /** @var \Bolt\Application $app */
        $app = $this->getContainer();

        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = $app['url_generator'];

        return $urlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        return [
            'getHomeMeetings'          => 'getHomeMeetings',
            'getLastMeetings'          => 'getLastMeetings',
            'getNextMeetings'          => 'getNextMeetings',
            'getPoolsPerCategoryName'  => 'getPoolsPerCategoryName',
            'getPoolRankingPerPoolId'  => 'getPoolRankingPerPoolId',
            'getPoolMeetingsPerPoolId' => 'getPoolMeetingsPerPoolId',
            'renderTournament'         => 'renderTournament',
        ];
    }

    /**
     * @return \Bolt\Storage\EntityManagerInterface
     */
    protected function getStorage()
    {
        /** @var \Bolt\Application $app */
        $app = $this->getContainer();
        /** @var \Bolt\Storage\EntityManagerInterface $storage */
        $storage = $app['storage'];

        return $storage;
    }

    /**
     * Recupère les poules à partir de l'id de compétition donné.
     *
     * @param integer $championshipId
     * @param array   $categoryNames
     *
     * @return Pool[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    protected function getPools($championshipId, array $categoryNames)
    {
        /** @var PoolRepository $poolRepository */
        $poolRepository = $this->getStorage()->getRepository('championship_pool');
        $pools = $poolRepository->findByChampionshipId($championshipId);

        return $pools;
    }

    /**
     * @param \Bolt\Legacy\Content $competitionRecord
     *
     * @return array
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getHomeMeetings($competitionRecord)
    {
        $homeMeetings = [
            'sat'      => [], // Rencontres du samedi
            'sun'      => [], // Rencontres du dimanche
            'satSlots' => [], // Créneaux du samedi pour lesquels il existe au moins 1 rencontre
            'sunSlots' => [], // Créneaux du dimanche pour lesquels il existe au moins 1 rencontre
        ];

        $fromDate = Carbon::createFromFormat('Y-m-d', $competitionRecord->get('home_meetings_from_date'));
        $toDate = Carbon::createFromFormat('Y-m-d', $competitionRecord->get('home_meetings_to_date'));

        if (Carbon::SUNDAY === $fromDate->dayOfWeek) {
            $fromDate->addDay(-1);
        } elseif (Carbon::SATURDAY !== $fromDate->dayOfWeek) {
            $fromDate->next(Carbon::SATURDAY);
        }
        // Maintenant, notre date de départ $fromDate est forcément un samedi

        if (Carbon::SUNDAY !== $toDate->dayOfWeek) {
            $toDate->next(Carbon::SUNDAY);
        }
        // Maintenant, notre date de fin $toDate est forcément un dimanche

        $saturdayDate = clone $fromDate;
        while ($saturdayDate->lt($toDate)) {
            $homeMeetings['sat'][$saturdayDate->format('d/m')] = [];
            $homeMeetings['sun'][$saturdayDate->addDay()->format('d/m')] = [];

            $saturdayDate->next(Carbon::SATURDAY);
        }

        /** @var PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $this->getStorage()->getRepository('championship_pool_meeting');
        $homeMeetingsFromDate = $poolMeetingRepository->findHomeMeetingsBetweenDate($fromDate, $toDate);

        foreach ($homeMeetingsFromDate as $homeMeeting) {
            $formattedDate = $homeMeeting->getFinalDate()->format('d/m');
            $formattedTime = $homeMeeting->getTime()->format('H:i');

            /**
             * On considère qu'il peut y avoir 3 "créneaux" de rencontres :
             * - [AM] matin
             * - [MD] midi (/début d'après-midi)
             * - [PM] après-midi
             * Selon l'horaire de la rencontre, on affecte l'affecte à tel ou tel créneau
             */
            if ($formattedTime < '10:00') {
                $slot = 'AM';
            } elseif ($formattedTime < '15:00') {
                $slot = 'MD';
            } else {
                $slot = 'PM';
            }

            if (Carbon::SATURDAY === $homeMeeting->getFinalDate()->dayOfWeek) {
                $homeMeetings['sat'][$formattedDate][$slot] = $homeMeeting;
                // On marque le créneau comme utilisé par au moins 1 rencontre (info utile pour gérer l'affichage sur le FO)
                $homeMeetings['satSlots'][$slot] = true;
            } elseif (Carbon::SUNDAY === $homeMeeting->getFinalDate()->dayOfWeek) {
                $homeMeetings['sun'][$formattedDate][$slot] = $homeMeeting;
                // On marque le créneau comme utilisé par au moins 1 rencontre (info utile pour gérer l'affichage sur le FO)
                $homeMeetings['sunSlots'][$slot] = true;
            } // else: c'est la merde...

        }

        return $homeMeetings;
    }
}
