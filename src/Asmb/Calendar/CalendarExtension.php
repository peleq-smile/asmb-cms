<?php

namespace Bundle\Asmb\Calendar;

use Bolt\Extension\SimpleExtension;
use Bolt\Storage\Field\Collection\LazyFieldCollection;
use Bundle\Asmb\Calendar\Helpers\CalendarHelper;
use Carbon\Carbon;

/**
 * Asmb Calendar bundle extension loader.
 *
 * @see https://docs.bolt.cm/3.6/extensions/advanced/storage-repositories
 */
class CalendarExtension extends SimpleExtension
{
    protected $eventTypes;

    /**
     * @param \Bolt\Legacy\Content $calendarRecord
     *
     * @return array
     */
    public function getCalendarData($calendarRecord)
    {
        // On construit le calendrier des dates du 1er sept au 30 juin
        $year = (int) $calendarRecord->get('year');

        $lessonsFromDate = Carbon::createFromFormat('Y-m-d', $calendarRecord->get('lessons_from_date'));
        $lessonsToDate = Carbon::createFromFormat('Y-m-d', $calendarRecord->get('lessons_to_date'));
        $calendar = CalendarHelper::buildAnnualCalendar($year, $lessonsFromDate, $lessonsToDate);

        // On y ajoute les événements à afficher
        /** @var \Bolt\Storage\Field\Collection\RepeatingFieldCollection $events */
        $events = $calendarRecord->get('events');
        /** @var \Bolt\Storage\Field\Collection\LazyFieldCollection $event */
        foreach ($events->getValues() as $event) {
            $evtTitle = $event->get('evt_title');
            /** @var Carbon $evtFromDate */
            $evtFromDate = $event->get('evt_from_date');
            $evtDuration = $event->get('evt_duration');
            $evtWithLesson = (bool) $event->get('evt_with_lesson');

            $evtMonthLabel = CalendarHelper::buildCalendarDateMonthLabel($evtFromDate);
            $evtDayLabel = CalendarHelper::buildCalendarDateDayLabel($evtFromDate);
            $calendar[$evtMonthLabel][$evtDayLabel]['event'] = [
                'name'     => $evtTitle,
                'color'    => $this->getEventTypeColor($event),
                'duration' => $evtDuration,
            ];

            // S'il s'agit d'un évènement où il n'y a pas cours collectif, on ajoute le marqueur sur chaque jour de
            // l'événement
            if (!$evtWithLesson) {
                $calendar[$evtMonthLabel][$evtDayLabel]['classNames'][] = 'no-lessons';
                $evtDayNext = clone $evtFromDate;
                for ($i = 1; $i < $evtDuration; $i++) {
                    // On marque également tous les autres jours de l'événement !
                    $evtDayNext->addDay();
                    $evtMonthLabelDayNext = CalendarHelper::buildCalendarDateMonthLabel($evtDayNext);
                    $evtDayLabelDayNext = CalendarHelper::buildCalendarDateDayLabel($evtDayNext);
                    $calendar[$evtMonthLabelDayNext][$evtDayLabelDayNext]['classNames'][] = 'no-lessons';
                }
            }

            $this->handleSplitEvent($event, $calendar);
        }

        $this->handleHolidays($calendarRecord, $calendar);

        return $calendar;
    }

    /**
     * Retourne la couleur de l'événement donné, selon son type.
     *
     * @param \Bolt\Storage\Field\Collection\LazyFieldCollection $event
     *
     * @return string
     */
    protected function getEventTypeColor(LazyFieldCollection $event)
    {
        $eventTypes = $this->getEventTypes();

        return $eventTypes[$event->get('evt_type')]['color'];
    }

    /**
     * Retourne la liste des types d'événements, sous la forme :
     * [
     *     <id type> => [
     *         'name' => <nom du type, par ex 'Cours collectifs'>,
     *         'color' => <couleur du type, par ex '#ffffff'>
     *     ],
     *     ...
     * ]
     *
     * @return array
     */
    public function getEventTypes()
    {
        if (null === $this->eventTypes) {
            $this->eventTypes = [];

            /** @var \Bolt\Storage\Query\Query $query */
            $query = $this->container['query'];
            /** @var \Bolt\Storage\Query\QueryResultset $queryResultSet */
            $queryResultSet = $query->getContent('type_evenement_calendriers', ['order' => 'position']);
            /** @var \Bolt\Storage\Entity\Content $content */
            foreach ($queryResultSet as $content) {
                $this->eventTypes[$content->getId()] = $content->getValues();
            }
        }

        return $this->eventTypes;
    }

    /**
     * Gère le découpage d'un événement sur plusieurs mois.
     *
     * @param LazyFieldCollection $event
     * @param array               $calendar
     */
    protected function handleSplitEvent(LazyFieldCollection $event, &$calendar)
    {
        $evtDuration = $event->get('evt_duration');

        if ($evtDuration > 1) {
            $evtFromDate = $event->get('evt_from_date');
            $evtToDate = clone $evtFromDate;
            $evtToDate->addDay($evtDuration - 1);
            $evtMonthLabel = CalendarHelper::buildCalendarDateMonthLabel($evtFromDate);
            $evtDayLabel = CalendarHelper::buildCalendarDateDayLabel($evtFromDate);

            $evtMonthEndLabel = CalendarHelper::buildCalendarDateMonthLabel($evtToDate);
            if ($evtMonthLabel != $evtMonthEndLabel) {
                // La durée de l'événement du mois suivant est égal au jour du mois, car c'est du 1er au X du mois
                $evtDurationOnNextMonth = (int) $evtToDate->format('d');

                $evtFirstDayOfNextMonth = $evtToDate->addDay(-1 * $evtDurationOnNextMonth + 1);
                $evtNextMonthDayLabel = CalendarHelper::buildCalendarDateDayLabel($evtFirstDayOfNextMonth);

                $calendar[$evtMonthEndLabel][$evtNextMonthDayLabel]['event'] = [
                    'name'     => $event->get('evt_title'),
                    'color'    => $this->getEventTypeColor($event),
                    'duration' => $evtDurationOnNextMonth,
                ];

                // On réduit la durée de l'événement sur le mois initial, pour ne pas que cela dépasse du mois.
                $calendar[$evtMonthLabel][$evtDayLabel]['event']['duration'] = $evtDuration - $evtDurationOnNextMonth;
            }
        }
    }

    /**
     * Ajoute les infos sur les vacances scolaires, à partir des données saisies dans le contenu Calendrier.
     *
     * @param \Bolt\Legacy\Content $calendarRecord
     * @param array                $calendar
     */
    protected function handleHolidays($calendarRecord, &$calendar)
    {
        /** @var \Bolt\Storage\Field\Collection\LazyFieldCollection $holidays */
        foreach ($calendarRecord->get('holidays')->getValues() as $holidays) {
            /** @var Carbon $fromDate */
            $fromDate = $holidays->get('holidays_from_date');
            /** @var Carbon $toDate */
            $toDate = $holidays->get('holidays_to_date');

            do {
                $date = $fromDate;
                $monthLabel = CalendarHelper::buildCalendarDateMonthLabel($date); // ex: "septembre"
                $dayLabel = CalendarHelper::buildCalendarDateDayLabel($date); // ex: "01 lun"

                $calendar[$monthLabel][$dayLabel]['classNames'][] = 'is-holidays';
                $calendar[$monthLabel][$dayLabel]['title'] = $holidays->get('name');

                $date->addDay();
            } while ($date <= $toDate);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        return [
            'getCalendarData'       => 'getCalendarData',
            'getCalendarEventTypes' => 'getEventTypes',
        ];
    }
}
