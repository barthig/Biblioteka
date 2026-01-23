<?php
namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed-demo-data',
    description: 'Seeds demo users for testing and demonstration'
)]
class SeedDemoDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $demoUsers = [
            [
                'email' => 'admin@biblioteka.pl',
                'name' => 'Administrator',
                'roles' => ['ROLE_ADMIN'],
                'password' => 'admin123',
                'phone' => '+48123456789',
            ],
            [
                'email' => 'bibliotekarz@biblioteka.pl',
                'name' => 'Jan Bibliotekarz',
                'roles' => ['ROLE_LIBRARIAN'],
                'password' => 'lib123',
                'phone' => '+48123456790',
            ],
            [
                'email' => 'czytelnik1@example.com',
                'name' => 'Anna Kowalska',
                'roles' => ['ROLE_USER'],
                'password' => 'user123',
                'phone' => '+48123456791',
            ],
            [
                'email' => 'czytelnik2@example.com',
                'name' => 'Piotr Nowak',
                'roles' => ['ROLE_USER'],
                'password' => 'user123',
                'phone' => '+48123456792',
            ],
            [
                'email' => 'czytelnik3@example.com',
                'name' => 'Maria Wiśniewska',
                'roles' => ['ROLE_USER'],
                'password' => 'user123',
                'phone' => '+48123456793',
            ],
        ];

        $created = 0;
        foreach ($demoUsers as $userData) {
            $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $userData['email']]);
            if ($existing) {
                $io->note("User {$userData['email']} already exists, skipping.");
                continue;
            }

            $user = new User();
            $user->setEmail($userData['email']);
            $user->setName($userData['name']);
            $user->setRoles($userData['roles']);
            $user->setPhone($userData['phone']);
            
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);
            
            $this->em->persist($user);
            $io->success("Created user: {$userData['email']} (password: {$userData['password']})");
            $created++;
        }

        if ($created > 0) {
            $this->em->flush();
            $io->success("Created $created demo users successfully!");
        } else {
            $io->info("No new users created - all demo users already exist.");
        }

        return Command::SUCCESS;
    }
}
