<?php

namespace Bundle\Asmb\Visitors;

use Bolt\Exception\InvalidRepositoryException;
use Bolt\Extension\DatabaseSchemaTrait;
use Bolt\Extension\SimpleExtension;
use Bundle\Asmb\Common\Extension\TwigBackendTrait;
use Bundle\Asmb\Visitors\Database\Schema\Table;
use Bundle\Asmb\Visitors\Entity;
use Bundle\Asmb\Visitors\Helpers\VisitorHelper;
use Bundle\Asmb\Visitors\Repository;
use Pimple as Container;
use Silex\Application;

/**
 * Asmb Visitors bundle extension loader.
 */
class VisitorsExtension extends SimpleExtension
{
    use DatabaseSchemaTrait;
    use TwigBackendTrait;

    /**
     * {@inheritdoc}
     */
    protected function registerAssets()
    {
        // Widget qui affiche le nombre de visiteurs
        $widgetGetVisitors = new \Bolt\Asset\Widget\Widget();
        $widgetGetVisitors->setClass('getvisitors');
        $widgetGetVisitors->setZone('frontend');
        $widgetGetVisitors->setLocation('footer');
        $widgetGetVisitors->setCacheDuration(1); // 1 second
        $widgetGetVisitors->setCallback([$this, 'getCurrentVisitorsCountHtml']);
        $widgetGetVisitors->setDefer(true);

        return [$widgetGetVisitors];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerServices(Application $app)
    {
        $this->extendDatabaseSchemaServices();
    }

    /**
     * {@inheritdoc}
     */
    protected function registerExtensionTables()
    {
        return [
            'visitor'            => Table\Visitor::class,
            'visitor_statistics' => Table\VisitorStatistics::class,
            'visit_statistics'   => Table\VisitStatistics::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        return [
            '/extensions/visitors' => new Controller\Backend\StatisticsController(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerRepositoryMappings()
    {
        return [
            'visitor'            => [Entity\Visitor::class => Repository\VisitorRepository::class],
            'visitor_statistics' => [Entity\VisitorStatistics::class => Repository\VisitorStatisticsRepository::class],
            'visit_statistics'   => [Entity\VisitStatistics::class => Repository\VisitStatisticsRepository::class],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        /** @see https://stackoverflow.com/questions/52754936/overwrite-backend-template-in-bolt-cms */
        if ($this->getEnd() == 'backend') {
            return [
                'templates/backend' => ['namespace' => 'AsmbVisitors'],
            ];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerNutCommands(Container $container)
    {
        return [
            new Nut\RefreshStatisticsCommand($container),
        ];
    }

    /**
     * Update current visitors count.
     *
     * @throws InvalidRepositoryException
     * @throws \Exception
     */
    public function updateCurrentVisitorsCount()
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $currentIp = $_SERVER['REMOTE_ADDR'];

            /** @var \Bundle\Asmb\Visitors\Repository\VisitorRepository $visitorRepo */
            $visitorRepo = $this->getStorageEntityManager()->getRepository('visitor');

            $visitor = new Entity\Visitor();
            $visitor->setIp($currentIp);

            // TODO à supprimer, à terme
            $visitor->setHttpUserAgent($_SERVER['HTTP_USER_AGENT']);

            // On loggue aussi le nom d'utilisateur
            $app = $this->getContainer();
            $currentUsername = $app['users']->getCurrentUserProperty('username');
            $visitor->setUsername($currentUsername);

            // On détecte le navigateur et la version (pour stats)
            list($browserName, $browserVersion) = VisitorHelper::getBrowserNameAndVersion();
            $visitor->setBrowserName($browserName);
            $visitor->setBrowserVersion($browserVersion);

            // On détecte l'OS et le terminal utilisé (pour stats)
            list($osName, $terminal) = VisitorHelper::getOsNameAndTerminal();
            $visitor->setOsName($osName);
            $visitor->setTerminal($terminal);

            // On enregistre la géolocalisation
            if (isset($_SERVER['GEOIP_CITY'])) {
                if (isset($_SERVER['GEOIP_COUNTRY_NAME']) && 'France' !== $_SERVER['GEOIP_COUNTRY_NAME']) {
                    $visitor->setGeolocalization(
                        $_SERVER['GEOIP_CITY'] . ' (' . $_SERVER['GEOIP_COUNTRY_NAME'] . ')'
                    );
                } else {
                    $visitor->setGeolocalization($_SERVER['GEOIP_CITY']);
                }
            }

            $visitorRepo->addOrUpdateVisitor($visitor);
        }
    }

    /**
     * Return html content to display current online visitor(s) count.
     *
     * @return string
     * @throws InvalidRepositoryException
     */
    public function getCurrentVisitorsCountHtml()
    {
        $this->updateCurrentVisitorsCount();

        /** @var Repository\VisitorRepository $visitorRepo */
        $visitorRepo = $this->getStorageEntityManager()->getRepository('visitor');
        $count = $visitorRepo->countCurrentVisitors();

        // On en profite pour mettre à jour le nombre max de visiteurs simultanés
        $this->getStorageEntityManager()->getRepository('visitor_statistics')->updateMaxSimultaneous($count);

        $label = 'visiteur';
        if ($count > 1) {
            $label = 'visiteurs';
        }
        $html = "EN LIGNE :<br>$count $label";

        die($html);
    }

    /**
     * Return storage entity manager.
     *
     * @return \Bolt\Storage\EntityManagerInterface
     */
    protected function getStorageEntityManager()
    {
        $app = $this->getContainer();

        return $app['storage'];
    }
}
