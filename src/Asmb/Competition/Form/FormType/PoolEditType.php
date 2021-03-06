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
                'fft_id',
                Type\TextType::class,
                [
                    'label'     => Trans::__('general.phrase.fft_id'),
                    'required'  => true,
                    'read_only' => $options['has_teams'],
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
                'has_teams',
            ]
        );
    }
}
