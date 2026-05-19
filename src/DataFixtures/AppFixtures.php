<?php

namespace App\DataFixtures;

use App\Entity\EconomicParams;
use App\Entity\Farm;
use App\Entity\Parcel;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@agritech.it')
            ->setRoles(['ROLE_ADMIN'])
            ->setPhone('+39320000000')
            ->setWhatsappOptIn(false)
            ->setLanguage('it')
            ->setPassword($this->hasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        $testUser = new User();
        $testUser->setEmail('test@agritech.it')
            ->setRoles(['ROLE_USER'])
            ->setPhone('+39321111111')
            ->setWhatsappOptIn(false)
            ->setLanguage('it')
            ->setPassword($this->hasher->hashPassword($testUser, 'password123'));
        $manager->persist($testUser);

        $farm = new Farm();
        $farm->setName('Azienda Matraxia')
            ->setLatitude(37.63)
            ->setLongitude(13.63)
            ->setSurface(3.0)
            ->setSoilType('clay_loam')
            ->setMunicipality('Cammarata')
            ->setAltitude(650)
            ->setDistanceSeaKm(45.0)
            ->setUser($testUser);
        $manager->persist($farm);

        $parcelNord = new Parcel();
        $parcelNord->setName('Parcella Nord')
            ->setSurface(1.5)
            ->setTreeCount(250)
            ->setVariety('Nocellara del Belice')
            ->setPlantingYear(2027)
            ->setIsBiological(true)
            ->setAspect('N')
            ->setAltitude(680)
            ->setFarm($farm);
        $manager->persist($parcelNord);

        $parcelSud = new Parcel();
        $parcelSud->setName('Parcella Sud')
            ->setSurface(1.5)
            ->setTreeCount(250)
            ->setVariety('Biancolilla')
            ->setPlantingYear(2027)
            ->setIsBiological(true)
            ->setAspect('S')
            ->setAltitude(620)
            ->setFarm($farm);
        $manager->persist($parcelSud);

        $economicParams = new EconomicParams();
        $economicParams->setOilPriceEur(14.0)
            ->setWaterCostEur(2.0)
            ->setLaborCostEur(12.0)
            ->setDefaultYieldKgHa(3000.0)
            ->setFarm($farm);
        $manager->persist($economicParams);

        $manager->flush();
    }
}