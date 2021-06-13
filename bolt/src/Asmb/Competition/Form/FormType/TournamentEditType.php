<?php

namespace Bundle\Asmb\Competition\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Tournament\Table;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TournamentEditType extends AbstractType
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
                    'label' => Trans::__('general.phrase.year'),
                    'choices' => $this->getYearsAsChoices(),
                    'expanded' => false,
                    'required' => true,
                ]
            );
        $builder->add(
            'name',
            Type\TextType::class,
            [
                'label' => Trans::__('general.phrase.name'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ]
        );
        $builder->add(
            'short_name',
            Type\TextType::class,
            [
                'label' => Trans::__('general.phrase.short_name'),
                'required' => false,
                'constraints' => [
                    new Assert\Length(['min' => 0, 'max' => 20]),
                ],
                'attr' => [
                    'size' => 20,
                    'maxlength' => 20,
                ],
            ]
        );
        $builder->add(
            'from_date',
            Type\DateType::class,
            [
                'label' => Trans::__('general.phrase.from_date'),
                'required' => true,
                'widget' => 'single_text',
            ]
        );
        $builder->add(
            'to_date',
            Type\DateType::class,
            [
                'label' => Trans::__('general.phrase.to_date'),
                'required' => true,
                'widget' => 'single_text',
            ]
        );
        $builder->add(
            'is_internal',
            Type\CheckboxType::class,
            [
                'label' => Trans::__('general.phrase.is_internal'),
                'required' => false,
            ]
        );
        $builder->add(
            'save',
            Type\SubmitType::class,
            [
                'label' => Trans::__('general.phrase.save'),
            ]
        );

        return $this;
    }

    /**
     * Construit une liste de choix d'années.
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
}