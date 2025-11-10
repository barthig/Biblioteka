<?php
namespace App\Controller;

use App\Entity\BookDigitalAsset;
use App\Repository\BookDigitalAssetRepository;
use App\Repository\BookRepository;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;

class BookAssetController extends AbstractController
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    public function list(
        int $id,
        Request $request,
        BookRepository $bookRepository,
        BookDigitalAssetRepository $assetRepository,
        SecurityService $security
    ): JsonResponse {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $book = $bookRepository->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $items = array_map(fn (BookDigitalAsset $asset) => $this->serializeAsset($asset), $assetRepository->findForBook($book));

        return $this->json(['items' => $items]);
    }

    public function upload(
        int $id,
        Request $request,
        BookRepository $bookRepository,
        ManagerRegistry $doctrine,
        SecurityService $security
    ): JsonResponse {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $book = $bookRepository->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $label = isset($data['label']) && is_string($data['label']) ? trim($data['label']) : 'Plik cyfrowy';
        $originalFilename = isset($data['filename']) && is_string($data['filename']) ? trim($data['filename']) : ($label . '.bin');
        $mimeType = isset($data['mimeType']) && is_string($data['mimeType']) ? trim($data['mimeType']) : 'application/octet-stream';
        $content = isset($data['content']) && is_string($data['content']) ? $data['content'] : null;

        if ($content === null) {
            return $this->json(['error' => 'Missing content payload (base64)'], 400);
        }

        $binary = base64_decode($content, true);
        if ($binary === false) {
            return $this->json(['error' => 'Invalid base64 payload'], 400);
        }

        try {
            $storageName = $this->storeBinary($binary, pathinfo($originalFilename, PATHINFO_EXTENSION));
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }

        $asset = (new BookDigitalAsset())
            ->setBook($book)
            ->setLabel($label)
            ->setOriginalFilename($originalFilename)
            ->setMimeType($mimeType)
            ->setSize(strlen($binary))
            ->setStorageName($storageName);

        $book->addDigitalAsset($asset);

        $em = $doctrine->getManager();
        $em->persist($asset);
        $em->flush();

        return $this->json($this->serializeAsset($asset), 201);
    }

    public function download(
        int $id,
        int $assetId,
        Request $request,
        BookRepository $bookRepository,
        BookDigitalAssetRepository $assetRepository,
        SecurityService $security
    ): BinaryFileResponse|JsonResponse {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $book = $bookRepository->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $asset = $assetRepository->find($assetId);
        if (!$asset || $asset->getBook()->getId() !== $book->getId()) {
            return $this->json(['error' => 'Asset not found'], 404);
        }

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
    }

    public function delete(
        int $id,
        int $assetId,
        Request $request,
        BookRepository $bookRepository,
        BookDigitalAssetRepository $assetRepository,
        ManagerRegistry $doctrine,
        SecurityService $security
    ): JsonResponse {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $book = $bookRepository->find($id);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        $asset = $assetRepository->find($assetId);
        if (!$asset || $asset->getBook()->getId() !== $book->getId()) {
            return $this->json(['error' => 'Asset not found'], 404);
        }

        $path = $this->assetDirectory() . DIRECTORY_SEPARATOR . $asset->getStorageName();
        if (is_file($path)) {
            @unlink($path);
        }

        $em = $doctrine->getManager();
        $book->removeDigitalAsset($asset);
        $em->remove($asset);
        $em->flush();

        return new JsonResponse(null, 204);
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

    private function storeBinary(string $binary, string $extension): string
    {
        $extension = trim($extension) !== '' ? strtolower($extension) : 'bin';
        $filename = sprintf('%s.%s', bin2hex(random_bytes(12)), $extension);
        $dir = $this->assetDirectory();
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create asset storage directory: ' . $dir);
        }

        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        if (file_put_contents($path, $binary) === false) {
            throw new \RuntimeException('Unable to store uploaded asset');
        }

        return $filename;
    }

    private function assetDirectory(): string
    {
        return $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'digital-assets';
    }
}
