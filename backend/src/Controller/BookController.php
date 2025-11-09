<?php
namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BookController extends AbstractController
{
    public function list(BookRepository $repo): JsonResponse
    {
        $books = $repo->findAll();
        return $this->json($books, 200, [], ['groups' => ['book:read']]);
    }

    public function getBook(int $id, BookRepository $repo): JsonResponse
    {
        $book = $repo->find($id);
        if (!$book) return $this->json(['error' => 'Book not found'], 404);
        return $this->json($book, 200, [], ['groups' => ['book:read']]);
    }

    public function create(
        Request $request,
        ManagerRegistry $doctrine,
        SecurityService $security,
        AuthorRepository $authorRepository,
        CategoryRepository $categoryRepository
    ): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        $data = json_decode($request->getContent(), true) ?? [];

        if (empty($data['title'])) {
            return $this->json(['error' => 'Missing title'], 400);
        }

        $authorId = $data['authorId'] ?? null;
        if (!$authorId || !ctype_digit((string) $authorId)) {
            return $this->json(['error' => 'Invalid authorId'], 400);
        }

        $author = $authorRepository->find((int) $authorId);
        if (!$author) {
            return $this->json(['error' => 'Author not found'], 404);
        }

        $categoryIds = $data['categoryIds'] ?? [];
        if (!is_array($categoryIds) || empty($categoryIds)) {
            return $this->json(['error' => 'At least one category is required'], 400);
        }

        $uniqueCategoryIds = array_unique(array_map('intval', $categoryIds));
        $categories = $categoryRepository->findBy(['id' => $uniqueCategoryIds]);
        if (count($categories) !== count($uniqueCategoryIds)) {
            return $this->json(['error' => 'One or more categories not found'], 404);
        }

        $totalCopies = isset($data['totalCopies']) ? (int) $data['totalCopies'] : (int) ($data['copies'] ?? 1);
        if ($totalCopies < 1) {
            return $this->json(['error' => 'totalCopies must be at least 1'], 400);
        }

        $copies = isset($data['copies']) ? (int) $data['copies'] : $totalCopies;
        $copies = max(0, min($copies, $totalCopies));

        $book = (new Book())
            ->setTitle($data['title'])
            ->setAuthor($author)
            ->setIsbn($data['isbn'] ?? null)
            ->setTotalCopies($totalCopies)
            ->setCopies($copies)
            ->setDescription($data['description'] ?? null);

        foreach ($categories as $category) {
            $book->addCategory($category);
        }

        $em = $doctrine->getManager();
        $em->persist($book);
        $em->flush();

        return $this->json($book, 201, [], ['groups' => ['book:read']]);
    }

    public function update(
        int $id,
        Request $request,
        BookRepository $repo,
        ManagerRegistry $doctrine,
        SecurityService $security,
        AuthorRepository $authorRepository,
        CategoryRepository $categoryRepository
    ): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        $book = $repo->find($id);
        if (!$book) return $this->json(['error' => 'Book not found'], 404);
        $data = json_decode($request->getContent(), true) ?? [];

        if (!empty($data['title'])) {
            $book->setTitle($data['title']);
        }

        if (isset($data['authorId'])) {
            $authorId = $data['authorId'];
            if (!ctype_digit((string) $authorId)) {
                return $this->json(['error' => 'Invalid authorId'], 400);
            }
            $author = $authorRepository->find((int) $authorId);
            if (!$author) {
                return $this->json(['error' => 'Author not found'], 404);
            }
            $book->setAuthor($author);
        }

        if (isset($data['categoryIds']) && is_array($data['categoryIds'])) {
            $uniqueCategoryIds = array_unique(array_map('intval', $data['categoryIds']));
            if (empty($uniqueCategoryIds)) {
                return $this->json(['error' => 'At least one category is required'], 400);
            }
            $categories = $categoryRepository->findBy(['id' => $uniqueCategoryIds]);
            if (count($categories) !== count($uniqueCategoryIds)) {
                return $this->json(['error' => 'One or more categories not found'], 404);
            }
            $book->clearCategories();
            foreach ($categories as $category) {
                $book->addCategory($category);
            }
        }

        if (isset($data['totalCopies'])) {
            $totalCopies = (int) $data['totalCopies'];
            if ($totalCopies < 1) {
                return $this->json(['error' => 'totalCopies must be at least 1'], 400);
            }
            $book->setTotalCopies($totalCopies);
        }

        if (isset($data['copies'])) {
            $copies = (int) $data['copies'];
            if ($copies < 0) {
                return $this->json(['error' => 'copies must be non-negative'], 400);
            }
            $book->setCopies($copies);
        }

        if (array_key_exists('description', $data)) {
            $book->setDescription($data['description']);
        }

        if (array_key_exists('isbn', $data)) {
            $book->setIsbn($data['isbn']);
        }

        $em = $doctrine->getManager();
        $em->persist($book);
        $em->flush();

        return $this->json($book, 200, [], ['groups' => ['book:read']]);
    }

    public function delete(int $id, BookRepository $repo, ManagerRegistry $doctrine, Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        $book = $repo->find($id);
        if (!$book) return $this->json(['error' => 'Book not found'], 404);
        $em = $doctrine->getManager();
        $em->remove($book);
        $em->flush();
        return new JsonResponse(null, 204);
    }
}
