<?php

namespace App\Form;

use App\Entity\Cotisation;
use App\Entity\Membre;
use App\Repository\MembreRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CotisationType extends AbstractType
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
                'label' => 'Sélectionner le membre',
                'placeholder' => 'Choisir un membre...',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de cotisation',
                'choices' => [
                    'Cotisation mensuelle' => Cotisation::TYPE_MENSUELLE,
                    'Cotisation exceptionnelle' => Cotisation::TYPE_EXCEPTIONNELLE,
                    'Cotisation sociale' => Cotisation::TYPE_SOCIALE,
                ],
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Montant (F CFA)',
                'currency' => false,
                'attr' => ['step' => 500],
            ])
            ->add('datePaiement', DateType::class, [
                'label' => 'Date de paiement',
                'widget' => 'single_text',
                'data' => new \DateTime(),
            ])
            ->add('moisConcerne', TextType::class, [
                'label' => 'Mois concerné',
                'required' => false,
                'attr' => ['type' => 'month'],
                'help' => 'Pour les cotisations mensuelles',
            ])
            ->add('note', TextType::class, [
                'label' => 'Note (optionnelle)',
                'required' => false,
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    '✅ Payée' => Cotisation::STATUT_PAYEE,
                    '⚠️ Partielle' => Cotisation::STATUT_PARTIELLE,
                    '⏳ En attente' => Cotisation::STATUT_EN_ATTENTE,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cotisation::class,
        ]);
    }
}
