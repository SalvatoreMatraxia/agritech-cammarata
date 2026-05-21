<?php

namespace App\Controller\Admin;

use App\Entity\Farm;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class FarmCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Farm::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Azienda')
            ->setEntityLabelInPlural('Aziende')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nome');
        yield AssociationField::new('user', 'Proprietario');
        yield TextField::new('municipality', 'Comune');
        yield NumberField::new('surface', 'Superficie (ha)')->setNumDecimals(1);
        yield NumberField::new('altitude', 'Altitudine (m)');
        yield TextField::new('soilType', 'Tipo suolo')->hideOnIndex();
        yield NumberField::new('distanceSeaKm', 'Distanza mare (km)')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Creata')->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('municipality', 'Comune'))
            ->add(NumericFilter::new('surface', 'Superficie (ha)'));
    }
}