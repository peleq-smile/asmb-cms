<?php

namespace Bundle\Asmb\Competition\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire d'ajout ou édition d'une poule.
 *
 * @copyright 2019
 */
class PoolEditType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        /** @var \Bundle\Asmb\Competition\Entity\Championship\Pool $pool */
        $pool = $builder->getData();

        $keyTransLabelSave = (null !== $pool->getId()) ? 'general.phrase.save' : 'general.phrase.add';

        $builder->add(
            'championship_id',
            Type\HiddenType::class,
            [
                'empty_data' => $options['championship_id'],
            ]
        );

        if (null !== $pool->getPosition()) {
            $builder->add(
                'position',
                Type\IntegerType::class
            );
        }

        $builder
            ->add(
                'category_identifier',
                Type\ChoiceType::class,
                [
                    'choices_as_values' => true,
                    'choices'           => $options['categories'],
                    'label'             => Trans::__('general.phrase.category'),
                    'required'          => true,
                ]
            )
            ->add(
                'name',
                Type\TextType::class,
                [
                    'label'       => Trans::__('general.phrase.name'),
                    'required'    => true,
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ]
            )
            ->add(
                'championship_fft_id',
                Type\TextType::class,
                [
                    'label'     => Trans::__('general.phrase.championship_fft_id'),
                    'required'  => false,
                    'read_only' => $options['has_teams'],
                ]
            )
            ->add(
                'division_fft_id',
                Type\TextType::class,
                [
                    'label'     => Trans::__('general.phrase.division_fft_id'),
                    'required'  => false,
                    'read_only' => $options['has_teams'],
                ]
            )
            ->add(
                'fft_id',
                Type\TextType::class,
                [
                    'label'     => Trans::__('general.phrase.fft_id'),
                    'required'  => true,
                    'read_only' => $options['has_teams'],
                ]
            )
            ->add(
                'calendar_color',
                Type\ChoiceType::class,
                [
                    'label'             => Trans::__('general.phrase.calendar_color'),
                    'required'          => false,
                    'choices_as_values' => true,
                    'choices'           => $options['calendarEventTypes'],
                ]
            )

            ->add(
                'save',
                Type\SubmitType::class,
                [
                    'label' => Trans::__($keyTransLabelSave),
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
                'championship_id',
                'categories',
                'has_teams',
                'calendarEventTypes',
            ]
        );
    }
}
