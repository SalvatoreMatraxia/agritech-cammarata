<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TreatmentControllerTest extends WebTestCase
{
    public function testTreatmentListRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/treatments');

        $this->assertResponseRedirects('/login');
    }

    public function testNewTreatmentForm(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em   = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'test@agritech.it']);

        if ($user === null) {
            $this->markTestSkipped('Fixture user test@agritech.it not found — run fixtures first.');
        }

        $client->loginUser($user);
        $client->request('GET', '/treatments/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }
}