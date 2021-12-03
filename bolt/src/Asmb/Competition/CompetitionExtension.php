<?php

namespace Bundle\Asmb\Competition;

use Bolt\Extension\DatabaseSchemaTrait;
use Bolt\Extension\SimpleExtension;
use Bolt\Menu\MenuEntry;
use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Common\Extension\TwigBackendTrait;
use Bundle\Asmb\Competition\Database\Schema\Table;
use Bundle\Asmb\Competition\Entity;
use Bundle\Asmb\Competition\Extension\TwigFiltersTrait;
use Bundle\Asmb\Competition\Extension\TwigFunctionsTrait;
use Bundle\Asmb\Competition\Guesser\PoolTeamsGuesser;
use Bundle\Asmb\Competition\Parser\Championship\PoolMeetingsParser;
use Bundle\Asmb\Competition\Parser\Championship\PoolRankingParser;
use Bundle\Asmb\Competition\Parser\Championship\PoolTeamsParser;
use Bundle\Asmb\Competition\Parser\Tournament\DbParser;
use Bundle\Asmb\Competition\Parser\Tournament\JaTennisJsonParser;
use Bundle\Asmb\Competition\Parser\Tournament\JaTennisJsParser;
use Pimple as Container;
use Silex\Application;

/**
 * Asmb Competition bundle extension loader.
 *
 * @see https://docs.bolt.cm/3.6/extensions/advanced/storage-repositories
 */
class CompetitionExtension extends SimpleExtension
{
    use DatabaseSchemaTrait;

    use TwigFiltersTrait;
    use TwigFunctionsTrait;
    use TwigBackendTrait;

