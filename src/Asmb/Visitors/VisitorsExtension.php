<?php
namespace Bundle\Asmb\Visitors;

use Silex\Application;
use Bolt\Exception\InvalidRepositoryException;
use Bolt\Extension\DatabaseSchemaTrait;
use Bolt\Extension\SimpleExtension;
use Bundle\Asmb\Visitors\Entity;
use Bundle\Asmb\Visitors\Repository;
use Bundle\Asmb\Visitors\Database\Schema\Table;

/**
 * Asmb Visitors bundle extension loader.
 */
class VisitorsExtension extends SimpleExtension
{
    use DatabaseSchemaTrait;

    /**
     * Update current visitors count.
     *
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function updateCurrentVisitorsCount()
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $currentIp = $_SERVER['REMOTE_ADDR'];
            $httpUserAgent = $_SERVER['HTTP_USER_AGENT'];

            /** @var \Bundle\Asmb\Visitors\Repository\VisitorRepository $visitorRepo */
            $visitorRepo = $this->getStorageEntityManager()->getRepository('visitor');

            $visitor = new Entity\Visitor();
            $visitor->setIp($currentIp);
            $visitor->setHttpUserAgent($httpUserAgent);

            $visitorRepo->addOrUpdateVisitor($visitor);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function registerAssets()
    {
        // Widget qui màj le compteur de visiteurs
        $widgetUpdateVisitorsCount = new \Bolt\Asset\Widget\Widget();
        $widgetUpdateVisitorsCount->setZone('frontend');
        $widgetUpdateVisitorsCount->setLocation('footer');
        $widgetUpdateVisitorsCount->setPriority(1);
        $widgetUpdateVisitorsCount->setCacheDuration(Repository\VisitorRepository::$expirationTime);
        $widgetUpdateVisitorsCount->setCallback([$this, 'updateCurrentVisitorsCount']);

        // Widget qui affiche le nombre de visiteurs
        $widgetGetVisitors = new \Bolt\Asset\Widget\Widget();
        $widgetGetVisitors->setClass('getvisitors');
        $widgetGetVisitors->setZone('frontend');
        $widgetGetVisitors->setLocation('footer');
        $widgetGetVisitors->setPriority(2);
        $widgetUpdateVisitorsCount->setCacheDuration(Repository\VisitorRepository::$expirationTime);
        $widgetGetVisitors->setCallback([$this, 'getCurrentVisitorsCountHtml']);

        return [$widgetUpdateVisitorsCount, $widgetGetVisitors];
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
        return ['visitor' => Table\Visitor::class];
    }

    /**
     * Return html content to display current online visitor(s) count.
     *
     * @return string
     */
    public function getCurrentVisitorsCountHtml()
    {
        $html = '';

        $count = $this->getCurrentVisitorsCount();
        if ($count > 0) {
            $label = 'visiteurs';

            if (1 === $count) {
                $label = 'visiteur';
            }

            $html = "EN LIGNE :<br>$count $label";
        }

        return $html;
    }

    /**
     * Retrieve current online visitor(s) count.
     *
     * @return int
     */
    protected function getCurrentVisitorsCount()
    {
        try {
            $visitorRepo = $this->getStorageEntityManager()->getRepository('visitor');
            $visitors = $visitorRepo->findBy(['isActive' => 1]);
            $count = count($visitors);
        } catch (InvalidRepositoryException $e) {
            $count = 0;
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerRepositoryMappings()
    {
        return [
            'visitor' => [Entity\Visitor::class => Repository\VisitorRepository::class],
        ];
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

