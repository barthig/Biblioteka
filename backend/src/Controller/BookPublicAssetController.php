<?php
namespace App\Controller;

use App\Dto\ApiError;
use App\Entity\Book;
use App\Entity\BookDigitalAsset;
use App\Repository\BookDigitalAssetRepository;
use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'BookAsset')]
class BookPublicAssetController extends AbstractController
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly BookRepository $bookRepository,
        private readonly BookDigitalAssetRepository $assetRepository
    ) {
    }

    #[OA\Get(
        path: '/api/books/{id}/cover',
        summary: 'Public cover image for a book',
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Image stream', content: new OA\MediaType(mediaType: 'image/*')),
            new OA\Response(response: 404, description: 'Cover not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function cover(int $id, Request $request): BinaryFileResponse|JsonResponse
    {
        $book = $this->bookRepository->find($id);
        if (!$book instanceof Book) {
            return $this->json(ApiError::notFound('Book not found'), 404);
        }

        $assets = $this->assetRepository->findForBook($book);
        if ($assets === []) {
            return $this->json(ApiError::notFound('Cover not found'), 404);
        }

        $asset = $this->pickCoverAsset($assets);
        if (!$asset instanceof BookDigitalAsset) {
            return $this->json(ApiError::notFound('Cover not found'), 404);
        }

        $path = $this->assetDirectory() . DIRECTORY_SEPARATOR . $asset->getStorageName();
        if (!is_file($path)) {
            return $this->json(ApiError::notFound('Cover not found'), 404);
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $asset->getMimeType());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $asset->getOriginalFilename()
        );
        $response->setMaxAge(3600);
        $response->setPublic();

        return $response;
    }

    /**
     * @param BookDigitalAsset[] $assets
     */
    private function pickCoverAsset(array $assets): ?BookDigitalAsset
    {
        $best = null;
        foreach ($assets as $asset) {
            $mime = strtolower($asset->getMimeType());
            $label = strtolower($asset->getLabel());
            $isImage = str_starts_with($mime, 'image/');
            $looksLikeCover = $isImage && ($label !== '' && (str_contains($label, 'cover') || str_contains($label, 'okład') || str_contains($label, 'oklad')));

            if ($looksLikeCover) {
                return $asset;
            }
            if ($isImage && $best === null) {
                $best = $asset;
            }
        }
        return $best; // first image fallback
    }

    private function assetDirectory(): string
    {
        return $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'digital-assets';
    }
}
