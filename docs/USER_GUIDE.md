# Instrukcja użytkownika systemu Smart Library

Dokument opisuje podstawową obsługę systemu Smart Library z perspektywy czytelnika, bibliotekarza oraz administratora. Instrukcja jest przeznaczona dla użytkowników końcowych i może być dołączona do dokumentacji projektu jako podręcznik obsługi aplikacji.

## 1. Dostęp do aplikacji

System jest dostępny w przeglądarce internetowej. W typowej konfiguracji lokalnej aplikacja działa pod adresem:

- `http://localhost` - adres publiczny przez bramę Traefik,
- `http://localhost:3000` - bezpośredni adres frontendu w trybie developerskim.

Do korzystania z części funkcji wymagane jest konto użytkownika. System rozróżnia trzy główne role:

- czytelnik - korzysta z katalogu, wypożyczeń, rezerwacji, ocen i powiadomień,
- bibliotekarz - obsługuje wypożyczenia, zwroty, egzemplarze, rezerwacje i akcesje,
- administrator - zarządza użytkownikami, rolami, ustawieniami i raportami.

## 2. Rejestracja i logowanie

### Rejestracja konta

1. Otwórz stronę startową aplikacji.
2. Wybierz opcję rejestracji.
3. Podaj wymagane dane, w tym adres e-mail, imię i nazwisko oraz hasło.
4. Zaakceptuj wymagane zgody.
5. Zatwierdź formularz.
6. Po utworzeniu konta system zapisuje powitalne powiadomienie w panelu powiadomień.

Jeżeli konfiguracja systemu wymaga weryfikacji konta, użytkownik powinien przejść proces aktywacji zgodnie z komunikatem widocznym po rejestracji.

### Logowanie

1. Wybierz opcję logowania.
2. Wpisz adres e-mail oraz hasło.
3. Po poprawnym logowaniu system przenosi użytkownika do chronionej części aplikacji.
4. W przypadku wygaśnięcia sesji należy zalogować się ponownie.

## 3. Strona startowa

Strona startowa prezentuje najważniejsze informacje o bibliotece:

- wyszukiwarkę lub skrót do katalogu książek,
- nowe pozycje w katalogu,
- ogłoszenia,
- nadchodzące wydarzenia,
- dane kontaktowe i godziny otwarcia.

Ogłoszenia i wydarzenia są widoczne również dla użytkownika niezalogowanego, o ile zostały oznaczone jako aktywne i przeznaczone na stronę główną.

## 4. Obsługa konta czytelnika

### Przeglądanie katalogu

1. Otwórz sekcję `Książki`.
2. Wpisz frazę w polu wyszukiwania.
3. Opcjonalnie rozwiń filtry zaawansowane.
4. Wybierz autora, kategorię, typ zasobu, przedział wiekowy, rok wydania lub dostępność.
5. Zatwierdź wyszukiwanie.
6. Kliknij wybraną książkę, aby przejść do szczegółów.

System obsługuje paginację wyników. Jeżeli nie ma wyników, aplikacja pokazuje komunikat o braku książek spełniających kryteria.

### Szczegóły książki

Widok szczegółów książki może zawierać:

- tytuł,
- autora,
- kategorię,
- wydawnictwo,
- rok wydania,
- opis,
- status dostępności,
- listę egzemplarzy,
- oceny i opinie,
- rekomendacje podobnych pozycji.

### Wypożyczenie książki

1. Otwórz szczegóły wybranej książki.
2. Sprawdź, czy książka ma dostępny egzemplarz.
3. Wybierz opcję wypożyczenia.
4. System sprawdza limit wypożyczeń, blokady konta, zaległe opłaty i dostępność egzemplarza.
5. Po poprawnym wypożyczeniu książka pojawia się w sekcji `Moje wypożyczenia`.
6. Użytkownik otrzymuje powiadomienie o wypożyczeniu i terminie zwrotu.

Jeżeli książka nie jest dostępna, system może zaproponować rezerwację.

### Rezerwacja książki

1. Otwórz szczegóły książki.
2. Wybierz opcję rezerwacji, jeżeli pozycja nie jest aktualnie dostępna.
3. System dodaje rezerwację do kolejki.
4. Status rezerwacji można sprawdzić w sekcji `Rezerwacje`.
5. Po utworzeniu rezerwacji użytkownik otrzymuje powiadomienie.
6. Gdy egzemplarz zostanie zwrócony i przypisany do rezerwacji, system informuje użytkownika, że pozycja jest gotowa do odbioru.

### Moje wypożyczenia

Sekcja `Moje wypożyczenia` pozwala sprawdzić:

- aktywne wypożyczenia,
- terminy zwrotu,
- status zwrotu,
- możliwość prolongaty,
- historię zakończonych wypożyczeń.

Jeżeli wypożyczenie jest po terminie, system może naliczyć opłatę regulaminową przy zwrocie.

### Oceny i opinie

1. Otwórz szczegóły książki.
2. Wybierz ocenę w skali od 1 do 5.
3. Opcjonalnie dodaj komentarz.
4. Zapisz opinię.
5. System aktualizuje średnią ocen książki.
6. Użytkownik otrzymuje powiadomienie o zapisaniu oceny lub opinii.

