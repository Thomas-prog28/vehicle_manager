<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Form\VehicleType;
use App\Repository\VehicleRepository;
use App\Repository\FuelLogRepository;
use App\Service\InvoiceAnalyzerService;
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
        VehicleRepository $vehicleRepository,
        FuelLogRepository $fuelLogRepository,
        InvoiceAnalyzerService $analyzer
    ): Response {
        $vehicle = new Vehicle();
        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $vehicle->setUser($this->getUser());

            if ($vehicle->getServiceIntervalKm() === null || $vehicle->getServiceIntervalMonths() === null) {
                $year = $vehicle->getFirstRegistrationDate() ? (int) $vehicle->getFirstRegistrationDate()->format('Y') : null;
                $predicted = $analyzer->predictServiceInterval(
                    (string) $vehicle->getType(),
                    (string) $vehicle->getBrand(),
                    (string) $vehicle->getModel(),
                    $vehicle->getFuelType(),
                    $year
                );
                $vehicle->setServiceIntervalKm($vehicle->getServiceIntervalKm() ?? $predicted['km']);
                $vehicle->setServiceIntervalMonths($vehicle->getServiceIntervalMonths() ?? $predicted['months']);
            }

            $em->persist($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule ajouté à ton garage.');

            return $this->redirectToRoute('app_dashboard');
        }

        $vehicles = $vehicleRepository->findBy(['user' => $this->getUser(), 'archived' => false]);

        $totalInsurance = 0.0;
        $totalExpensesYear = 0.0;
        $alertsCount = 0;
        $countedInsurances = [];

        $currentYear = (int) date('Y');
        $today = new \DateTime('today');
        $in30Days = (new \DateTime('today'))->modify('+30 days');

        foreach ($vehicles as $v) {
            // 1. Dépenses d'entretien de l'année en cours
            foreach ($v->getInvoices() as $invoice) {
                if ($invoice->getInvoiceDate()?->format('Y') == $currentYear && $invoice->getTotalTTC() !== null) {
                    $totalExpensesYear += (float) $invoice->getTotalTTC();
                }
            }

            // 2. Cotisation totale d'assurance en cours (non expirée) + échéances dans les 30 jours
            foreach ($v->getCoverages() as $coverage) {
                $ins = $coverage->getInsurance();
                if ($ins && !in_array($ins->getId(), $countedInsurances, true)) {
                    $countedInsurances[] = $ins->getId();

                    if ($ins->getEndDate() === null || $ins->getEndDate() >= $today) {
                        $totalInsurance += (float) ($ins->getTotalAmount() ?? $coverage->getPremium());

                        if ($ins->getEndDate() !== null && $ins->getEndDate() <= $in30Days) {
                            $alertsCount++;
                        }
                    }
                }
            }

            // 3. Alerte si la révision du véhicule est imminente ou dépassée
            if ($v->getNextServiceInfo()['isUrgent']) {
                $alertsCount++;
            }
        }

        $lastFuelLog = !empty($vehicles)
            ? $fuelLogRepository->findOneBy(['vehicle' => $vehicles], ['filledAt' => 'DESC', 'id' => 'DESC'])
            : null;

        return $this->render('dashboard/index.html.twig', [
            'vehicles' => $vehicles,
            'total_insurance' => $totalInsurance,
            'total_expenses' => $totalExpensesYear,
            'alerts_count' => $alertsCount,
            'last_fuel_log' => $lastFuelLog,
            'form' => $form,
            'open_modal' => $form->isSubmitted() && !$form->isValid(),
        ]);
    }
}
