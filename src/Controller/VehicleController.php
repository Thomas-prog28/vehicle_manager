<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\User;
use App\Entity\Vehicle;
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

        $invoice = new Invoice();
        $invoiceForm = $this->createForm(InvoiceType::class, $invoice);
        $invoiceForm->handleRequest($request);

        if ($invoiceForm->isSubmitted() && $invoiceForm->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $invoiceForm->get('invoiceFile')->getData();

            if ($uploadedFile) {
                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/invoices';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                try {
                    $uploadedFile->move($uploadDir, $newFilename);
                    $invoice->setInvoicePath($newFilename);
                    $invoice->setInvoiceOriginalName($uploadedFile->getClientOriginalName());
                    $invoice->setVehicle($vehicle);

                    $em->persist($invoice);
                    $em->flush();

                    $this->addFlash('success', 'Facture ajoutée avec succès.');

                    return $this->redirectToRoute('app_vehicle_show', ['id' => $vehicle->getId()]);
                } catch (FileException) {
                    $this->addFlash('error', 'Impossible d’enregistrer le fichier sur le serveur.');
                }
            }
        }

        $totalSpent = 0.0;
        $currentMileage = 0;

        foreach ($vehicle->getInvoices() as $item) {
            if ($item->getTotalTTC() !== null) {
                $totalSpent += (float) $item->getTotalTTC();
            }
            if ($item->getMileage() !== null && $item->getMileage() > $currentMileage) {
                $currentMileage = $item->getMileage();
            }
        }

        return $this->render('vehicle/show.html.twig', [
            'vehicle' => $vehicle,
            'total_spent' => $totalSpent,
            'current_mileage' => $currentMileage,
            'invoice_form' => $invoiceForm,
            'open_invoice_modal' => $invoiceForm->isSubmitted() && !$invoiceForm->isValid(),
        ]);
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
