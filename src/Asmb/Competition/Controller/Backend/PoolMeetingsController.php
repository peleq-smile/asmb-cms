<?php

namespace Bundle\Asmb\Competition\Controller\Backend;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Form\FormType\PoolMeetingsEditType;
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
    /**
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $c)
    {
        $c->match('/', 'index')
            ->bind('poolmeetings');

        $c->match('/edit', 'edit')
            ->bind('poolmeetingsedit');

        return $c;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $pastDays = (int) $this->getAsmbConfig('last_meetings_past_days');
        $futureDays = (int) $this->getAsmbConfig('next_meetings_future_days');

        return $this->render(
            '@AsmbCompetition/pool-meetings/index.twig',
            [],
            [
                'pastDays'     => $pastDays,
                'futureDays'   => $futureDays,
                'lastMeetings' => $this->getPastOrFutureMeetings(-1 * $pastDays),
                'nextMeetings' => $this->getPastOrFutureMeetings($futureDays),
            ]
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param                                           $championshipId
     *
     * @return \Bolt\Response\TemplateResponse|\Bolt\Response\TemplateView|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Request $request)
    {
        $futureDays = 2 * 365; // On récupère les prochaines rencontres... sur 2 ans !

        $meetings = $this->getPastOrFutureMeetings($futureDays, false, false);

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
     * Construction du formulaire d'édition des horaires des rencontres à venir du club.
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
}
