<?php

namespace Bundle\Asmb\Competition;

use Bolt\Extension\DatabaseSchemaTrait;
use Bolt\Extension\SimpleExtension;
use Bolt\Menu\MenuEntry;
use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Database\Schema\Table;
use Bundle\Asmb\Competition\Extension\TwigFiltersTrait;
use Bundle\Asmb\Competition\Extension\TwigFunctionsTrait;
use Bundle\Asmb\Competition\Guesser\PoolTeamsGuesser;
use Bundle\Asmb\Competition\Parser\PoolMeetingsParser;
use Bundle\Asmb\Competition\Parser\PoolRankingParser;
use Bundle\Asmb\Competition\Parser\PoolTeamsParser;
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

    /**
     * {@inheritdoc}
     */
    protected function registerExtensionTables()
    {
        return [
            'championship'              => Table\Championship::class,
            'championship_category'     => Table\ChampionshipCategory::class,
            'championship_pool'         => Table\ChampionshipPool::class,
            'championship_pool_meeting' => Table\ChampionshipPoolMeeting::class,
            'championship_pool_ranking' => Table\ChampionshipPoolRanking::class,
            'championship_pool_team'    => Table\ChampionshipPoolTeam::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        $config = $this->getConfig();
        $asmbCompetitionConfig = $config['asmb']['competition'];

        return [
            '/extensions/competition/championship'  => new Controller\Backend\ChampionshipController($asmbCompetitionConfig),
            '/extensions/competition/pool'          => new Controller\Backend\PoolController($asmbCompetitionConfig),
            '/extensions/competition/pool/meetings' => new Controller\Backend\PoolMeetingsController($asmbCompetitionConfig),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerMenuEntries()
    {
        // TODO créer permission ?

        $menu = MenuEntry::create('competition-menu', 'competition')
            ->setLabel(Trans::__('general.phrase.competition'))
            ->setIcon('fa:trophy')
            ->setPermission('contentaction');

        $submenuChampionship = MenuEntry::create('championship-submenu', 'championship')
            ->setLabel(Trans::__('general.phrase.championship'))
            ->setIcon('fa:users');
        $submenuMeetingsOfTheMoment = MenuEntry::create('meetings-of-the-moment-submenu', 'pool/meetings')
            ->setLabel(Trans::__('general.phrase.meetings-of-the-moment'))
            ->setIcon('fa:calendar');

        $menu->add($submenuChampionship);
        $menu->add($submenuMeetingsOfTheMoment);

        return [
            $menu,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerRepositoryMappings()
    {
        return [
            'championship'              => [Entity\Championship::class => Repository\ChampionshipRepository::class],
            'championship_category'     => [Entity\Championship\Category::class => Repository\Championship\CategoryRepository::class],
            'championship_pool'         => [Entity\Championship\Pool::class => Repository\Championship\PoolRepository::class],
            'championship_pool_meeting' => [Entity\Championship\PoolMeeting::class => Repository\Championship\PoolMeetingRepository::class],
            'championship_pool_ranking' => [Entity\Championship\PoolRanking::class => Repository\Championship\PoolRankingRepository::class],
            'championship_pool_team'    => [Entity\Championship\PoolTeam::class => Repository\Championship\PoolTeamRepository::class],
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
            function ($app) {
                return new PoolMeetingsParser();
            }
        );
        $app['pool_ranking_parser'] = $app->share(
            function ($app) {
                return new PoolRankingParser();
            }
        );
        $app['pool_teams_guesser'] = $app->share(
            function ($app) {
                return new PoolTeamsGuesser();
            }
        );
        $app['pool_teams_parser'] = $app->share(
            function ($app) {
                return new PoolTeamsParser();
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
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
     * @return string
     */
    private function getEnd()
    {
        $backendPrefix = $this->container['config']->get('general/branding/path');
        $end = $this->container['config']->getWhichEnd();

        switch ($end) {
            case 'backend':
                return 'backend';
                break;
            case 'async':
                // we have async request
                // if the request begin with "/admin" (general/branding/path)
                // it has been made on backend else somewhere else
                $url = '/' . ltrim($_SERVER['REQUEST_URI'], $this->container['paths']['root']);
                $adminUrl = '/' . trim($backendPrefix, '/');
                if (strpos($url, $adminUrl) === 0) {
                    return 'backend';
                }
                break;
            default:
                break;
        }

        return $end;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerNutCommands(Container $container)
    {
        return [
            new Nut\RefreshCommand($container),
        ];
    }
}
