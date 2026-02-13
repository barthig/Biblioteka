<?php
declare(strict_types=1);
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $authors = [];
        for ($i = 1; $i <= 30; $i++) {
            $author = (new Author())
                ->setName('Author ' . $i);

            $manager->persist($author);
            $authors[] = $author;
        }

        $categoryNames = [
            'Science Fiction', 'Fantasy', 'Non-fiction', 'Technology', 'History',
            'Mystery', 'Romance', 'Thriller', 'Biography', 'Self-Help',
            'Poetry', 'Drama', 'Adventure', 'Horror', 'Philosophy',
            'Psychology', 'Science', 'Mathematics', 'Art', 'Music',
            'Travel', 'Cooking', 'Health', 'Business', 'Economics',
            'Politics', 'Religion', 'Education', 'Children', 'Young Adult',
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
              ->setPassword('temp');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin1234'));
        $admin->markVerified();
        $manager->persist($admin);
        $users[] = $admin;
        $groupSequence = [
            User::GROUP_STANDARD,
            User::GROUP_STUDENT,
            User::GROUP_RESEARCHER,
            User::GROUP_CHILD,
        ];
        for ($i = 1; $i <= 30; $i++) {
            $roles = $i === 1 ? ['ROLE_LIBRARIAN'] : ['ROLE_USER'];
            $user = (new User())
                ->setName('User ' . $i)
                ->setEmail('user' . $i . '@example.com')
                ->setRoles($roles)
                ->setMembershipGroup($groupSequence[($i - 1) % count($groupSequence)])
                ->setPhoneNumber('+48 600 ' . str_pad((string) $i, 6, '0', STR_PAD_LEFT))
                ->setAddressLine('Ul. Biblioteczna ' . $i)
                ->setCity('Miasto ' . $i)
                ->setPostalCode(str_pad((string) $i, 5, '0', STR_PAD_LEFT))
                  ->setPassword('temp');
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password' . $i));
            $user->markVerified();

            if ($i === 30) {
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
        $suppliers = [];
        for ($i = 1; $i <= 30; $i++) {
            $supplier = (new Supplier())
                ->setName('Supplier ' . $i)
                ->setContactEmail('contact' . $i . '@supplier.pl')
                ->setContactPhone('+48 ' . str_pad((string)(100000000 + $i), 9, '0', STR_PAD_LEFT))
                ->setCity('City ' . $i)
                ->setCountry('Polska');
            
            $manager->persist($supplier);
            $suppliers[] = $supplier;
        }

        // Acquisition budgets
        $budgets = [];
        for ($i = 1; $i <= 30; $i++) {
            $year = 2024 + ($i % 3);
            $budget = (new AcquisitionBudget())
                ->setName('Budget ' . $i . ' - ' . $year)
                ->setFiscalYear((string) $year)
                ->setCurrency('PLN')
                ->setAllocatedAmount((string) (10000 + ($i * 1000)));
            
            $manager->persist($budget);
            $budgets[] = $budget;
        }

        $manager->flush();

        for ($i = 0; $i < 30; $i++) {
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

        // Seed acquisition orders and expenses (30 each)
        $orders = [];
        for ($i = 1; $i <= 30; $i++) {
            $supplier = $suppliers[$i % count($suppliers)];
            $budget = $budgets[$i % count($budgets)];
            
            $order = (new AcquisitionOrder())
                ->setSupplier($supplier)
                ->setBudget($budget)
                ->setTitle('Acquisition Order ' . $i)
                ->setDescription('Order description for acquisition ' . $i)
                ->setItems([
                    ['title' => 'Book Set ' . $i, 'quantity' => 5 + ($i % 10), 'unitPrice' => 30.00 + ($i * 2)],
                ])
                ->setCurrency('PLN')
                ->setTotalAmount((string) ((5 + ($i % 10)) * (30.00 + ($i * 2))))
                ->markOrdered()
                ->setExpectedAt((new \DateTimeImmutable())->modify('+' . (7 + $i) . ' days'));
            
            if ($i % 3 === 0) {
                $order->markReceived();
            }
            
            $manager->persist($order);
            $orders[] = $order;
            
            // Create expense for each order
            $expenseAmount = (string) ((5 + ($i % 10)) * (30.00 + ($i * 2)));
            $expense = (new AcquisitionExpense())
                ->setBudget($budget)
                ->setOrder($order)
                ->setAmount($expenseAmount)
                ->setCurrency('PLN')
                ->setDescription('Invoice for order ' . $i)
                ->setType(AcquisitionExpense::TYPE_ORDER);
            
            $budget->registerExpense($expenseAmount);
            $manager->persist($expense);
            $manager->persist($budget);
        }

        $manager->flush();

        // Weeding records (30)
        $weedingActions = [WeedingRecord::ACTION_DISCARD, WeedingRecord::ACTION_DONATE, WeedingRecord::ACTION_TRANSFER];
        $weedingReasons = ['Damaged', 'Outdated', 'Duplicate', 'Low circulation', 'Poor condition'];
        $conditions = ['Good', 'Fair', 'Poor', 'Damaged'];
        
        for ($i = 0; $i < 30; $i++) {
            $book = $books[$i];
            $copies = $book->getInventory()->toArray();
            if (empty($copies)) {
                continue;
            }
            
            $copy = $copies[0];
            $copy->setStatus(BookCopy::STATUS_WITHDRAWN)
                 ->setConditionState($conditions[$i % count($conditions)]);
            $book->recalculateInventoryCounters();
            
            $weeding = (new WeedingRecord())
                ->setBook($book)
                ->setBookCopy($copy)
                ->setProcessedBy($users[$i % count($users)])
                ->setReason($weedingReasons[$i % count($weedingReasons)])
                ->setAction($weedingActions[$i % count($weedingActions)])
                ->setNotes('Weeding record ' . ($i + 1));
            
            $manager->persist($copy);
            $manager->persist($book);
            $manager->persist($weeding);
        }

        $manager->flush();
    }
}
