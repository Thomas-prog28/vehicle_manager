<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setDefaultSort(['lastLoginAt' => 'DESC', 'id' => 'DESC'])
            ->setSearchFields(['email']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('email', 'Adresse email');
        yield ChoiceField::new('roles', 'Rôles')
            ->setChoices([
                'Utilisateur' => 'ROLE_USER',
                'Administrateur' => 'ROLE_ADMIN',
            ])
            ->allowMultipleChoices()
            ->renderAsBadges([
                'ROLE_ADMIN' => 'danger',
                'ROLE_USER' => 'info',
            ]);
        yield BooleanField::new('isVerified', 'Compte vérifié');
        yield IntegerField::new('loginCount', 'Connexions')->hideOnForm();
        yield DateTimeField::new('lastLoginAt', 'Dernière connexion')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();
        yield IntegerField::new('uploadedFilesCount', 'Fichiers téléversés')
            ->hideOnForm()
            ->setSortable(false);
    }
}
