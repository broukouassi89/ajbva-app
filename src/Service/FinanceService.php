<?php

namespace App\Service;

use App\Entity\CasSocial;
use App\Entity\Cotisation;
use App\Entity\Membre;
use App\Repository\CasSocialRepository;
use App\Repository\CotisationRepository;
use App\Repository\MembreRepository;
use App\Repository\ProjetRepository;
use Doctrine\ORM\EntityManagerInterface;

class FinanceService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CotisationRepository $cotisationRepo,
        private readonly CasSocialRepository $casSocialRepo,
        private readonly MembreRepository $membreRepo,
        private readonly ProjetRepository $projetRepo,
        private readonly SettingService $settingService,
    ) {}

    public function getAssistancesDefaults(): array
    {
        return [
            'Décès membre'      => $this->settingService->get('assistance_deces_membre'),
            'Décès conjoint(e)' => $this->settingService->get('assistance_deces_conjoint'),
            'Décès enfant'      => $this->settingService->get('assistance_deces_enfant'),
            'Décès père/mère'   => $this->settingService->get('assistance_deces_parent_base'),
            'Mariage'           => $this->settingService->get('assistance_mariage'),
            'Naissance'         => $this->settingService->get('assistance_naissance'),
        ];
    }

    public function getCotisationSociale(): int
    {
        return $this->settingService->get('cotisation_sociale', 1000);
    }

    /**
     * Calculer les arriérés d'un membre
     */
    public function getArrieresByMembre(Membre $membre): array
    {
        // 1. Arriérés mensuels
        // On calcule le nombre de mois depuis l'adhésion jusqu'à aujourd'hui
        $dateAdhesion = $membre->getDateAdhesion();
        $aujourdhui = new \DateTime();
        
        $diff = $dateAdhesion->diff($aujourdhui);
        $nbMoisTheoriques = ($diff->y * 12) + $diff->m + 1; // +1 pour inclure le mois en cours
        
        $montantMensuelUnitaire = 1500; // Paramètre à extraire si besoin, mais ici fixe par règle métier
        $totalDuMensuel = $nbMoisTheoriques * $montantMensuelUnitaire;
        
        $totalPayeMensuel = (float) $this->cotisationRepo->createQueryBuilder('c')
            ->select('SUM(c.montant)')
            ->where('c.membre = :membre')
            ->andWhere('c.type = :type')
            ->andWhere('c.statut = :statut')
            ->setParameter('membre', $membre)
            ->setParameter('type', Cotisation::TYPE_MENSUELLE)
            ->setParameter('statut', Cotisation::STATUT_PAYEE)
            ->getQuery()->getSingleScalarResult() ?? 0;
            
        $arriereMensuel = max(0, $totalDuMensuel - $totalPayeMensuel);

        // 2. Arriérés Sociaux & Exceptionnels (ceux en attente dans la table cotisation)
        $autresArrieres = $this->cotisationRepo->createQueryBuilder('c')
            ->select('c.type, SUM(c.montant) as total')
            ->where('c.membre = :membre')
            ->andWhere('c.statut = :statut')
            ->andWhere('c.type != :typeMensuel')
            ->setParameter('membre', $membre)
            ->setParameter('statut', Cotisation::STATUT_EN_ATTENTE)
            ->setParameter('typeMensuel', Cotisation::TYPE_MENSUELLE)
            ->groupBy('c.type')
            ->getQuery()->getArrayResult();

        $detailAutres = [];
        $totalAutres = 0;
        foreach ($autresArrieres as $row) {
            $detailAutres[$row['type']] = (float) $row['total'];
            $totalAutres += (float) $row['total'];
        }

        return [
            'mensuel' => $arriereMensuel,
            'autres'  => $detailAutres,
            'total'   => $arriereMensuel + $totalAutres,
            'nbMoisRetard' => floor($arriereMensuel / $montantMensuelUnitaire),
        ];
    }

    /**
     * Obtenir tous les membres débiteurs avec leurs montants
     */
    public function getListeDebiteurs(): array
    {
        $membresActifs = $this->membreRepo->findBy(['statut' => Membre::STATUT_ACTIF]);
        $debiteurs = [];
        $totalGlobalArrieres = 0;

        foreach ($membresActifs as $membre) {
            $arrieres = $this->getArrieresByMembre($membre);
            if ($arrieres['total'] > 0) {
                $debiteurs[] = [
                    'membre' => $membre,
                    'details' => $arrieres,
                    'total' => $arrieres['total']
                ];
                $totalGlobalArrieres += $arrieres['total'];
            }
        }

        // Tri par montant décroissant
        usort($debiteurs, fn($a, $b) => $b['total'] <=> $a['total']);

        return [
            'liste' => $debiteurs,
            'totalGlobal' => $totalGlobalArrieres
        ];
    }

    /**
     * Solde total de la caisse = Entrées - Sorties (assistances payées)
     */
    public function getSoldeCaisse(): float
    {
        $entrees = $this->cotisationRepo->getTotalEntrees();
        $sorties = $this->casSocialRepo->getTotalAssistancesPayees();
        return $entrees - $sorties;
    }

    /**
     * Récupérer tous les flux financiers (entrées et sorties)
     */
    public function getFluxFinanciers(?int $limit = null): array
    {
        // 1. Récupérer les entrées (Cotisations payées)
        $cotisations = $this->cotisationRepo->findBy(
            ['statut' => Cotisation::STATUT_PAYEE],
            ['datePaiement' => 'DESC'],
            $limit
        );

        // 2. Récupérer les sorties (Assistances payées)
        $assistances = $this->casSocialRepo->findBy(
            ['statutAssistance' => 'Payée'],
            ['datePaiementAssistance' => 'DESC'],
            $limit
        );

        $flux = [];

        foreach ($cotisations as $c) {
            $flux[] = [
                'date'        => $c->getDatePaiement(),
                'type'        => 'Entrée',
                'categorie'   => $c->getType(),
                'libelle'     => $c->getNote() ?: sprintf('Cotisation %s - %s', $c->getType(), $c->getMembre()->getNomComplet()),
                'montant'     => (float) $c->getMontant(),
                'reference'   => $c->getRecuNumero(),
                'objet'       => $c,
            ];
        }

        foreach ($assistances as $a) {
            $flux[] = [
                'date'        => $a->getDatePaiementAssistance(),
                'type'        => 'Sortie',
                'categorie'   => 'Assistance Sociale',
                'libelle'     => sprintf('Assistance %s - %s', $a->getType(), $a->getMembre()->getNomComplet()),
                'montant'     => (float) $a->getMontantAssistance(),
                'reference'   => sprintf('AS-%05d', $a->getId()),
                'objet'       => $a,
            ];
        }

        // Tri par date décroissante
        usort($flux, fn($a, $b) => $b['date'] <=> $a['date']);

        if ($limit) {
            $flux = array_slice($flux, 0, $limit);
        }

        return $flux;
    }

    /**
     * Données pour le dashboard
     */
    public function getDashboardStats(): array
    {
        $annee = (int) date('Y');
        $membres = $this->membreRepo->countByStatut();
        $evolution = $this->cotisationRepo->getEvolutionMensuelle($annee);
        $repartitionVillages = $this->membreRepo->countByVillage();
        $repartitionGenre = $this->membreRepo->countByGenre();
        $typesCotisations = $this->cotisationRepo->getTotalByType($annee);
        $casSociauxStats = $this->casSocialRepo->getStatsByType();
        $valeurPatrimoine = $this->projetRepo->getTotalBenefices();
        $projets = $this->projetRepo->findAll();
        $debiteursStats = $this->getListeDebiteurs();

        return [
            'membres'               => $membres,
            'soldeCaisse'           => $this->getSoldeCaisse(),
            'cotisationsMoisCourant'=> $this->cotisationRepo->getTotalMoisCourant(),
            'casSociauxEnAttente'   => $this->casSocialRepo->countEnAttente(),
            'evolution'             => $evolution,
            'repartitionVillages'   => $repartitionVillages,
            'repartitionGenre'      => $repartitionGenre,
            'typesCotisations'      => $typesCotisations,
            'casSociauxStats'       => $casSociauxStats,
            'projets'               => $projets,
            'arrieres'              => $debiteursStats,
            'annee'                 => $annee,
        ];
    }

    /**
     * Rapport annuel des cotisations par membre
     */
    public function getRapportAnnuel(int $annee): array
    {
        $membres = $this->membreRepo->findActifs();
        $cotisationsTotaux = $this->cotisationRepo->getRapportAnnuel($annee);
        $cotisationAnnuelle = $this->settingService->get('cotisation_annuelle', 18000);

        $totauxParMembre = [];
        foreach ($cotisationsTotaux as $row) {
            $totauxParMembre[$row['membre_id']] = (float) $row['total'];
        }

        $rapport = [];
        foreach ($membres as $membre) {
            $totalPaye = $totauxParMembre[$membre->getId()] ?? 0.0;
            $resteAPayer = max(0, $cotisationAnnuelle - $totalPaye);
            $rapport[] = [
                'membre'       => $membre,
                'totalPaye'    => $totalPaye,
                'resteAPayer'  => $resteAPayer,
                'pourcentage'  => min(100, (int) round($totalPaye / $cotisationAnnuelle * 100)),
                'statut'       => $totalPaye >= $cotisationAnnuelle ? 'Complet' : ($totalPaye > 0 ? 'Partiel' : 'Non payé'),
            ];
        }

        // Tri par pourcentage décroissant
        usort($rapport, fn($a, $b) => $b['pourcentage'] <=> $a['pourcentage']);

        return $rapport;
    }

    /**
     * Déclencher une assistance lors d'un cas social
     */
    public function calculerMontantAssistance(CasSocial $cas): float
    {
        $type = $cas->getType();
        $defaults = $this->getAssistancesDefaults();
        $montantBase = (float) ($defaults[$type] ?? 0);
        $membre = $cas->getMembre();

        if ($type === 'Naissance') {
            // Un membre ne peut être assisté plus d'une fois par an pour une naissance
            $annee = $cas->getDateEvenement() ? (int) $cas->getDateEvenement()->format('Y') : (int) date('Y');
            $start = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $annee));
            $end = $start->modify('+1 year');

            $dejaAssiste = $this->casSocialRepo->createQueryBuilder('cs')
                ->select('COUNT(cs.id)')
                ->where('cs.membre = :membre')
                ->andWhere('cs.type = :type')
                ->andWhere('cs.dateEvenement >= :start')
                ->andWhere('cs.dateEvenement < :end')
                ->setParameter('membre', $membre)
                ->setParameter('type', 'Naissance')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()->getSingleScalarResult();

            if ($dejaAssiste > 0) {
                throw new \RuntimeException("Ce membre a déjà reçu une assistance pour naissance cette année.");
            }
        }

        if ($type === 'Décès père/mère') {
            // Calcul dynamique : chaque enfant biologique (membre actif) reçoit le montant de base (50 000)
            if ($membre->getGrandeFamille()) {
                $enfantsActifs = $this->membreRepo->count([
                    'grandeFamille' => $membre->getGrandeFamille(),
                    'statut'        => Membre::STATUT_ACTIF,
                ]);
                return $enfantsActifs * $montantBase;
            }
            return $montantBase;
        }

        return $montantBase;
    }

    /**
     * Générer les cotisations sociales pour tous les membres actifs lors d'un cas social
     */
    public function genererCotisationsSociales(CasSocial $cas): int
    {
        $membresActifs = $this->membreRepo->findActifs();
        $membreConcerne = $cas->getMembre();
        $count = 0;

        // Montant spécifique pour le décès d'un membre (3000) sinon montant standard (1000)
        $montantSocial = ($cas->getType() === 'Décès membre') 
            ? $this->settingService->get('cotisation_sociale_deces_membre', 3000) 
            : $this->settingService->get('cotisation_sociale', 1000);

        foreach ($membresActifs as $membre) {
            if ($membre->getId() === $membreConcerne->getId()) continue;

            $cotisation = new Cotisation();
            $cotisation->setMembre($membre)
                ->setType(Cotisation::TYPE_SOCIALE)
                ->setMontant($montantSocial)
                ->setDatePaiement(new \DateTime())
                ->setCasSocial($cas)
                ->setStatut(Cotisation::STATUT_EN_ATTENTE)
                ->setNote(sprintf('Cotisation sociale – %s de %s', $cas->getType(), $membreConcerne->getNomComplet()));

            $this->em->persist($cotisation);
            $count++;
        }

        $this->em->flush();
        return $count;
    }
}
