<?php
declare(strict_types=1);

namespace App\Application\Handler\Query;

use App\Application\Query\User\GetUserByIdQuery;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class GetUserByIdQueryHandler
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    public function __invoke(GetUserByIdQuery $query): ?User
    {
        return $this->userRepository->find($query->userId);
    }
}
