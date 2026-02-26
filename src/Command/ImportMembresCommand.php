<?php

namespace App\Command;

use App\Entity\Membre;
use App\Repository\MembreRepository;
use App\Service\MembreService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-membres',
    description: 'Importe des membres à partir d\'un fichier CSV (séparateur point-virgule)',
)]
class ImportMembresCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MembreService $membreService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Chemin vers le fichier CSV');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');

        if (!file_exists($filePath)) {
            $io->error(sprintf('Le fichier "%s" n\'existe pas.', $filePath));
            return Command::FAILURE;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $io->error('Impossible d\'ouvrir le fichier.');
            return Command::FAILURE;
        }

        // Lire l'en-tête
        $header = fgetcsv($handle, 0, ';');
        $count = 0;
        $errors = 0;

        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            try {
                if (count($data) < 9) continue;

                $membre = new Membre();
                $membre->setNom($data[0]);
                $membre->setPrenom($data[1]);
                $membre->setGenre($data[2]);
                $membre->setDateNaissance(new \DateTime($data[3]));
                $membre->setDateAdhesion(new \DateTime($data[4]));
                $membre->setTelephone($data[5]);
                $membre->setEmail($data[6] ?: null);
                $membre->setGrandeFamille($data[7] ?: null);
                $membre->setVillageOrigine($data[8] ?: null);
                $membre->setStatut($data[9] ?? Membre::STATUT_ACTIF);
                
                // Utiliser le service pour créer le membre et son compte
                $this->membreService->creerMembre($membre, null);

                $count++;
            } catch (\Exception $e) {
                $io->warning(sprintf('Erreur à la ligne %d : %s', $count + 2, $e->getMessage()));
                $errors++;
            }
        }

        $this->entityManager->flush();
        fclose($handle);

        $io->success(sprintf('%d membres ont été importés avec succès (%d erreurs).', $count, $errors));

        return Command::SUCCESS;
    }
}
