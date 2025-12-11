<?php
namespace App\Controller;

use App\Controller\Traits\ValidationTrait;
use App\Request\UpdateSettingsRequest;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SettingsController extends AbstractController
{
    use ValidationTrait;
    private array $defaults = [
        'loanLimitPerUser' => 5,
        'loanDurationDays' => 14,
        'notificationsEnabled' => true,
        'integrations' => [
            'queue' => 'ok',
            'email' => 'ok',
        ],
    ];

    public function getSettings(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        if ($request->query->getBoolean('integrationsDown', false)) {
            return $this->json(['error' => 'Integration service unavailable'], 503);
        }

        return $this->json($this->defaults, 200);
    }

    public function updateSettings(Request $request, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Invalid JSON payload'], 400);
        }
        
        // Walidacja DTO
        $dto = $this->mapArrayToDto($payload, new UpdateSettingsRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        $settings = $this->defaults;

        if (array_key_exists('loanLimitPerUser', $payload)) {
            $limit = (int)$payload['loanLimitPerUser'];
            if ($limit < 1 || $limit > 20) {
                return $this->json(['error' => 'loanLimitPerUser must be between 1 and 20'], 422);
            }
            $settings['loanLimitPerUser'] = $limit;
        }

        if (array_key_exists('loanDurationDays', $payload)) {
            $duration = (int)$payload['loanDurationDays'];
            if ($duration < 7 || $duration > 60) {
                return $this->json(['error' => 'loanDurationDays must be between 7 and 60'], 422);
            }
            $settings['loanDurationDays'] = $duration;
        }

        if (array_key_exists('notificationsEnabled', $payload)) {
            $settings['notificationsEnabled'] = (bool)$payload['notificationsEnabled'];
        }

        if ($request->headers->get('X-Config-Service') === 'offline') {
            return $this->json(['error' => 'Configuration backend unavailable'], 503);
        }

        return $this->json([
            'updated' => true,
            'settings' => $settings,
        ], 200);
    }
}
