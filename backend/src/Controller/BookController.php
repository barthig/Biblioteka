<?php
namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Book;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;

class BookController extends AbstractController
{
    public function list(BookRepository $repo): JsonResponse
    {
        $books = $repo->findAll();
        return $this->json($books, 200);
    }

    public function getBook(int $id, BookRepository $repo): JsonResponse
    {
        $book = $repo->find($id);
        if (!$book) return $this->json(['error' => 'Book not found'], 404);
        return $this->json($book, 200);
    }

    public function create(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        $data = json_decode($request->getContent(), true);
        if (empty($data['title']) || empty($data['author'])) {
            return $this->json(['error' => 'Missing title or author'], 400);
        }
        $book = new Book();
        $book->setTitle($data['title'])->setAuthor($data['author'])->setIsbn($data['isbn'] ?? null)->setCopies((int)($data['copies'] ?? 1));
        $em = $doctrine->getManager();
        $em->persist($book);
        $em->flush();
        return $this->json($book, 201);
    }

    public function update(int $id, Request $request, BookRepository $repo, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }
        $book = $repo->find($id);
        if (!$book) return $this->json(['error' => 'Book not found'], 404);
        $data = json_decode($request->getContent(), true);
        if (!empty($data['title'])) $book->setTitle($data['title']);
        if (!empty($data['author'])) $book->setAuthor($data['author']);
        if (isset($data['copies'])) $book->setCopies((int)$data['copies']);
        $em = $doctrine->getManager();
        $em->persist($book);
        $em->flush();
        return $this->json($book, 200);
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
        return $this->json(null, 204);
    }
}
