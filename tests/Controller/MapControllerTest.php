<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MapControllerTest extends WebTestCase
{
    public function testMapRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/map');

        $this->assertResponseRedirects('/login');
    }

    public function testMapLoads(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em   = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'test@agritech.it']);

        if ($user === null) {
            $this->markTestSkipped('Fixture user test@agritech.it not found — run fixtures first.');
        }

        $client->loginUser($user);
        $client->request('GET', '/map');

        $this->assertResponseIsSuccessful();
    }
}