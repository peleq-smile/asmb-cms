<?php

namespace Bundle\Asmb\Competition\Controller\Backend\Championship;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Controller\Backend\AbstractController;
use Bundle\Asmb\Competition\Form\FormType\PoolMeetingsEditType;
use Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository;
use Silex\ControllerCollection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur pour les routes des rencontres.
 *
 * @copyright 2019
 */
class PoolMeetingsController extends AbstractController
{
    /** @var integer */
    private $meetingsPastDays;
    /** @var integer */
    private $meetingsFutureDays;

    /**
     * AbstractController constructor.
     *
     * @param integer[] $meetingsParameters
     */
    public function __construct($meetingsParameters)
    {
        $this->meetingsPastDays = $meetingsParameters['meetings_past_days'];
        $this->meetingsFutureDays = $meetingsParameters['meetings_future_days'];
    }

    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $c)
    {
        $c->match('/', 'index')
            ->bind('poolmeetings');

        $c->match('/edit', 'editFuture')
            ->bind('poolmeetingseditfuture');

        $c->match('/edit/past', 'editPast')
            ->bind('poolmeetingseditpast');

        return $c;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        return $this->render(
            '@AsmbCompetition/pool-meetings/index.twig',
            [],
            [
                'pastDays'     => $this->meetingsPastDays,
                'futureDays'   => $this->meetingsFutureDays,
                'lastMeetings' => $this->getPastOrFutureMeetings(-1 * $this->meetingsPastDays),
                'nextMeetings' => $this->getPastOrFutureMeetings($this->meetingsFutureDays),
            ]
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Bolt\Response\TemplateResponse|\Bolt\Response\TemplateView|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editFuture(Request $request)
    {
        $futureDays = 2 * 365; // On récupère les prochaines rencontres... sur 2 ans !

        $meetings = $this->getPastOrFutureMeetings($futureDays, false, true);

        $editForm = $this->buildEditForm($request, $meetings);
        if ($this->handleEditFormSubmit($request, $editForm)) {
            // We don't want to POST again data, so we redirect to current in GET route in case of submitted form
            // with success
            return $this->redirectToRoute('poolmeetingsedit');
        }

        $context = [
            'editForm' => $editForm->createView(),
        ];

        return $this->render(
            '@AsmbCompetition/pool-meetings/edit.twig',
            $context,
            [
                'meetings' => $meetings,
            ]
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Bolt\Response\TemplateResponse|\Bolt\Response\TemplateView|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editPast(Request $request)
    {
        $pastDays = -31; // On récupère les dernières rencontres sur 1 mois

        $meetings = $this->getPastOrFutureMeetings($pastDays, false, true);

        $editForm = $this->buildEditForm($request, $meetings);
        if ($this->handleEditFormSubmit($request, $editForm)) {
            return $this->redirectToRoute('poolmeetingseditpast');
        }

        $context = [
            'editForm' => $editForm->createView(),
        ];

        return $this->render(
            '@AsmbCompetition/pool-meetings/edit.twig',
            $context,
            [
                'meetings' => $meetings,
            ]
        );
    }

    /**
     * Construction du formulaire d'édition des horaires des rencontres du club passées en paramètre.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array                                     $meetings
     *
     * @return FormInterface
     */
    protected function buildEditForm(Request $request, array $meetings)
    {
        $formOptions = [
            'meetings' => $meetings,
        ];
        $form = $this->createFormBuilder(PoolMeetingsEditType::class, null, $formOptions)
            ->getForm()
            ->handleRequest($request);

        return $form;
    }

    /**
     * Gère la soumission du formulaire d'édition des horaires des rencontres à venir du club.
     *
     * @param FormInterface $form
     *
     * @return boolean
     */
    protected function handleEditFormSubmit(Request $request, FormInterface $form)
    {
        if ($form->isSubmitted() && $form->isValid()) {
            $repository = $this->getRepository('championship_pool_meeting');
            $formData = $form->getData();

            $meetings = $form->getConfig()->getOption('meetings');
            try {
                /** @var \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting $meeting */
                foreach ($meetings as $meeting) {
                    $time = $formData['pool_meeting' . $meeting->getId() . '_time'];
                    $meeting->setTime($time);

                    $reportDate = $formData['pool_meeting' . $meeting->getId() . '_report_date'];
                    if ($reportDate !== $meeting->getDate()) {
                        $meeting->setReportDate($reportDate);
                    } else {
                        $meeting->setReportDate(null);
                    }

                    $repository->save($meeting, true);
                }

                $this->flashes()->success(
                    Trans::__('page.edit-pool-meetings.message.saved')
                );

                return true;
            } catch (\Exception $e) {
                $this->flashes()->error(
                    Trans::__('page.edit-pool-meetings.message.not-saved')
                );
                $this->flashes()->error($e->getMessage());
            }
        }

        return false;
    }

    /**
     * Retourne les rencontres du moment, dans le passé ou le futur selon que $pastOrFutureDays soit négatif (passé)
     * ou positif (futur).
     *
     * @param int  $pastOrFutureDays
     * @param bool $onlyActiveChampionship
     * @param bool $withReportDates
     *
     * @return \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting[]
     */
    protected function getPastOrFutureMeetings(
        $pastOrFutureDays,
        $onlyActiveChampionship = true,
        $withReportDates = true
    ) {
        /** @var PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $this->getRepository('championship_pool_meeting');
        $pastDays = ($pastOrFutureDays < 0) ? (-1 * $pastOrFutureDays) : 0;
        $futureDays = ($pastOrFutureDays > 0) ? $pastOrFutureDays : 0;
        $meetingsOfTheMoment = $poolMeetingRepository
            ->findClubMeetingsOfTheMoment($pastDays, $futureDays, $onlyActiveChampionship, $withReportDates);

        return $meetingsOfTheMoment;
    }
}
