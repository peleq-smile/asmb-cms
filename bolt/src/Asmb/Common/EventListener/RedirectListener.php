<?php
namespace Bundle\Asmb\Common\EventListener;

use Bolt\AccessControl\AccessChecker;
use Bolt\Users;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RedirectListener implements EventSubscriberInterface
{
    /** @var \Symfony\Component\HttpFoundation\Session\Session */
    protected $session;
    /** @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface */
    protected $urlGenerator;
    /** @var \Bolt\Users */
    protected $users;
    /** @var \Bolt\AccessControl\AccessChecker $authentication */
    protected $authentication;

    public function __construct(
        Session $session,
        UrlGeneratorInterface $urlGenerator,
        Users $users,
        AccessChecker $authentication
    ) {
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
        $this->users = $users;
        $this->authentication = $authentication;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        if (!$response->isRedirect() || !$response instanceof RedirectResponse) {
            return;
        }

        if ('postLogin' === $request->get('_route')) {
            $this->handleRedirectToEditScores($response);
        }
    }

    private function handleRedirectToEditScores(RedirectResponse $response)
    {
        $authCookie = $this->session->get('authentication');
        if ($authCookie === null || !$this->authentication->isValidSession($authCookie)) {
            return;
        }

        /** @var \Bolt\Storage\Entity\Users $user */
        $user = $authCookie->getUser();
        $mainRole = $user->getRoles()[0];
        if ('scoresEditor' === $mainRole) {
            $editScoresPath = $this->urlGenerator->generate('tournamenteditscores', ['id' => null]);
            $response->setTargetUrl($editScoresPath);
        }
    }
}