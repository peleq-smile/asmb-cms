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
 * Competition match score editing form type.
 *
 * @copyright 2019
 */
class MatchEditScoreType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $data = $builder->getData();
        $matchesData = $data['matches'];
        foreach ($matchesData as $matchesPerPoolId) {
            foreach ($matchesPerPoolId as $matches) {
                /** @var \Bundle\Asmb\Competition\Entity\Championship\Match $match */
                foreach ($matches as $match) {
                    // Home team score
                    $builder->add(
                        "pool{$match->getPoolId()}_day{$match->getDay()}_match{$match->getId()}_score_home",
                        Type\NumberType::class,
                        [
                            'data'     => $match->getScoreHome(),
                            'required' => false,
                            'attr'     => ['class' => 'input-score'],
                        ]
                    );

                    // Visitor team score
                    $builder->add(
                        "pool{$match->getPoolId()}_day{$match->getDay()}_match{$match->getId()}_score_visitor",
                        Type\NumberType::class,
                        [
                            'data'     => $match->getScoreVisitor(),
                            'required' => false,
                            'attr'     => ['class' => 'input-score'],
                        ]
                    );
                }
            }
        }

        $builder->add(
            'scores_save',
            Type\SubmitType::class,
            [
                'label' => Trans::__('general.phrase.save'),
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
}
