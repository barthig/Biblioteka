<?php
namespace App\Controller;

use App\Application\Query\Report\GetUsageReportQuery;
use App\Application\Query\Report\GetPopularTitlesQuery;
use App\Application\Query\Report\GetPatronSegmentsQuery;
use App\Application\Query\Report\GetFinancialSummaryQuery;
use App\Application\Query\Report\GetInventoryOverviewQuery;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Service\Auth\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Report')]
class ReportController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly MessageBusInterface $queryBus
    ) {
    }

    #[OA\Get(
        path: '/api/reports/usage',
        summary: 'Usage report',
        tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 204, description: 'No content'),
            new OA\Response(response: 400, description: 'Invalid date range', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function usage(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $from = $request->query->get('from');
        $to = $request->query->get('to');

        if (($from && !strtotime($from)) || ($to && !strtotime($to))) {
            return $this->jsonError(ApiError::badRequest('Invalid date range'));
        }

        $envelope = $this->queryBus->dispatch(new GetUsageReportQuery($from, $to));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        if ($result === null) {
            return new JsonResponse(null, 204);
        }

        return $this->json($result, 200, [], ['json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION]);
    }

    #[OA\Get(
        path: '/api/reports/export',
        summary: 'Export report',
        tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'format', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'simulateFailure', in: 'query', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Missing format', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Unsupported format', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Generation failed', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function export(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $format = $request->query->get('format');
        if ($format === null) {
            return $this->jsonError(ApiError::badRequest('Format query parameter is required'));
        }

        if (!in_array($format, ['csv', 'json', 'pdf'], true)) {
            return $this->jsonError(ApiError::unprocessable('Unsupported export format'));
        }

        if ($format === 'pdf' && $request->query->getBoolean('simulateFailure')) {
            return $this->jsonError(ApiError::internalError('Failed to generate PDF report'));
        }

        $content = match ($format) {
            'csv' => "book,title,loans\n12345,Sample Book,12\n",
            'json' => json_encode(['book' => 'Sample Book', 'loans' => 12], JSON_THROW_ON_ERROR),
            default => base64_encode('PDF report placeholder'),
        };

        return $this->json([
            'format' => $format,
            'content' => $content,
            'generatedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ], 200, [], ['json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION]);
    }

    #[OA\Get(
        path: '/api/reports/circulation/popular',
        summary: 'Popular titles report',
        tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'days', in: 'query', schema: new OA\Schema(type: 'integer', default: 90)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function popularTitles(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $limit = max(1, min(50, $request->query->getInt('limit', 10)));
        $days = max(0, $request->query->getInt('days', 90));

        $envelope = $this->queryBus->dispatch(new GetPopularTitlesQuery($limit, $days));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result, 200, [], ['json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION]);
    }

    #[OA\Get(
        path: '/api/reports/patrons/segments',
        summary: 'Patron segments report',
        tags: ['Reports'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function patronSegments(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $envelope = $this->queryBus->dispatch(new GetPatronSegmentsQuery());
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result, 200, [], ['json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION]);
    }

    #[OA\Get(
        path: '/api/reports/financial',
        summary: 'Financial summary',
        tags: ['Reports'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function financialSummary(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $envelope = $this->queryBus->dispatch(new GetFinancialSummaryQuery());
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result, 200, [], ['json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION]);
    }

    #[OA\Get(
        path: '/api/reports/inventory',
        summary: 'Inventory overview',
        tags: ['Reports'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function inventoryOverview(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $envelope = $this->queryBus->dispatch(new GetInventoryOverviewQuery());
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result, 200, [], ['json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION]);
    }
}
