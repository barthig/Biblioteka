<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Legacy stub kept to avoid autowiring errors after removing the reader orders feature.
 * Any direct access now returns HTTP 410 to signal the endpoint's removal.
 */
final class OrderController extends AbstractController
{
    private function gone(): JsonResponse
    {
        return $this->json([
            'error' => 'Orders feature has been removed.'
        ], 410);
    }

    public function list(): JsonResponse
    {
        return $this->gone();
    }

    public function create(): JsonResponse
    {
        return $this->gone();
    }

    public function cancel(): JsonResponse
    {
        return $this->gone();
    }
}
