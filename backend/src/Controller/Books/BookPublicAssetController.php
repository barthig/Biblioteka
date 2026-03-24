<?php
declare(strict_types=1);
namespace App\Controller\Books;

use App\Controller\Traits\ExceptionHandlingTrait;
use App\Dto\ApiError;
use App\Entity\Book;
use App\Entity\BookDigitalAsset;
use App\Repository\BookDigitalAssetRepository;
use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'BookAsset')]
class BookPublicAssetController extends AbstractController
{
    use ExceptionHandlingTrait;

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
    public function cover(int $id, Request $request): Response
    {
        $book = $this->bookRepository->find($id);
        if (!$book instanceof Book) {
            return $this->placeholderResponse('?', 'Book not found');
        }

        $assets = $this->assetRepository->findForBook($book);
        if ($assets === []) {
            return $this->placeholderResponse($this->coverLetter($book->getTitle()), 'Cover not found');
        }

        $asset = $this->pickCoverAsset($assets);
        if (!$asset instanceof BookDigitalAsset) {
            return $this->placeholderResponse($this->coverLetter($book->getTitle()), 'Cover not found');
        }

        $path = $this->assetDirectory() . DIRECTORY_SEPARATOR . $asset->getStorageName();
        if (!is_file($path)) {
            return $this->placeholderResponse($this->coverLetter($book->getTitle()), 'Cover not found');
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

    private function placeholderResponse(string $letter, string $reason): Response
    {
        $safeLetter = htmlspecialchars($letter !== '' ? mb_substr($letter, 0, 1) : '?', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="320" height="480" viewBox="0 0 320 480" role="img" aria-label="Brak okładki">
  <rect width="320" height="480" fill="#e7dcc7"/>
  <rect x="24" y="24" width="272" height="432" rx="18" fill="#f7f2e8" stroke="#c9bda7" stroke-width="4"/>
  <text x="160" y="255" text-anchor="middle" font-family="Georgia, serif" font-size="128" fill="#8a6f47">$safeLetter</text>
</svg>
SVG;

        $response = new Response($svg, Response::HTTP_OK, ['Content-Type' => 'image/svg+xml; charset=UTF-8']);
        $response->headers->set('Cache-Control', 'public, max-age=3600');
        $response->headers->set('X-Cover-Placeholder', $reason);

        return $response;
    }

    private function coverLetter(?string $title): string
    {
        $trimmed = trim((string) $title);
        if ($trimmed === '') {
            return '?';
        }

        return mb_strtoupper(mb_substr($trimmed, 0, 1));
    }

    private function assetDirectory(): string
    {
        return $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'digital-assets';
    }
}

