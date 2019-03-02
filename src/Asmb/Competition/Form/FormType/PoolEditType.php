<?php

namespace Bundle\Asmb\Competition\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Competition pool editing form type.
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

        $keyTransLabelSave = ($builder->getData()->getId()) ? 'general.phrase.save' : 'general.phrase.add';

        $builder
            ->add(
                'championship_id',
                Type\HiddenType::class,
                [
                    'empty_data' => $options['championship_id'],
                ]
            )
            ->add(
                'position',
                Type\IntegerType::class,
                [
                    'required' => false,
                ]
            )
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
                    'label'       => Trans::__('general.phrase.name'),
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
                'link_fft',
                Type\TextType::class,
                [
                    'label'       => Trans::__('general.phrase.link_fft'),
                    'required'    => false,
                    'attr'        => ['size' => '100'],
                    'constraints' => [
                        new Assert\Url(),
                    ],
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
                'category_names',
            ]
        );
    }
}
