<?php

namespace Bundle\Asmb\Competition\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Tournament\Box;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TournamentBoxesScoreEditType extends AbstractType
{
    use FormWithWinnerChoicesTrait;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        /** @var Box[] $boxes */
        $boxes = $options['boxes']; // Boîtes avec score manquant

        foreach ($boxes as $box) {
            if (null === $box->getBoxTop() || null === $box->getBoxBtm()) {
                continue;
            }

            // Il s'agit d'une rencontre à venir, mais la date est encore inconnue => on zappe
            if (null === $box->getDatetime()) {
                continue;
            }

            // Joueur gagnant
            $winnerData = null;
            if ($box->getPlayerName() === $box->getBoxTop()->getPlayerName()) {
                $winnerData = 'top';
            } elseif ($box->getPlayerName() === $box->getBoxBtm()->getPlayerName()) {
                $winnerData = 'btm';
            }
            $winnerChoices = $this->buildWinnerChoices($box);
            $builder->add(
                'box-' . $box->getId() . '_winner',
                Type\ChoiceType::class,
                [
                    'required' => false,
                    'choices' => $winnerChoices,
                    'data' => $winnerData,
                    'attr' => [
                        'disabled' => empty($winnerChoices),
                        'class' => empty($winnerChoices) ? '' : 'to-setted'
                    ]
                ]
            );
            $builder->add(
                'box-' . $box->getId() . '_score',
                Type\TextType::class,
                [
                    'required' => false,
                    'constraints' => [
                        new Assert\Length(['max' => 20])
                    ],
                    'attr' => [
                        'disabled' => empty($winnerChoices),
                        'class' => empty($winnerChoices) ? '' : 'to-setted'
                    ]
                ]
            );
            $builder->add(
                'save_all',
                Type\SubmitType::class,
                [
                    'label' => Trans::__('general.phrase.save-all'),
                ]
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(
            [
                'boxes',
            ]
        );
    }
}