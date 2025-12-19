<?php
namespace App\Controller;

use App\Application\Query\Report\GetUsageReportQuery;
use App\Application\Query\Report\GetPopularTitlesQuery;
use App\Application\Query\Report\GetPatronSegmentsQuery;
use App\Application\Query\Report\GetFinancialSummaryQuery;
use App\Application\Query\Report\GetInventoryOverviewQuery;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class ReportController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $queryBus
    ) {
    }

    public function usage(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $from = $request->query->get('from');
        $to = $request->query->get('to');

        if (($from && !strtotime($from)) || ($to && !strtotime($to))) {
            return $this->json(['message' => 'Invalid date range'], 400);
        }

        $envelope = $this->queryBus->dispatch(new GetUsageReportQuery($from, $to));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        if ($result === null) {
            return new JsonResponse(null, 204);
        }

        return $this->json($result, 200, [], ['json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION]);
    }

    public function export(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $format = $request->query->get('format');
        if ($format === null) {
            return $this->json(['message' => 'Format query parameter is required'], 400);
        }

        if (!in_array($format, ['csv', 'json', 'pdf'], true)) {
            return $this->json(['message' => 'Unsupported export format'], 422);
        }

        if ($format === 'pdf' && $request->query->getBoolean('simulateFailure')) {
            return $this->json(['message' => 'Failed to generate PDF report'], 500);
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

    public function popularTitles(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $limit = max(1, min(50, $request->query->getInt('limit', 10)));
        $days = max(0, $request->query->getInt('days', 90));

        $envelope = $this->queryBus->dispatch(new GetPopularTitlesQuery($limit, $days));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result, 200, [], ['json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION]);
    }

    public function patronSegments(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $envelope = $this->queryBus->dispatch(new GetPatronSegmentsQuery());
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result, 200, [], ['json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION]);
    }

    public function financialSummary(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $envelope = $this->queryBus->dispatch(new GetFinancialSummaryQuery());
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result, 200, [], ['json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION]);
    }

    public function inventoryOverview(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $envelope = $this->queryBus->dispatch(new GetInventoryOverviewQuery());
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json($result, 200, [], ['json_encode_options' => \JSON_PRESERVE_ZERO_FRACTION]);
    }
}
