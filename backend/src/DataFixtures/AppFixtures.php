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
use App\Entity\Supplier;
use App\Entity\AcquisitionBudget;
use App\Entity\AcquisitionOrder;
use App\Entity\AcquisitionExpense;
use App\Entity\WeedingRecord;
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
        $admin = (new User())
            ->setName('Admin')
            ->setEmail('admin@example.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setMembershipGroup(User::GROUP_STANDARD)
            ->setPhoneNumber('+48 600 000 10')
            ->setAddressLine('Ul. Biblioteczna 10')
            ->setCity('Miasto 10')
            ->setPostalCode('00-10')
            ->setPassword(password_hash('Admin1234', PASSWORD_BCRYPT));
        $admin->markVerified();
        $manager->persist($admin);
        $users[] = $admin;
        $groupSequence = [
            User::GROUP_STANDARD,
            User::GROUP_STUDENT,
            User::GROUP_RESEARCHER,
            User::GROUP_CHILD,
        ];
        for ($i = 1; $i <= 6; $i++) {
            $roles = $i === 1 ? ['ROLE_LIBRARIAN'] : ['ROLE_USER'];
            $user = (new User())
                ->setName('User ' . $i)
                ->setEmail('user' . $i . '@example.com')
                ->setRoles($roles)
                ->setMembershipGroup($groupSequence[($i - 1) % count($groupSequence)])
                ->setPhoneNumber('+48 600 000 0' . $i)
                ->setAddressLine('Ul. Biblioteczna ' . $i)
                ->setCity('Miasto ' . $i)
                ->setPostalCode('00-0' . $i)
                ->setPassword(password_hash('password' . $i, PASSWORD_BCRYPT));

            $user->markVerified();

            if ($i === 6) {
                $user->block('Przykładowa blokada testowa');
            }

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
                $accessType = $copyIndex === 1 ? BookCopy::ACCESS_OPEN_STACK : BookCopy::ACCESS_STORAGE;
                $location = $accessType === BookCopy::ACCESS_OPEN_STACK ? 'Czytelnia główna' : 'Magazyn główny';
                $copy = (new BookCopy())
                    ->setBook($book)
                    ->setInventoryCode(sprintf('BK%02d-%03d', $i, $copyIndex))
                    ->setStatus(BookCopy::STATUS_AVAILABLE)
                    ->setLocation($location)
                    ->setAccessType($accessType);

                $book->addInventoryCopy($copy);
                $manager->persist($copy);
            }

            $book->recalculateInventoryCounters();
        }

        $manager->flush();

        // Sample suppliers and budgets for acquisitions
        $supplierA = (new Supplier())
            ->setName('Księgarnia Główna')
            ->setContactEmail('kontakt@ksiegarnia-glowna.pl')
            ->setContactPhone('+48 22 123 45 67')
            ->setCity('Warszawa')
            ->setCountry('Polska');

        $supplierB = (new Supplier())
            ->setName('Digital Reads Sp. z o.o.')
            ->setContactEmail('sales@digitalreads.pl')
            ->setContactPhone('+48 58 987 65 43')
            ->setCity('Gdańsk')
            ->setCountry('Polska');

        $budget2025 = (new AcquisitionBudget())
            ->setName('Budżet podstawowy 2025')
            ->setFiscalYear('2025')
            ->setCurrency('PLN')
            ->setAllocatedAmount('50000.00');

        $manager->persist($supplierA);
        $manager->persist($supplierB);
        $manager->persist($budget2025);

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

        // Seed acquisition order and expense
        $order = (new AcquisitionOrder())
            ->setSupplier($supplierA)
            ->setBudget($budget2025)
            ->setTitle('Nowo?>ci wydawnicze - stycze?"')
            ->setDescription('Zakup nowo?>ci do dzia?u literatury pi?tknej i naukowej')
            ->setItems([
                ['title' => 'Nowa era AI', 'quantity' => 10, 'unitPrice' => 45.50],
                ['title' => 'Historia regionu', 'quantity' => 6, 'unitPrice' => 52.00],
            ])
            ->setCurrency('PLN')
            ->setTotalAmount('986.00')
            ->markOrdered()
            ->setExpectedAt((new \DateTimeImmutable())->modify('+14 days'));

        $manager->persist($order);
        $manager->flush();

        $expense = (new AcquisitionExpense())
            ->setBudget($budget2025)
            ->setOrder($order)
            ->setAmount('986.00')
            ->setCurrency('PLN')
            ->setDescription('Zakup nowo?>ci wydawniczych - faktura FV/01/2025')
            ->setType(AcquisitionExpense::TYPE_ORDER);

        $budget2025->registerExpense('986.00');

        $manager->persist($expense);
        $manager->persist($budget2025);
        $manager->flush();

        // Sample weeding record
        $firstBook = $books[0];
        $firstCopy = $firstBook->getInventory()->first() ?: null;
        if ($firstCopy instanceof BookCopy) {
            $firstCopy->setStatus(BookCopy::STATUS_WITHDRAWN)->setConditionState('Zniszczony egzemplarz');
            $firstBook->recalculateInventoryCounters();

            $weeding = (new WeedingRecord())
                ->setBook($firstBook)
                ->setBookCopy($firstCopy)
                ->setProcessedBy($users[0])
                ->setReason('Uszkodzenia uniemo??liwiaj??ce wypo??yczenia')
                ->setAction(WeedingRecord::ACTION_DISCARD)
                ->setNotes('Wycofano podczas przegl??du rocznego');

            $manager->persist($firstCopy);
            $manager->persist($firstBook);
            $manager->persist($weeding);
        }

        $manager->flush();
    }
}
