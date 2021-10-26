<?php

namespace Bundle\Asmb\Competition\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Tournament\Table;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TournamentTableEditType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add(
                'name',
                Type\TextType::class,
                [
                    'label' => Trans::__('general.phrase.name'),
                    'required' => true,
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ]
            )
            ->add(
                'category',
                Type\ChoiceType::class,
                [
                    'label' => Trans::__('general.phrase.category_name'),
                    'required' => true,
                    'choices' => Table::$categories,
                ]
            )
            ->add(
                'previous_table_id',
                Type\ChoiceType::class,
                [
                    'label' => Trans::__('page.edit-tournament.table.previous_table_id'),
                    'required' => false,
                    'choices' => $this->buildPreviousTablesChoices($options),
                ]
            )
            ->add(
                'position',
                Type\HiddenType::class,
                [
                    'label' => Trans::__('page.edit-tournament.table.position'),
                    'required' => false,
                    'empty_data' => 0,
                ]
            )
            ->add(
                'visible',
                Type\CheckboxType::class,
                [
                    'label' => Trans::__('page.edit-tournament.table.visible'),
                    'required' => false,
                ]
            )
            ->add(
                'tournament_id',
                Type\HiddenType::class
            )
            ->add(
                'save',
                Type\SubmitType::class,
                [
                    'label' => Trans::__('general.phrase.save'),
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
                'otherTables',
            ]
        );
    }

    protected function buildPreviousTablesChoices(array $options)
    {
        $previousTablesChoices = [];

        /** @var Table[] $otherTables */
        $otherTables = $options['otherTables'];
        foreach ($otherTables as $table) {
            $previousTablesChoices[$table->getId()] = $table->getName();
        }

        return $previousTablesChoices;
    }
}
