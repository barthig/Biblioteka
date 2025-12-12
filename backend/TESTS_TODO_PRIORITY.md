# 📋 Priorytetowe Testy do Napisania

## 🎯 KRYTYCZNE PRIORITY (15 testów - DO ZROBIENIA NAJPIERW)

### 1. Loan Handlers (NAJWAŻNIEJSZE)
```bash
tests/Application/Handler/CreateLoanHandlerTest.php
tests/Application/Handler/ReturnLoanHandlerTest.php
tests/Application/Handler/ExtendLoanHandlerTest.php
tests/Application/Handler/DeleteLoanHandlerTest.php
```

**Powód**: Wypożyczenia to rdzeń biblioteki. CreateLoanHandler używa BookService (złożona logika).

**Scenariusze do przetestowania**:
- ✅ Poprawne utworzenie wypożyczenia
- ✅ Blokada zablokowanego użytkownika
- ✅ Limit wypożyczeń
- ✅ Rezerwacja spełniona
- ✅ Kolejka rezerwacji
- ✅ Preferowany egzemplarz
- ✅ Zwrot książki
- ✅ Zwrot + przekazanie rezerwacji
- ✅ Przedłużenie wypożyczenia
- ✅ Limit przedłużeń

---

### 2. Fine Handlers (FINANSOWE)
```bash
tests/Application/Handler/CreateFineHandlerTest.php
tests/Application/Handler/PayFineHandlerTest.php
tests/Application/Handler/CancelFineHandlerTest.php
```

**Powód**: Operacje finansowe - zero błędów!

**Scenariusze**:
- ✅ Utworzenie kary
- ✅ Płatność pełna
- ✅ Płatność częściowa
- ✅ Anulowanie kary
- ✅ Walidacja amount

---

### 3. Reservation Handlers (WAŻNE)
```bash
tests/Application/Handler/CreateReservationHandlerTest.php
tests/Application/Handler/CancelReservationHandlerTest.php
```

**Powód**: Kluczowa funkcjonalność kolejki.

**Scenariusze**:
- ✅ Utworzenie rezerwacji
- ✅ Blokada jeśli książka dostępna
- ✅ Duplikat rezerwacji
- ✅ Anulowanie przez właściciela
- ✅ Anulowanie przez bibliotekarza

---

### 4. Book Handlers (CORE DOMAIN)
```bash
tests/Application/Handler/CreateBookHandlerTest.php
tests/Application/Handler/UpdateBookHandlerTest.php
tests/Application/Handler/DeleteBookHandlerTest.php
```

**Powód**: CRUD książek - fundament systemu.

**Scenariusze**:
- ✅ Utworzenie książki z kategoriami
- ✅ Utworzenie z inventory
- ✅ Aktualizacja metadanych
- ✅ Walidacja ISBN
- ✅ Usunięcie książki
- ✅ Blokada usunięcia jeśli wypożyczone

---

### 5. User Handlers (SECURITY)
```bash
tests/Application/Handler/CreateUserHandlerTest.php
tests/Application/Handler/UpdateUserHandlerTest.php
tests/Application/Handler/DeleteUserHandlerTest.php
tests/Application/Handler/BlockUserHandlerTest.php
tests/Application/Handler/UnblockUserHandlerTest.php
```

**Powód**: Zarządzanie użytkownikami - bezpieczeństwo.

**Scenariusze**:
- ✅ Utworzenie użytkownika
- ✅ Hash hasła
- ✅ Aktualizacja profilu
- ✅ Aktualizacja membership
- ✅ Usunięcie użytkownika
- ✅ Blokada użytkownika
- ✅ Odblokowanie
- ✅ Walidacja ról

---

## 📊 ŚREDNI PRIORITY (20 testów)

### 6. BookInventory Handlers
```bash
tests/Application/Handler/CreateBookCopyHandlerTest.php
tests/Application/Handler/UpdateBookCopyHandlerTest.php
tests/Application/Handler/DeleteBookCopyHandlerTest.php
```

