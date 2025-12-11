<?php
namespace App\Controller;

use App\Controller\Traits\ValidationTrait;
use App\Entity\Book;
use App\Entity\Favorite;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Request\AddFavoriteRequest;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FavoriteController extends AbstractController
{
    use ValidationTrait;
    public function list(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        /** @var FavoriteRepository $repo */
        $repo = $doctrine->getRepository(Favorite::class);
        $favorites = $repo->findByUser($user);

        return $this->json([
            'data' => $favorites,
            'meta' => [
                'total' => count($favorites)
            ]
        ], 200, [], ['groups' => ['favorite:read', 'book:read']]);
    }

    public function add(Request $request, ManagerRegistry $doctrine, SecurityService $security, ValidatorInterface $validator): JsonResponse
    {
        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        
        // Walidacja DTO
        $dto = $this->mapArrayToDto($data, new AddFavoriteRequest());
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }
        
        $bookId = $dto->bookId;

        $userRepo = $doctrine->getRepository(User::class);
        $bookRepo = $doctrine->getRepository(Book::class);
        /** @var FavoriteRepository $favoriteRepo */
        $favoriteRepo = $doctrine->getRepository(Favorite::class);

        $user = $userRepo->find((int) $payload['sub']);
        $book = $bookRepo->find((int) $bookId);
        if (!$user || !$book) {
            return $this->json(['error' => 'User or book not found'], 404);
        }

        if ($favoriteRepo->findOneByUserAndBook($user, $book)) {
            return $this->json(['error' => 'Książka znajduje się już na Twojej półce'], 409);
        }

        $favorite = (new Favorite())
            ->setUser($user)
            ->setBook($book);

        $em = $doctrine->getManager();
        $em->persist($favorite);
        $em->flush();

        return $this->json(['data' => $favorite], 201, [], ['groups' => ['favorite:read', 'book:read']]);
    }

    public function remove(string $bookId, Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!ctype_digit($bookId) || (int) $bookId <= 0) {
            return $this->json(['error' => 'Invalid book id'], 400);
        }

        $payload = $security->getJwtPayload($request);
        if (!$payload || !isset($payload['sub'])) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $user = $doctrine->getRepository(User::class)->find((int) $payload['sub']);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $book = $doctrine->getRepository(Book::class)->find((int) $bookId);
        if (!$book) {
            return $this->json(['error' => 'Book not found'], 404);
        }

        /** @var FavoriteRepository $favoriteRepo */
        $favoriteRepo = $doctrine->getRepository(Favorite::class);
        $favorite = $favoriteRepo->findOneByUserAndBook($user, $book);
        if (!$favorite) {
            return $this->json(['error' => 'Pozycja nie znajduje się na Twojej półce'], 404);
        }

        $em = $doctrine->getManager();
        $em->remove($favorite);
        $em->flush();

        return new JsonResponse(null, 204);
    }
}
