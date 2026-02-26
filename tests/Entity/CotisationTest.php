<?php

namespace App\Tests\Entity;

use App\Entity\Cotisation;
use PHPUnit\Framework\TestCase;

class CotisationTest extends TestCase
{
    public function testTypeLabelMensuelle(): void
    {
        $c = new Cotisation();
        $c->setType(Cotisation::TYPE_MENSUELLE);
        $this->assertSame('Cotisation Mensuelle', $c->getTypeLabel());
    }

    public function testTypeLabelAdhesion(): void
    {
        $c = new Cotisation();
        $c->setType(Cotisation::TYPE_ADHESION);
        $this->assertSame("Carte d'Adhésion", $c->getTypeLabel());
    }

    public function testMontantFormate(): void
    {
        $c = new Cotisation();
        $c->setMontant(1500);
        $this->assertStringContainsString('1', $c->getMontantFormate());
        $this->assertStringContainsString('F CFA', $c->getMontantFormate());
    }

    public function testStatutBadgeClass(): void
    {
        $c = new Cotisation();
        $c->setStatut(Cotisation::STATUT_PAYEE);
        $this->assertSame('badge-success', $c->getStatutBadgeClass());

        $c->setStatut(Cotisation::STATUT_EN_ATTENTE);
        $this->assertSame('badge-secondary', $c->getStatutBadgeClass());
    }

    public function testStatutParDefautPayee(): void
    {
        $c = new Cotisation();
        $this->assertSame(Cotisation::STATUT_PAYEE, $c->getStatut());
    }
}