### 7. BookAsset Handlers
```bash
tests/Application/Handler/UploadBookAssetHandlerTest.php
tests/Application/Handler/DeleteBookAssetHandlerTest.php
```

### 8. Review Handler
```bash
tests/Application/Handler/CreateReviewHandlerTest.php
# Jeśli dodasz DeleteReviewCommand:
tests/Application/Handler/DeleteReviewHandlerTest.php
```

### 9. Favorite Handlers
```bash
tests/Application/Handler/AddFavoriteHandlerTest.php
tests/Application/Handler/RemoveFavoriteHandlerTest.php
```

### 10. Announcement Handlers (już masz 3, dodaj pozostałe)
```bash
tests/Application/Handler/CreateAnnouncementHandlerTest.php
tests/Application/Handler/UpdateAnnouncementHandlerTest.php
```

### 11. Catalog Handlers
```bash
tests/Application/Handler/ImportCatalogHandlerTest.php
tests/Application/Handler/ExportCatalogHandlerTest.php
```

### 12. Acquisition Handlers (masz już Budget, dodaj Order)
```bash
tests/Application/Handler/CreateOrderHandlerTest.php  # ✅ Już istnieje
tests/Application/Handler/ReceiveOrderHandlerTest.php
tests/Application/Handler/CancelOrderHandlerTest.php
tests/Application/Handler/UpdateOrderStatusHandlerTest.php
```

### 13. Weeding Handler
```bash
tests/Application/Handler/CreateWeedingRecordHandlerTest.php
```

### 14. Account Handlers
```bash
tests/Application/Handler/UpdateAccountHandlerTest.php
tests/Application/Handler/ChangePasswordHandlerTest.php
```

---

## 🔍 NISKI PRIORITY (Query Handlers - 28 testów)

Query handlers są prostsze (read-only), ale warto przetestować:

### Lista Query Handlers do przetestowania:
```bash
# Dashboard
tests/Application/Handler/GetOverviewHandlerTest.php

# Book
tests/Application/Handler/GetBookHandlerTest.php
tests/Application/Handler/ListBooksHandlerTest.php

# Loan
tests/Application/Handler/GetLoanHandlerTest.php
tests/Application/Handler/ListLoansHandlerTest.php
tests/Application/Handler/ListUserLoansHandlerTest.php

# Fine
tests/Application/Handler/ListFinesHandlerTest.php

# Reservation
tests/Application/Handler/ListReservationsHandlerTest.php

# Review
tests/Application/Handler/ListBookReviewsHandlerTest.php

# Acquisition
# ✅ GetBudgetSummaryHandlerTest - już istnieje
tests/Application/Handler/ListBudgetsHandlerTest.php
tests/Application/Handler/ListOrdersHandlerTest.php
tests/Application/Handler/ListSuppliersHandlerTest.php

# Announcement
tests/Application/Handler/GetAnnouncementHandlerTest.php
tests/Application/Handler/ListAnnouncementsHandlerTest.php

# BookAsset
tests/Application/Handler/GetBookAssetHandlerTest.php
tests/Application/Handler/ListBookAssetsHandlerTest.php

# BookInventory
tests/Application/Handler/ListBookCopiesHandlerTest.php

# Favorite
tests/Application/Handler/ListUserFavoritesHandlerTest.php

# Weeding
tests/Application/Handler/ListWeedingRecordsHandlerTest.php

# AuditLog
tests/Application/Handler/ListAuditLogsHandlerTest.php
tests/Application/Handler/GetEntityHistoryHandlerTest.php

# Reports
tests/Application/Handler/GetFinancialSummaryHandlerTest.php
tests/Application/Handler/GetInventoryOverviewHandlerTest.php
tests/Application/Handler/GetPatronSegmentsHandlerTest.php
tests/Application/Handler/GetPopularTitlesHandlerTest.php
tests/Application/Handler/GetUsageReportHandlerTest.php
```

---

## 📈 POSTĘP TESTOWANIA

