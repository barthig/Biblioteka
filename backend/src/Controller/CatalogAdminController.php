<?php
namespace App\Controller;

use App\Entity\Book;
use App\Entity\BookCopy;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CatalogAdminController extends AbstractController
{
    public function export(Request $request, BookRepository $bookRepository, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $books = $bookRepository->findBy([], ['id' => 'ASC']);
        $payload = array_map(function (Book $book) {
            $categories = [];
            foreach ($book->getCategories() as $category) {
                $categories[] = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                ];
            }

            return [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'author' => [
                    'id' => $book->getAuthor()->getId(),
                    'name' => $book->getAuthor()->getName(),
                ],
                'isbn' => $book->getIsbn(),
                'publisher' => $book->getPublisher(),
                'publicationYear' => $book->getPublicationYear(),
                'resourceType' => $book->getResourceType(),
                'signature' => $book->getSignature(),
                'description' => $book->getDescription(),
                'categories' => $categories,
                'copies' => $book->getCopies(),
                'totalCopies' => $book->getTotalCopies(),
            ];
        }, $books);

        return $this->json(['items' => $payload]);
    }

    public function import(
        Request $request,
        ManagerRegistry $doctrine,
        AuthorRepository $authorRepository,
        CategoryRepository $categoryRepository,
        SecurityService $security
    ): JsonResponse {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
            return $this->json(['error' => 'Invalid payload structure'], 400);
        }

        $em = $doctrine->getManager();
        $imported = [];
        foreach ($data['items'] as $idx => $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = isset($item['title']) && is_string($item['title']) ? trim($item['title']) : null;
            $authorName = isset($item['author']) ? $item['author'] : null;
            if (is_array($authorName) && isset($authorName['name'])) {
                $authorName = $authorName['name'];
            }
            $authorName = is_string($authorName) ? trim($authorName) : null;

            if ($title === null || $title === '' || $authorName === null || $authorName === '') {
                continue;
            }

            $author = $authorRepository->findOneBy(['name' => $authorName]);
            if (!$author) {
                continue;
            }

            $book = new Book();
            $book
                ->setTitle($title)
                ->setAuthor($author)
                ->setIsbn(isset($item['isbn']) ? ($item['isbn'] ?: null) : null)
                ->setPublisher(isset($item['publisher']) ? ($item['publisher'] ?: null) : null)
                ->setPublicationYear(isset($item['publicationYear']) ? (int) $item['publicationYear'] : null)
                ->setResourceType(isset($item['resourceType']) ? ($item['resourceType'] ?: null) : null)
                ->setSignature(isset($item['signature']) ? ($item['signature'] ?: null) : null)
                ->setDescription(isset($item['description']) ? ($item['description'] ?: null) : null);

            if (isset($item['categoryIds']) && is_array($item['categoryIds'])) {
                $categories = $categoryRepository->findBy(['id' => $item['categoryIds']]);
                foreach ($categories as $category) {
                    $book->addCategory($category);
                }
            }

            $em->persist($book);

            $count = isset($item['copies']) ? max(0, (int) $item['copies']) : 0;
            for ($i = 0; $i < $count; ++$i) {
                $copy = (new BookCopy())
                    ->setInventoryCode(sprintf('IMPORT-%s-%03d', strtoupper(bin2hex(random_bytes(3))), $i + 1))
                    ->setStatus(BookCopy::STATUS_AVAILABLE)
                    ->setAccessType(BookCopy::ACCESS_STORAGE);

                $book->addInventoryCopy($copy);
                $em->persist($copy);
            }

            $book->recalculateInventoryCounters();
            $imported[] = $title;
        }

        $em->flush();

        return $this->json([
            'importedCount' => count($imported),
            'items' => $imported,
        ]);
    }
}
