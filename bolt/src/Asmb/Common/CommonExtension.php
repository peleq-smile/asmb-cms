<?php

namespace Bundle\Asmb\Common;

use Bolt\Extension\SimpleExtension;
use Bundle\Asmb\Common\EventListener\RedirectListener;
use Bundle\Asmb\Common\Extension\TwigBackendTrait;
use Bundle\Asmb\Common\Extension\TwigFiltersTrait;
use Bundle\Asmb\Common\Extension\TwigFunctionsTrait;
use Bundle\Asmb\Common\Service\MailSender;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Asmb Common bundle extension loader.
 *
 * @see https://docs.bolt.cm/3.6/extensions/advanced/storage-repositories
 */
class CommonExtension extends SimpleExtension
{
    use TwigBackendTrait;
    use TwigFunctionsTrait;
    use TwigFiltersTrait;

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        /** @see https://stackoverflow.com/questions/52754936/overwrite-backend-template-in-bolt-cms */
        if ($this->getEnd() == 'backend') {
            return [
                'view/backend' => ['position' => 'prepend', 'namespace' => 'bolt'],
                'templates/backend' => ['namespace' => 'AsmbCommon'],
            ];
        }

        return [
            'templates' => ['namespace' => 'AsmbCommon'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerFrontendControllers()
    {
        return [
            '/acces-bureau' => new Controller\AuthenticationController(),
            '/page/contact' => new Controller\ContactController(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function subscribe(EventDispatcherInterface $dispatcher)
    {
        $app = $this->getContainer();

        $redirectListener = new RedirectListener(
            $app['session'],
            $app['url_generator.lazy'],
            $app['users'],
            $app['access_control']
        );
        $dispatcher->addListener(KernelEvents::RESPONSE, [$redirectListener, 'onResponse']);
    }

    /**
     * {@inheritdoc}
     */
    protected function registerServices(Application $app)
    {
        $app['mail_sender'] = $app->share(
            function () use ($app) {
                return new MailSender(
                    $app['config'],
                    $app['mailer'],
                    $app['swiftmailer.transport'],
                    $app['swiftmailer.spooltransport'],
                    $app['twig']
                );
            }
        );
    }
}
