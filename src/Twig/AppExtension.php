<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Repository\CasSocialRepository;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly CasSocialRepository $casSocialRepo,
    ) {}

    public function getGlobals(): array
    {
        return [
            'cas_attente' => $this->casSocialRepo->countEnAttente(),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('fcfa', [$this, 'formatFCFA']),
            new TwigFilter('age', [$this, 'calculerAge']),
            new TwigFilter('anciennete', [$this, 'calculerAnciennete']),
            new TwigFilter('initiales', [$this, 'getInitiales']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('badge_class', [$this, 'getBadgeClass']),
        ];
    }

    /**
     * Formate un nombre en F CFA
     * Usage Twig : {{ 150000|fcfa }}  →  "150 000 F CFA"
     */
    public function formatFCFA(float|int|string $montant): string
    {
        return number_format((float) $montant, 0, ',', ' ') . ' F CFA';
    }

    /**
     * Calcule l'âge à partir d'une date
     * Usage Twig : {{ membre.dateNaissance|age }}  →  "30"
     */
    public function calculerAge(?\DateTimeInterface $date): int
    {
        if (!$date) return 0;
        return (new \DateTime())->diff($date)->y;
    }

    /**
     * Calcule l'ancienneté formatée
     * Usage Twig : {{ membre.dateAdhesion|anciennete }}  →  "2 ans et 3 mois"
     */
    public function calculerAnciennete(?\DateTimeInterface $date): string
    {
        if (!$date) return '—';
        $diff = (new \DateTime())->diff($date);
        $years = $diff->y;
        $months = $diff->m;

        if ($years === 0 && $months === 0) return "Moins d'un mois";
        if ($years === 0) return "{$months} mois";
        if ($months === 0) return "{$years} an" . ($years > 1 ? 's' : '');
        return "{$years} an" . ($years > 1 ? 's' : '') . " et {$months} mois";
    }

    /**
     * Extrait les initiales d'un nom complet
     */
    public function getInitiales(string $nom): string
    {
        $parts = explode(' ', trim($nom));
        $initiales = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            if (!empty($part)) $initiales .= strtoupper($part[0]);
        }
        return $initiales ?: '??';
    }

    /**
     * Retourne la classe CSS du badge selon une valeur
     */
    public function getBadgeClass(string $valeur): string
    {
        return match(strtolower($valeur)) {
            'actif', 'payée', 'complet', 'bon état', 'terminé'  => 'badge-success',
            'inactif', 'refusée', 'hors service'                 => 'badge-danger',
            'en attente', 'partiel', 'à entretenir'              => 'badge-warning',
            'validée', 'en cours'                                => 'badge-info',
            'mensuelle', 'adhésion'                              => 'badge-primary',
            default                                              => 'badge-secondary',
        };
    }
}
