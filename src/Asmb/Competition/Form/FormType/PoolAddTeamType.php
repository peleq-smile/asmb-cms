<?php

namespace Bundle\Asmb\Competition\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Championship\Team;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Competition adding a team into a pool form type.
 *
 * @copyright 2019
 */
class PoolAddTeamType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add(
                'pool' . $builder->getData()->getId() . '_add_team_championshipId',
                Type\HiddenType::class,
                [
                    'empty_data'    => $options['championship_id'],
                    'property_path' => 'championship_id',
                ]
            )
            ->add(
                'pool' . $builder->getData()->getId() . '_add_team_teamId',
                Type\ChoiceType::class,
                [
                    'choices_as_values' => false,
                    'choices'           => $options['available_teams'],
                    'multiple'          => true,
                    'label'             => Trans::__('general.phrase.team'),
                    'required'          => true,
                    'data'              => [],
                    'property_path'     => 'team',
                ]
            )
            ->add(
                'pool' . $builder->getData()->getId() . '_add_team_save',
                Type\SubmitType::class,
                [
                    'label' => Trans::__('general.phrase.add'),
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
                'available_teams',
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
