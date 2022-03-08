<?php

namespace Bundle\Asmb\Competition\Nut;

use Bolt\Nut\BaseCommand;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Helpers\DateHelper;
use Carbon\Carbon;
use Composer\Downloader\TransportException;
use Swift_Message as Message;
use Swift_Mime_SimpleMessage;
use Swift_RfcComplianceException as RfcComplianceException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Commande Nut pour notifier des rencontres à domicile des prochains jours.
 * Permettra de prévenir l'administrateur BJ de réserver les créneaux !
 *
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2022
 */
class NotifyCommand extends BaseCommand
{
    protected $isQuietMode = false;

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->isQuietMode = (bool)$input->getOption('quiet');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('asmb:competition:notify')
            ->setDescription('Notifier des rencontres à domicile aux horaires connus !')
            ->addArgument('email', InputArgument::REQUIRED, 'email de réception');
    }

    /**
     * {@inheritdoc}
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailTo = $input->getArgument('email');

        /** @var \Bolt\Storage\EntityManagerInterface $storage */
        $storage = $this->app['storage'];

        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $storage->getRepository('championship_pool_meeting');

        // Récupération des rencontres à domicile prochaines

        [$pastDays, $futureDays] = $this->getPastAndFutureDays();
        $poolMeetings = $poolMeetingRepository->findClubMeetingsOfTheMoment($pastDays, $futureDays, false, true, true);

        // TODO amériorer
        foreach ($poolMeetings as $idx => $poolMeeting) {
            if (PoolMeeting::STATUS_NOTIFY_DONE === $poolMeeting->getNotifyStatus()) {
                unset($poolMeetings[$idx]);
            }
        }

        if (!empty($poolMeetings)) {
            $mailTo = explode(',', $mailTo);
            $error = $this->sendMail($mailTo, $poolMeetings);

            if (empty($error)) {
                foreach ($poolMeetings as $poolMeeting) {
                    $poolMeeting->setNotifyStatus(PoolMeeting::STATUS_NOTIFY_DONE);
                    $poolMeetingRepository->save($poolMeeting);
                }
            }
        }
    }

    protected function sendMail(array $mailTo, array $poolMeetings): ?string
    {
        /** @var \Swift_Mailer $mailer */
        $mailer = $this->app['mailer'];
        $transport = $this->app['swiftmailer.spooltransport'];

        $name = $this->getConfig('general/mailoptions/senderName');
        $sendermail = $this->getConfig('general/mailoptions/senderMail');
        $from = [$sendermail => $name];
        $subject = '[ASMB] Rencontres à domicile';

        /** @var Carbon $fromDate */
        $fromDate = $poolMeetings[0]->getFinalDate();
        $toDate = null;
        if (count($poolMeetings) > 1) {
            $toDate = $poolMeetings[count($poolMeetings) - 1]->getFinalDate();
            $subject.= ' du ' . $fromDate->format('d/m/y') . ' au ' . $toDate->format('d/m/y');
        } else {
            $subject.= ' du ' . $fromDate->format('d/m/y');
        }

        // Contenu du mail
        $mailHtml = $this->app['twig']->render(
            '@AsmbCompetition/email/soon_home_meetings.twig',
            [
                'poolMeetings' => $poolMeetings,
                'fromDateFormatted' => DateHelper::formatWithLocalizedDayAndMonth($fromDate, false),
                'toDateFormatted' => DateHelper::formatWithLocalizedDayAndMonth($toDate, false),
            ]
        );

        try {
            /** @var Message $message */
            $message = $mailer
                ->createMessage('message')
                ->setSubject($subject)
                ->setFrom($from)
                ->setTo($mailTo)
                ->setBody($mailHtml, 'text/html')
                ->addPart(preg_replace('/^[\t ]+|[\t ]+$/m', '', strip_tags($mailHtml)), 'text/plain')
                ->setPriority(Swift_Mime_SimpleMessage::PRIORITY_HIGH);
        } catch (RfcComplianceException $e) {
            return "The email address set in 'mailoptions/senderMail' is not a valid email address.";
        }

        try {
            // Try and send immediately
            $failedRecipients = [];
            $mailer->send($message, $failedRecipients);
            $transport->getSpool()->flushQueue($this->app['swiftmailer.transport']);
        } catch (TransportException $e) {
            // Sending message failed. What else can we do, send via snailmail?
            return "The 'mailoptions' need to be set in app/config/config.yml";
        }

        return '';
    }

    protected function getConfig(string $name)
    {
        return $this->app['config']->get($name);
    }

    private function getPastAndFutureDays()
    {
        $pastAndFutureDays = [];

        $todayDay = Carbon::today()->dayOfWeek;
        switch ($todayDay) {
            case 0: // dimanche
                $pastAndFutureDays = [-8, 14];
                break;
            case 1: // lundi
                $pastAndFutureDays = [-7, 13];
                break;
            case 2: // mardi
                $pastAndFutureDays = [-6, 12];
                break;
            case 3: // mercredi
                $pastAndFutureDays = [-5, 8];
                break;
            case 4: // jeudi
                $pastAndFutureDays = [-5, 8];
                break;
            case 5: // vendredi
                $pastAndFutureDays = [-5, 8];
                break;
            case 6: // samedi
                $pastAndFutureDays = [-5, 8];
                break;

            default:
                break;
        }

        return $pastAndFutureDays;
    }
}
