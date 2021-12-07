<?php

namespace Bundle\Asmb\Competition\Extension;

use Bolt\AccessControl\Token\Token;
use Bolt\Application;
use Bolt\Exception\InvalidRepositoryException;
use Bolt\Filesystem\Handler\File;
use Bolt\Filesystem\Manager;
use Bolt\Legacy\Content;
use Bolt\Storage\EntityManagerInterface;
use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Entity\Championship\PoolRanking;
use Bundle\Asmb\Competition\Parser\Tournament\AbstractParser;
use Bundle\Asmb\Competition\Parser\Tournament\DbParser;
use Bundle\Asmb\Competition\Parser\Tournament\JaTennisJsonParser;
use Bundle\Asmb\Competition\Parser\Tournament\JaTennisJsParser;
use Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolRankingRepository;
use Bundle\Asmb\Competition\Repository\Championship\PoolRepository;
use Bundle\Asmb\Competition\Repository\ChampionshipRepository;
use Carbon\Carbon;
use Cocur\Slugify\Slugify;
use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

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
     * @return PoolMeeting[]
     * @throws InvalidRepositoryException
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
     * @return PoolMeeting[]
     * @throws InvalidRepositoryException
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
     * @return PoolMeeting[]
     * @throws InvalidRepositoryException
     * @noinspection PhpUndefinedMethodInspection
     */
    protected function getLastOrNextMeetings($pastOrFutureDays)
    {
        $sortedMeetings = [];

        if ($pastOrFutureDays != 0) {
            $groupedMeetings = [];

            /** @var PoolMeetingRepository $poolMeetingRepository */
            $poolMeetingRepository = $this->getStorage()->getRepository('championship_pool_meeting');
            $pastDays = ($pastOrFutureDays < 0) ? (-1 * $pastOrFutureDays) : 0;
            $futureDays = ($pastOrFutureDays > 0) ? $pastOrFutureDays : 0;
            $meetingsOfTheMoment = $poolMeetingRepository->findClubMeetingsOfTheMoment($pastDays, $futureDays);

            // On récupère le contenu "Competition" pour regrouper les rencontres par Championnat/catégorie et
            // pour ajouter un lien vers la page
            /** @var Application $app */
            $app = $this->getContainer();
            foreach ($meetingsOfTheMoment as $meeting) {
                // On ignore les rencontres dont l'une des équipes contient "Exempt"
                if (
                    stripos($meeting->getHomeTeamName(), 'exempt') !== false ||
                    stripos($meeting->getVisitorTeamName(), 'exempt') !== false
                ) {
                    continue;
                }

                /** @see https://docs.bolt.cm/3.6/extensions/storage/queries */
                $competitionPage = $app['query']->getContent(
                    'competition',
                    [
                        'championship_id' => $meeting->getChampionshipId(),
                        'championship_categories' => '%' . $meeting->getCategoryName() . '%',
                        'returnsingle' => true
                    ]
                );

                if (null !== $competitionPage && $competitionPage) {
                    $meetingDate = $meeting->getFinalDate()->format('Ymd');

                    $meeting->setCompetitionRecordTitle($competitionPage->getShortTitle());
                    $meeting->setCompetitionRecordSlug($competitionPage->getSlug());

                    if (!isset($groupedMeetings[$meetingDate . '-' . $competitionPage->getId()])) {
                        $groupedMeetings[$meetingDate . '-' . $competitionPage->getId()] = [$meeting];
                    } else {
                        $groupedMeetings[$meetingDate . '-' . $competitionPage->getId()][] = $meeting;
                    }
                }
            }

            foreach ($groupedMeetings as $meetings) {
                $sortedMeetings = array_merge($sortedMeetings, $meetings);
            }
        }

        return $sortedMeetings;
    }

    /**
     * @param integer $championshipId
     *
     * @return Championship
     * @throws InvalidRepositoryException
     */
    public function getChampionship($championshipId)
    {
        /** @var ChampionshipRepository $championshipRepository */
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
     * @throws InvalidRepositoryException
     */
    public function getPoolsPerCategoryName($championshipId, array $categoryNames)
    {
        /** @var PoolRepository $poolRepository */
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
     * @return PoolRanking[]
     * @throws InvalidRepositoryException
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
     * @throws InvalidRepositoryException
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
     * @throws InvalidRepositoryException
     */
    public function getChampionshipById(int $championshipId): ?Championship
    {
        /** @var ChampionshipRepository $championshipRepository */
        $championshipRepository = $this->getStorage()->getRepository('championship');

        return $championshipRepository->find($championshipId);
    }

    /**
     * @param Content $competitionRecord
     *
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderTournament($competitionRecord)
    {
        // Récupération de l'url donnée dans le contenu "Compétition"
        $tournamentId = $competitionRecord->get('tournament_id');
        $jsFileUrl = $competitionRecord->get('tournament_url_js');
        $jsonFileUrl = $competitionRecord->get('tournament_url_json');

        if ($tournamentId) {
            $tournamentContent = $this->renderTournamentFromDb($tournamentId);
        } elseif ($jsFileUrl) {
            $tournamentContent = $this->renderTournamentFromJsFile($jsFileUrl);
        } elseif ($jsonFileUrl) {
            $tournamentContent = $this->renderTournamentFromJsonFile($jsonFileUrl);
        } else {
            $tournamentContent = '';
        }

        return $tournamentContent;
    }

    /**
     * @param string $jsFileUrl
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function renderTournamentFromJsFile(string $jsFileUrl): string
    {
        // Chemin vers le fichier .html dont on veut vérifier l'existence / générer le contenu / récupérer le contenu
        $htmlFilePath = $this->getHtmlFilePath($jsFileUrl);
        $htmlFile = $this->getFile($htmlFilePath);

        if ($htmlFile->exists()) {
            $tournamentContent = $htmlFile->read();
        } else {
            $jsFileAbsoluteUrl = $this->getFileUrl($jsFileUrl);

            /** @var JaTennisJsParser $parser */
            $parser = $this->container['ja_tennis_js_parser'];
            $parser->setFileUrl($jsFileAbsoluteUrl);
            $parsedData = $parser->parse();

            $tournamentContent = $this->getRenderedTournamentContent($parser, $parsedData, true);

            // On génère le .html pour la prochaine fois
            $htmlFilePath = str_replace($htmlFile->getMountPoint(), '', $htmlFilePath);
            $htmlFile->setPath($htmlFilePath);

            if (!isset($parsedData['error'])) {
                $endDate = Carbon::createFromFormat('Y-m-d', $parsedData['info']['end']);
                $endDate->setTime(0, 0);

                // On ne sauvegarde pas la version HTML si le tournoi est en cours, afin d'éviter d'avoir des données
                // non à jour.
                if ($endDate < Carbon::tomorrow()) {
                    $htmlFile->write($tournamentContent);
                }
            }
        }

        return $tournamentContent;
    }

    /**
     * @param string $jsonFileUrl
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function renderTournamentFromJsonFile(string $jsonFileUrl): string
    {
        // Chemin vers le fichier .html dont on veut vérifier l'existence / générer le contenu / récupérer le contenu
        $htmlFilePath = $this->getHtmlFilePath($jsonFileUrl);
        $htmlFile = $this->getFile($htmlFilePath);

        if ($htmlFile->exists()) {
            $tournamentContent = $htmlFile->read();
        } else {
            $jsonFileAbsoluteUrl = $this->getFileUrl($jsonFileUrl);

            /** @var JaTennisJsonParser $parser */
            $parser = $this->container['ja_tennis_json_parser'];
            $parser->setFileUrl($jsonFileAbsoluteUrl);
            $parsedData = $parser->parse();

            $tournamentContent = $this->getRenderedTournamentContent($parser, $parsedData, true);

            // On génère le .html pour la prochaine fois
            $htmlFilePath = str_replace($htmlFile->getMountPoint(), '', $htmlFilePath);
            $htmlFile->setPath($htmlFilePath);

            if (!isset($parsedData['error'])) {
                $endDate = Carbon::createFromFormat('Y-m-d', $parsedData['info']['end']);
                $endDate->setTime(0, 0, 0);

                // On ne sauvegarde pas la version HTML si le tournoi est en cours, afin d'éviter d'avoir des données
                // non à jour.
                if ($endDate < Carbon::tomorrow()) {
                    $htmlFile->write($tournamentContent);
                }
            }
        }

        return $tournamentContent;
    }

    protected function renderTournamentFromDb($tournamentId)
    {
        /** @var DbParser $parser */
        $parser = $this->container['tournament_db_parser'];
        $parser->setTournamentId($tournamentId);
        $parsedData = $parser->parse();

        $tournament = $parser->getTournament();
        $displayTimes = $tournament->getDisplayTimes();

        try {
            $tournamentContent = $this->getRenderedTournamentContent($parser, $parsedData, $displayTimes);
        } catch (Exception $e) {
            $tournamentContent = '';
        }

        return $tournamentContent;
    }

    /**
     * @param AbstractParser $parser
     * @param array          $parsedData
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function getRenderedTournamentContent(AbstractParser $parser, array $parsedData, bool $displayTimes)
    {
        if (isset($parsedData['error'])) {
            $app = $this->getContainer();
            $token = $app['session']->get('authentication');
            if (null !== $token && null !== $token->getUser()) {
                return 'Une erreur est survenue dans le traitement des données du tournoi :<br>'
                . $parsedData['error']
                . '<pre style="text-align: left !important;">'
                . $parsedData['trace'] . '</pre>';
            }

            return 'Une erreur est survenue dans le traitement des données du tournoi :((( !';
        }

        $display = '#res';
        $planningDayFormatted = '';

        if (isset($parsedData['info']['begin'])) {
            // Règle d'affichage du tournoi:
            // - Tournoi terminé : on affiche la page de résultat
            // - Tournoi à venir : on affiche la page de planning avec le 1er jour du tournoi
            // - Tournoi en cours : on affiche la page du jour J ou du prochain jour de tournoi
            $now = Carbon::now();

            $beginDate = Carbon::createFromFormat('Y-m-d', $parsedData['info']['begin']);
            $beginDate->setTime(0, 0);
            $endDate = Carbon::createFromFormat('Y-m-d', $parsedData['info']['end']);
            $endDate->setTime(23, 59);

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
        }

        $context = [
            'parsedData' => $parsedData,
            'display' => $display,
            'plaDay' => $planningDayFormatted,
            'displayTimes' => $displayTimes
        ];

        /** @var $twig Environment */
        $twig = $this->container['twig'];

        return $twig->render('@AsmbCompetition/tournament/tournament.twig', $context);
    }

    /**
     * Retourne le chemin du fichier .html à vérifier l'existence / à générer.
     *
     * @param string $fileUrl
     *
     * @return string
     */
    protected function getHtmlFilePath(string $fileUrl): string
    {
        $basename = rawurldecode(basename($fileUrl));

        // S'il y a des paramètres après l'extension .json ou .js, on les retire (en plus de l'extension)
        if (false !== strpos($basename, '.json')) {
            // on regarde d'abord l'extension .json car '.json' contient '.js' ;)
            $htmlFileName = substr($basename, 0, strpos($basename, '.json'));
        } else {
            $htmlFileName = substr($basename, 0, strpos($basename, '.js'));
        }
        // On "slugify" le nom du json (= remplacement des caractères spéciaux)
        $htmlFileName = Slugify::create()->slugify($htmlFileName);

        return 'tournois/html/' . $htmlFileName . '.html';
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

            /** @var Application $app */
            $app = $this->getContainer();

            /** @var \Bolt\Filesystem\Manager $fsm */
            $fsm = $app['filesystem'];
            $webFs = $fsm->getFilesystem('web');
            $publicFolder = $webFs->getAdapter()->getPathPrefix();
            $url = rtrim($publicFolder, '/') . $uri;
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
     * @return File
     */
    protected function getFile($filePath)
    {
        /** @var Manager $fileManager */
        $fileManager = $this->container['filesystem'];
        /** @var File $file */
        $path = ltrim(str_replace('files', '', $filePath), '/');
        $file = $fileManager->getFilesystem('files')->getFile($path);

        return $file;
    }

    /**
     * @return UrlGeneratorInterface
     */
    protected function getUrlGenerator()
    {
        /** @var Application $app */
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
            'getHomeMeetings' => 'getHomeMeetings',
            'getLastMeetings' => 'getLastMeetings',
            'getNextMeetings' => 'getNextMeetings',
            'getPoolsPerCategoryName' => 'getPoolsPerCategoryName',
            'getPoolRankingPerPoolId' => 'getPoolRankingPerPoolId',
            'getPoolMeetingsPerPoolId' => 'getPoolMeetingsPerPoolId',
            'getChampionshipById' => 'getChampionshipById',
            'renderTournament' => 'renderTournament',
        ];
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getStorage()
    {
        /** @var Application $app */
        $app = $this->getContainer();
        /** @var EntityManagerInterface $storage */
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
     * @throws InvalidRepositoryException
     * @noinspection PhpUnusedParameterInspection
     */
    protected function getPools($championshipId, array $categoryNames)
    {
        /** @var PoolRepository $poolRepository */
        $poolRepository = $this->getStorage()->getRepository('championship_pool');
        $pools = $poolRepository->findByChampionshipId($championshipId);

        return $pools;
    }

    /**
     * @param Content $competitionRecord
     *
     * @return array
     * @throws InvalidRepositoryException
     */
    public function getHomeMeetings($competitionRecord)
    {
        $homeMeetings = [
            'sat' => [], // Rencontres du samedi
            'sun' => [], // Rencontres du dimanche
            'satSlots' => [], // Créneaux du samedi pour lesquels il existe au moins 1 rencontre
            'sunSlots' => [], // Créneaux du dimanche pour lesquels il existe au moins 1 rencontre
        ];

        $fromDate = Carbon::createFromFormat('Y-m-d', $competitionRecord->get('home_meetings_from_date'));
        $toDate = Carbon::createFromFormat('Y-m-d', $competitionRecord->get('home_meetings_to_date'));

        // Pour la date de départ, on compare avec la date actuelle, afin de ne pas
        // afficher les rencontres antérieures au mois en cours
        $firstDayOfCurrentMonth = Carbon::now();
        $firstDayOfCurrentMonth->day(1);

        if ($firstDayOfCurrentMonth->greaterThan($fromDate)) {
            $fromDate = $firstDayOfCurrentMonth;
        }

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
