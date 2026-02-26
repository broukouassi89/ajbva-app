<?php

namespace App\Service;

use App\Entity\Cotisation;
use App\Entity\Membre;
use App\Entity\User;
use App\Repository\MembreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MembreService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MembreRepository $membreRepository,
        private readonly SettingService $settingService,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly string $photosDir,
    ) {}

    public function creerMembre(Membre $membre, ?UploadedFile $photoFile): array
    {
        // Générer identifiant unique
        $membre->setIdentifiant($this->membreRepository->generateIdentifiant());

        // Upload photo
        if ($photoFile) {
            $filename = $this->uploadPhoto($photoFile);
            $membre->setPhoto($filename);
        }

        $this->em->persist($membre);

        // Créer automatiquement le compte utilisateur
        $user = new User();
        // Utiliser l'email du membre ou générer un login basé sur l'identifiant
        $email = $membre->getEmail() ?? strtolower($membre->getIdentifiant()) . '@ajbva.local';
        $user->setEmail($email)
            ->setFullName($membre->getNomComplet())
            ->setMembre($membre)
            ->setRoles(['ROLE_MEMBRE'])
            ->setMustChangePassword(true);

        // Générer un mot de passe aléatoire (8 caractères)
        $plainPassword = bin2hex(random_bytes(4)); 
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->em->persist($user);
        $this->em->flush();

        // Enregistrer la carte d'adhésion (paramétrée)
        $montantAdhesion = $this->settingService->get('carte_adhesion', 5000);
        $cotisation = new Cotisation();
        $cotisation->setMembre($membre)
            ->setType(Cotisation::TYPE_ADHESION)
            ->setMontant($montantAdhesion)
            ->setDatePaiement($membre->getDateAdhesion())
            ->setNote("Carte d'adhésion")
            ->setStatut(Cotisation::STATUT_PAYEE)
            ->setRecuNumero($this->generateRecuNumero());

        $this->em->persist($cotisation);
        $this->em->flush();

        return [
            'membre' => $membre,
            'user' => $user,
            'plainPassword' => $plainPassword
        ];
    }

    public function updatePhoto(Membre $membre, UploadedFile $photoFile): void
    {
        // Supprimer ancienne photo
        if ($membre->getPhoto()) {
            $oldPath = $this->photosDir . '/' . $membre->getPhoto();
            if (file_exists($oldPath)) unlink($oldPath);
        }

        $filename = $this->uploadPhoto($photoFile);
        $membre->setPhoto($filename);
        $this->em->flush();
    }

    public function supprimerMembre(Membre $membre): void
    {
        if ($membre->getPhoto()) {
            $path = $this->photosDir . '/' . $membre->getPhoto();
            if (file_exists($path)) unlink($path);
        }
        $this->em->remove($membre);
        $this->em->flush();
    }

    private function uploadPhoto(UploadedFile $file): string
    {
        $filename = uniqid('photo_') . '.' . $file->guessExtension();
        if (!is_dir($this->photosDir)) {
            mkdir($this->photosDir, 0775, true);
        }
        $file->move($this->photosDir, $filename);
        return $filename;
    }

    private function generateRecuNumero(): string
    {
        return sprintf('RECU-%s-%05d', date('Y'), mt_rand(1, 99999));
    }
}
