<?php

namespace App\Controller;

use App\Repository\VehicleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(VehicleRepository $vehicleRepository): Response
    {
        $vehicles = $vehicleRepository->findBy(
            ['user' => $this->getUser(), 'archived' => false],
            ['brand' => 'ASC']
        );

        return $this->render('dashboard/index.html.twig', ['vehicles' => $vehicles]);
    }
}
