<?php

namespace Bundle\Asmb\Competition\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Bundle\Asmb\Competition\Entity\Championship\Team;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type de formulaire pour l'édition des (horaires de) rencontres.
 *
 * @copyright 2019
 */
class PoolMeetingsEditType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $meetings = $options['meetings'];

        /** @var \Bundle\Asmb\Competition\Entity\Championship\PoolMeeting $meeting */
        foreach ($meetings as $meeting) {
            $builder->add(
                'pool_meeting' . $meeting->getId() . '_time',
                Type\TimeType::class,
                [
                    'data'     => $meeting->getTime(),
                    'widget'   => 'single_text',
                    'required' => false,
                ]
            );

            // On vérifie si la rencontre est annoncée comme reportée et s'il
            // manque la date. Dans ce cas, on ajoute une classe CSS pour focus
            if ($meeting->getIsReported() && null === $meeting->getReportDate()) {
                $attr = [
                    'style' => 'color: white; background: #a94442;'
                ];
            } elseif ($meeting->getIsReported() && null !== $meeting->getReportDate()) {
                $attr = [
                    'style' => 'color: white; background: #3c763d;'
                ];
            } else {
                $attr = [];
            }

            $builder->add(
                'pool_meeting' . $meeting->getId() . '_report_date',
                Type\DateType::class,
                [
                    'data'     => $meeting->getReportDate(),
                    'widget'   => 'single_text',
                    'required' => false,
                    'attr'     =>  $attr
                ]
            );
            $builder->add(
                'pool_meeting' . $meeting->getId() . '_is_reported',
                Type\CheckboxType::class,
                [
                    'label'    => Trans::__('general.phrase.reported'),
                    'data'     => (bool) $meeting->getIsReported(),
                    'required' => false,
                ]
            );
        }

        $builder
            ->add(
                'pool_meeting_save',
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
                'meetings',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return null;
    }
}
