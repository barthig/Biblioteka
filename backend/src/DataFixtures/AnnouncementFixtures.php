<?php
namespace App\DataFixtures;

use App\Entity\Announcement;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AnnouncementFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Pobierz użytkownika administratora
        $adminUser = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@biblioteka.pl']);
        
        if (!$adminUser) {
            // Jeśli nie ma admina, utwórz go
            $adminUser = new User();
            $adminUser->setEmail('admin@biblioteka.pl');
            $adminUser->setName('Administrator');
            $adminUser->setRoles(['ROLE_LIBRARIAN', 'ROLE_ADMIN']);
            $adminUser->setPassword(password_hash('admin', PASSWORD_BCRYPT));
            $adminUser->markVerified(); // Use markVerified() method
            $adminUser->recordPrivacyConsent();
            $manager->persist($adminUser);
        }

        // 1. Ogłoszenie powitalne - przypięte, na stronie głównej
        $welcome = new Announcement();
        $welcome->setTitle('Witamy w systemie bibliotecznym!');
        $welcome->setContent('Witamy w nowym systemie zarządzania biblioteką. Zachęcamy do zapoznania się z dostępnymi funkcjami i skorzystania z naszych zbiorów.');
        $welcome->setType('info');
        $welcome->setCreatedBy($adminUser);
        $welcome->setIsPinned(true);
        $welcome->setShowOnHomepage(true);
        $welcome->setTargetAudience(['all']);
        $welcome->publish();
        $manager->persist($welcome);

        // 2. Ogłoszenie o godzinach otwarcia
        $hours = new Announcement();
        $hours->setTitle('Godziny otwarcia biblioteki');
        $hours->setContent('Biblioteka czynna: Poniedziałek-Piątek: 8:00-20:00, Sobota: 9:00-14:00, Niedziela: nieczynne.');
        $hours->setType('info');
        $hours->setCreatedBy($adminUser);
        $hours->setIsPinned(true);
        $hours->setShowOnHomepage(true);
        $hours->setTargetAudience(['all']);
        $hours->publish();
        $manager->persist($hours);

        // 3. Pilne ogłoszenie o zmianie regulaminu
        $rules = new Announcement();
        $rules->setTitle('Aktualizacja regulaminu wypożyczeń');
        $rules->setContent('Informujemy o zmianie regulaminu wypożyczeń. Od 1 stycznia maksymalny czas wypożyczenia książek wydłuża się z 30 do 45 dni. Szczegóły dostępne w zakładce Regulaminy.');
        $rules->setType('warning');
        $rules->setCreatedBy($adminUser);
        $rules->setIsPinned(false);
        $rules->setShowOnHomepage(true);
        $rules->setTargetAudience(['all']);
        $rules->setExpiresAt((new \DateTimeImmutable())->modify('+30 days'));
        $rules->publish();
        $manager->persist($rules);

        // 4. Ogłoszenie o nowych książkach
        $newBooks = new Announcement();
        $newBooks->setTitle('Nowe pozycje w zbiorach biblioteki');
        $newBooks->setContent('W grudniu uzupełniliśmy zbiory o ponad 100 nowych tytułów z zakresu literatury popularnej, naukowej i podręczników akademickich. Sprawdź nowości w katalogu!');
        $newBooks->setType('info');
        $newBooks->setCreatedBy($adminUser);
        $newBooks->setIsPinned(false);
        $newBooks->setShowOnHomepage(true);
        $newBooks->setTargetAudience(['all']);
        $newBooks->publish();
        $manager->persist($newBooks);

        // 5. Ogłoszenie o planowanej przerwie technicznej
        $maintenance = new Announcement();
        $maintenance->setTitle('Planowana przerwa techniczna systemu');
        $maintenance->setContent('W dniu 15 grudnia w godzinach 02:00-04:00 planowana jest przerwa techniczna związana z aktualizacją systemu. W tym czasie katalog online może być niedostępny.');
        $maintenance->setType('maintenance');
        $maintenance->setCreatedBy($adminUser);
        $maintenance->setIsPinned(true);
        $maintenance->setShowOnHomepage(true);
        $maintenance->setTargetAudience(['all']);
        $maintenance->setExpiresAt(new \DateTimeImmutable('2025-12-16'));
        $maintenance->publish();
        $manager->persist($maintenance);

        // 6. Ogłoszenie tylko dla bibliotekarzy
        $staffMeeting = new Announcement();
        $staffMeeting->setTitle('Spotkanie zespołu bibliotekarzy');
        $staffMeeting->setContent('Przypominamy o spotkaniu zespołu w czwartek o godz. 14:00 w sali konferencyjnej. Tematyka: omówienie nowych procedur katalogowania.');
        $staffMeeting->setType('info');
        $staffMeeting->setCreatedBy($adminUser);
        $staffMeeting->setIsPinned(false);
        $staffMeeting->setShowOnHomepage(false);
        $staffMeeting->setTargetAudience(['librarians']);
        $staffMeeting->publish();
        $manager->persist($staffMeeting);

        // 7. Pilne ogłoszenie o karach
        $fines = new Announcement();
        $fines->setTitle('Przypomnienie o terminach zwrotu');
        $fines->setContent('Przypominamy o terminowym zwrocie wypożyczonych książek. Za każdy dzień zwłoki naliczana jest kara w wysokości 0.50 PLN. Prosimy o przestrzeganie terminów.');
        $fines->setType('urgent');
        $fines->setCreatedBy($adminUser);
        $fines->setIsPinned(false);
        $fines->setShowOnHomepage(true);
        $fines->setTargetAudience(['users']);
        $fines->publish();
        $manager->persist($fines);

        // 8. Szkic ogłoszenia (nieopublikowane)
        $draft = new Announcement();
        $draft->setTitle('Konkurs czytelniczy - szkic');
        $draft->setContent('Planujemy konkurs czytelniczy dla stałych czytelników. Szczegóły wkrótce...');
        $draft->setType('info');
        $draft->setCreatedBy($adminUser);
        $draft->setIsPinned(false);
        $draft->setShowOnHomepage(true);
        $draft->setTargetAudience(['all']);
        // Pozostaw jako draft - nie publikuj
        $manager->persist($draft);

        $manager->flush();
    }
}
