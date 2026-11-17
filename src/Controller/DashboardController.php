<?php

namespace App\Controller;

use App\Repository\VehicleRepository;
use App\Entity\Vehicle;
use App\Form\VehicleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        VehicleRepository $vehicleRepository
    ): Response {
        $vehicle = new Vehicle();
        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $vehicle->setUser($this->getUser());
            $em->persist($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule ajouté à ton garage.');

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('dashboard/index.html.twig', [
            'vehicles' => $vehicleRepository->findBy(['user' => $this->getUser(), 'archived' => false]),
            'form' => $form,
            'open_modal' => $form->isSubmitted() && !$form->isValid(),
        ]);
    }
}
