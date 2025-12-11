<?php
namespace App\Controller;

use App\Service\RegistrationException;
use App\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RegistrationController extends AbstractController
{
    public function register(Request $request, RegistrationService $registrationService): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?: [];

        try {
            $token = $registrationService->register($payload);
        } catch (RegistrationException $exception) {
            $status = $exception->getCode();
            if ($status < 400 || $status >= 600) {
                $status = 400;
            }

            return $this->json(['error' => $exception->getMessage()], $status);
        } catch (\Throwable $error) {
            return $this->json(['error' => 'Nie udało się utworzyć konta.'], 500);
        }

        return $this->json([
            'status' => 'pending_verification',
            'userId' => $token->getUser()->getId(),
            'verificationToken' => $token->getToken(),
        ], 201);
    }

    public function verify(string $token, RegistrationService $registrationService): JsonResponse
    {
        try {
            $user = $registrationService->verify($token);
        } catch (RegistrationException $exception) {
            $status = $exception->getCode();
            if ($status < 400 || $status >= 600) {
                $status = 400;
            }

            return $this->json(['error' => $exception->getMessage()], $status);
        } catch (\Throwable $error) {
            return $this->json(['error' => 'Nie udało się zweryfikować konta.'], 500);
        }

        $response = [
            'status' => 'account_verified',
            'userId' => $user->getId(),
            'pendingApproval' => $user->isPendingApproval(),
        ];

        return $this->json($response, 200);
    }
}
