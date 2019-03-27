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
 * Type de formulaire pour l'édition des équipes d'une poule.
 *
 * @copyright 2019
 */
class PoolsTeamsEditType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        /** @var \Bundle\Asmb\Competition\Entity\Championship $championship */
        $championship = $builder->getData();

        /** @var \Bundle\Asmb\Competition\Entity\Championship\PoolTeam[][] $poolTeamsPerPoolId */
        $poolTeamsPerPoolId = $options['poolTeamsPerPoolId'];

        foreach ($poolTeamsPerPoolId as $poolId => $poolTeams) {
            foreach ($poolTeams as $poolTeam) {
                // Champ de personnalisation du nom de l'équipe en interne
                $builder->add(
                    'pool_team' . $poolTeam->getId() . '_name',
                    Type\TextType::class,
                    [
                        'property_path' => 'name',
                        'data'          => $poolTeam->getName(),
                        'attr'          => ['maxlength' => 20, 'class' => 'team-name']
                    ]
                );

                // Champ pour indiquer si l'équipe fait partie du club ou non
                $builder->add(
                    'pool_team' . $poolTeam->getId() . '_is_club',
                    Type\CheckboxType::class,
                    [
                        'property_path' => 'is_club',
                        'data'          => $poolTeam->isClub(),
                        'label'         => false,
                        'required'      => false,
                    ]
                );
            }
        }

        $builder
            ->add(
                'pools_teams_championship_id',
                Type\HiddenType::class,
                [
                    'data' => $championship->getId(),
                ]
            )
            ->add(
                'pools_teams_save',
                Type\SubmitType::class,
                [
                    'label' => Trans::__('general.phrase.save-all'),
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
                'poolTeamsPerPoolId',
            ]
        );
    }
}
