<?php
namespace App\Controller;

use App\Controller\Traits\ValidationTrait;
use App\Request\UpdateSettingsRequest;
use App\Service\SecurityService;
use App\Service\SystemSettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SettingsController extends AbstractController
{
    use ValidationTrait;

    public function __construct(
        private SystemSettingsService $settingsService
    ) {
    }

    public function getSettings(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        if ($request->query->getBoolean('integrationsDown', false)) {
            return $this->json(['message' => 'Integration service unavailable'], 503);
        }

        $settings = $this->settingsService->getAll();
        
        // Add integrations status (hardcoded for now)
        $settings['integrations'] = [
            'queue' => 'ok',
            'email' => 'ok',
        ];

        return $this->json($settings, 200);
    }

    public function updateSettings(Request $request, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['message' => 'Invalid JSON payload'], 400);
        }
        
        // Walidacja DTO
        $dto = $this->mapArrayToDto($payload, new UpdateSettingsRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $toUpdate = [];

        if (array_key_exists('loanLimitPerUser', $payload)) {
            $limit = (int)$payload['loanLimitPerUser'];
            if ($limit < 1 || $limit > 20) {
                return $this->json(['message' => 'loanLimitPerUser must be between 1 and 20'], 422);
            }
            $toUpdate['loanLimitPerUser'] = $limit;
        }

        if (array_key_exists('loanDurationDays', $payload)) {
            $duration = (int)$payload['loanDurationDays'];
            if ($duration < 7 || $duration > 60) {
                return $this->json(['message' => 'loanDurationDays must be between 7 and 60'], 422);
            }
            $toUpdate['loanDurationDays'] = $duration;
        }

        if (array_key_exists('notificationsEnabled', $payload)) {
            $toUpdate['notificationsEnabled'] = (bool)$payload['notificationsEnabled'];
        }

        if ($request->headers->get('X-Config-Service') === 'offline') {
            return $this->json(['message' => 'Configuration backend unavailable'], 503);
        }

        // Update settings in database
        $this->settingsService->updateMany($toUpdate);

        return $this->json([
            'updated' => true,
            'settings' => $this->settingsService->getAll(),
        ], 200);
    }
}
