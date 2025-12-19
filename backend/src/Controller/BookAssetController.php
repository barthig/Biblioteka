<?php
namespace App\Controller;

use App\Application\Command\BookAsset\DeleteBookAssetCommand;
use App\Application\Command\BookAsset\UploadBookAssetCommand;
use App\Application\Query\BookAsset\GetBookAssetQuery;
use App\Application\Query\BookAsset\ListBookAssetsQuery;
use App\Entity\BookDigitalAsset;
use App\Controller\Traits\ExceptionHandlingTrait;
use App\Service\SecurityService;
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

class BookAssetController extends AbstractController
{
    use ExceptionHandlingTrait;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly MessageBusInterface $queryBus,
        private readonly MessageBusInterface $commandBus
    ) {
    }

    public function list(int $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
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
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    public function upload(int $id, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $label = isset($data['label']) && is_string($data['label']) ? trim($data['label']) : 'Plik cyfrowy';
        $originalFilename = isset($data['filename']) && is_string($data['filename']) ? trim($data['filename']) : ($label . '.bin');
        $mimeType = isset($data['mimeType']) && is_string($data['mimeType']) ? trim($data['mimeType']) : 'application/octet-stream';
        $content = isset($data['content']) && is_string($data['content']) ? $data['content'] : null;

        if ($content === null) {
            return $this->json(['error' => 'Missing content payload (base64)'], 400);
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
                return $this->json(['error' => $e->getMessage()], $e->getStatusCode());
            }
            $statusCode = match (true) {
                str_contains($e->getMessage(), 'not found') => 404,
                str_contains($e->getMessage(), 'Invalid base64 payload') => 400,
                default => 500,
            };
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }
    }

    public function download(int $id, int $assetId, Request $request, SecurityService $security): BinaryFileResponse|JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        try {
            $envelope = $this->queryBus->dispatch(new GetBookAssetQuery($id, $assetId));
            $asset = $envelope->last(HandledStamp::class)?->getResult();
            
            $path = $this->assetDirectory() . DIRECTORY_SEPARATOR . $asset->getStorageName();
            if (!is_file($path)) {
                return $this->json(['error' => 'File has been removed from storage'], 410);
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
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    public function delete(int $id, int $assetId, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        try {
            $this->commandBus->dispatch(new DeleteBookAssetCommand($id, $assetId));
            return new JsonResponse(null, 204);
        } catch (\Throwable $e) {
            $e = $this->unwrapThrowable($e);
            if ($response = $this->jsonFromHttpException($e)) {
                return $response;
            }
            return $this->json(['error' => $e->getMessage()], 404);
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