### Ulubione i rekomendacje

Użytkownik może dodawać książki do ulubionych oraz korzystać z rekomendacji generowanych na podstawie historii, ocen i interakcji z katalogiem. Rekomendacje są dostępne w dedykowanej sekcji lub przy szczegółach książki.

### Powiadomienia

Panel powiadomień pokazuje komunikaty dotyczące między innymi:

- utworzenia konta,
- wypożyczenia książki,
- zwrotu książki,
- rezerwacji,
- przygotowania rezerwacji do odbioru,
- dodania oceny lub opinii,
- nowych ogłoszeń i wydarzeń,
- nowych pozycji w katalogu.

## 5. Obsługa bibliotekarza

Bibliotekarz korzysta z panelu operacyjnego dostępnego po zalogowaniu kontem z odpowiednią rolą.

### Wypożyczenia

Typowy proces wypożyczenia przez bibliotekarza:

1. Otwórz panel bibliotekarza.
2. Wyszukaj użytkownika.
3. Wyszukaj książkę lub egzemplarz.
4. Wybierz konkretny dostępny egzemplarz.
5. Określ termin zwrotu, jeżeli formularz tego wymaga.
6. Zatwierdź wypożyczenie.
7. Sprawdź komunikat systemowy i status egzemplarza.

System blokuje wypożyczenie, jeżeli użytkownik przekroczył limit, ma zaległe opłaty, jest zablokowany albo egzemplarz jest niedostępny.

### Zwroty

1. Wyszukaj użytkownika lub aktywne wypożyczenie.
2. Wybierz wypożyczenie do zwrotu.
3. Zatwierdź zwrot.
4. System zmienia status egzemplarza.
5. Jeżeli zwrot jest po terminie, system nalicza opłatę.
6. Jeżeli na książkę czeka rezerwacja, system może przypisać zwrócony egzemplarz do kolejnego czytelnika.

### Katalog i egzemplarze

Bibliotekarz może:

- dodawać książki,
- edytować metadane książek,
- zarządzać egzemplarzami,
- sprawdzać statusy egzemplarzy,
- wycofywać egzemplarze z obiegu.

### Akcesje

Panel `Akcesje` służy do obsługi procesów zakupów i wycofań:

- dostawcy,
- budżety,
- zamówienia,
- wydatki budżetowe,
- ubytki i wycofania egzemplarzy.

Przy ręcznym księgowaniu wydatku należy wybrać budżet, podać kwotę oraz opis. System zapisuje wydatek jako pozycję budżetową i aktualizuje podsumowanie.

## 6. Obsługa administratora

Administrator ma dostęp do funkcji konfiguracyjnych i nadzorczych.

### Zarządzanie użytkownikami

Administrator może:

- wyszukiwać użytkowników,
- tworzyć konta,
- edytować dane kontaktowe,
- nadawać i odbierać role,
- blokować i odblokowywać konto,
- usuwać użytkowników, jeżeli pozwala na to logika systemu.

Po utworzeniu konta przez administratora użytkownik otrzymuje powitalne powiadomienie.

### Role i uprawnienia

System korzysta z ról, między innymi:

- `ROLE_USER`,
- `ROLE_LIBRARIAN`,
- `ROLE_ADMIN`.

Dostęp do paneli zależy od ról przypisanych do konta.

### Raporty i ustawienia

Administrator może sprawdzać raporty operacyjne, statystyki, opłaty oraz ustawienia systemowe, takie jak limity wypożyczeń lub parametry pracy biblioteki.

## 7. Tryb PWA

Aplikacja obsługuje tryb PWA. Oznacza to, że można ją zainstalować z poziomu przeglądarki na komputerze lub urządzeniu mobilnym.

Funkcje PWA:

- instalacja aplikacji,
- zapamiętanie powłoki aplikacji,
- szybsze ponowne uruchamianie,
- podstawowa obsługa ostatnio odwiedzonych zasobów,
- informowanie o dostępności nowej wersji aplikacji.

## 8. Najczęstsze problemy

### Nie mogę się zalogować

Sprawdź poprawność adresu e-mail i hasła. Jeżeli sesja wygasła, zaloguj się ponownie.

### Nie widzę przycisku wypożyczenia

Książka może być niedostępna, konto może nie mieć wymaganej roli albo użytkownik może mieć zaległe opłaty.

### Nie widzę ogłoszeń na stronie głównej

Ogłoszenie musi być aktywne, oznaczone jako widoczne na stronie głównej i mieścić się w zakresie dat publikacji.

### Nie mogę dodać wydatku w akcesjach

Sprawdź, czy wybrano budżet, wpisano dodatnią kwotę oraz opis. W przypadku wygaśnięcia sesji należy zalogować się ponownie.

### Nie widzę powiadomień

Odśwież widok powiadomień. Jeżeli powiadomienie dotyczy operacji wykonanej przed wdrożeniem mechanizmu powiadomień, może nie istnieć w historii.
