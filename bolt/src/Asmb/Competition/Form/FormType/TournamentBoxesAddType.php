<?php

namespace Bundle\Asmb\Competition\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Carbon\Carbon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TournamentBoxesAddType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        // En entrée du formulaire :
        // - le tournoi
        // - l'ID du tableau
        // - le nombre de boîtes "sortantes" à ajouter
        // - le nombre de boîtes "sortantes" déjà existantes dans ce tableau
        // - les dates de début et de fin du tournoi
        $tournament = $options['tournament'];
        $tableId = $options['tableId'];
        $nbOutToAdd = $options['nbOutToAdd'];
        $existingNbOut = $options['existingNbOut'];
        $nbRound = $options['nbRound'];
        /** @var Carbon $fromDate */
        $fromDate = $options['fromDate'];
        /** @var Carbon $toDate */
        $toDate = $options['toDate'];

        $fromDate->setTime(0, 0);
        $toDate->setTime(23, 0);

        for ($idxOut = 1; $idxOut <= $nbOutToAdd; $idxOut++) {
            $builder->add(
                'box' . $idxOut . '_date',
                Type\DateType::class,
                [
                    'widget' => 'single_text',
                    'required' => false,
                    'attr' => [
                        'class' => 'input-sm',
                        'min' => $fromDate->format(TournamentBoxEditType::DATE_MIN_MAX_FORMAT),
                        'max' => $toDate->format(TournamentBoxEditType::DATE_MIN_MAX_FORMAT),
                    ]
                ]
            );
            $builder->add(
                'box' . $idxOut . '_time',
                Type\TimeType::class,
                [
                    'widget' => 'single_text',
                    'required' => false,
                    'attr' => [
                        'class' => 'input-sm',
                        'min' => $fromDate->format(TournamentBoxEditType::TIME_MIN_MAX_FORMAT),
                        'max' => $toDate->format(TournamentBoxEditType::TIME_MIN_MAX_FORMAT),
                    ]
                ]
            );
            $builder->add(
                'box' . $idxOut . '_qualif_out',
                Type\TextType::class,
                [
                    'label' => Trans::__('page.edit-tournament.box.out'),
                    'required' => false,
                    'attr' => ['maxlength' => 3, 'class' => 'input-sm setted', 'readonly' => true],
                    'data' => 'Q' . ($existingNbOut + $idxOut)
                ]
            );

            $prefixElement = 'box' . $idxOut;
            // Box précédentes (haut + bas)
            $this->addTopOrBottomBoxFormElements($builder, $prefixElement, $tournament, true, $fromDate, $toDate, $nbRound);
            $this->addTopOrBottomBoxFormElements($builder, $prefixElement, $tournament, false, $fromDate, $toDate, $nbRound);
        }

        $builder
            ->add(
                'boxes_table_id',
                Type\HiddenType::class,
                [
                    'data' => $tableId,
                ]
            )
            ->add(
                'boxes_save',
                Type\SubmitType::class,
                [
                    'label' => Trans::__('general.phrase.save-all'),
                ]
            );

        return $this;
    }

    protected function addTopOrBottomBoxFormElements(
        FormBuilderInterface $builder,
        $parentPrefixElement,
        $tournament,
        $isTop,
        Carbon $fromDate,
        Carbon $toDate,
        $nbRound
    ) {
        $prefixElement = ($isTop) ? $parentPrefixElement . '-1' : $parentPrefixElement . '-2';

        if ($nbRound > 2) {
            // Sur le 1er tour (= les boîtes de la colonne la + à gauche) on ne peut saisir que des joueurs
            $builder->add(
                $prefixElement . '_date',
                Type\DateType::class,
                [
                    'widget' => 'single_text',
                    'required' => false,
                    'attr' => [
                        'class' => 'input-sm',
                        'min' => $fromDate->format(TournamentBoxEditType::DATE_MIN_MAX_FORMAT),
                        'max' => $toDate->format(TournamentBoxEditType::DATE_MIN_MAX_FORMAT),
                    ]
                ]
            );
            $builder->add(
                $prefixElement . '_time',
                Type\TimeType::class,
                [
                    'widget' => 'single_text',
                    'required' => false,
                    'attr' => [
                        'class' => 'input-sm',
                        'min' => $fromDate->format(TournamentBoxEditType::TIME_MIN_MAX_FORMAT),
                        'max' => $toDate->format(TournamentBoxEditType::TIME_MIN_MAX_FORMAT),
                    ]
                ]
            );
        }
        $builder->add(
            $prefixElement . '_qualif_in',
            Type\IntegerType::class,
            [
                'label' => Trans::__('page.edit-tournament.box.in'),
                'required' => false,
                'attr' => ['maxlength' => 3, 'class' => 'input-sm', 'placeholder' => '1, 2, ...'],
            ]
        );
        $builder->add(
            $prefixElement . '_player_name',
            Type\TextType::class,
            [
                'required' => false,
                'attr' => [
                    'class' => 'input-sm',
                    'maxlength' => 30,
                    'placeholder' => Trans::__('page.edit-tournament.box.player_name')
                ]
            ]
        );
        $builder->add(
            $prefixElement . '_player_rank',
            Type\TextType::class,
            [
                'required' => false,
                'attr' => [
                    'class' => 'input-sm input-ranking',
                    'maxlength' => 5,
                    'placeholder' => Trans::__('page.edit-tournament.box.player_rank')
                ],
            ]
        );
        $builder->add(
            $prefixElement . '_player_club',
            Type\TextType::class,
            [
                'required' => false,
                'attr' => [
                    'class' => 'input-sm',
                    'maxlength' => 20,
                    'placeholder' => Trans::__('page.edit-tournament.box.player_club'),
                ],
                'data' => $tournament->isInternal() ? 'ASMB' : null
            ]
        );

        $nbRound--;
        if ($nbRound > 1) {
            // Box précédentes (haut + bas)
            $this->addTopOrBottomBoxFormElements($builder, $prefixElement, $tournament, true, $fromDate, $toDate, $nbRound);
            $this->addTopOrBottomBoxFormElements($builder, $prefixElement, $tournament, false, $fromDate, $toDate, $nbRound);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(
            [
                'tournament',
                'tableId',
                'nbOutToAdd',
                'existingNbOut',
                'nbRound',
                'fromDate',
                'toDate',
            ]
        );
    }
}