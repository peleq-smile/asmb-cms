<?php

namespace Bundle\Asmb\Competition\Nut;

use Bolt\Nut\BaseCommand;
use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Service\Notifier;
use Carbon\Carbon;
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

        /** @var Notifier $notifier */
        $notifier = $this->app['notifier'];

        // Récupération des rencontres à domicile prochaines
        $poolMeetings = $notifier->getSoonPoolMeetings();

        // Si toutes les rencontres ont déjà été notifiées, on ne fait rien.
        $abort = true;
        foreach ($poolMeetings as $poolMeeting) {
            if (empty($poolMeeting->getTime()) && !Carbon::today()->isFriday()) {
                // si une seule rencontre parmi celles remontées n'a pas encore d'horaire et qu'on n'est pas vendredi,
                // on laisse tomber (un mail sera renvoyé le lendemain)
                $abort = true;
                break;
            }
            if (PoolMeeting::STATUS_NOTIFY_TODO === $poolMeeting->getNotifyStatus()) {
                $abort = false;
            }
        }

//        if (!$abort) {
            $notifier->notify($mailTo, $poolMeetings);
//        }

        return 0;
    }
}
