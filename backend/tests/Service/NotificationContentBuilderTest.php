<?php
namespace App\Tests\Service;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\Reservation;
use App\Entity\User;
use App\Service\Notification\NotificationContentBuilder;
use PHPUnit\Framework\TestCase;

class NotificationContentBuilderTest extends TestCase
{
    public function testBuildLoanDueIncludesEmailChannel(): void
    {
        $user = (new User())->setName('Reader')->setEmail('reader@example.com');
        $book = (new Book())->setTitle('Title')->setAuthor(new Author());
        $loan = (new Loan())->setUser($user)->setBook($book)->setDueAt(new \DateTimeImmutable('+2 days'));

        $builder = new NotificationContentBuilder();
        $content = $builder->buildLoanDue($user, $loan);

        $this->assertSame(['email'], $content->getChannels());
    }

    public function testBuildLoanOverdueAddsSmsWhenPhoneAvailable(): void
    {
        $user = (new User())->setName('Reader')->setEmail('reader@example.com')->setPhoneNumber('123');
        $book = (new Book())->setTitle('Title')->setAuthor(new Author());
        $loan = (new Loan())->setUser($user)->setBook($book)->setDueAt(new \DateTimeImmutable('-2 days'));

        $builder = new NotificationContentBuilder();
        $content = $builder->buildLoanOverdue($user, $loan, 3);

        $this->assertContains('sms', $content->getChannels());
    }

    public function testBuildReservationReadyIncludesDeadline(): void
    {
        $user = (new User())->setName('Reader')->setEmail('reader@example.com');
        $book = (new Book())->setTitle('Title')->setAuthor(new Author());
        $reservation = (new Reservation())->setUser($user)->setBook($book);

        $builder = new NotificationContentBuilder();
        $content = $builder->buildReservationReady($user, $reservation);

        $this->assertStringContainsString('Rezerwacja', $content->getSubject());
    }
}
