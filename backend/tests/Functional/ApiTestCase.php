<?php

namespace App\Tests\Functional;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Loan;
use App\Entity\BookCopy;
use App\Entity\Fine;
use App\Entity\User;
use App\Entity\Supplier;
use App\Entity\AcquisitionBudget;
use App\Service\Auth\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

abstract class ApiTestCase extends WebTestCase
{
    protected const API_SECRET = 'test-secret';
    protected const JWT_SECRET = 'test_jwt_secret_for_backend_suite_123';

    private static bool $schemaInitialized = false;

    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('API_SECRET=' . self::API_SECRET);
        $_ENV['API_SECRET'] = self::API_SECRET;
        putenv('JWT_SECRET=' . self::JWT_SECRET);
        $_ENV['JWT_SECRET'] = self::JWT_SECRET;
        putenv('LEGACY_AUTH_SUBSCRIBER=0');
        $_ENV['LEGACY_AUTH_SUBSCRIBER'] = '0';
        putenv('MESSENGER_TRANSPORT_DSN=sync://');
        $_ENV['MESSENGER_TRANSPORT_DSN'] = 'sync://';

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $this->entityManager = $em;

        $this->ensureSchema();

        $this->purgeDatabase();
        $this->seedDefaultUsers();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager) && $this->entityManager->isOpen()) {
            $this->entityManager->clear();
        }

        parent::tearDown();
    }

    private function resetEntityManager(): void
    {
        $this->entityManager = static::getContainer()->get('doctrine')->resetManager();
    }

    private function ensureEntityManagerOpen(): void
    {
        if (!$this->entityManager->isOpen()) {
            $this->resetEntityManager();
        }
    }

    protected function createApiClient(?string $token = null): HttpKernelBrowser
    {
        static::ensureKernelShutdown();

        $server = [];
        $server['HTTP_X_API_SECRET'] = self::API_SECRET;

        if ($token) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        return static::createClient([], $server);
    }
    
    /**
     * Create authenticated client WITHOUT API_SECRET header.
     * Use this to test proper role-based access control.
     * API_SECRET grants ROLE_ADMIN and bypasses access control checks.
     */
    protected function createAuthenticatedClientWithoutApiSecret(User $user, array $extraClaims = []): HttpKernelBrowser
    {
        static::ensureKernelShutdown();
        
        $token = $this->generateTokenForUser($user, $extraClaims);
        $server = [];
        $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        return static::createClient([], $server);
    }

    protected function createClientWithoutSecret(array $server = []): HttpKernelBrowser
    {
        static::ensureKernelShutdown();

        return static::createClient([], $server);
    }

    protected function loginAndGetToken(string $email = 'user1@example.com', string $password = 'StrongPass1'): string
    {
        $client = $this->createApiClient();
        $this->jsonRequest($client, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertResponseStatusCodeSame(200, 'Login should return HTTP 200');

        $data = $this->getJsonResponse($client);
        $this->assertArrayHasKey('token', $data, 'Login response must contain token');

        return (string) $data['token'];
    }

    protected function generateTokenForUser(User $user, array $extra = []): string
    {
        $claims = array_merge([
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ], $extra);

        return JwtService::createToken($claims);
    }

    protected function createAuthenticatedClient(User $user, array $extraClaims = []): HttpKernelBrowser
    {
        $token = $this->generateTokenForUser($user, $extraClaims);
        return $this->createApiClient($token);
    }

    protected function jsonRequest(HttpKernelBrowser $client, string $method, string $uri, ?array $payload = null, array $server = []): void
    {
        $content = $payload !== null ? json_encode($payload, JSON_THROW_ON_ERROR) : null;
        $headers = array_merge(['CONTENT_TYPE' => 'application/json'], $server);
        if ($content !== null) {
            $headers['CONTENT_LENGTH'] = (string) strlen($content);
        }

        $parameters = $payload ?? [];

        call_user_func([
            $client,
            'request',
        ], $method, $uri, $parameters, [], $headers, $content);
    }

    protected function sendRequest(HttpKernelBrowser $client, string $method, string $uri, array $server = []): void
    {
        call_user_func([
            $client,
            'request',
        ], $method, $uri, [], [], $server);
    }

    protected function getJsonResponse(HttpKernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();
        if ($content === '' || $content === null) {
            return [];
        }

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    protected function createUser(string $email, array $roles = ['ROLE_USER'], string $password = 'StrongPass1', ?string $name = null): User
    {
        $this->ensureEntityManagerOpen();

        $existing = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);
        if ($existing instanceof User) {
            if ($roles !== []) {
                $existing->setRoles($roles);
            }
            if ($name !== null) {
                $existing->setName($name);
            }
            $this->entityManager->flush();
            return $existing;
        }

        $user = new User();
        $user->setEmail($email)
            ->setName($name ?? ucfirst(strstr($email, '@', true) ?: 'User'))
            ->setRoles($roles);

        /** @var \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, $password));
        $user->markVerified();

        $this->entityManager->persist($user);

        try {
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            $this->entityManager->clear();
            $existing = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['email' => $email]);
            if ($existing instanceof User) {
                return $existing;
            }

            throw $exception;
        }
        return $user;
    }

    protected function createAuthor(string $name = 'Author'): Author
    {
        $this->ensureEntityManagerOpen();
        $author = (new Author())->setName($name);
        $this->entityManager->persist($author);
        $this->entityManager->flush();

        return $author;
    }

    protected function createCategory(string $name = 'General'): Category
    {
        $this->ensureEntityManagerOpen();
        $repository = $this->entityManager->getRepository(Category::class);
        $existing = $repository->findOneBy(['name' => $name]);
        if ($existing instanceof Category) {
            return $existing;
        }

        $category = (new Category())->setName($name);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    /**
     * @param Category[] $categories
     */
    protected function createBook(
        string $title = 'Sample Book',
        ?Author $author = null,
        int $copies = 3,
        ?array $categories = null,
        ?int $totalCopies = null,
        ?string $ageGroup = null
    ): Book
    {
        $this->ensureEntityManagerOpen();
        $author ??= $this->createAuthor('Author ' . uniqid('', true));
        $categories = $categories ?? [$this->createCategory('General')];

        $total = $totalCopies ?? max($copies, 1);

        $book = (new Book())
            ->setTitle($title)
            ->setAuthor($author)
            ->setIsbn('ISBN-' . substr(md5($title . microtime()), 0, 8));

        if ($ageGroup !== null) {
            $book->setTargetAgeGroup($ageGroup);
        }

        foreach ($categories as $category) {
            $book->addCategory($category);
        }

        $this->entityManager->persist($book);

        $available = min($copies, $total);
        for ($i = 1; $i <= $total; $i++) {
            $copy = (new BookCopy())
                ->setBook($book)
                ->setInventoryCode(sprintf('TST-%05d-%02d', random_int(1000, 9999), $i))
                ->setStatus($i <= $available ? BookCopy::STATUS_AVAILABLE : BookCopy::STATUS_MAINTENANCE);

            $book->addInventoryCopy($copy);
            $this->entityManager->persist($copy);
        }

        $book->recalculateInventoryCounters();
        $this->entityManager->flush();

        return $book;
    }

    protected function createLoan(User $user, Book $book, ?\DateTimeImmutable $due = null, bool $returned = false): Loan
    {
        $this->ensureEntityManagerOpen();
        $availableCopy = null;
        foreach ($book->getInventory() as $copy) {
            if ($copy->getStatus() === BookCopy::STATUS_AVAILABLE) {
                $availableCopy = $copy;
                break;
            }
        }

        if (!$availableCopy) {
            $availableCopy = (new BookCopy())
                ->setBook($book)
                ->setInventoryCode(sprintf('ADHOC-%05d', random_int(10000, 99999)))
                ->setStatus(BookCopy::STATUS_AVAILABLE);
            $book->addInventoryCopy($availableCopy);
            $this->entityManager->persist($availableCopy);
        }

        if (!$returned) {
            $availableCopy->setStatus(BookCopy::STATUS_BORROWED);
        }

        $loan = new Loan();
        $loan->setUser($user)
            ->setBook($book)
            ->setBookCopy($availableCopy)
            ->setDueAt($due ?? new \DateTimeImmutable('+14 days'));

        if ($returned) {
            $loan->setReturnedAt(new \DateTimeImmutable('-1 day'));
            $availableCopy->setStatus(BookCopy::STATUS_AVAILABLE);
        }

        $book->recalculateInventoryCounters();

        $this->entityManager->persist($loan);
        $this->entityManager->persist($book);
        $this->entityManager->flush();

        return $loan;
    }

    protected function createSupplier(string $name = 'Biblioteka Dostawca', bool $active = true, ?string $email = null): Supplier
    {
        $this->ensureEntityManagerOpen();
        $supplier = (new Supplier())
            ->setName($name)
            ->setActive($active);

        if ($email !== null) {
            $supplier->setContactEmail($email);
        }

        $this->entityManager->persist($supplier);
        $this->entityManager->flush();

        return $supplier;
    }

    protected function createBudget(
        string $name = 'Budżet Zakupów',
        string $fiscalYear = '2025',
        string $allocatedAmount = '1000.00',
        string $currency = 'PLN',
        string $spentAmount = '0.00'
    ): AcquisitionBudget {
        $this->ensureEntityManagerOpen();
        $budget = (new AcquisitionBudget())
            ->setName($name)
            ->setFiscalYear($fiscalYear)
            ->setCurrency($currency)
            ->setAllocatedAmount($allocatedAmount)
            ->setSpentAmount($spentAmount);

        $this->entityManager->persist($budget);
        $this->entityManager->flush();

        return $budget;
    }

    protected function createFineForLoan(
        Loan $loan,
        string $amount = '5.00',
        bool $markPaid = false,
        string $reason = 'Testowa kara',
        string $currency = 'PLN'
    ): Fine {
        $this->ensureEntityManagerOpen();
        $fine = (new Fine())
            ->setLoan($loan)
            ->setAmount($amount)
            ->setCurrency($currency)
            ->setReason($reason);

        if ($markPaid) {
            $fine->markAsPaid();
        }

        $this->entityManager->persist($fine);
        $this->entityManager->flush();

        return $fine;
    }

    private function ensureSchema(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        if (empty($metadata)) {
            return;
        }

        if (!self::$schemaInitialized) {
            $schemaTool = new SchemaTool($this->entityManager);
            $schemaTool->dropSchema($metadata);
             $schemaTool->updateSchema($metadata, true);
            self::$schemaInitialized = true;
            return;
        }

        $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
        $tableNames = array_map(
            static fn ($table): string => strtolower($table->getName()),
            $schemaManager->introspectSchema()->getTables()
        );

        foreach (['app_user', 'book', 'book_copy', 'loan', 'reservation'] as $requiredTable) {
            if (!in_array($requiredTable, $tableNames, true)) {
                $schemaTool = new SchemaTool($this->entityManager);
                 $schemaTool->updateSchema($metadata, true);
                return;
            }
        }
    }
    private function purgeDatabase(): void
    {
        foreach ([
            'DELETE FROM App\Entity\BackupRecord br',
            'DELETE FROM App\Entity\IntegrationConfig ic',
            'DELETE FROM App\Entity\SystemSetting ss',
            'DELETE FROM App\Entity\StaffRole sr',
            'DELETE FROM App\Entity\AcquisitionExpense ae',
            'DELETE FROM App\Entity\AcquisitionOrder ao',
            'DELETE FROM App\Entity\AcquisitionBudget ab',
            'DELETE FROM App\Entity\Supplier s',
            'DELETE FROM App\Entity\WeedingRecord wr',
            'DELETE FROM App\Entity\Fine f',
            'DELETE FROM App\Entity\NotificationLog nl',
            'DELETE FROM App\Entity\RefreshToken rt',
            'DELETE FROM App\Entity\Reservation r',
            'DELETE FROM App\Entity\Loan l',
            'DELETE FROM App\Entity\BookCopy bc',
            'DELETE FROM App\Entity\Book b',
            'DELETE FROM App\Entity\Category c',
            'DELETE FROM App\Entity\Author a',
            'DELETE FROM App\Entity\Announcement an',
            'DELETE FROM App\Entity\User u',
        ] as $dql) {
            $this->safeDelete($dql);
        }

        $this->entityManager->clear();
        if (self::getContainer()->has('cache.rate_limiter')) {
            self::getContainer()->get('cache.rate_limiter')->clear();
        }
    }

    private function safeDelete(string $dql): void
    {
        try {
            $this->entityManager->createQuery($dql)->execute();
        } catch (\Throwable $exception) {
            // Ignore missing tables in lightweight SQLite test schema.
        }
    }
    private function seedDefaultUsers(): void
    {
        $existing = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'verified@example.com']);

        if ($existing === null) {
            $this->createUser('verified@example.com', ['ROLE_USER', 'ROLE_SYSTEM'], 'password123', 'Verified User');
        }
    }
}













