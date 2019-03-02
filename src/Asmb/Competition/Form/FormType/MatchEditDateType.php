<?php

namespace Bundle\Asmb\Competition\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Carbon\Carbon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Competition match date editing form type.
 *
 * @copyright 2019
 */
class MatchEditDateType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $excludedDate = $options['excluded_date'];

        $builder
            ->add(
                'match' . $builder->getData()->getId() . '_date',
                Type\DateType::class,
                [
                    'label'         => Trans::__('general.phrase.date'),
                    'required'      => false,
                    'widget'        => 'choice',
                    'years'         => $options['available_years'],
                    'property_path' => 'date',
                    'constraints'   => [
                        new Assert\NotEqualTo($excludedDate),
                    ],
                ]
            )
            ->add(
                'match' . $builder->getData()->getId(). '_save_date',
                Type\ButtonType::class,
                [
                    'label' => Trans::__('page.edit.button.validate'),
                    'attr' => ['data-dismiss' => 'modal'],
                ]
            );

        return $this;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(
            [
                'excluded_date',
                'available_years',
            ]
        );
    }
}
