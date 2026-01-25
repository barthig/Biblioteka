<?php
namespace App\Controller\User;

use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Service\Loan\FeeService;
use App\Service\Auth\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Fees')]
class UserFeeController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly SecurityService $security,
        private readonly FeeService $feeService
    ) {
    }

    #[OA\Get(
        path: '/api/me/fees',
        summary: 'List current user fees',
        tags: ['Fees'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Fine')
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->jsonError(ApiError::unauthorized());
        }

        try {
            $fees = $this->feeService->listOutstandingFees($userId);
        } catch (\RuntimeException $e) {
            return $this->jsonError(ApiError::notFound('User'));
        }

        return $this->json([
            'data' => $fees,
        ], 200, [], [
            'groups' => ['fine:read', 'loan:read'],
            'json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION,
        ]);
    }

    #[OA\Post(
        path: '/api/me/fees/{feeId}/pay',
        summary: 'Mark user fee as paid',
        tags: ['Fees'],
        parameters: [
            new OA\Parameter(name: 'feeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Fine')),
            new OA\Response(response: 400, description: 'Already paid', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Fee not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function pay(string $feeId, Request $request): JsonResponse
    {
        if (!ctype_digit($feeId) || (int) $feeId <= 0) {
            return $this->jsonError(ApiError::badRequest('Invalid fee id'));
        }

        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->jsonError(ApiError::unauthorized());
        }

        try {
            $fee = $this->feeService->markFeePaid($userId, (int) $feeId);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonError(ApiError::badRequest($e->getMessage()));
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            if ($message === 'User not found') {
                return $this->jsonError(ApiError::notFound('User'));
            }
            if ($message === 'Fee not found') {
                return $this->jsonError(ApiError::notFound('Fee'));
            }
            return $this->jsonError(ApiError::internalError('Internal error'));
        }

        return $this->json($fee, 200, [], [
            'groups' => ['fine:read', 'loan:read'],
            'json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION,
        ]);
    }
}