### Obecny stan:
- ✅ Istniejące: 14 plików testowych
- ✅ Przechodzące: 37/37 testów (100%)
- 📊 Pokrycie: 14/72 Handlers (19%)

### Cel 1 (MINIMUM): Krytyczne testy
- 🎯 Dodaj: 15 plików testowych
- 🎯 Nowe testy: ~45 testów
- 🎯 Pokrycie: 29/72 Handlers (40%)

### Cel 2 (ZALECANE): Średnie priority
- 🎯 Dodaj: 20 plików testowych
- 🎯 Nowe testy: ~60 testów
- 🎯 Pokrycie: 49/72 Handlers (68%)

### Cel 3 (IDEALNE): Wszystkie Handlers
- 🎯 Dodaj: 58 plików testowych
- 🎯 Nowe testy: ~174 testy
- 🎯 Pokrycie: 72/72 Handlers (100%)

---

## 🛠️ TEMPLATE TESTU HANDLER

```php
<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Example\ExampleCommand;
use App\Application\Handler\ExampleHandler;
use App\Entity\Example;
use App\Repository\ExampleRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ExampleHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private ExampleRepository $repository;
    private ExampleHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ExampleRepository::class);
        
        $this->handler = new ExampleHandler(
            $this->em,
            $this->repository
        );
    }

    public function testHandleSuccess(): void
    {
        $command = new ExampleCommand(
            name: 'Test Name',
            value: 'Test Value'
        );

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->em
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Example::class));

        $this->em
            ->expects($this->once())
            ->method('flush');

        $result = ($this->handler)($command);

        $this->assertInstanceOf(Example::class, $result);
        $this->assertEquals('Test Name', $result->getName());
    }

    public function testThrowsExceptionWhenNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not found');

        $command = new ExampleCommand(id: 999);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        ($this->handler)($command);
    }

    public function testValidatesInput(): void
    {
        $this->expectException(\RuntimeException::class);

        $command = new ExampleCommand(
            name: '', // Invalid
            value: 'Test'
        );

        ($this->handler)($command);
    }
}
```

---

## ✅ WYKONAJ KROK PO KROKU

### Krok 1: Krytyczne testy (1-2 tygodnie)
```bash
# Dzień 1-3: Loan Handlers
composer test tests/Application/Handler/CreateLoanHandlerTest.php
composer test tests/Application/Handler/ReturnLoanHandlerTest.php
composer test tests/Application/Handler/ExtendLoanHandlerTest.php

# Dzień 4-5: Fine Handlers
composer test tests/Application/Handler/CreateFineHandlerTest.php
composer test tests/Application/Handler/PayFineHandlerTest.php

# Dzień 6-7: Reservation Handlers
composer test tests/Application/Handler/CreateReservationHandlerTest.php
composer test tests/Application/Handler/CancelReservationHandlerTest.php

# Dzień 8-10: Book Handlers
composer test tests/Application/Handler/CreateBookHandlerTest.php
composer test tests/Application/Handler/UpdateBookHandlerTest.php
composer test tests/Application/Handler/DeleteBookHandlerTest.php

# Dzień 11-14: User Handlers
composer test tests/Application/Handler/CreateUserHandlerTest.php
composer test tests/Application/Handler/UpdateUserHandlerTest.php
composer test tests/Application/Handler/DeleteUserHandlerTest.php
composer test tests/Application/Handler/BlockUserHandlerTest.php
```

### Krok 2: Uruchom wszystkie testy
```bash
composer test
```

### Krok 3: Sprawdź pokrycie
```bash
php vendor/bin/phpunit --coverage-html coverage/
```

### Krok 4: Raport
```bash
# Zaktualizuj TEST_COVERAGE_REPORT.md
# z nowymi statystykami
```

---

*Priorytetyzacja oparta na:*
- *Krytyczność operacji biznesowej*
- *Złożoność logiki*
- *Ryzyko błędów*
- *Operacje finansowe*
- *Bezpieczeństwo danych*
