<?php

namespace Bundle\Asmb\Competition\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Competition championship editing form type.
 *
 * @copyright 2019
 */
class ChampionshipEditType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add(
                'year',
                Type\ChoiceType::class,
                [
                    'label'    => Trans::__('general.phrase.year'),
                    'choices'  => $this->getYearsAsChoices(),
                    'expanded' => false,
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
                    'attr'        => [
                        'size'      => 20,
                        'maxlength' => 20,
                    ],
                ]
            )
            ->add(
                'is_active',
                Type\ChoiceType::class,
                [
                    'choices'    => [
                        0 => Trans::__('general.phrase.no'),
                        1 => Trans::__('general.phrase.yes'),
                    ],
                    'label'      => false,
                    'empty_data' => 0,
                ]
            )
            ->add('save', Type\SubmitType::class, ['label' => Trans::__('page.edit.button.save-edit')]);

        return $this;
    }

    /**
     * Build a year choice array map.
     *
     * @return array
     */
    private function getYearsAsChoices()
    {
        $oldestYear = date('Y');
        $newestYear = date('Y') + 1;
        $years = range($oldestYear, $newestYear);

        return array_combine($years, $years);
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
