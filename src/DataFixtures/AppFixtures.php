<?php

namespace App\DataFixtures;

use App\Entity\EconomicParams;
use App\Entity\Farm;
use App\Entity\OrganicProduct;
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
        $this->loadUsers($manager);
        $this->loadOrganicProducts($manager);
        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager): void
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
            ->setPlantingYear(2010)
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
            ->setPlantingYear(2010)
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
    }

    private function loadOrganicProducts(ObjectManager $manager): void
    {
        $products = [
            ['name' => 'Poltiglia Bordolese',      'substance' => 'Rame solfato',          'halfLife' => 14, 'washOff' => 25.0, 'threshold' => 0.30, 'maxDose' => 3.0,  'waiting' => 0, 'cost' => 20.0, 'reg' => 'IT-RM-2345'],
            ['name' => 'Rame Idrossido',            'substance' => 'Rame idrossido',         'halfLife' => 10, 'washOff' => 20.0, 'threshold' => 0.30, 'maxDose' => 2.5,  'waiting' => 0, 'cost' => 18.0, 'reg' => 'IT-RM-2346'],
            ['name' => 'Spinosad (Success)',        'substance' => 'Spinosine A+D',          'halfLife' => 7,  'washOff' => 15.0, 'threshold' => 0.25, 'maxDose' => 0.25, 'waiting' => 7, 'cost' => 30.0, 'reg' => 'IT-RM-3891'],
            ['name' => 'Bacillus thuringiensis',   'substance' => 'Bt kurstaki',            'halfLife' => 5,  'washOff' => 10.0, 'threshold' => 0.20, 'maxDose' => 1.5,  'waiting' => 0, 'cost' => 15.0, 'reg' => 'IT-RM-4012'],
            ['name' => 'Caolino',                  'substance' => 'Silicato di alluminio',  'halfLife' => 21, 'washOff' => 30.0, 'threshold' => 0.40, 'maxDose' => 20.0, 'waiting' => 0, 'cost' => 12.0, 'reg' => 'IT-RM-5100'],
            ['name' => 'Olio di Neem',             'substance' => 'Azadiractina',           'halfLife' => 7,  'washOff' => 15.0, 'threshold' => 0.25, 'maxDose' => 5.0,  'waiting' => 3, 'cost' => 25.0, 'reg' => 'IT-RM-6230'],
            ['name' => 'Zeolite',                  'substance' => 'Clinoptilolite',         'halfLife' => 14, 'washOff' => 25.0, 'threshold' => 0.35, 'maxDose' => 25.0, 'waiting' => 0, 'cost' => 10.0, 'reg' => 'IT-RM-7001'],
        ];

        foreach ($products as $p) {
            $product = new OrganicProduct();
            $product->setCommercialName($p['name'])
                ->setActiveSubstance($p['substance'])
                ->setHalfLifeDays($p['halfLife'])
                ->setWashOffMm($p['washOff'])
                ->setReTreatmentThreshold($p['threshold'])
                ->setMaxDosePerHa($p['maxDose'])
                ->setWaitingDays($p['waiting'])
                ->setCostPerHa($p['cost'])
                ->setRegistrationNumber($p['reg'])
                ->setIsAllowedOrganic(true);
            $manager->persist($product);
        }
    }
}