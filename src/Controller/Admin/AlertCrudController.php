<?php

namespace App\Controller\Admin;

use App\Entity\Alert;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class AlertCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Alert::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Alert')
            ->setEntityLabelInPlural('Alert')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('farm', 'Azienda');
        yield TextField::new('type', 'Tipo');
        yield TextField::new('severity', 'Severità');
        yield TextareaField::new('message', 'Messaggio')
            ->setMaxLength(80)
            ->hideOnIndex();
        yield TextField::new('message', 'Messaggio (estratto)')
            ->setMaxLength(60)
            ->onlyOnIndex();
        yield BooleanField::new('isRead', 'Letto');
        yield BooleanField::new('sentWhatsapp', 'WhatsApp inviato');
        yield DateTimeField::new('createdAt', 'Data')->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('type', 'Tipo'))
            ->add(TextFilter::new('severity', 'Severità'))
            ->add(BooleanFilter::new('isRead', 'Letto'));
    }
}