<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Account\UpdateAccountCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateAccountHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository
    ) {
    }

    public function __invoke(UpdateAccountCommand $command): User
    {
        $user = $this->userRepository->find($command->userId);
        
        if (!$user) {
            throw new NotFoundHttpException('Użytkownik nie istnieje');
        }

        if ($command->email !== null) {
            $email = trim($command->email);
            $existing = $this->userRepository->findOneBy(['email' => $email]);
            if ($existing && $existing->getId() !== $user->getId()) {
                throw new ConflictHttpException('Adres e-mail jest już zajęty');
            }
            $user->setEmail($email);
        }

        if ($command->name !== null) {
            $name = trim($command->name);
            if ($name === '') {
                throw new BadRequestHttpException('Imię i nazwisko nie mogą być puste');
            }
            $user->setName($name);
        }

        if ($command->phoneNumber !== null) {
            $phone = trim($command->phoneNumber);
            $user->setPhoneNumber($phone !== '' ? $phone : null);
        }

        if ($command->addressLine !== null) {
            $address = trim($command->addressLine);
            $user->setAddressLine($address !== '' ? $address : null);
        }

        if ($command->city !== null) {
            $city = trim($command->city);
            $user->setCity($city !== '' ? $city : null);
        }

        if ($command->postalCode !== null) {
            $postal = trim($command->postalCode);
            $user->setPostalCode($postal !== '' ? $postal : null);
        }

        if ($command->newsletterSubscribed !== null) {
            $user->setNewsletterSubscribed($command->newsletterSubscribed);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
