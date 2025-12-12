<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Author\GetAuthorQuery;
use App\Entity\Author;
use App\Repository\AuthorRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetAuthorHandler
{
    public function __construct(
        private readonly AuthorRepository $authorRepository
    ) {
    }

    public function __invoke(GetAuthorQuery $query): Author
    {
        $author = $this->authorRepository->find($query->authorId);
        
        if (!$author) {
            throw new NotFoundHttpException('Author not found');
        }

        return $author;
    }
}
