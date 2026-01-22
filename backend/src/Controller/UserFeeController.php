<?php
namespace App\Controller;

use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Repository\FineRepository;
use App\Repository\UserRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly UserRepository $users,
        private readonly FineRepository $fines,
        private readonly EntityManagerInterface $em
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

        $user = $this->users->find($userId);
        if (!$user) {
            return $this->jsonError(ApiError::notFound('User'));
        }

        $fees = $this->fines->findOutstandingByUser($user);

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

        $user = $this->users->find($userId);
        if (!$user) {
            return $this->jsonError(ApiError::notFound('User'));
        }

        $fee = $this->fines->findOneByIdAndUser((int) $feeId, $user);
        if (!$fee) {
            return $this->jsonError(ApiError::notFound('Fee'));
        }

        if ($fee->isPaid()) {
            return $this->jsonError(ApiError::badRequest('Fee already paid'));
        }

        $fee->markAsPaid();
        $this->em->flush();

        return $this->json($fee, 200, [], [
            'groups' => ['fine:read', 'loan:read'],
            'json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION,
        ]);
    }
}
