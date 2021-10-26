<?php

namespace Bundle\Asmb\Common\Controller;

use Bolt\Application;
use Bolt\Controller\Base;
use Bolt\Events\AccessControlEvent;
use Bundle\Asmb\Common\Form\FormType;
use Silex\ControllerCollection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Storage\Entity;
use Symfony\Component\Form\Form;
use Bolt\Translation\Translator as Trans;

/**
 * Contrôleur qui gère la connexion au BO depuis le Front.
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2019
 */
class AuthenticationController extends Base
{
    /**
     * Affiche et gère la soumission du formulaire de connexion au BO depuis le Front.
     *
     * @param \Bolt\Application                         $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @see \Bolt\Controller\Backend\Authentication::getLogin
     * @throws \Bolt\Exception\AccessControlException
     */
    public function login(Application $app, Request $request)
    {
        if ($this->getOption('general/enforce_ssl') && !$request->isSecure()) {
            return $this->redirect(preg_replace('/^http:/i', 'https:', $request->getUri()));
        }

        $userEntity = new Entity\Users();
        // Generate the form
        $form = $this->createFormBuilder(FormType\UserLoginType::class, $userEntity)
            ->getForm()
            ->handleRequest($request);

        /** @var Form $form */
        if ($form->isSubmitted() && $form->isValid()) {
            $response = $this->handlePostLogin($request, $form);
            if ($response instanceof Response) {
                return $response;
            }
        }

        $context = [
            'form'        => $form->createView(),
            'randomquote' => false,
        ];

        return $this->render('@AsmbCommon/authentication/login.twig', $context);
    }

    /**
     * Handle a login POST.
     *
     * @param Request       $request
     * @param FormInterface $form
     *
     * @return Response
     * @throws \Bolt\Exception\AccessControlException
     */
    protected function handlePostLogin(Request $request, FormInterface $form)
    {
        $event = new AccessControlEvent($request);
        $username = $form->get('username')->getData();
        $password = $form->get('password')->getData();

        if (!$this->getLoginService()->login($username, $password, $event)) {
            return null;
        }

        // Authentication data is cached in the session and if we can't get it
        // now, everyone is going to have a bad day. Make that obvious.
        if (!$token = $this->session()->get('authentication')) {
            $this->flashes()->error(Trans::__('general.phrase.error-session-data-login'));

            return null;
        }

        // Log in, if credentials are correct.
        $this->app['logger.system']->info('Logged in: ' . $username, ['event' => 'authentication']);

        $retreat = $this->session()->get('retreat', ['route' => 'dashboard', 'params' => []]);
        $response = $this->setAuthenticationCookie(
            $request,
            $this->redirectToRoute($retreat['route'], $retreat['params']),
            (string) $token
        );

        return $response;
    }

    /**
     * Returns the Login object.
     *
     * @return \Bolt\AccessControl\Login
     */
    protected function getLoginService()
    {
        return $this->app['access_control.login'];
    }

    /**
     * Set the authentication cookie in the response.
     *
     * @param Request  $request
     * @param Response $response
     * @param string   $token
     *
     * @return Response
     */
    protected function setAuthenticationCookie(Request $request, Response $response, $token)
    {
        $response->setVary('Cookies', false)->setMaxAge(0)->setPrivate();
        $response->headers->setCookie(
            new Cookie(
                $this->app['token.authentication.name'],
                $token,
                time() + $this->getOption('general/cookies_lifetime'),
                $request->getBasePath(),
                $this->getOption('general/cookies_domain'),
                $this->getOption('general/enforce_ssl'),
                true
            )
        );

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function addRoutes(ControllerCollection $c)
    {
        $c->match('/', [$this, 'login']);

        return $c;
    }
}
