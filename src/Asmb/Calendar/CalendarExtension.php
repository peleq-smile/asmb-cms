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
        /** @var \Bolt\Storage\Field\Collection\RepeatingFieldCollection|array $events */
        $events = $calendarRecord->get('events'); // On n'obtient pas la même chose en mode "preview" qu'en mode réel...
        $values = is_array($events) ? $events : $events->getValues();
        /** @var \Bolt\Storage\Field\Collection\LazyFieldCollection|array $event */
        foreach ($values as $event) {
            $evtTitle = $this->getElementData($event, 'evt_title');
            /** @var Carbon $evtFromDate */
            $evtFromDate = $this->getCarbonDate($this->getElementData($event, 'evt_from_date'));
            $evtDuration = $this->getElementData($event, 'evt_duration');
            $evtWithLesson = (bool) ($this->getElementData($event, 'evt_with_lesson'));

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
     * @param \Bolt\Storage\Field\Collection\LazyFieldCollection|array $event
     *
     * @return string
     */
    protected function getEventTypeColor($event)
    {
        $eventTypes = $this->getEventTypes();

        $eventType = $this->getElementData($event, 'evt_type');

        return $eventTypes[$eventType]['color'];
    }

    /**
     * Retourne la valeur de la donnée demandée pour l'élément donné.
     * Gère le cas où $element est un objet (mode "vue normale") ou un tableau (mode "prévisualisation").
     *
     * @param \Bolt\Storage\Field\Collection\LazyFieldCollection|array $element
     * @param string                                                   $dataKey
     *
     * @return mixed
     */
    protected function getElementData($element, $dataKey)
    {
        if (is_array($element)) {
            $dataValue = isset($element[$dataKey]) ? $element[$dataKey] : null;
        } else {
            $dataValue = $element->get($dataKey);
        }

        return $dataValue;
    }

    /**
     * Transforme la date donnée en objet Carbon s'il s'agit d'une chaîne.
     *
     * @param Carbon|string $date
     *
     * @return Carbon
     */
    protected function getCarbonDate($date)
    {
        if (is_string($date)) {
            $stringDate = $date;
            $date = Carbon::createFromFormat('Y-m-d', $stringDate);
        }

        return $date;
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
     * @param LazyFieldCollection|array $event
     * @param array                     $calendar
     */
    protected function handleSplitEvent($event, &$calendar)
    {
        $evtDuration = $this->getElementData($event, 'evt_duration');

        if ($evtDuration > 1) {
            $evtFromDate = $this->getCarbonDate($this->getElementData($event, 'evt_from_date'));
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
                    'name'     => $this->getElementData($event, 'evt_title'),
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
        $values = is_array($calendarRecord->get('holidays')) ?  $calendarRecord->get('holidays') : $calendarRecord->get('holidays')->getValues();

        /** @var \Bolt\Storage\Field\Collection\LazyFieldCollection $holidays */
        foreach ($values as $holidays) {
            /** @var Carbon $fromDate */
            $fromDate = $this->getCarbonDate($this->getElementData($holidays, 'holidays_from_date'));
            /** @var Carbon $toDate */
            $toDate = $this->getCarbonDate($this->getElementData($holidays, 'holidays_to_date'));

            do {
                $date = $fromDate;
                $monthLabel = CalendarHelper::buildCalendarDateMonthLabel($date); // ex: "septembre"
                $dayLabel = CalendarHelper::buildCalendarDateDayLabel($date); // ex: "01 lun"

                $calendar[$monthLabel][$dayLabel]['classNames'][] = 'is-holidays';
                $calendar[$monthLabel][$dayLabel]['title'] = $this->getElementData($holidays, 'name');

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
