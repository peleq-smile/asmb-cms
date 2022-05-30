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
            // validation du captcha
            /** @see https://developers.google.com/recaptcha/docs/verify */
            $captchaToken = $request->request->get('g-recaptcha-response');
            if ($this->validCaptcha($request, $captchaToken)) {
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
            } else {
                $this->flashes()->error('Votre message n\'as pas été envoyé.');
            }
        }

        return $this->redirectToRoute('contentlink', [
            'contenttypeslug' => 'page',
            'slug' => 'contact'
        ]);
    }

    private function validCaptcha(Request $request, ?string $token): bool
    {
        if (null === $token ) {
            return false;
        }

        $fields = [
            'secret' => $this->getOption('general/recaptcha/secret_key'),
            'response' => $token,
            'remoteip' => $request->getClientIp(),
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->getOption('general/recaptcha/api_site_verify_url'),
            CURLOPT_CUSTOMREQUEST => $this->getOption('general/recaptcha/api_site_verify_method'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERAGENT => $request->headers->get('User-Agent'),
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $headers = [
            'Content-Type' => 'application/json',
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);
        $infos = curl_getinfo($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if (Response::HTTP_OK === $infos['http_code']) {
            $responseAsArray = json_decode($response, true);
            if (isset($responseAsArray['success'], $responseAsArray['score'])) {
                return $responseAsArray['success'] && $responseAsArray['score'] > 0.5;
            }
        }

        return false;
    }
}
