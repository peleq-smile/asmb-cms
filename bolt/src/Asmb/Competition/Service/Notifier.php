<?php

namespace Bundle\Asmb\Competition\Service;

use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Silex\Application;
use Bundle\Asmb\Competition\Helpers\DateHelper;
use Carbon\Carbon;
use Composer\Downloader\TransportException;
use Swift_Message;
use Swift_Mime_SimpleMessage;

/**
 * Service d'envoi d'une notification des prochaines rencontres.
 *
 * @copyright 2022
 */
class Notifier
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getSoonPoolMeetings(): array
    {
        /** @var \Bolt\Storage\EntityManagerInterface $storage */
        $storage = $this->app['storage'];

        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $storage->getRepository('championship_pool_meeting');
        [$pastDays, $futureDays] = $this->getPastAndFutureDays();

        return $poolMeetingRepository->findClubMeetingsOfTheMoment(
            $pastDays,
            $futureDays,
            false,
            true,
            true
        );
    }

    public function notify(string $mailTo, array $poolMeetings)
    {
        if (!empty($poolMeetings)) {
            /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository $poolMeetingRepository */
            $poolMeetingRepository = $this->app['storage']->getRepository('championship_pool_meeting');

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
            /** @var Carbon $toDate */
            $toDate = $poolMeetings[count($poolMeetings) - 1]->getFinalDate();

            if ($toDate->format('Y-m-d') === $fromDate->format('Y-m-d')) {
                $subject.= ' du ' . $fromDate->format('d/m/y');
                $toDate = null;
            } else {
                $subject.= ' du ' . $fromDate->format('d/m/y') . ' au ' . $toDate->format('d/m/y');
            }
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
            /** @var Swift_Message $message */
            $message = $mailer
                ->createMessage()
                ->setSubject($subject)
                ->setFrom($from)
                ->setTo($mailTo)
                ->setBody($mailHtml, 'text/html')
                ->addPart(preg_replace('/^[\t ]+|[\t ]+$/m', '', strip_tags($mailHtml)), 'text/plain')
                ->setPriority(Swift_Mime_SimpleMessage::PRIORITY_HIGH);
        } catch (\Swift_RfcComplianceException $e) {
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

    protected function getPastAndFutureDays()
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