<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Account\GetAccountDetailsQuery;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class GetAccountDetailsHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function __invoke(GetAccountDetailsQuery $query): array
    {
        $user = $this->userRepository->find($query->userId);
        if (!$user instanceof User) {
            throw new NotFoundHttpException('User not found');
        }

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'phoneNumber' => $user->getPhoneNumber(),
            'addressLine' => $user->getAddressLine(),
            'city' => $user->getCity(),
            'postalCode' => $user->getPostalCode(),
            'pesel' => $user->getPesel(),
            'cardNumber' => $user->getCardNumber(),
            'cardExpiry' => $user->getCardExpiry()?->format('Y-m-d'),
            'accountStatus' => $user->getAccountStatus() ?? 'Aktywne',
            'newsletterSubscribed' => $user->isNewsletterSubscribed(),
            'newsletter' => $user->isNewsletterSubscribed(),
            'keepHistory' => $user->getKeepHistory() ?? false,
            'emailLoans' => $user->getEmailLoans() ?? true,
            'emailReservations' => $user->getEmailReservations() ?? true,
            'emailFines' => $user->getEmailFines() ?? true,
            'emailAnnouncements' => $user->getEmailAnnouncements() ?? false,
            'preferredContact' => $user->getPreferredContact() ?? 'email',
            'defaultBranch' => $user->getDefaultBranch(),
            'theme' => $user->getTheme() ?? 'auto',
            'fontSize' => $user->getFontSize() ?? 'standard',
            'language' => $user->getLanguage() ?? 'pl',
            'membershipGroup' => $user->getMembershipGroup(),
            'createdAt' => $user->getCreatedAt()?->format('c'),
        ];
    }
}
