<?php

namespace App\Service;

use App\Repository\MembreRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;

class ExcelService
{
    public function __construct(
        private readonly MembreRepository $membreRepo,
    ) {}

    public function exportMembres(): Response
    {
        $membres = $this->membreRepo->findAll();
        
        $handle = fopen('php://memory', 'r+');
        fputcsv($handle, ['ID', 'Nom', 'Prénom', 'Genre', 'Téléphone', 'Email', 'Famille', 'Village', 'Statut'], ';');

        foreach ($membres as $m) {
            fputcsv($handle, [
                $m->getIdentifiant(),
                $m->getNom(),
                $m->getPrenom(),
                $m->getGenre(),
                $m->getTelephone(),
                $m->getEmail(),
                $m->getGrandeFamille(),
                $m->getVillageOrigine(),
                $m->getStatut()
            ], ';');
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        $response = new Response($content);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'membres_ajbva_' . date('Y-m-d') . '.csv'
        );

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
