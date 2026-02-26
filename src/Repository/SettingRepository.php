<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    public function findAllAsArray(): array
    {
        $settings = $this->findAll();
        $data = [];
        foreach ($settings as $setting) {
            $val = $setting->getValeur();
            if ($setting->getType() === 'integer') $val = (int) $val;
            if ($setting->getType() === 'decimal') $val = (float) $val;
            $data[$setting->getCle()] = $val;
        }
        return $data;
    }
}
