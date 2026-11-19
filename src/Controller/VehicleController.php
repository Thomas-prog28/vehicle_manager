<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\Covered;
use App\Entity\Insurance;
use App\Form\InsuranceCoverageType;
use App\Form\InvoiceType;
use App\Form\VehicleType;
use App\Service\InvoiceAnalyzerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_USER')]
final class VehicleController extends AbstractController
{
    #[Route('/vehicle/new', name: 'app_vehicle_new')]
    public function new(Request $request, EntityManagerInterface $em, #[CurrentUser] User $user): Response
    {
        $vehicle = new Vehicle();
        $vehicle->setUser($user);

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

    #[Route('/vehicle/{id}', name: 'app_vehicle_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(
        Vehicle $vehicle,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        #[CurrentUser] User $user
    ): Response {
        if ($vehicle->getUser() !== $user) {
            throw $this->createAccessDeniedException('Ce véhicule ne t’appartient pas.');
        }

        // 1. Formulaire Facture
        $invoice = new Invoice();
        $invoiceForm = $this->createForm(InvoiceType::class, $invoice);
        $invoiceForm->handleRequest($request);

        if ($invoiceForm->isSubmitted() && $invoiceForm->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $invoiceForm->get('invoiceFile')->getData();
            $newFilename = $this->moveUploadedFile($uploadedFile, 'invoices', $slugger);

            $invoice->setInvoicePath($newFilename);
            $invoice->setInvoiceOriginalName($uploadedFile->getClientOriginalName());
            $invoice->setVehicle($vehicle);

            $em->persist($invoice);
            $em->flush();

            $this->addFlash('success', 'Facture ajoutée avec succès.');
            return $this->redirectToRoute('app_vehicle_show', ['id' => $vehicle->getId()]);
        }

        // 2. Formulaire Assurance (Insurance + Covered)
        $insurance = new Insurance();
        $insuranceForm = $this->createForm(InsuranceCoverageType::class, $insurance);
        $insuranceForm->handleRequest($request);

        if ($insuranceForm->isSubmitted() && $insuranceForm->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $insuranceForm->get('insuranceFile')->getData();
            $newFilename = $this->moveUploadedFile($uploadedFile, 'insurances', $slugger);

            $premium = (string) $insuranceForm->get('premium')->getData();

            $insurance->setUser($user);
            $insurance->setInsurancePath($newFilename);
            $insurance->setInsuranceOriginalName($uploadedFile->getClientOriginalName());
            // Si le scan IA a rempli le total global (ex: 968.55 €), on le garde ; sinon on prend la cotisation du véhicule
            $insurance->setTotalAmount($insurance->getTotalAmount() ?: $premium);

            $covered = new Covered();
            $covered->setVehicle($vehicle);
            $covered->setInsurance($insurance);
            $covered->setPremium($premium);

            $em->persist($insurance);
            $em->persist($covered);
            $em->flush();

            $this->addFlash('success', 'Contrat d’assurance associé au véhicule.');
            return $this->redirectToRoute('app_vehicle_show', ['id' => $vehicle->getId()]);
        }

        $totalSpent = 0.0;
        foreach ($vehicle->getInvoices() as $item) {
            if ($item->getTotalTTC() !== null) {
                $totalSpent += (float) $item->getTotalTTC();
            }
        }

        return $this->render('vehicle/show.html.twig', [
            'vehicle' => $vehicle,
            'total_spent' => $totalSpent,
            'current_mileage' => $vehicle->getCurrentMileage(),
            'invoice_form' => $invoiceForm,
            'insurance_form' => $insuranceForm,
            'open_modal_id' => $invoiceForm->isSubmitted() && !$invoiceForm->isValid()
                ? 'modal-invoice'
                : ($insuranceForm->isSubmitted() && !$insuranceForm->isValid() ? 'modal-insurance' : null),
        ]);
    }

    #[Route('/insurance/analyze', name: 'app_insurance_analyze', methods: ['POST'])]
    public function analyzeInsurance(Request $request, InvoiceAnalyzerService $analyzer): JsonResponse
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('document');

        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'Fichier invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $vehiclePlate = $request->query->get('plate');
        $vehiclePlate = $request->query->get('vehicle');

        try {
            return $this->json($analyzer->analyzeInsurance($file, $vehiclePlate));
        } catch (\Throwable) {
            return $this->json(['error' => 'Erreur lors de l’analyse par l’IA.'], Response::HTTP_BAD_GATEWAY);
        }
    }


    private function moveUploadedFile(UploadedFile $file, string $subDir, SluggerInterface $slugger): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $newFilename = $slugger->slug($originalFilename) . '-' . uniqid() . '.' . $file->guessExtension();

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $subDir;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $file->move($uploadDir, $newFilename);
        return $newFilename;
    }


    #[Route('/invoice/analyze', name: 'app_invoice_analyze', methods: ['POST'])]
    public function analyzeInvoice(Request $request, InvoiceAnalyzerService $analyzer): JsonResponse
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('document');

        if (!$file || !$file->isValid()) {
            return $this->json([
                'error' => $file ? $file->getErrorMessage() : 'Aucun fichier reçu.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes, true) || $file->getSize() > 10 * 1024 * 1024) {
            return $this->json(['error' => 'Format non supporté ou fichier trop lourd (10 Mo max).'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $extractedData = $analyzer->analyze($file);

            return $this->json($extractedData);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }
    }
}
