<?php

namespace App\Controller\Admin;

use App\Entity\Treatment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class TreatmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Treatment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Trattamento')
            ->setEntityLabelInPlural('Trattamenti')
            ->setDefaultSort(['date' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('parcel', 'Parcella');
        yield DateField::new('date', 'Data');
        yield TextField::new('productName', 'Prodotto');
        yield TextField::new('activeSubstance', 'Principio attivo')->hideOnIndex();
        yield NumberField::new('dosePerHa', 'Dose/ha')->setNumDecimals(2)->hideOnIndex();
        yield TextField::new('doseUnit', 'Unità')->hideOnIndex();
        yield TextField::new('targetPest', 'Target');
        yield BooleanField::new('isOrganic', 'Biologico');
        yield TextField::new('registeredVia', 'Registrato via');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('productName', 'Prodotto'))
            ->add(BooleanFilter::new('isOrganic', 'Biologico'))
            ->add(TextFilter::new('registeredVia', 'Registrato via'));
    }
}