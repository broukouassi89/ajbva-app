<?php

namespace App\Form;

use App\Entity\Mandat;
use App\Entity\Membre;
use App\Repository\MembreRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MandatType extends AbstractType
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
                'placeholder' => 'Sélectionner un membre...',
            ])
            ->add('poste', TextType::class, [
                'label' => 'Poste / Responsabilité',
                'attr' => ['placeholder' => 'ex: Président, Trésorier...'],
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de prise de fonction',
                'widget' => 'single_text',
                'data' => new \DateTime(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mandat::class,
        ]);
    }
}
