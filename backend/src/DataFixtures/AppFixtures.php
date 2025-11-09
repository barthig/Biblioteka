<?php
namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Loan;
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
                ->setPassword(password_hash('password' . $i, PASSWORD_BCRYPT));

            $manager->persist($user);
            $users[] = $user;
        }

        $books = [];
        for ($i = 1; $i <= 30; $i++) {
            $author = $authors[$i % count($authors)];
            $totalCopies = 2 + ($i % 4);
            $availableCopies = $totalCopies - ($i % 2);

            $book = (new Book())
                ->setTitle('Book title ' . $i)
                ->setAuthor($author)
                ->setIsbn('ISBN-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT))
                ->setTotalCopies($totalCopies)
                ->setCopies($availableCopies)
                ->setDescription(sprintf('Sample description for book %d in the Biblioteka catalog.', $i));

            $assignedCategories = array_rand($categories, 2);
            foreach ((array) $assignedCategories as $index) {
                $book->addCategory($categories[$index]);
            }

            $manager->persist($book);
            $books[] = $book;
        }

        for ($i = 0; $i < 15; $i++) {
            $book = $books[$i];
            if ($book->getCopies() <= 0) {
                continue;
            }

            $user = $users[$i % count($users)];

            $book->setCopies($book->getCopies() - 1);

            $loan = (new Loan())
                ->setBook($book)
                ->setUser($user)
                ->setDueAt(new \DateTimeImmutable(sprintf('+%d days', 14 + $i)));

            if ($i % 3 === 0) {
                $loan->setReturnedAt(new \DateTimeImmutable(sprintf('-%d days', $i + 1)));
                $book->setCopies($book->getCopies() + 1);
            }

            $manager->persist($loan);
        }

        $manager->flush();
    }
}
