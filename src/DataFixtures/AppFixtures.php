<?php

namespace App\DataFixtures;

use App\Entity\CropOperation;
use App\Entity\EconomicParams;
use App\Entity\Farm;
use App\Entity\FarmSeasonRecord;
use App\Entity\OrganicProduct;
use App\Entity\Parcel;
use App\Entity\Prediction;
use App\Entity\PredictionAccuracy;
use App\Entity\Treatment;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        [$farm, $parcelNord, $parcelSud] = $this->loadUsers($manager);
        $products = $this->loadOrganicProducts($manager);
        $this->loadSeasonRecords($manager, $farm);
        $this->loadTreatments($manager, $parcelNord, $parcelSud, $products);
        $this->loadCropOperations($manager, $parcelNord, $parcelSud);
        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager): array
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
            ->setLatitude(37.65332531881009)
            ->setLongitude(13.68599814665818)
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

        return [$farm, $parcelNord, $parcelSud];
    }

    private function loadSeasonRecords(ObjectManager $manager, Farm $farm): void
    {
        $seasons = [
            [
                'year'              => 2024,
                'season'            => '2023-2024',
                'actualYieldKg'     => 4200.0,
                'actualOilLiters'   => 840.0,
                'oilQuality'        => 'EVO',
                'predictedYieldKg'  => 3800.0,
                'predictionErrorPct'=> -10.5,
                'treatmentsCount'   => 3,
                'irrigationMc'      => 45.0,
                'pestEvents'        => [
                    ['date' => '2024-09-15', 'type' => 'mosca_olearia', 'severity' => 'medio', 'confirmed' => true],
                ],
                'diseaseEvents'     => [
                    ['date' => '2024-04-10', 'type' => 'occhio_pavone', 'severity' => 'basso', 'confirmed' => true],
                ],
                'weather'           => ['temp_avg_spring' => 16.2, 'temp_avg_summer' => 27.8, 'rain_total' => 520, 'frost_days' => 3, 'heat_days' => 12],
                'notes'             => 'Prima stagione completa. Mosca controllata con Spinosad. Buona qualità olio.',
                'predDate'          => '2024-06-01',
                'confidence'        => 0.72,
                'validatedAt'       => '2024-12-15',
            ],
            [
                'year'              => 2025,
                'season'            => '2024-2025',
                'actualYieldKg'     => 2800.0,
                'actualOilLiters'   => 560.0,
                'oilQuality'        => 'EVO',
                'predictedYieldKg'  => 3200.0,
                'predictionErrorPct'=> 12.5,
                'treatmentsCount'   => 4,
                'irrigationMc'      => 85.0,
                'pestEvents'        => [
                    ['date' => '2025-08-20', 'type' => 'mosca_olearia', 'severity' => 'alto',  'confirmed' => true],
                    ['date' => '2025-10-01', 'type' => 'tignola',       'severity' => 'basso', 'confirmed' => false],
                ],
                'diseaseEvents'     => [],
                'weather'           => ['temp_avg_spring' => 17.1, 'temp_avg_summer' => 29.5, 'rain_total' => 320, 'frost_days' => 1, 'heat_days' => 25],
                'notes'             => 'Anno difficile — siccità prolungata luglio-agosto. Mosca aggressiva. Modello da ricalibrare su eventi estremi.',
                'predDate'          => '2025-06-01',
                'confidence'        => 0.68,
                'validatedAt'       => '2025-12-10',
            ],
            [
                'year'              => 2026,
                'season'            => '2025-2026',
                'actualYieldKg'     => 5100.0,
                'actualOilLiters'   => 1020.0,
                'oilQuality'        => 'EVO',
                'predictedYieldKg'  => 4800.0,
                'predictionErrorPct'=> -6.2,
                'treatmentsCount'   => 2,
                'irrigationMc'      => 30.0,
                'pestEvents'        => [
                    ['date' => '2026-09-28', 'type' => 'mosca_olearia', 'severity' => 'basso', 'confirmed' => true],
                ],
                'diseaseEvents'     => [
                    ['date' => '2026-11-05', 'type' => 'lebbra', 'severity' => 'basso', 'confirmed' => true],
                ],
                'weather'           => ['temp_avg_spring' => 15.8, 'temp_avg_summer' => 26.5, 'rain_total' => 680, 'frost_days' => 5, 'heat_days' => 8],
                'notes'             => 'Ottima stagione. Piogge primaverili abbondanti. Modello sempre più preciso. Lebbra lieve controllata con rame.',
                'predDate'          => '2026-01-15',
                'confidence'        => 0.81,
                'validatedAt'       => '2026-12-20',
            ],
        ];

        foreach ($seasons as $s) {
            $record = new FarmSeasonRecord();
            $record->setYear($s['year'])
                ->setSeason($s['season'])
                ->setActualYieldKg($s['actualYieldKg'])
                ->setActualOilLiters($s['actualOilLiters'])
                ->setOilQuality($s['oilQuality'])
                ->setPredictedYieldKg($s['predictedYieldKg'])
                ->setPredictionErrorPct($s['predictionErrorPct'])
                ->setTreatmentsCount($s['treatmentsCount'])
                ->setIrrigationTotalMc($s['irrigationMc'])
                ->setPestEvents($s['pestEvents'])
                ->setDiseaseEvents($s['diseaseEvents'])
                ->setWeatherSummary($s['weather'])
                ->setNotes($s['notes'])
                ->setFarm($farm);
            $manager->persist($record);

            $farmSurface = 3.0; // Azienda Matraxia — 2 parcelle × 1.5 ha
            $kgHa       = (int) round($s['predictedYieldKg'] / $farmSurface);
            $oilLiters  = (int) round($s['predictedYieldKg'] * 0.20);

            $prediction = new Prediction();
            $prediction->setType('yield')
                ->setResult([
                    'yield_kg_ha' => $kgHa,
                    'oil_liters'  => $oilLiters,
                    'scenarios'   => [
                        'pessimistico' => ['yield_kg_ha' => (int) round($kgHa * 0.80)],
                        'medio'        => ['yield_kg_ha' => $kgHa],
                        'ottimistico'  => ['yield_kg_ha' => (int) round($kgHa * 1.20)],
                    ],
                ])
                ->setConfidence($s['confidence'])
                ->setMethod('ml_model')
                ->setExplanationText('Previsione resa olivo calcolata su dati meteo, GDD e storico aziendale.')
                ->setScenarios(null)
                ->setCreatedAt(new \DateTimeImmutable($s['predDate']))
                ->setTargetDate(new \DateTimeImmutable($s['year'] . '-11-01'))
                ->setFarm($farm);
            $manager->persist($prediction);

            $accuracy = new PredictionAccuracy();
            $accuracy->setActualValue($s['actualYieldKg'])
                ->setErrorAbsolute(abs($s['actualYieldKg'] - $s['predictedYieldKg']))
                ->setErrorPct($s['predictionErrorPct'])
                ->setValidatedAt(new \DateTimeImmutable($s['validatedAt']))
                ->setValidatedBy('Salvatore Matraxia')
                ->setPrediction($prediction);
            $manager->persist($accuracy);
        }
    }

    private function loadOrganicProducts(ObjectManager $manager): array
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

        $indexed = [];
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
            $indexed[$p['name']] = $product;
        }

        return $indexed;
    }

    private function loadTreatments(ObjectManager $manager, Parcel $parcelNord, Parcel $parcelSud, array $products): void
    {
        $treatments = [
            [
                'date'              => '2026-03-15',
                'productName'       => 'Poltiglia bordolese',
                'activeSubstance'   => 'Rame solfato',
                'dosePerHa'         => 3.0,
                'doseUnit'          => 'kg',
                'totalQuantity'     => 4.5,
                'targetPest'        => 'Occhio di pavone',
                'applicationMethod' => 'Atomizzatore',
                'weatherConditions' => 'Soleggiato, 14°C, vento leggero',
                'operator'          => 'Salvatore Matraxia',
                'isOrganic'         => true,
                'registeredVia'     => 'web',
                'costEur'           => 30.0,
                'notes'             => 'Trattamento preventivo fine inverno',
                'product'           => $products['Poltiglia Bordolese'] ?? null,
                'parcel'            => $parcelNord,
            ],
            [
                'date'              => '2026-09-20',
                'productName'       => 'Spinosad (Success)',
                'activeSubstance'   => 'Spinosine A+D',
                'dosePerHa'         => 0.3,
                'doseUnit'          => 'l',
                'totalQuantity'     => 0.45,
                'targetPest'        => 'Mosca olearia',
                'applicationMethod' => 'Esca proteica',
                'weatherConditions' => 'Nuvoloso, 26°C',
                'operator'          => 'Salvatore Matraxia',
                'isOrganic'         => true,
                'registeredVia'     => 'whatsapp',
                'costEur'           => 45.0,
                'notes'             => 'Alert mosca dal DSS — rischio alto',
                'product'           => $products['Spinosad (Success)'] ?? null,
                'parcel'            => $parcelNord,
            ],
            [
                'date'              => '2026-11-10',
                'productName'       => 'Rame idrossido',
                'activeSubstance'   => 'Rame idrossido',
                'dosePerHa'         => 2.5,
                'doseUnit'          => 'kg',
                'totalQuantity'     => 3.75,
                'targetPest'        => 'Lebbra olivo',
                'applicationMethod' => 'Atomizzatore',
                'weatherConditions' => 'Post-pioggia, 12°C',
                'operator'          => 'Salvatore Matraxia',
                'isOrganic'         => true,
                'registeredVia'     => 'web',
                'costEur'           => 27.0,
                'notes'             => 'Dopo prime piogge autunnali',
                'product'           => $products['Rame Idrossido'] ?? null,
                'parcel'            => $parcelNord,
            ],
        ];

        foreach ($treatments as $t) {
            $treatment = new Treatment();
            $treatment->setDate(new \DateTimeImmutable($t['date']))
                ->setProductName($t['productName'])
                ->setActiveSubstance($t['activeSubstance'])
                ->setDosePerHa($t['dosePerHa'])
                ->setDoseUnit($t['doseUnit'])
                ->setTotalQuantity($t['totalQuantity'])
                ->setTargetPest($t['targetPest'])
                ->setApplicationMethod($t['applicationMethod'])
                ->setWeatherConditions($t['weatherConditions'])
                ->setOperator($t['operator'])
                ->setIsOrganic($t['isOrganic'])
                ->setRegisteredVia($t['registeredVia'])
                ->setCostEur($t['costEur'])
                ->setNotes($t['notes'])
                ->setParcel($t['parcel'])
                ->setOrganicProduct($t['product']);
            $manager->persist($treatment);
        }
    }

    private function loadCropOperations(ObjectManager $manager, Parcel $parcelNord, Parcel $parcelSud): void
    {
        $operations = [
            [
                'date'          => '2026-02-15',
                'type'          => 'potatura',
                'description'   => 'Potatura di formazione e pulizia rami secchi',
                'durationHours' => 8.0,
                'operator'      => 'Salvatore Matraxia',
                'costEur'       => 0.0,
                'notes'         => 'Rami bruciati in campo',
                'parcel'        => $parcelNord,
            ],
            [
                'date'          => '2026-08-01',
                'type'          => 'irrigazione',
                'description'   => 'Irrigazione di soccorso per siccità prolungata',
                'durationHours' => 3.0,
                'operator'      => 'Salvatore Matraxia',
                'costEur'       => 30.0,
                'notes'         => '15 mc/ha — alert stress idrico dal DSS',
                'parcel'        => $parcelNord,
            ],
            [
                'date'          => '2026-09-15',
                'type'          => 'monitoraggio',
                'description'   => 'Controllo trappole cromotropiche mosca olearia — 5 catture',
                'durationHours' => 1.0,
                'operator'      => 'Salvatore Matraxia',
                'costEur'       => 0.0,
                'notes'         => 'Soglia superata — trattamento programmato',
                'parcel'        => $parcelNord,
            ],
            [
                'date'          => '2026-11-01',
                'type'          => 'raccolta',
                'description'   => 'Raccolta meccanica Nocellara del Belice — 2.100 kg',
                'durationHours' => 12.0,
                'operator'      => 'Salvatore Matraxia',
                'costEur'       => 150.0,
                'notes'         => 'Olive sane, maturazione ottimale',
                'parcel'        => $parcelNord,
            ],
        ];

        foreach ($operations as $o) {
            $op = new CropOperation();
            $op->setDate(new \DateTimeImmutable($o['date']))
                ->setType($o['type'])
                ->setDescription($o['description'])
                ->setDurationHours($o['durationHours'])
                ->setOperator($o['operator'])
                ->setCostEur($o['costEur'])
                ->setNotes($o['notes'])
                ->setParcel($o['parcel']);
            $manager->persist($op);
        }
    }
}