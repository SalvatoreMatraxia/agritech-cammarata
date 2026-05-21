<?php

namespace App\Controller\Admin;

use App\Entity\FarmSeasonRecord;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;

class FarmSeasonRecordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FarmSeasonRecord::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Storico Stagionale')
            ->setEntityLabelInPlural('Storico Stagionale')
            ->setDefaultSort(['year' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('farm', 'Azienda');
        yield NumberField::new('year', 'Anno');
        yield NumberField::new('actualYieldKg', 'Resa reale (kg)')->setNumDecimals(0);
        yield NumberField::new('actualOilLiters', 'Olio reale (L)')->setNumDecimals(0);
        yield NumberField::new('predictedYieldKg', 'Resa prevista (kg)')->setNumDecimals(0)->hideOnIndex();
        yield NumberField::new('predictionErrorPct', 'Errore %')->setNumDecimals(1);
        yield TextareaField::new('notes', 'Note')->hideOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('year', 'Anno'));
    }
}