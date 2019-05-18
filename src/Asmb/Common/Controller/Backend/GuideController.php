<?php

namespace Bundle\Asmb\Common\Controller\Backend;

use Bolt\Controller\Backend\BackendBase;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for Pool routes.
 *
 * @copyright 2019
 */
class GuideController extends BackendBase
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        /** @var \Bolt\Response\TemplateView $templateView */
        $templateView = $this->render('@AsmbCommon/guide/_content.md');

        // Transforme le markdown en HTML
        $markdownContent = $this->app['twig']->render($templateView->getTemplate(), $templateView->getContext()->toArray());
        $htmlContent = $this->app['markdown']->text($markdownContent);

        return $this->render(
            '@AsmbCommon/guide.twig',
            [],
            [
                'content' => $htmlContent,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function addRoutes(ControllerCollection $c)
    {
        $c->match('/', 'index')
            ->bind('guide');

        return $c;
    }
}
