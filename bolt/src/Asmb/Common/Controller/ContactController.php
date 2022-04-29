<?php

namespace Bundle\Asmb\Common\Controller;

use Bolt\Application;
use Bolt\Controller\Base;
use Bolt\Events\AccessControlEvent;
use Bundle\Asmb\Common\Form\FormType;
use Bundle\Asmb\Common\Service\MailSender;
use Silex\ControllerCollection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Storage\Entity;
use Symfony\Component\Form\Form;
use Bolt\Translation\Translator as Trans;

/**
 * Contrôleur pour le formulaire de contact
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2022
 */
class ContactController extends Base
{
    /**
     * {@inheritdoc}
     */
    protected function addRoutes(ControllerCollection $c)
    {
        $c->match('/soumission', 'submit')
            ->bind('contactsubmit');

        return $c;
    }

    public function submit(Application $app, Request $request)
    {
        $form = $this->createFormBuilder(FormType\ContactType::class)
            ->getForm()
            ->handleRequest($request);

        /** @var Form $form */
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var MailSender $mailSender */
            $mailSender = $app['mail_sender'];

            $errorMessage = $mailSender->sendContactMail(
                $form->get('contactEmail')->getData(),
                $form->get('contactName')->getData(),
                $form->get('contactSubject')->getData(),
                $form->get('contactMessage')->getData()
            );

            if (!empty($errorMessage)) {
                $this->flashes()->error('Votre message n\'as pas été envoyé.<br>Erreur : ' . $errorMessage);
            } else {
                $this->flashes()->success('Votre message a bien été envoyé, nous tâchons de vous répondre dans les meilleurs délais !<br><br>Le Bureau ASMB');
            }
        }

        return $this->redirectToRoute('contentlink', [
            'contenttypeslug' => 'page',
            'slug' => 'contact'
        ]);
    }
}
