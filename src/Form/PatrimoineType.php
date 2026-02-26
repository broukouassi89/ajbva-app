<?php

namespace App\Form;

use App\Entity\Patrimoine;
use App\Entity\Projet;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PatrimoineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du bien',
                'attr' => ['placeholder' => 'ex: Lot de 100 chaises'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('valeurAchat', MoneyType::class, [
                'label' => 'Valeur d\'achat (F CFA)',
                'currency' => false,
            ])
            ->add('dateAcquisition', DateType::class, [
                'label' => 'Date d\'acquisition',
                'widget' => 'single_text',
                'data' => new \DateTime(),
            ])
            ->add('etat', ChoiceType::class, [
                'label' => 'État actuel',
                'choices' => array_combine(Patrimoine::ETATS, Patrimoine::ETATS),
            ])
            ->add('projet', EntityType::class, [
                'class' => Projet::class,
                'choice_label' => 'nom',
                'label' => 'Projet associé (optionnel)',
                'placeholder' => 'Aucun projet spécifique',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Patrimoine::class,
        ]);
    }
}
