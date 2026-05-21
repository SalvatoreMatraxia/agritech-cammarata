<?php

namespace App\Controller\Admin;

use App\Entity\Prediction;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class PredictionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Prediction::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Previsione')
            ->setEntityLabelInPlural('Previsioni')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('farm', 'Azienda');
        yield TextField::new('type', 'Tipo');
        yield NumberField::new('confidence', 'Confidence')->setNumDecimals(2);
        yield TextField::new('method', 'Metodo');
        yield DateField::new('targetDate', 'Data target');
        yield DateTimeField::new('createdAt', 'Creata')->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('type', 'Tipo'))
            ->add(TextFilter::new('method', 'Metodo'))
            ->add(NumericFilter::new('confidence', 'Confidence min'));
    }
}