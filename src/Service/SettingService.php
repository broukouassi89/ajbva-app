<?php

namespace App\Service;

use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Setting;

class SettingService
{
    private ?array $cache = null;

    public function __construct(
        private readonly SettingRepository $settingRepo,
        private readonly EntityManagerInterface $em
    ) {}

    public function get(string $cle, mixed $default = null): mixed
    {
        if ($this->cache === null) {
            $this->cache = $this->settingRepo->findAllAsArray();
        }

        return $this->cache[$cle] ?? $default;
    }

    public function all(): array
    {
        return $this->settingRepo->findAll();
    }

    public function update(string $cle, mixed $valeur): void
    {
        $setting = $this->settingRepo->findOneBy(['cle' => $cle]);
        if ($setting) {
            $setting->setValeur((string) $valeur);
            $this->em->flush();
            $this->cache = null; // Clear cache
        }
    }

    /**
     * Initialise les paramètres par défaut s'ils n'existent pas
     */
    public function initializeDefaults(): void
    {
        $defaults = [
            'cotisation_annuelle' => ['label' => 'Cotisation Annuelle (F CFA)', 'val' => '18000', 'type' => 'integer'],
            'cotisation_mensuelle' => ['label' => 'Cotisation Mensuelle (F CFA)', 'val' => '1500', 'type' => 'integer'],
            'carte_adhesion'      => ['label' => 'Prix Carte Adhésion (F CFA)', 'val' => '5000', 'type' => 'integer'],
            'cotisation_sociale'  => ['label' => 'Cotisation Sociale Standard (F CFA)', 'val' => '1000', 'type' => 'integer'],
            'cotisation_sociale_deces_membre' => ['label' => 'Cotisation Sociale Décès Membre (F CFA)', 'val' => '3000', 'type' => 'integer'],
            'assistance_deces_membre'   => ['label' => 'Assistance Décès Membre (F CFA)', 'val' => '300000', 'type' => 'integer'],
            'assistance_deces_conjoint' => ['label' => 'Assistance Décès Conjoint (F CFA)', 'val' => '75000', 'type' => 'integer'],
            'assistance_deces_enfant'   => ['label' => 'Assistance Décès Enfant (F CFA)', 'val' => '50000', 'type' => 'integer'],
            'assistance_deces_parent_base' => ['label' => 'Assistance Décès Parent (Base par enfant)', 'val' => '50000', 'type' => 'integer'],
            'assistance_mariage'  => ['label' => 'Assistance Mariage (F CFA)', 'val' => '50000', 'type' => 'integer'],
            'assistance_naissance' => ['label' => 'Assistance Naissance (F CFA)', 'val' => '35000', 'type' => 'integer'],
        ];

        foreach ($defaults as $cle => $data) {
            $exists = $this->settingRepo->findOneBy(['cle' => $cle]);
            if (!$exists) {
                $s = new Setting();
                $s->setCle($cle)
                  ->setLabel($data['label'])
                  ->setValeur($data['val'])
                  ->setType($data['type']);
                $this->em->persist($s);
            }
        }
        $this->em->flush();
    }
}
