<?php

namespace App\Form;

use App\Entity\ProcesVerbal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProcesVerbalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu du compte-rendu',
                'attr' => [
                    'rows' => 15,
                    'class' => 'form-control ck-editor-rich',
                    'placeholder' => 'Saisissez ici les points abordés et les décisions prises...'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProcesVerbal::class,
        ]);
    }
}
