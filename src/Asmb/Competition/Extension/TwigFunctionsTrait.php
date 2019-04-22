<?php

namespace Bundle\Asmb\Competition\Extension;

use Bundle\Asmb\Competition\Entity\Championship\PoolMeeting;
use Bundle\Asmb\Competition\Helpers\PoolMeetingHelper;
use Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository;

/**
 * Déclaration de fonctions Twig.
 */
trait TwigFunctionsTrait
{
    /**
     * Retourne les dernières rencontres du club.
     *
     * @return \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getLastMeetings()
    {
        $config = $this->getConfig();
        $pastDays = -1 * $config['asmb']['competition']['last_meetings_past_days'];

        return $this->getLastOrNextMeetings($pastDays);
    }

    /**
     * Retourne les prochaines rencontres du club.
     *
     * @return \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    public function getNextMeetings()
    {
        $config = $this->getConfig();
        $futureDays = $config['asmb']['competition']['next_meetings_future_days'];

        return $this->getLastOrNextMeetings($futureDays);
    }

    /**
     * Retourne les rencontres du moment, dans le passé ou le futur selon que $pastOrFutureDays soit négatif (passé)
     * ou positif (futur).
     *
     * @param int $pastOrFutureDays
     *
     * @return \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting[]
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    protected function getLastOrNextMeetings($pastOrFutureDays)
    {
        /** @var \Bolt\Application $app */
        $app = $this->getContainer();
        /** @var \Bolt\Storage\EntityManagerInterface $storage */
        $storage = $app['storage'];
        /** @var PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $storage->getRepository('championship_pool_meeting');
        $pastDays = ($pastOrFutureDays < 0) ? (-1 * $pastOrFutureDays) : 0;
        $futureDays = ($pastOrFutureDays > 0) ? $pastOrFutureDays : 0;
        $meetingsOfTheMoment = $poolMeetingRepository->findClubMeetingsOfTheMoment($pastDays, $futureDays);

        return $meetingsOfTheMoment;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        return [
            'getLastMeetings' => 'getLastMeetings',
            'getNextMeetings' => 'getNextMeetings',
        ];
    }
}
