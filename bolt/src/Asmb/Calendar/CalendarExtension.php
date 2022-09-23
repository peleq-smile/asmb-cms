<?php

namespace Bundle\Asmb\Calendar;

use Bolt\Extension\SimpleExtension;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\Field\Collection\LazyFieldCollection;
use Bundle\Asmb\Calendar\Helpers\CalendarHelper;
use Bundle\Asmb\Competition\Entity\Championship;
use Bundle\Asmb\Competition\Entity\Championship\Pool;
use Bundle\Asmb\Competition\Repository\Championship\PoolRepository;
use Carbon\Carbon;
use Twig\Markup;

/**
 * Asmb Calendar bundle extension loader.
 *
 * @see https://docs.bolt.cm/3.6/extensions/advanced/storage-repositories
 */
class CalendarExtension extends SimpleExtension
{
    protected $eventTypes;

    /**
     * @param \Bolt\Legacy\Content|Content $calendarRecord
     * @param string $field
     *
     * @return false|mixed|string
     */
    private function getCalendarRecordFieldContent($calendarRecord, string $field)
    {
        $fieldContent = $calendarRecord->get($field);
        if ($fieldContent instanceof Markup) {
            $fieldContent = $fieldContent->__toString();
        }

        return $fieldContent;
    }

    /**
     * @param \Bolt\Legacy\Content|Content $calendarRecord
     *
     * @return array
     */
    public function getCalendarData($calendarRecord)
    {
        $calendar = [];

        // On construit le calendrier des dates du 1er sept au 30 juin
        $year = (int)$this->getCalendarRecordFieldContent($calendarRecord, 'year');

        $lessonsFromDate = $this->getCalendarRecordFieldContent($calendarRecord, 'lessons_from_date');
        $lessonsToDate = $this->getCalendarRecordFieldContent($calendarRecord, 'lessons_to_date');

        if (empty($year) || empty($lessonsFromDate) || empty($lessonsToDate)) {
            return $calendar;
        }

        $lessonsFromDate = Carbon::createFromFormat('Y-m-d', $lessonsFromDate);
        $lessonsToDate = Carbon::createFromFormat('Y-m-d', $this->getCalendarRecordFieldContent($calendarRecord, 'lessons_to_date'));
        $calendar = CalendarHelper::buildAnnualCalendar($year, $lessonsFromDate, $lessonsToDate);
        $calendarStartDate = Carbon::createFromFormat('Y-m-d', "$year-9-1")->setTime(0, 0);

        // On y ajoute les journées de championnat avec les données qu'on a
        $this->handleChampionshipMeetings($calendar, $year);

        // On y ajoute les événements à afficher
        /** @var \Bolt\Storage\Field\Collection\RepeatingFieldCollection|array $events */
        $events = $this->getCalendarRecordFieldContent($calendarRecord, 'events'); // On n'obtient pas la même chose en mode "preview" qu'en mode réel...
        $values = is_array($events) ? $events : $events->getValues();
        /** @var LazyFieldCollection|array $event */
        foreach ($values as $event) {
            /** @var Carbon $evtFromDate */
            $evtFromDate = $this->getCarbonDate($this->getElementData($event, 'evt_from_date'));

            if ($evtFromDate < $calendarStartDate) {
                // si on duplique un calendrier, on peutse retrouver avec des vieux évts : on les ignore.
                continue;
            }

            $evtTitle = $this->getElementData($event, 'evt_title');
            $evtDuration = $this->getElementData($event, 'evt_duration');
            $evtWithLesson = (bool)($this->getElementData($event, 'evt_with_lesson'));
            $evtLink = ($this->getElementData($event, 'evt_link'));
            $color = $this->getEventTypeColor($event);
            $this->addDataToCalendar($calendar, $evtTitle, $evtFromDate, $evtDuration, $evtWithLesson, $color, $evtLink);

            $this->handleSplitEvent($event, $calendar);
        }

        $this->handleHolidays($calendarRecord, $calendar);

        return $calendar;
    }

