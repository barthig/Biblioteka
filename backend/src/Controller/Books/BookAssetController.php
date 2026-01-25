<?php
namespace App\Controller\Books;

use App\Application\Command\BookAsset\DeleteBookAssetCommand;
use App\Application\Command\BookAsset\UploadBookAssetCommand;
use App\Application\Query\BookAsset\GetBookAssetQuery;
use App\Application\Query\BookAsset\ListBookAssetsQuery;
use App\Dto\ApiError;
use App\Entity\BookDigitalAsset;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Service\Auth\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'BookAsset')]
class BookAssetController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly MessageBusInterface $queryBus,
        private readonly MessageBusInterface $commandBus
    ) {
    }

    #[OA\Get(
        path: '/api/admin/books/{id}/assets',
        summary: 'List book assets',
        tags: ['Assets'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Book not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function list(int $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        try {
            $envelope = $this->queryBus->dispatch(new ListBookAssetsQuery($id));
            $result = $envelope->last(HandledStamp::class)?->getResult();
            return $this->json($result);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonError(ApiError::notFound($e->getMessage()));
        }
    }

    #[OA\Post(
        path: '/api/admin/books/{id}/assets',
        summary: 'Upload book asset',
        tags: ['Assets'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'label', type: 'string', nullable: true),
                    new OA\Property(property: 'filename', type: 'string', nullable: true),
                    new OA\Property(property: 'mimeType', type: 'string', nullable: true),
                    new OA\Property(property: 'content', type: 'string', description: 'Base64 file content'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Book not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function upload(int $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $label = isset($data['label']) && is_string($data['label']) ? trim($data['label']) : 'Plik cyfrowy';
        $originalFilename = isset($data['filename']) && is_string($data['filename']) ? trim($data['filename']) : ($label . '.bin');
        $mimeType = isset($data['mimeType']) && is_string($data['mimeType']) ? trim($data['mimeType']) : 'application/octet-stream';
        $content = isset($data['content']) && is_string($data['content']) ? $data['content'] : null;

        if ($content === null) {
            return $this->jsonError(ApiError::badRequest('Missing content payload (base64)'));
        }

        try {
            $command = new UploadBookAssetCommand($id, $label, $originalFilename, $mimeType, $content);
            $envelope = $this->commandBus->dispatch($command);
            $asset = $envelope->last(HandledStamp::class)?->getResult();
            
            return $this->json($this->serializeAsset($asset), 201);
        } catch (\Throwable $e) {
            if ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious() ?? $e;
            }
            if ($e instanceof HttpExceptionInterface) {
                return $this->jsonError(ApiError::fromException($e));
            }
            $statusCode = match (true) {
                str_contains($e->getMessage(), 'not found') => 404,
                str_contains($e->getMessage(), 'Invalid base64 payload') => 400,
                default => 500,
            };
            if ($statusCode === 404) {
                return $this->jsonError(ApiError::notFound($e->getMessage()));
            } elseif ($statusCode === 400) {
                return $this->jsonError(ApiError::badRequest($e->getMessage()));
            }
            return $this->jsonError(ApiError::internalError($e->getMessage()));
        }
    }

    #[OA\Get(
        path: '/api/admin/books/{id}/assets/{assetId}',
        summary: 'Download book asset',
        tags: ['Assets'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'assetId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File download', content: new OA\MediaType(mediaType: 'application/octet-stream')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 410, description: 'File removed', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function download(int $id, int $assetId, Request $request, SecurityService $security): BinaryFileResponse|JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        try {
            $query = new GetBookAssetQuery(bookId: $id, assetId: $assetId);
            $envelope = $this->queryBus->dispatch($query);
            $asset = $envelope->last(HandledStamp::class)?->getResult();
            
            if (!$asset) {
                return $this->jsonError(ApiError::notFound('Asset'));
            }
            
            $path = $this->assetDirectory() . DIRECTORY_SEPARATOR . $asset->getStorageName();
            if (!is_file($path)) {
                return $this->jsonError(ApiError::gone('File has been removed from storage'));
            }

            $response = new BinaryFileResponse($path);
            $response->headers->set('Content-Type', $asset->getMimeType());
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $asset->getOriginalFilename()
            );

            return $response;
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonError(ApiError::notFound($e->getMessage()));
        }
    }

    #[OA\Delete(
        path: '/api/admin/books/{id}/assets/{assetId}',
        summary: 'Delete book asset',
        tags: ['Assets'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'assetId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function delete(int $id, int $assetId, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->jsonError(ApiError::forbidden());
        }

        try {
            $this->commandBus->dispatch(new DeleteBookAssetCommand($id, $assetId));
            return new JsonResponse(null, 204);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->jsonError(ApiError::notFound($e->getMessage()));
        }
    }

    private function serializeAsset(BookDigitalAsset $asset): array
    {
        return [
            'id' => $asset->getId(),
            'label' => $asset->getLabel(),
            'filename' => $asset->getOriginalFilename(),
            'mimeType' => $asset->getMimeType(),
            'size' => $asset->getSize(),
            'createdAt' => $asset->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    private function assetDirectory(): string
    {
        return $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'digital-assets';
    }
}

