<?php

namespace Bundle\Asmb\Competition\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Tournament\Box;
use Carbon\Carbon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TournamentBoxEditType extends AbstractType
{
    const DATETIME_MIN_MAX_FORMAT = 'Y-m-d\TH:i';
    const DATE_MIN_MAX_FORMAT = 'Y-m-d';
    const TIME_MIN_MAX_FORMAT = 'H:i';

    use FormWithWinnerChoicesTrait;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        /** @var Box $box */
        $box = $builder->getData();

        /** @var Carbon $fromDate */
        $fromDate = $options['fromDate'];
        /** @var Carbon $toDate */
        $toDate = $options['toDate'];

        $fromDate->setTime(9, 0);
        $toDate->setTime(22, 0);

        if (null !== $box->getBoxBtm() && null !== $box->getBoxTop()) {
            // La boîte a des éléments précédents
            $builder->add(
                'date',
                Type\DateType::class,
                [
                    'widget' => 'single_text',
                    'required' => false,
                    'attr' => [
                        'min' => $fromDate->format(self::DATE_MIN_MAX_FORMAT),
                        'max' => $toDate->format(self::DATE_MIN_MAX_FORMAT),
                    ]
                ]
            );
            $builder->add(
                'time',
                Type\TimeType::class,
                [
                    'widget' => 'single_text',
                    'required' => false,
                    'attr' => [
                        'min' => $fromDate->format(self::TIME_MIN_MAX_FORMAT),
                        'max' => $toDate->format(self::TIME_MIN_MAX_FORMAT),
                    ]
                ]
            );

            // On construit une liste de sélection du vainqueur, s'il existe un joueur renseigné dans les boîtes
            // précédentes
            $winnerData = null;
            if ($box->getPlayerName() === $box->getBoxTop()->getPlayerName()) {
                $winnerData = 'top';
            } elseif ($box->getPlayerName() === $box->getBoxBtm()->getPlayerName()) {
                $winnerData = 'btm';
            }
            $builder->add(
                'winner',
                Type\ChoiceType::class,
                [
                    'label' => Trans::__('page.edit-tournament.box.winner'),
                    'required' => false,
                    'choices' => $this->buildWinnerChoices($box),
                    'data' => $winnerData,
                ]
            );

            $builder->add(
                'score',
                Type\TextType::class,
                [
                    'label' => Trans::__('page.edit-tournament.box.score'),
                    'required' => false,
                    'constraints' => [
                        new Assert\Length(['max' => 11])
                    ],
                    'attr' => ['maxlength' => 11, 'autocomplete' => 'off']
                ]
            );

            $builder->add(
                'qualif_out',
                Type\TextType::class,
                [
                    'label' => Trans::__('page.edit-tournament.box.out'),
                    'required' => false,
                    'attr' => ['maxlength' => 3],
                ]
            );
        } else {
            // La boîte n'a pas d'éléments précédents
            $builder->add(
                'player_name',
                Type\TextType::class,
                [
                    'label' => Trans::__('page.edit-tournament.box.player'),
                    'required' => false,
                    'attr' => [
                        'maxlength' => 30,
                        'placeholder' => Trans::__('page.edit-tournament.box.player_name'),
                    ]
                ]
            );
            $builder->add(
                'player_rank',
                Type\TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'maxlength' => 5,
                        'placeholder' => Trans::__('page.edit-tournament.box.player_rank'),
                    ],
                ]
            );
            $builder->add(
                'player_club',
                Type\TextType::class,
                [
                    'required' => false,
                    'attr' => [
                        'maxlength' => 20,
                        'placeholder' => Trans::__('page.edit-tournament.box.player_club'),
                    ]
                ]
            );
            $builder->add(
                'qualif_in',
                Type\IntegerType::class,
                [
                    'label' => Trans::__('page.edit-tournament.box.in'),
                    'required' => false,
                    'attr' => ['maxlength' => 3],
                ]
            );
        }

        $builder->add(
            'table_id',
            Type\HiddenType::class
        );
        $builder->add(
            'save',
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
                'fromDate',
                'toDate',
            ]
        );
    }
}