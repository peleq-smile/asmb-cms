<?php

namespace Bundle\Asmb\Competition;

use Bolt\Asset\File\Stylesheet;
use Bolt\Extension\DatabaseSchemaTrait;
use Bolt\Extension\SimpleExtension;
use Bolt\Menu\MenuEntry;
use Bundle\Asmb\Competition\Database\Schema\Table;
use Silex\Application;
use Silex\ControllerCollection;

/**
 * Asmb Competition bundle extension loader.
 *
 * @see https://docs.bolt.cm/3.6/extensions/advanced/storage-repositories
 */
class CompetitionExtension extends SimpleExtension
{
    use DatabaseSchemaTrait;

    /**
     * {@inheritdoc}
     */
    protected function registerExtensionTables()
    {
        return [
            'championship'           => Table\Championship::class,
            'championship_category'  => Table\ChampionshipCategory::class,
            'championship_match'     => Table\ChampionshipMatch::class,
            'championship_pool'      => Table\ChampionshipPool::class,
            'championship_pool_day'  => Table\ChampionshipPoolDay::class,
            'championship_pool_team' => Table\ChampionshipPoolTeam::class,
            'championship_team'      => Table\ChampionshipTeam::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        return [
            '/extensions/championship' => new Controller\Backend\ChampionshipController(),
            '/extensions/match'        => new Controller\Backend\MatchController(),
            '/extensions/team'         => new Controller\Backend\TeamController(),
            '/extensions/pool'         => new Controller\Backend\PoolController(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerMenuEntries()
    {
        $menu = MenuEntry::create('championship-menu', 'championship')
            ->setLabel('Championnats')
            ->setIcon('fa:trophy')
            ->setPermission('settings');

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
            'championship'          => [Entity\Championship::class => Repository\ChampionshipRepository::class],
            'championship_category'  => [Entity\Championship\Category::class => Repository\Championship\CategoryRepository::class],
            'championship_match'     => [Entity\Championship\Match::class => Repository\Championship\MatchRepository::class],
            'championship_pool'      => [Entity\Championship\Pool::class => Repository\Championship\PoolRepository::class],
            'championship_pool_day'  => [Entity\Championship\PoolDay::class => Repository\Championship\PoolDayRepository::class],
            'championship_pool_team' => [Entity\Championship\PoolTeam::class => Repository\Championship\PoolTeamRepository::class],
            'championship_team'      => [Entity\Championship\Team::class => Repository\Championship\TeamRepository::class],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerServices(Application $app)
    {
        $this->extendDatabaseSchemaServices();
        $this->extendRepositoryMapping();
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
}
