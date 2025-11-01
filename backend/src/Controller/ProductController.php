<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends AbstractController
{
    public function list(): JsonResponse
    {
        // Simple demo resources related to the Biblioteka project
        $products = [
            ['id' => 1, 'name' => 'Study Room Reservation', 'description' => 'Reservation slot for a study room'],
            ['id' => 2, 'name' => 'PHP Basics Book', 'description' => 'Introductory PHP book available in the library'],
        ];
        return $this->json($products, 200);
    }

    public function getProduct(string $id, Request $request): JsonResponse
    {
        // validate id (must be positive integer)
        if (!ctype_digit($id) || (int)$id <= 0) {
            return $this->json(['error' => 'Invalid id parameter'], 400);
        }

        $products = [
            1 => ['id' => 1, 'name' => 'Study Room Reservation', 'description' => 'Reservation slot for a study room'],
            2 => ['id' => 2, 'name' => 'PHP Basics Book', 'description' => 'Introductory PHP book available in the library'],
        ];

        $pid = (int)$id;
        if (!isset($products[$pid])) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        return $this->json($products[$pid], 200);
    }
}
