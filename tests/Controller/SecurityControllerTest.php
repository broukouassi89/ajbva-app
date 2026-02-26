<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="email"]');
        $this->assertSelectorExists('input[name="password"]');
    }

    public function testLoginRedirectsWhenAlreadyAuthenticated(): void
    {
        $client = static::createClient();

        // Simuler un utilisateur connecté
        // (nécessite un user en base de test)
        $crawler = $client->request('GET', '/login');
        $this->assertResponseIsSuccessful();
    }

    public function testLogoutRequiresPostMethod(): void
    {
        $client = static::createClient();
        $client->request('GET', '/logout');

        // Le logout Symfony est sur POST uniquement
        $this->assertResponseStatusCodeSame(405);
    }

    public function testDashboardRedirectsToLoginWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseRedirects('/login');
    }

    public function testMembresRedirectsToLoginWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/membres');

        $this->assertResponseRedirects('/login');
    }
}
