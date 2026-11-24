<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\FuelLogRepository;
use App\Repository\InsuranceRepository;
use App\Repository\InvoiceRepository;
use App\Repository\UserRepository;
use App\Repository\VehicleRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class AdminDashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly VehicleRepository $vehicleRepository,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly InsuranceRepository $insuranceRepository,
        private readonly FuelLogRepository $fuelLogRepository,
    ) {}

    public function index(): Response
    {
        $users = $this->userRepository->findBy([], ['loginCount' => 'DESC']);

        $totalLogins = 0;
        $totalFiles = 0;
        foreach ($users as $user) {
            $totalLogins += $user->getLoginCount();
            $totalFiles += $user->getUploadedFilesCount();
        }

        return $this->render('admin/dashboard.html.twig', [
            'users' => $users,
            'total_users' => count($users),
            'total_logins' => $totalLogins,
            'total_files' => $totalFiles,
            'total_vehicles' => $this->vehicleRepository->count([]),
            'total_invoices' => $this->invoiceRepository->count([]),
            'total_insurances' => $this->insuranceRepository->count([]),
            'total_fuel_logs' => $this->fuelLogRepository->count([]),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Garage — Administration');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-chart-line');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');
        yield MenuItem::section('Application');
        yield MenuItem::linkToRoute('Retour à mon garage', 'fa fa-car', 'app_dashboard');
    }
}
