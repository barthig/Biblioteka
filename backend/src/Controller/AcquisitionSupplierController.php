<?php
namespace App\Controller;

use App\Entity\Supplier;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AcquisitionSupplierController extends AbstractController
{
    public function list(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $active = $request->query->get('active');
        $criteria = [];
        if ($active !== null) {
            $criteria['active'] = filter_var($active, FILTER_VALIDATE_BOOLEAN);
        }

        $suppliers = $doctrine->getRepository(Supplier::class)->findBy($criteria, ['name' => 'ASC']);
        return $this->json($suppliers, 200, [], ['groups' => ['supplier:read']]);
    }

    public function create(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        if (empty($data['name'])) {
            return $this->json(['error' => 'Supplier name is required'], 400);
        }

        $supplier = new Supplier();
        $supplier->setName((string) $data['name'])
            ->setContactEmail($data['contactEmail'] ?? null)
            ->setContactPhone($data['contactPhone'] ?? null)
            ->setAddressLine($data['addressLine'] ?? null)
            ->setCity($data['city'] ?? null)
            ->setCountry($data['country'] ?? null)
            ->setTaxIdentifier($data['taxIdentifier'] ?? null)
            ->setNotes($data['notes'] ?? null);

        $activeFlag = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($activeFlag === null) {
            return $this->json(['error' => 'Invalid active flag'], 400);
        }
        $supplier->setActive($activeFlag);

        $em = $doctrine->getManager();
        $em->persist($supplier);
        $em->flush();

        return $this->json($supplier, 201, [], ['groups' => ['supplier:read']]);
    }

    public function update(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid supplier id'], 400);
        }

        $supplier = $doctrine->getRepository(Supplier::class)->find((int) $id);
        if (!$supplier) {
            return $this->json(['error' => 'Supplier not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        if (isset($data['name'])) {
            $supplier->setName((string) $data['name']);
        }
        if (array_key_exists('contactEmail', $data)) {
            $supplier->setContactEmail($data['contactEmail']);
        }
        if (array_key_exists('contactPhone', $data)) {
            $supplier->setContactPhone($data['contactPhone']);
        }
        if (array_key_exists('addressLine', $data)) {
            $supplier->setAddressLine($data['addressLine']);
        }
        if (array_key_exists('city', $data)) {
            $supplier->setCity($data['city']);
        }
        if (array_key_exists('country', $data)) {
            $supplier->setCountry($data['country']);
        }
        if (array_key_exists('taxIdentifier', $data)) {
            $supplier->setTaxIdentifier($data['taxIdentifier']);
        }
        if (array_key_exists('notes', $data)) {
            $supplier->setNotes($data['notes']);
        }
        if (array_key_exists('active', $data)) {
            $activeFlag = filter_var($data['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($activeFlag === null) {
                return $this->json(['error' => 'Invalid active flag'], 400);
            }
            $supplier->setActive($activeFlag);
        }

        $em = $doctrine->getManager();
        $em->persist($supplier);
        $em->flush();

        return $this->json($supplier, 200, [], ['groups' => ['supplier:read']]);
    }

    public function deactivate(string $id, Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        if (!ctype_digit($id) || (int) $id <= 0) {
            return $this->json(['error' => 'Invalid supplier id'], 400);
        }

        $supplier = $doctrine->getRepository(Supplier::class)->find((int) $id);
        if (!$supplier) {
            return $this->json(['error' => 'Supplier not found'], 404);
        }

        $supplier->setActive(false);
        $em = $doctrine->getManager();
        $em->persist($supplier);
        $em->flush();

        return new JsonResponse(null, 204);
    }
}
