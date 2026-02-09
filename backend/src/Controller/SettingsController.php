<?php
declare(strict_types=1);
namespace App\Controller;

use App\Controller\Traits\ValidationTrait;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Request\UpdateSettingsRequest;
use App\Service\Auth\SecurityService;
use App\Service\System\SystemSettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Settings')]
class SettingsController extends AbstractController
{
    use ValidationTrait;
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly SystemSettingsService $settingsService
    ) {
    }

    #[OA\Get(
        path: '/api/settings',
        summary: 'Get system settings',
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(name: 'integrationsDown', in: 'query', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 503, description: 'Service unavailable', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function getSettings(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        if ($request->query->getBoolean('integrationsDown', false)) {
            return $this->jsonError(new ApiError(
                code: 'SERVICE_UNAVAILABLE',
                message: 'Integration service unavailable',
                statusCode: 503
            ));
        }

        $settings = $this->settingsService->getAll();
        
        // Add integrations status (hardcoded for now)
        $settings['integrations'] = [
            'queue' => 'ok',
            'email' => 'ok',
        ];

        return $this->json($settings, 200);
    }

    #[OA\Patch(
        path: '/api/settings',
        summary: 'Update system settings',
        tags: ['Settings'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'loanLimitPerUser', type: 'integer', nullable: true),
                    new OA\Property(property: 'loanDurationDays', type: 'integer', nullable: true),
                    new OA\Property(property: 'notificationsEnabled', type: 'boolean', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Invalid JSON', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 503, description: 'Service unavailable', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function updateSettings(Request $request, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->jsonError(ApiError::badRequest('Invalid JSON payload'));
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
                return $this->jsonError(ApiError::unprocessable('loanLimitPerUser must be between 1 and 20'));
            }
            $toUpdate['loanLimitPerUser'] = $limit;
        }

        if (array_key_exists('loanDurationDays', $payload)) {
            $duration = (int)$payload['loanDurationDays'];
            if ($duration < 7 || $duration > 60) {
                return $this->jsonError(ApiError::unprocessable('loanDurationDays must be between 7 and 60'));
            }
            $toUpdate['loanDurationDays'] = $duration;
        }

        if (array_key_exists('notificationsEnabled', $payload)) {
            $toUpdate['notificationsEnabled'] = (bool)$payload['notificationsEnabled'];
        }

        if ($request->headers->get('X-Config-Service') === 'offline') {
            return $this->jsonError(new ApiError(
                code: 'SERVICE_UNAVAILABLE',
                message: 'Configuration backend unavailable',
                statusCode: 503
            ));
        }

        // Update settings in database
        $this->settingsService->updateMany($toUpdate);

        return $this->json([
            'updated' => true,
            'settings' => $this->settingsService->getAll(),
        ], 200);
    }
}
