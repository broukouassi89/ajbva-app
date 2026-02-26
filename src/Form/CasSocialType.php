<?php

namespace App\Form;

use App\Entity\CasSocial;
use App\Entity\Membre;
use App\Repository\MembreRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CasSocialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('membre', EntityType::class, [
                'class' => Membre::class,
                'choice_label' => function(Membre $membre) {
                    return $membre->getNomComplet() . ' (' . $membre->getIdentifiant() . ')';
                },
                'query_builder' => function(MembreRepository $repo) {
                    return $repo->createQueryBuilder('m')
                        ->where('m.statut = :statut')
                        ->setParameter('statut', 'Actif')
                        ->orderBy('m.nom', 'ASC');
                },
                'label' => 'Membre concerné',
                'placeholder' => 'Choisir un membre...',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'événement',
                'choices' => array_combine(array_keys(CasSocial::TYPES), array_keys(CasSocial::TYPES)),
            ])
            ->add('dateEvenement', DateType::class, [
                'label' => 'Date de l\'événement',
                'widget' => 'single_text',
                'data' => new \DateTime(),
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (optionnelle)',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CasSocial::class,
        ]);
    }
}
