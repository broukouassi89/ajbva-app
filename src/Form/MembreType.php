<?php

namespace App\Form;

use App\Entity\Membre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    ChoiceType, DateType, EmailType, FileType, TelType, TextType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class MembreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom *',
                'attr'  => ['placeholder' => 'Ex: KOUASSI', 'class' => 'form-control', 'oninput' => 'this.value=this.value.toUpperCase()'],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom *',
                'attr'  => ['placeholder' => 'Ex: Jean-Baptiste', 'class' => 'form-control'],
            ])
            ->add('genre', ChoiceType::class, [
                'label'   => 'Genre *',
                'choices' => ['♂ Masculin' => 'Masculin', '♀ Féminin' => 'Féminin'],
                'attr'    => ['class' => 'form-control form-select'],
            ])
            ->add('dateNaissance', DateType::class, [
                'label'  => 'Date de naissance *',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control', 'onchange' => 'calculerAge(this.value)', 'max' => date('Y-m-d')],
            ])
            ->add('dateAdhesion', DateType::class, [
                'label'    => "Date d'adhésion *",
                'widget'   => 'single_text',
                'disabled' => $isEdit,
                'attr'     => ['class' => 'form-control', 'onchange' => 'calculerAnciennete(this.value)', 'max' => date('Y-m-d')],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone *',
                'attr'  => ['placeholder' => '+225 07 00 00 00 00', 'class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'label'    => 'Email (optionnel)',
                'required' => false,
                'attr'     => ['placeholder' => 'exemple@email.com', 'class' => 'form-control'],
            ])
            ->add('grandeFamille', ChoiceType::class, [
                'label'       => 'Grande famille',
                'required'    => false,
                'placeholder' => 'Sélectionner une famille',
                'choices'     => array_combine(Membre::GRANDES_FAMILLES, Membre::GRANDES_FAMILLES),
                'attr'        => ['class' => 'form-control form-select'],
            ])
            ->add('villageOrigine', ChoiceType::class, [
                'label'       => "Village d'origine",
                'required'    => false,
                'placeholder' => 'Sélectionner un village',
                'choices'     => array_combine(Membre::VILLAGES, Membre::VILLAGES),
                'attr'        => ['class' => 'form-control form-select'],
            ])
            ->add('photoFile', FileType::class, [
                'label'       => 'Photo',
                'mapped'      => false,
                'required'    => false,
                'constraints' => [
                    new File([
                        'maxSize'          => '2M',
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image (JPG, PNG ou WebP).',
                    ]),
                ],
                'attr' => ['class' => 'form-control', 'accept' => 'image/*'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Membre::class,
            'is_edit'    => false,
        ]);
    }
}
