<?php

namespace App\DataFixtures;

use App\Entity\CasSocial;
use App\Entity\Cotisation;
use App\Entity\Membre;
use App\Entity\Patrimoine;
use App\Entity\Projet;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        echo "🌱 Chargement des fixtures...\n";

        // ─── MEMBRES ─────────────────────────────────────────────────────
        $membresData = [
            ['KOUASSI', 'Jean-Baptiste', 'Masculin', '1990-03-15', '2020-01-10', '+225 07 12 34 56 78', 'jean.kouassi@email.com', 'Famille Kouassi', 'Botro'],
            ['KONAN', 'Marie-Claire', 'Féminin', '1995-07-22', '2021-03-15', '+225 05 98 76 54 32', 'marie.konan@email.com', 'Famille Konan', 'Botro'],
            ['KOFFI', 'Pierre-Emmanuel', 'Masculin', '1988-11-05', '2019-06-01', '+225 01 23 45 67 89', null, 'Famille Koffi', 'Village Avoisinant 1'],
            ['YAO', 'Aimée', 'Féminin', '1993-04-18', '2022-09-20', '+225 07 55 44 33 22', 'aimee.yao@email.com', 'Famille Yao', 'Botro'],
            ['BROU', 'Maxime', 'Masculin', '1985-12-30', '2018-01-05', '+225 05 11 22 33 44', null, 'Famille Brou', 'Village Avoisinant 2'],
            ['ASSI', 'Christine', 'Féminin', '1998-08-14', '2023-02-28', '+225 01 66 77 88 99', 'christine.assi@email.com', 'Famille Assi', 'Botro'],
            ['KRA', 'Olivier', 'Masculin', '1992-05-25', '2021-07-12', '+225 07 99 00 11 22', null, 'Famille Kra', 'Village Avoisinant 3'],
            ['NGORAN', 'Sylvie', 'Féminin', '1987-01-08', '2017-05-30', '+225 05 33 44 55 66', 'sylvie.ngoran@email.com', "Famille N'Goran", 'Botro'],
        ];

        $membres = [];
        foreach ($membresData as $i => [$nom, $prenom, $genre, $naissance, $adhesion, $tel, $email, $famille, $village]) {
            $m = new Membre();
            $m->setIdentifiant(sprintf('AJBVA-%s-%04d', date('Y'), $i + 1))
              ->setNom($nom)->setPrenom($prenom)->setGenre($genre)
              ->setDateNaissance(new \DateTime($naissance))
              ->setDateAdhesion(new \DateTime($adhesion))
              ->setTelephone($tel)->setEmail($email)
              ->setGrandeFamille($famille)->setVillageOrigine($village)
              ->setStatut($i < 6 ? Membre::STATUT_ACTIF : Membre::STATUT_INACTIF);
            $manager->persist($m);
            $membres[] = $m;

            // Créer un compte utilisateur pour chaque membre
            $u = new User();
            $u->setEmail($email ?? strtolower($m->getIdentifiant()) . '@ajbva.local')
              ->setFullName($m->getNomComplet())
              ->setRoles(['ROLE_USER'])
              ->setMembre($m)
              ->setMustChangePassword(true)
              ->setPassword($this->hasher->hashPassword($u, 'password123'));
            $manager->persist($u);
        }

        // ─── COTISATIONS ──────────────────────────────────────────────────
        $annee = date('Y');
        $moisLabels = ['01','02','03','04','05','06','07','08','09','10','11','12'];
        foreach ($membres as $idx => $membre) {
            // Carte adhésion
            $cAdh = new Cotisation();
            $cAdh->setMembre($membre)->setType(Cotisation::TYPE_ADHESION)
                 ->setMontant(5000)->setDatePaiement($membre->getDateAdhesion())
                 ->setRecuNumero(sprintf('RECU-%s-%05d', $annee, mt_rand(1,99999)))
                 ->setStatut(Cotisation::STATUT_PAYEE)->setNote("Carte d'adhésion");
            $manager->persist($cAdh);

            // Cotisations mensuelles (selon statut)
            $nbMois = $membre->getStatut() === Membre::STATUT_ACTIF ? mt_rand(6, 12) : mt_rand(0, 4);
            for ($m = 0; $m < min($nbMois, 12); $m++) {
                $c = new Cotisation();
                $c->setMembre($membre)->setType(Cotisation::TYPE_MENSUELLE)
                  ->setMontant(1500)
                  ->setDatePaiement(new \DateTime("{$annee}-{$moisLabels[$m]}-15"))
                  ->setMoisConcerne("{$annee}-{$moisLabels[$m]}")
                  ->setRecuNumero(sprintf('RECU-%s-%05d', $annee, mt_rand(1,99999)))
                  ->setStatut(Cotisation::STATUT_PAYEE);
                $manager->persist($c);
            }
        }

        // ─── CAS SOCIAUX ──────────────────────────────────────────────────
        $casData = [
            [$membres[0], 'Mariage', '2024-03-15', 25000, 'Payée'],
            [$membres[2], 'Naissance', '2024-06-20', 10000, 'Payée'],
            [$membres[4], 'Décès père/mère', '2024-08-10', 45000, 'Validée'],
            [$membres[1], 'Naissance', '2024-10-05', 10000, 'En attente'],
        ];
        foreach ($casData as [$membre, $type, $date, $montant, $statut]) {
            $cas = new CasSocial();
            $cas->setMembre($membre)->setType($type)
                ->setDateEvenement(new \DateTime($date))
                ->setMontantAssistance($montant)
                ->setStatutAssistance($statut);
            if ($statut === 'Payée') $cas->setDatePaiementAssistance(new \DateTime($date . ' +7 days'));
            $manager->persist($cas);
        }

        // ─── PROJETS ──────────────────────────────────────────────────────
        $projet = new Projet();
        $projet->setNom('Construction Salle Communautaire')
               ->setDescription('Projet de construction d\'une salle polyvalente pour les réunions et événements.')
               ->setBudgetTotal(2500000)->setMontantCollecte(1800000)->setBenefices(0)
               ->setStatut('En cours')->setDateDebut(new \DateTime('2024-01-01'));
        $manager->persist($projet);

        $projet2 = new Projet();
        $projet2->setNom('Matériel Audiovisuel')->setDescription('Acquisition de matériel son et lumière.')
                ->setBudgetTotal(500000)->setMontantCollecte(500000)->setBenefices(50000)
                ->setStatut('Terminé')->setDateDebut(new \DateTime('2023-01-01'))->setDateFin(new \DateTime('2023-12-31'));
        $manager->persist($projet2);

        // ─── PATRIMOINE ───────────────────────────────────────────────────
        $p1 = new Patrimoine();
        $p1->setNom('Terrain Association')->setValeurAchat(1500000)
           ->setDateAcquisition(new \DateTime('2022-06-15'))->setEtat('Bon état')
           ->setDescription('Terrain de 500m² à Botro');
        $manager->persist($p1);

        $p2 = new Patrimoine();
        $p2->setNom('Système Sonorisation')->setValeurAchat(350000)
           ->setDateAcquisition(new \DateTime('2023-09-20'))->setEtat('Bon état')
           ->setProjet($projet2);
        $manager->persist($p2);

        // ─── UTILISATEURS ─────────────────────────────────────────────────
        $usersData = [
            ['admin@ajbva.ci', 'Admin@2024!', ['ROLE_SUPER_ADMIN'], 'Super Administrateur'],
            ['bureau@ajbva.ci', 'Bureau@2024!', ['ROLE_BUREAU'], 'Trésorier Konan'],
            ['vp.social@ajbva.ci', 'VPSocial@2024!', ['ROLE_VP_SOCIAL'], 'VP Affaires Sociales'],
        ];

        foreach ($usersData as [$email, $pass, $roles, $name]) {
            $user = new User();
            $user->setEmail($email)->setRoles($roles)->setFullName($name)
                 ->setPassword($this->hasher->hashPassword($user, $pass))
                 ->setMustChangePassword(false);
            $manager->persist($user);
        }

        $manager->flush();

        echo "\n✅ Fixtures chargées avec succès !\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Super Admin  : admin@ajbva.ci        / Admin@2024!\n";
        echo "Bureau       : bureau@ajbva.ci       / Bureau@2024!\n";
        echo "VP Social    : vp.social@ajbva.ci    / VPSocial@2024!\n";
        echo "Membre       : jean.kouassi@email.com / Membre@2024!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }
}
