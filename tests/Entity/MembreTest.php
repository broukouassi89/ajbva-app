<?php

namespace App\Tests\Entity;

use App\Entity\Membre;
use PHPUnit\Framework\TestCase;

class MembreTest extends TestCase
{
    private Membre $membre;

    protected function setUp(): void
    {
        $this->membre = new Membre();
        $this->membre
            ->setNom('KOUASSI')
            ->setPrenom('Jean-Baptiste')
            ->setGenre('Masculin')
            ->setDateNaissance(new \DateTime('-30 years'))
            ->setDateAdhesion(new \DateTime('-2 years -3 months'))
            ->setTelephone('+225 07 12 34 56 78');
    }

    public function testNomMiseEnMajuscules(): void
    {
        $this->membre->setNom('kouassi');
        $this->assertSame('KOUASSI', $this->membre->getNom());
    }

    public function testNomComplet(): void
    {
        $this->assertSame('Jean-Baptiste KOUASSI', $this->membre->getNomComplet());
    }

    public function testAgeCalcule(): void
    {
        $age = $this->membre->getAge();
        $this->assertSame(30, $age);
    }

    public function testAncienneteAvecAnneesEtMois(): void
    {
        $anciennete = $this->membre->getAnciennete();
        $this->assertStringContainsString('ans', $anciennete);
        $this->assertStringContainsString('mois', $anciennete);
    }

    public function testStatutParDefautEstActif(): void
    {
        $m = new Membre();
        $this->assertSame(Membre::STATUT_ACTIF, $m->getStatut());
        $this->assertTrue($m->isActif());
    }

    public function testPhotoUrlAvecGenreMasculin(): void
    {
        $url = $this->membre->getPhotoUrl();
        $this->assertStringContainsString('homme', $url);
    }

    public function testPhotoUrlAvecGenreFeminin(): void
    {
        $this->membre->setGenre('Féminin');
        $url = $this->membre->getPhotoUrl();
        $this->assertStringContainsString('femme', $url);
    }

    public function testPhotoUrlAvecPhotoPersonnalisee(): void
    {
        $this->membre->setPhoto('photo_123.jpg');
        $url = $this->membre->getPhotoUrl();
        $this->assertStringContainsString('photo_123.jpg', $url);
    }

    public function testGenreIcon(): void
    {
        $this->assertSame('♂', $this->membre->getGenreIcon());
        $this->membre->setGenre('Féminin');
        $this->assertSame('♀', $this->membre->getGenreIcon());
    }

    public function testStatutBadgeClass(): void
    {
        $this->assertSame('badge-success', $this->membre->getStatutBadgeClass());
        $this->membre->setStatut(Membre::STATUT_INACTIF);
        $this->assertSame('badge-danger', $this->membre->getStatutBadgeClass());
        $this->membre->setStatut(Membre::STATUT_ANCIEN);
        $this->assertSame('badge-secondary', $this->membre->getStatutBadgeClass());
    }

    public function testAncienneteMoinsUnMois(): void
    {
        $this->membre->setDateAdhesion(new \DateTime('-1 week'));
        $this->assertSame("Moins d'un mois", $this->membre->getAnciennete());
    }
}
