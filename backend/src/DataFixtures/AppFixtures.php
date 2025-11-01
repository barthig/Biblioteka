<?php
namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\User;
use App\Entity\Loan;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // create some users
        $users = [];
                for ($i = 1; $i <= 5; $i++) {
                        $u = new User();
                        $password = password_hash('password' . $i, PASSWORD_BCRYPT);
                        $u->setName('User '.$i)
                            ->setEmail('user'.$i.'@example.com')
                            ->setRoles(['ROLE_USER'])
                            ->setPassword($password);
                        $manager->persist($u);
                        $users[] = $u;
                }

        // create 30 books
        $books = [];
        for ($i = 1; $i <= 30; $i++) {
            $b = new Book();
            $b->setTitle('Book title '.$i)->setAuthor('Author '.(($i % 5) + 1))->setIsbn('ISBN-'.$i)->setCopies(1 + ($i % 3));
            $manager->persist($b);
            $books[] = $b;
        }

        // create a few loans
        $count = min(count($books), count($users));
        for ($i = 0; $i < $count; $i++) {
            $loan = new Loan();
            $loan->setBook($books[$i])->setUser($users[$i])->setDueAt(new \DateTimeImmutable('+14 days'));
            $manager->persist($loan);
        }

        $manager->flush();
    }
}
