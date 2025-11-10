<?php
namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\Category;
use App\Entity\Loan;
use App\Entity\Reservation;
use App\Entity\Fine;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $authors = [];
        for ($i = 1; $i <= 10; $i++) {
            $author = (new Author())
                ->setName('Author ' . $i);

            $manager->persist($author);
            $authors[] = $author;
        }

        $categoryNames = [
            'Science Fiction',
            'Fantasy',
            'Non-fiction',
            'Technology',
            'History',
            'Mystery',
            'Romance',
        ];

        $categories = [];
        foreach ($categoryNames as $name) {
            $category = (new Category())->setName($name);
            $manager->persist($category);
            $categories[] = $category;
        }

        $users = [];
        for ($i = 1; $i <= 6; $i++) {
            $roles = $i === 1 ? ['ROLE_LIBRARIAN'] : ['ROLE_USER'];
            $user = (new User())
                ->setName('User ' . $i)
                ->setEmail('user' . $i . '@example.com')
                ->setRoles($roles)
                ->setPhoneNumber('+48 600 000 0' . $i)
                ->setAddressLine('Ul. Biblioteczna ' . $i)
                ->setCity('Miasto ' . $i)
                ->setPostalCode('00-0' . $i)
                ->setPassword(password_hash('password' . $i, PASSWORD_BCRYPT));

            $manager->persist($user);
            $users[] = $user;
        }

        $books = [];
        $publishers = [
            'Wydawnictwo Literackie',
            'Czytelnik Press',
            'Akademia Nauki',
            'Digital Stories',
        ];
        $resourceTypes = ['Książka drukowana', 'E-book', 'Audiobook'];
        for ($i = 1; $i <= 30; $i++) {
            $author = $authors[$i % count($authors)];
            $book = (new Book())
                ->setTitle('Book title ' . $i)
                ->setAuthor($author)
                ->setIsbn('ISBN-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT))
                ->setDescription(sprintf('Sample description for book %d in the Biblioteka catalog.', $i))
                ->setPublisher($publishers[$i % count($publishers)])
                ->setPublicationYear(2000 + ($i % 20))
                ->setResourceType($resourceTypes[$i % count($resourceTypes)])
                ->setSignature(sprintf('SIG-%03d-%02d', $i, $i % 7 + 1));

            $assignedCategories = array_rand($categories, 2);
            foreach ((array) $assignedCategories as $index) {
                $book->addCategory($categories[$index]);
            }

            $manager->persist($book);
            $books[] = $book;

            $totalCopies = 2 + ($i % 4);
            for ($copyIndex = 1; $copyIndex <= $totalCopies; $copyIndex++) {
                $copy = (new BookCopy())
                    ->setBook($book)
                    ->setInventoryCode(sprintf('BK%02d-%03d', $i, $copyIndex))
                    ->setStatus(BookCopy::STATUS_AVAILABLE)
                    ->setLocation('Magazyn główny');

                $book->addInventoryCopy($copy);
                $manager->persist($copy);
            }

            $book->recalculateInventoryCounters();
        }

        $manager->flush();

        for ($i = 0; $i < 15; $i++) {
            $book = $books[$i];
            if ($book->getCopies() <= 0) {
                continue;
            }

            $user = $users[$i % count($users)];

            $availableCopies = $book->getInventory()->filter(static fn (BookCopy $copy) => $copy->getStatus() === BookCopy::STATUS_AVAILABLE);
            /** @var BookCopy|null $borrowedCopy */
            $borrowedCopy = $availableCopies->first() ?: null;
            if (!$borrowedCopy) {
                continue;
            }

            $borrowedCopy->setStatus(BookCopy::STATUS_BORROWED);

            $loan = (new Loan())
                ->setBook($book)
                ->setBookCopy($borrowedCopy)
                ->setUser($user)
                ->setDueAt(new \DateTimeImmutable(sprintf('+%d days', 14 + $i)));

            if ($i % 3 === 0) {
                $loan->setReturnedAt(new \DateTimeImmutable(sprintf('-%d days', $i + 1)));
                $borrowedCopy->setStatus(BookCopy::STATUS_AVAILABLE);
            }

            $manager->persist($loan);
            $book->recalculateInventoryCounters();

            if ($i % 4 === 0) {
                $reservation = (new Reservation())
                    ->setBook($book)
                    ->setUser($users[($i + 1) % count($users)])
                    ->setExpiresAt((new \DateTimeImmutable())->modify('+2 days'));

                if ($loan->getReturnedAt() === null) {
                    $reservation->setStatus(Reservation::STATUS_ACTIVE);
                } else {
                    $reservation->markFulfilled();
                    $reservation->assignBookCopy($borrowedCopy);
                }

                $manager->persist($reservation);
            }

            if ($loan->getReturnedAt() === null && $i % 5 === 0) {
                $fine = (new Fine())
                    ->setLoan($loan)
                    ->setAmount('15.00')
                    ->setReason('Przetrzymanie książki')
                    ->setCurrency('PLN');
                $manager->persist($fine);
            }
        }

        $manager->flush();
    }
}