    protected function addDataToCalendar(
        array  &$calendar,
        string $evtTitle,
        Carbon $evtFromDate,
        int    $evtDuration,
        bool   $evtWithLesson,
        $colors,
        ?string $evtLink = null
    ) {
        $colors = (is_array($colors) && count($colors) === 1) ? current($colors) : $colors;

        $evtMonthLabel = CalendarHelper::buildCalendarDateMonthLabel($evtFromDate);
        $evtDayLabel = CalendarHelper::buildCalendarDateDayLabel($evtFromDate);
        $calendar[$evtMonthLabel][$evtDayLabel]['event'] = [
            'name' => $evtTitle,
            'color' => $colors, // initialement 1 seule couleur, mais plusieurs possibles en fait !
            'duration' => $evtDuration,
            'link' => $evtLink
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
    }

    /**
     * Gère l'ajout des journées de rencontres par équipe.
     *
     * @param array $calendar
     * @param int $year year of calendar start
     */
    protected function handleChampionshipMeetings(array &$calendar, int $year)
    {
        $fromDate = Carbon::createFromDate($year, 9, 1)->format('Y-m-d');
        $toDate = Carbon::createFromDate($year+1, 8, 31)->format('Y-m-d');

        $meetingsByDate = [];

        // REPOSITORIES
        /** @var \Bundle\Asmb\Competition\Repository\ChampionshipRepository $championshipRepository */
        $championshipRepository = $this->container['storage']->getRepository('championship');
        /** @var PoolRepository $poolRepository */
        $poolRepository = $this->container['storage']->getRepository('championship_pool');
        /** @var \Bundle\Asmb\Competition\Repository\Championship\PoolMeetingRepository $poolMeetingRepository */
        $poolMeetingRepository = $this->container['storage']->getRepository('championship_pool_meeting');

        $championships = $championshipRepository->findBy(['is_active' => true]);

        /** @var Championship $championship */
        foreach ($championships as $championship) {
            $poolsByCategory = $poolRepository->findByChampionshipIdGroupByCategory($championship->getId());

            foreach ($poolsByCategory as $pools) {
                /** @var Pool $pool */
                foreach ($pools as $pool) {
                    $poolMeetings = $poolMeetingRepository->findClubMeetingsOfPool($pool->getId());

                    foreach ($poolMeetings as $poolMeeting) {
                        $day = $poolMeeting->getDay();
                        $meetingDate = $poolMeeting->getFinalDate()->format('Y-m-d');
                        if ($meetingDate < $fromDate || $meetingDate > $toDate) {
                            continue;
                        }

                        $clubTeamName = $poolMeeting->getHomeTeamName() ?? $poolMeeting->getVisitorTeamName();

                        if (!isset($meetingsByDate[$meetingDate]['title'])) {
                            // pas encore de rencontre à cette date
                            $meetingsByDate[$meetingDate]['title'] = 'J' . $day . ' ' . $clubTeamName;
                            $meetingsByDate[$meetingDate]['date'] = $poolMeeting->getFinalDate();
                        } elseif (strpos($meetingsByDate[$meetingDate]['title'], 'J' . $day) !== false) {
                            // déjà une rencontre à cette date, et c'est la même journée (J1, J2 etc.)
                            // on concatène, les rencontres sont triées par journée !
                            $meetingsByDate[$meetingDate]['title'] .= ' ' . $clubTeamName;
                        } else {
                            // déjà une autre rencontre à cette date, pas la même journée
                            $meetingsByDate[$meetingDate]['title'] .= ' + J' . $day . ' ' . $clubTeamName;
                        }
                        $meetingsByDate[$meetingDate]['colors'][$pool->getCalendarColor()] = $pool->getCalendarColor();
                    }
                }
            }
        }

        foreach ($meetingsByDate as $meetingData) {
            /** @var Carbon $evtFromDate */
            $evtFromDate = $meetingData['date'];
            $evtTitle = $meetingData['title'];
            $evtDuration = 1; // toujours sur 1 journée

            //TODOpeleq à retirer...
            if ($evtFromDate->format('Y-m-d') === '2022-02-04') {
                $evtOutOfHolidays = true;
            } else {
                $evtOutOfHolidays = false;
            }
            $evtWithLesson = !$evtFromDate->isDayOfWeek(0) && !$evtOutOfHolidays; // oui si autre jour que dimanche

            $colors = $meetingData['colors']; // il peut y avoir plusieurs couleurs...

            $this->addDataToCalendar($calendar, $evtTitle, $evtFromDate, $evtDuration, $evtWithLesson, $colors);
        }
    }

    /**
     * Retourne la couleur de l'événement donné, selon son type.
     *
     * @param LazyFieldCollection|array $event
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
     * @param LazyFieldCollection|array $element
     * @param string $dataKey
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
            $queryResultSet = $query->getContent(
                'type_evenement_calendriers',
                [
                    'status' => 'published',
                    'order' => 'position',
                ]
            );
            /** @var \Bolt\Storage\Entity\Content $content */
            foreach ($queryResultSet as $content) {
                $this->eventTypes[$content->getId()] = $content->getValues();
            }
        }

        return $this->eventTypes;
    }

    /**
     * Transforme la date donnée au format AAAA-MM-JJ vers le format demandé, en tenant compte de la locale courante.
     */
    public function formatLocalized(string $date, string $format)
    {
        $date = Carbon::createFromFormat('Y-m-d', $date);
        $formattedDate = $date->formatLocalized($format);

        return ucfirst($formattedDate);
    }

    /**
     * Gère le découpage d'un événement sur plusieurs mois.
     *
     * @param LazyFieldCollection|array $event
     * @param array $calendar
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
                $evtDurationOnNextMonth = (int)$evtToDate->format('d');

                $evtFirstDayOfNextMonth = $evtToDate->addDay(-1 * $evtDurationOnNextMonth + 1);
                $evtNextMonthDayLabel = CalendarHelper::buildCalendarDateDayLabel($evtFirstDayOfNextMonth);

                $calendar[$evtMonthEndLabel][$evtNextMonthDayLabel]['event'] = [
                    'name' => $this->getElementData($event, 'evt_title'),
                    'color' => $this->getEventTypeColor($event),
                    'duration' => $evtDurationOnNextMonth,
                    'link' => $this->getElementData($event, 'evt_link'),
                ];

                // On réduit la durée de l'événement sur le mois initial, pour ne pas que cela dépasse du mois.
                $calendar[$evtMonthLabel][$evtDayLabel]['event']['duration'] = $evtDuration - $evtDurationOnNextMonth;
            }
        }
    }

    /**
     * Ajoute les infos sur les vacances scolaires, à partir des données saisies dans le contenu Calendrier.
     *
     * @param \Bolt\Legacy\Content|Content $calendarRecord $calendarRecord
     * @param array $calendar
     */
    protected function handleHolidays($calendarRecord, array &$calendar)
    {
        $values = is_array($this->getCalendarRecordFieldContent($calendarRecord, 'holidays')) ?
            $this->getCalendarRecordFieldContent($calendarRecord, 'holidays') : $this->getCalendarRecordFieldContent($calendarRecord, 'holidays')->getValues();

        /** @var LazyFieldCollection $holidays */
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
                $calendar[$monthLabel][$dayLabel]['classNames'][] = 'no-lessons';
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
            'getCalendarData' => 'getCalendarData',
            'getCalendarEventTypes' => 'getEventTypes',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFilters()
    {
        return [
            'formatLocalized' => 'formatLocalized',
        ];
    }
}
