<?php

namespace Bundle\Asmb\Common\Service;

use Swift_Message;
use Swift_Mime_SimpleMessage;

/**
 * Service d'envoi de mails.
 *
 * @copyright 2022
 */
class MailSender
{
    private $config;
    /** @var \Swift_Mailer */
    private $swiftMailer;

    private $transport;

    private $spoolTransport;

    private $twig;

    public function __construct($config, $swiftMailer, $transport, $spoolTransport, $twig)
    {
        $this->config = $config;
        $this->swiftMailer = $swiftMailer;
        $this->transport = $transport;
        $this->spoolTransport = $spoolTransport;
        $this->twig = $twig;
    }

    protected function getConfig(string $name)
    {
        return $this->config->get($name);
    }

    public function sendContactMail(
        string $contactEmail,
        string $contactName,
        string $contactSubject,
        string $contactMessage
    ): ?string
    {
        $errorMessage = null;

        // Contenu du mail
        $mailHtml = $this->twig->render(
            '@AsmbCommon/email/contact.twig',
            [
                'contactMail' => $contactEmail,
                'contactName' => $contactName,
                'subject' => $contactSubject,
                'message' => $contactMessage,
            ]
        );

        $subjectPrefix = $this->getConfig('general/mailoptions/contactSubjectPrefix');
        $mailSubject = $subjectPrefix . ' ' . $contactSubject;

        $senderEmail = $this->getConfig('general/mailoptions/senderMail');
        $receiverEmail = $this->getConfig('general/mailoptions/contactReceiverEmail');

        try {
            /** @var Swift_Message $message */
            $message = $this->swiftMailer
                ->createMessage()
                ->setSubject($mailSubject)
                ->setFrom($senderEmail)
                ->setTo($receiverEmail)
                ->setReplyTo($contactEmail)
                ->setBody($mailHtml, 'text/html')
                ->addPart(preg_replace('/^[\t ]+|[\t ]+$/m', '', strip_tags($mailHtml)), 'text/plain')
                ->setPriority(Swift_Mime_SimpleMessage::PRIORITY_NORMAL);

            $failedRecipients = [];
            $this->swiftMailer->send($message, $failedRecipients);
            $this->spoolTransport->getSpool()->flushQueue($this->transport);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        return $errorMessage;
    }
}