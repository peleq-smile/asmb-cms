<?php

namespace Bundle\Asmb\Competition\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Helpers\PoolHelper;
use Carbon\Carbon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Competition pool matches editing form type.
 *
 * @copyright 2019
 */
class PoolEditMatchesType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $data = $builder->getData();
        $daysData = $data['days'];
        $matchesData = $data['matches'];

        $poolId = $options['pool_id'];
        $teamsCount = count($options['available_teams']);
        $daysCount = PoolHelper::getDaysCount($teamsCount);
        $matchesCountPerDay = PoolHelper::getMatchesCountPerDay($teamsCount);

        // Built of days input
        for ($day = 1; $day <= $daysCount; $day++) {
            /** @var \Carbon\Carbon|null $date */
            if (isset($daysData['day_' . $day])) {
                // Date exist
                $date = $daysData['day_' . $day];
            } else {
                $date = null;
            }

            $builder->add(
                'pool' . $poolId . '_day_' . $day,
                Type\DateType::class,
                [
                    'widget' => 'choice',
                    'years'  => $options['available_years'],
                    'data'   => $date,
                    'label'  => Trans::__('general.phrase.match-day', ['%day%' => $day])
                ]
            );

            // Built of teams selection, per day
            for ($matchPosition = 1; $matchPosition <= $matchesCountPerDay; $matchPosition++) {
                $homeTeamId = isset($matchesData['day_' . $day][$matchPosition]['home_team_id']) ?
                    $matchesData['day_' . $day][$matchPosition]['home_team_id'] : null;

                $visitorTeamId = isset($matchesData['day_' . $day][$matchPosition]['visitor_team_id']) ?
                    $matchesData['day_' . $day][$matchPosition]['visitor_team_id'] : null;

                /** @var \DateTime $time */
                $time = isset($matchesData['day_' . $day][$matchPosition]['time']) ?
                    $matchesData['day_' . $day][$matchPosition]['time'] : null;

                /** @var \DateTime $date */
                $date = isset($matchesData['day_' . $day][$matchPosition]['date']) ?
                    $matchesData['day_' . $day][$matchPosition]['date'] : null;

                // Home team selection
                $builder->add(
                    'pool' . $poolId . '_day_' . $day . '_match_' . $matchPosition . '_home_team_id',
                    Type\ChoiceType::class,
                    [
                        'choices_as_values' => false,
                        'choices'           => $options['available_teams'],
                        'required'          => false,
                        'data'              => $homeTeamId,
                    ]
                );

                // Visitor team selection
                $builder->add(
                    'pool' . $poolId . '_day_' . $day . '_match_' . $matchPosition . '_visitor_team_id',
                    Type\ChoiceType::class,
                    [
                        'choices_as_values' => false,
                        'choices'           => $options['available_teams'],
                        'required'          => false,
                        'data'              => $visitorTeamId,
                    ]
                );

                // Time match input
                $builder->add(
                    'pool' . $poolId . '_day_' . $day . '_match_' . $matchPosition . '_time',
                    Type\TimeType::class,
                    [
                        'widget'   => 'single_text',
                        'data'     => $time,
                        'required' => false,
                        'attr'     => ['min' => '08:00', 'max' => '21:00', 'step' => 300] // Step of 5min
                    ]

                );

                // Custom date input
                $builder->add(
                    'pool' . $poolId . '_day_' . $day . '_match_' . $matchPosition . '_date',
                    Type\DateType::class,
                    [
                        'label'    => Trans::__('general.phrase.date'),
                        'data'     => $date,
                        'required' => false,
                        'widget'   => 'choice',
                        'years'    => $options['available_years'],
                    ]
                );
            }
        }

        $builder->add(
            'pool' . $poolId . '_matches_save',
            Type\SubmitType::class,
            [
                'label' => Trans::__('general.phrase.save'),
            ]

        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(
            [
                'pool_id',
                'available_teams',
                'available_years',
            ]
        );
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return null;
    }
}
