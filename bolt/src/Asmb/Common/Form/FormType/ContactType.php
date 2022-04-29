<?php

namespace Bundle\Asmb\Common\Form\FormType;

use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author    Perrine Léquipé <perrine.lequipe@smile.fr>
 * @copyright 2022
 */
class ContactType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'contactName',
                TextType::class,
                [
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                    'label' => Trans::__('contact.name.label'),
                    'attr'  => [
                        'placeholder' => Trans::__('contact.name.placeholder'),
                    ],
                    'required' => true,
                ]
            )
            ->add(
                'contactEmail',
                EmailType::class,
                [
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                    'label' => Trans::__('contact.email.label'),
                    'attr'  => [
                        'placeholder' => Trans::__('contact.email.placeholder'),
                    ],
                    'required' => true,
                ]
            )
            ->add(
                'contactSubject',
                TextType::class,
                [
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                    'label' => Trans::__('contact.subject.label'),
                    'attr'  => [
                        'placeholder' => Trans::__('contact.subject.placeholder'),
                    ],
                    'required' => true,
                ]
            )
            ->add(
                'contactMessage',
                TextareaType::class,
                [
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                    'label' => Trans::__('contact.message.label'),
                    'attr'  => [
                        'placeholder' => Trans::__('contact.message.placeholder'),
                        'rows' => 8,
                    ],
                    'required' => true,
                ]
            )
            ->add('submit', SubmitType::class, ['label' => Trans::__('contact.button.send')])
        ;

        return $this;
    }
}