    /**
     * {@inheritdoc}
     */
    protected function registerExtensionTables(): array
    {
        return [
            'championship'              => Table\Championship::class,
            'championship_category'     => Table\ChampionshipCategory::class,
            'championship_pool'         => Table\ChampionshipPool::class,
            'championship_pool_meeting' => Table\ChampionshipPoolMeeting::class,
            'championship_pool_ranking' => Table\ChampionshipPoolRanking::class,
            'championship_pool_team'    => Table\ChampionshipPoolTeam::class,
            'tournament'                => Table\Tournament::class,
            'tournament_table'          => Table\TournamentTable::class,
            'tournament_box'            => Table\TournamentBox::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers(): array
    {
        // On récupère le nb de jours passés et à venir contribués dans le contenu "Page d'accueil"
        $meetingsParameters = $this->getMeetingsParameters();

        return [
            '/extensions/competition/championship'               => new Controller\Backend\ChampionshipController(),
            '/extensions/competition/championship/pool'          => new Controller\Backend\Championship\PoolController(),
            '/extensions/competition/championship/pool/meetings' => new Controller\Backend\Championship\PoolMeetingsController($meetingsParameters),
            '/extensions/competition/tournament'                 => new Controller\Backend\TournamentController(),
            '/extensions/competition/tournament/table'           => new Controller\Backend\Tournament\TableController(),
            '/extensions/competition/tournament/box'             => new Controller\Backend\Tournament\BoxController(),
        ];
    }

    /**
     * Retourne les paramètres "nb de jours passés" et "nb de jours à venir" pour la remontée des rencontres du moment.
     *
     * @return integer[]
     */
    protected function getMeetingsParameters(): array
    {
        /** @var \Bolt\Storage\Query\Query $query */
        $query = $this->container['query'];
        /** @var \Bolt\Storage\Entity\Content $content */
        $content = $query->getContent('homepage', ['returnsingle' => true]);

        return [
            'meetings_past_days'   => $content->get('meetings_past_days'),
            'meetings_future_days' => $content->get('meetings_future_days'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerMenuEntries(): array
    {
        $permission = 'competition';

        // Entrée du menu "Compétitions" : Championnats
        /** @var \Bolt\Users $usersService */
        $menuChampionship = MenuEntry::create('championship-menu')
            ->setRoute('championship')
            ->setLabel(Trans::__('general.phrase.championships'))
            ->setIcon('fa:flag')
            ->setPermission($permission);

        $submenuListChampionship = MenuEntry::create('championship-list-submenu')
            ->setLabel(Trans::__('general.phrase.championships-list'))
            ->setIcon('fa:list')
            ->setPermission($permission);

        $submenuMeetingsOfTheMoment = MenuEntry::create('meetings-of-the-moment-submenu', 'pool/meetings')
            ->setLabel(Trans::__('general.phrase.meetings-of-the-moment'))
            ->setIcon('fa:calendar')
            ->setPermission($permission);

        $menuTournament = MenuEntry::create('tournament-menu')
            ->setRoute('tournament')
            ->setLabel(Trans::__('general.phrase.tournaments'))
            ->setIcon('fa:sitemap')
            ->setPermission($permission);

        $menuChampionship->add($submenuListChampionship);
        $menuChampionship->add($submenuMeetingsOfTheMoment);

        return [
            $menuChampionship,
            $menuTournament
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerRepositoryMappings(): array
    {
        return [
            'championship'              => [Entity\Championship::class => Repository\ChampionshipRepository::class],
            'championship_category'     => [Entity\Championship\Category::class => Repository\Championship\CategoryRepository::class],
            'championship_pool'         => [Entity\Championship\Pool::class => Repository\Championship\PoolRepository::class],
            'championship_pool_meeting' => [Entity\Championship\PoolMeeting::class => Repository\Championship\PoolMeetingRepository::class],
            'championship_pool_ranking' => [Entity\Championship\PoolRanking::class => Repository\Championship\PoolRankingRepository::class],
            'championship_pool_team'    => [Entity\Championship\PoolTeam::class => Repository\Championship\PoolTeamRepository::class],
            'tournament'                => [Entity\Tournament::class => Repository\TournamentRepository::class],
            'tournament_table'          => [Entity\Tournament\Table::class => Repository\Tournament\TableRepository::class],
            'tournament_box'            => [Entity\Tournament\Box::class => Repository\Tournament\BoxRepository::class],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerServices(Application $app)
    {
        $this->extendDatabaseSchemaServices();
        $this->extendRepositoryMapping();

        $app['pool_meetings_parser'] = $app->share(
            function () {
                return new PoolMeetingsParser();
            }
        );
        $app['pool_ranking_parser'] = $app->share(
            function () {
                return new PoolRankingParser();
            }
        );
        $app['pool_teams_guesser'] = $app->share(
            function () {
                return new PoolTeamsGuesser();
            }
        );
        $app['pool_teams_parser'] = $app->share(
            function () {
                return new PoolTeamsParser();
            }
        );
        $app['ja_tennis_json_parser'] = $app->share(
            function () {
                return new JaTennisJsonParser();
            }
        );
        $app['ja_tennis_js_parser'] = $app->share(
            function () {
                return new JaTennisJsParser();
            }
        );

        $app['tournament_db_parser'] = $app->share(
            function ($app) {
                $tournamentRepository = $app['storage']->getRepository('tournament');
                $tournamentTableRepository = $app['storage']->getRepository('tournament_table');
                $tournamentBoxRepository = $app['storage']->getRepository('tournament_box');

                return new DbParser($tournamentRepository, $tournamentTableRepository, $tournamentBoxRepository);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths(): array
    {
        /** @see https://stackoverflow.com/questions/52754936/overwrite-backend-template-in-bolt-cms */
        if ($this->getEnd() == 'backend') {
            return [
                'view/backend'      => ['position' => 'prepend', 'namespace' => 'bolt'],
                'templates/backend' => ['namespace' => 'AsmbCompetition'],
            ];
        }

        return [
            'templates' => ['namespace' => 'AsmbCompetition'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerNutCommands(Container $container): array
    {
        return [
            new Nut\RefreshCommand($container),
            new Nut\TestParseCommand($container),
        ];
    }
}
