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

        die($html);
    }

    /**
     * Retrieve current online visitor(s) count.
     *
     * @return int
     */
    protected function getCurrentVisitorsCount()
    {
        $this->updateCurrentVisitorsCount();

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
