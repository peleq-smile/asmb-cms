<?php

namespace Bundle\Asmb\Competition;

use Bolt\Extension\DatabaseSchemaTrait;
use Bolt\Extension\SimpleExtension;
use Bolt\Menu\MenuEntry;
use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Common\Extension\TwigBackendTrait;
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
    use TwigBackendTrait;

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
     * Retourne les paramètres "nb de jours passés" et "nb de jours à venir" pour la remontée des rencontres du moment.
     *
     * @return integer[]
     */
    protected function getMeetingsParameters()
    {
        /** @var \Bolt\Storage\Query\Query $query */
        $query = $this->container['query'];
        /** @var \Bolt\Storage\Entity\Content $content */
        $content = $query->getContent('homepage', ['returnsingle' => true]);

        return [
            'meetings_past_days' => $content->get('meetings_past_days'),
            'meetings_future_days' => $content->get('meetings_future_days'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        // On récupère le nb de jours passés et à venir contribués dans le contenu "Page d'accueil"
        $meetingsParameters = $this->getMeetingsParameters();

        return [
            '/extensions/championship'               => new Controller\Backend\ChampionshipController(),
            '/extensions/championship/pool'          => new Controller\Backend\Championship\PoolController(),
            '/extensions/championship/pool/meetings' => new Controller\Backend\Championship\PoolMeetingsController($meetingsParameters),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerMenuEntries()
    {
        $permission = 'competition';

        /** @var \Bolt\Users $usersService */
        $menu = MenuEntry::create('competition-menu', 'championship')
            ->setRoute('championship')
            ->setLabel(Trans::__('general.phrase.manage-competition'))
            ->setIcon('fa:trophy')
            ->setPermission($permission);

        $submenuChampionship = MenuEntry::create('championship-submenu')
            ->setLabel(Trans::__('general.phrase.championship'))
            ->setIcon('fa:users')
            ->setPermission($permission);
        $submenuMeetingsOfTheMoment = MenuEntry::create('meetings-of-the-moment-submenu', 'pool/meetings')
            ->setLabel(Trans::__('general.phrase.meetings-of-the-moment'))
            ->setIcon('fa:calendar')
            ->setPermission($permission);

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
     * {@inheritdoc}
     */
    protected function registerNutCommands(Container $container)
    {
        return [
            new Nut\RefreshCommand($container),
        ];
    }
}
