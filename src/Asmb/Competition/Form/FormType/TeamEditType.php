<?php

namespace Bundle\Asmb\Competition\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Competition team editing form type.
 *
 * @copyright 2019
 */
class TeamEditType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $yesNoOptions = [
            Trans::__('general.phrase.boolean.no')  => 0,
            Trans::__('general.phrase.boolean.yes') => 1,
        ];

        $builder
            ->add(
                'category_name',
                Type\ChoiceType::class,
                [
                    'choices_as_values' => true,
                    'choices'           => $options['category_names'],
                    'label'             => Trans::__('general.phrase.category_name'),
                    'required'          => true,
                ]
            )
            ->add(
                'name',
                Type\TextType::class,
                [
                    'label'       => Trans::__('general.phrase.name-fft'),
                    'required'    => true,
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ]
            )
            ->add(
                'short_name',
                Type\TextType::class,
                [
                    'label'       => Trans::__('general.phrase.short_name'),
                    'required'    => false,
                    'constraints' => [
                        new Assert\Length(['min' => 0, 'max' => 20]),
                    ],
                ]
            )
            ->add(
                'is_club',
                Type\ChoiceType::class,
                [
                    'label'             => Trans::__('team.label.is_club'),
                    'choices_as_values' => true, // Can be removed when symfony/form:^3.0 is the minimum
                    'choices'           => $yesNoOptions,
                    'expanded'          => false,
                ]
            )
            ->add(
                'save',
                Type\SubmitType::class,
                [
                    'label' => Trans::__('page.edit.button.save-and-back'),
                    'attr'  => ['value' => 1],
                ]
            )
            ->add(
                'save_and_continue',
                Type\SubmitType::class,
                [
                    'label' => Trans::__('page.edit.button.save-and-continue'),
                    'attr'  => ['value' => 1],
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
                'category_names',
            ]
        );
    }
}
