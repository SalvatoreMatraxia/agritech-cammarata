<?php

namespace App\Controller\Admin;

use App\Entity\Parcel;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class ParcelCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Parcel::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Parcella')
            ->setEntityLabelInPlural('Parcelle')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nome');
        yield AssociationField::new('farm', 'Azienda');
        yield TextField::new('variety', 'Varietà');
        yield NumberField::new('surface', 'Superficie (ha)')->setNumDecimals(1);
        yield NumberField::new('treeCount', 'N° Piante');
        yield BooleanField::new('isBiological', 'Biologico');
        yield TextField::new('aspect', 'Esposizione')->hideOnIndex();
        yield NumberField::new('altitude', 'Altitudine (m)')->hideOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('variety', 'Varietà'))
            ->add(BooleanFilter::new('isBiological', 'Biologico'));
    }
}