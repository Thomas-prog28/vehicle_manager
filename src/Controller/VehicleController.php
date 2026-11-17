<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Vehicle;
use App\Form\VehicleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class VehicleController extends AbstractController
{
    #[Route('/vehicle/new', name: 'app_vehicle_new')]
    public function new(Request $request, EntityManagerInterface $em, #[CurrentUser] User $user): Response
    {
        $vehicle = new Vehicle();
        $vehicle->setUser($user);   // avant createForm, voir ci-dessous

        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($vehicle);
            $em->flush();
            $this->addFlash('success', 'Véhicule ajouté.');

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('vehicle/index.html.twig', ['form' => $form]);
    }
}
