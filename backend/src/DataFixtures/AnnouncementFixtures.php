<?php
declare(strict_types=1);
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
        $staffMeeting->setType('event');
        $staffMeeting->setCreatedBy($adminUser);
        $staffMeeting->setIsPinned(false);
        $staffMeeting->setShowOnHomepage(false);
        $staffMeeting->setTargetAudience(['librarians']);
        $staffMeeting->setEventAt((new \DateTimeImmutable())->modify('+10 days')->setTime(14, 0));
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

        // 9-30: Additional announcements for comprehensive seed data
        $announcementData = [
            ['Warsztaty literackie dla dzieci', 'Zapraszamy na warsztaty literackie dla dzieci w wieku 7-12 lat. Sobota, godz. 10:00.', 'event', false, true, ['children']],
            ['Klub książki - spotkanie', 'Miłośnicy literatury - zapraszamy na spotkanie klubu książki. Omawiamy "Mistrza i Małgorzatę".', 'event', false, true, ['all']],
            ['Zmiany w katalogu', 'Wprowadziliśmy usprawnienia w systemie wyszukiwania książek. Przetestujcie!', 'info', false, true, ['all']],
            ['Nowe stanowiska komputerowe', 'Udostępniliśmy 5 nowych stanowisk komputerowych w czytelni głównej.', 'info', false, true, ['all']],
            ['Wydłużone godziny w grudniu', 'W okresie przedświątecznym biblioteka otwarta będzie dłużej - do 22:00.', 'info', true, true, ['all']],
            ['Szkolenie z baz danych', 'Bezpłatne szkolenie z obsługi naukowych baz danych - zapisy w recepcji.', 'info', false, true, ['researchers']],
            ['Kary administracyjne', 'Przypominamy o możliwości regulowania kar online poprzez system płatności.', 'warning', false, true, ['users']],
            ['Nowa aplikacja mobilna', 'Uruchomiliśmy aplikację mobilną dla czytelników. Dostępna w Google Play i App Store.', 'info', true, true, ['all']],
            ['Kolekcja audiobooków', 'Rozszerzyliśmy ofertę o 200 nowych audiobooków w języku polskim i angielskim.', 'info', false, true, ['all']],
            ['Remont czytelni', 'W styczniu planowany jest remont czytelni naukowej. Czytelnia będzie nieczynna 7-14.01.', 'maintenance', true, true, ['all']],
            ['Konkurs fotograficzny', 'Konkurs fotograficzny "Moja ulubiona książka" - przyjmujemy zgłoszenia do końca miesiąca.', 'event', false, true, ['all']],
            ['Spotkanie z autorem', 'Spotkanie z Olgą Tokarczuk - 20 grudnia, godz. 18:00. Liczba miejsc ograniczona.', 'event', true, true, ['all']],
            ['Dni otwarte biblioteki', 'Zapraszamy na dni otwarte - zwiedzanie magazynów i warsztaty introligatorskie.', 'event', false, true, ['all']],
            ['Znaleziono dokumenty', 'W czytelni znaleziono dokumenty osobiste. Prosimy o kontakt z recepcją.', 'info', false, false, ['all']],
            ['Newsletter miesięczny', 'Zapisz się na newsletter i bądź na bieżąco z wydarzeniami i nowościami w bibliotece.', 'info', false, true, ['all']],
            ['Zmiana regulaminu dostępu', 'Aktualizacja regulaminu dostępu do zbiorów specjalnych. Szczegóły na stronie.', 'warning', false, true, ['researchers']],
            ['Inwentaryzacja roczna', 'W dniach 2-4 stycznia przeprowadzimy inwentaryzację. Możliwe opóźnienia w wypożyczeniach.', 'maintenance', true, true, ['all']],
            ['Zbiórka książek', 'Prowadzimy zbiórkę używanych książek dla szpitala dziecięcego. Dary przyjmujemy w recepcji.', 'info', false, true, ['all']],
            ['Zmiana hasła systemowego', 'Ze względów bezpieczeństwa prosimy o zmianę hasła co 90 dni.', 'warning', false, false, ['all']],
            ['Nowy system rezerwacji', 'Uruchomiliśmy nowy system rezerwacji sal i stanowisk komputerowych.', 'info', false, true, ['all']],
            ['Godziny dla seniorów', 'Czwartki 9:00-11:00 to godziny dedykowane seniorom - pomoc w obsłudze katalogu.', 'info', true, true, ['all']],
            ['Urlop bibliotekarza', 'Pani Maria będzie nieobecna 10-20 stycznia. Zastępstwo: Pan Tomasz.', 'info', false, false, ['librarians']],
        ];

        foreach ($announcementData as $index => $data) {
            $announcement = new Announcement();
            $announcement->setTitle($data[0]);
            $announcement->setContent($data[1]);
            $announcement->setType($data[2]);
            $announcement->setCreatedBy($adminUser);
            $announcement->setIsPinned($data[3]);
            $announcement->setShowOnHomepage($data[4]);
            $announcement->setTargetAudience($data[5]);
            
            // Set expiration for some announcements
            if ($index % 5 === 0) {
                $announcement->setExpiresAt((new \DateTimeImmutable())->modify('+' . (30 + $index) . ' days'));
            }
            
            // Set event date for event types
            if ($data[2] === 'event') {
                $announcement->setEventAt((new \DateTimeImmutable())->modify('+' . (7 + $index) . ' days')->setTime(15, 0));
            }
            
            $announcement->publish();
            $manager->persist($announcement);
        }

        $manager->flush();
    }
}
