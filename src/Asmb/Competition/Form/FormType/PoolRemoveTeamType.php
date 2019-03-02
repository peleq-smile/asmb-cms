<?php

namespace Bundle\Asmb\Competition\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Championship\Team;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Competition removing a team from a pool form type.
 *
 * @copyright 2019
 */
class PoolRemoveTeamType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add(
                'pool' . $builder->getData()->getId() . '_remove_team_teamId',
                Type\HiddenType::class,
                [
                    'property_path' => 'team',
                ]
            )
            ->add(
                'pool' . $builder->getData()->getId() . '_remove_team_save',
                Type\SubmitType::class
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
}
