<?php
namespace App\Command;

use App\Entity\NotificationLog;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\UserRepository;
use App\Service\Notification\NewsletterComposer;
use App\Service\Notification\NotificationContent;
use App\Service\Notification\NotificationSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'notifications:dispatch-newsletter', description: 'Send a digest of recent arrivals to newsletter subscribers.')]
class DispatchNewsletterCommand extends Command
{
    private const DEFAULT_BOOK_LIMIT = 10;
    private const LOG_TYPE = 'newsletter_digest';

    public function __construct(
        private UserRepository $userRepository,
        private BookRepository $bookRepository,
        private NewsletterComposer $composer,
        private NotificationSender $notificationSender,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'How many days back to look for new arrivals', '7')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of recipients to process (0 = unlimited)', '0')
            ->addOption('channel', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Channels to use (email, sms). Can be provided multiple times.', ['email'])
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview newsletter without sending notifications');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $daysWindow = max(1, (int) $input->getOption('days'));
        $recipientLimit = max(0, (int) $input->getOption('limit'));
        $dryRun = (bool) $input->getOption('dry-run');
        $channels = $this->normalizeChannels((array) $input->getOption('channel'));

        if ($channels === []) {
            $io->error('Select at least one supported channel: email, sms.');
            return Command::INVALID;
        }

        $since = (new \DateTimeImmutable(sprintf('-%d days', $daysWindow)))->setTime(0, 0);
        $books = $this->bookRepository->findNewArrivals($since, self::DEFAULT_BOOK_LIMIT);
        if ($books === []) {
            $io->warning('No new arrivals detected for the selected window.');
            return Command::SUCCESS;
        }

        $recipients = $this->userRepository->findNewsletterRecipients($recipientLimit);
        if ($recipients === []) {
            $io->warning('No recipients opted-in to the newsletter.');
            return Command::SUCCESS;
        }

        $content = $this->composer->compose($books, $daysWindow, $channels);

        if ($dryRun) {
            $this->renderDryRun($io, $content, $recipients, $channels);
            return Command::SUCCESS;
        }

        $stats = [
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($recipients as $user) {
            $io->writeln(sprintf('Dispatching newsletter to %s (#%d)', $user->getEmail() ?? 'user without email', $user->getId()), OutputInterface::VERBOSITY_VERBOSE);
            foreach ($channels as $channel) {
                $result = $this->sendForChannel($channel, $user, $content);
                $stats[$this->mapStatusKey($result['status'] ?? 'sent')]++;
                $this->recordLog($user, $channel, $content, $result, $daysWindow);
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Newsletter dispatched. Sent: %d, skipped: %d, failed: %d.',
            $stats['sent'],
            $stats['skipped'],
            $stats['failed']
        ));

        return Command::SUCCESS;
    }

    private function sendForChannel(string $channel, User $user, NotificationContent $content): array
    {
        return match ($channel) {
            'email' => $this->notificationSender->sendEmail($user, $content),
            'sms' => $this->notificationSender->sendSms($user, $content),
            default => ['status' => 'skipped', 'error' => 'unsupported_channel'],
        };
    }

    private function normalizeChannels(array $raw): array
    {
        $normalized = [];
        foreach ($raw as $value) {
            $channel = strtolower(trim((string) $value));
            if ($channel === '') {
                continue;
            }
            if (!in_array($channel, ['email', 'sms'], true)) {
                continue;
            }
            if (!in_array($channel, $normalized, true)) {
                $normalized[] = $channel;
            }
        }

        if ($normalized === []) {
            $normalized[] = 'email';
        }

        return $normalized;
    }

    private function renderDryRun(SymfonyStyle $io, NotificationContent $content, array $recipients, array $channels): void
    {
        $io->section('Newsletter preview (dry run)');
        $io->text(sprintf('Subject: %s', $content->getSubject()));
        $io->text(sprintf('Channels: %s', implode(', ', $channels)));

        $io->newLine();
        $io->text('Text body preview:');
        $io->writeln('------------------------');
        $previewLimit = 600;
        $textBody = $content->getTextBody();
        $preview = function_exists('mb_substr') ? mb_substr($textBody, 0, $previewLimit) : substr($textBody, 0, $previewLimit);
        $exceedsLimit = (function_exists('mb_strlen') ? mb_strlen($textBody) : strlen($textBody)) > $previewLimit;
        $io->writeln($preview . ($exceedsLimit ? "\n..." : ''));
        $io->writeln('------------------------');

        $rows = [];
        $listed = min(10, count($recipients));
        for ($i = 0; $i < $listed; $i++) {
            $user = $recipients[$i];
            $rows[] = [
                $user->getId(),
                $user->getFullName() ?? $user->getEmail(),
                $user->getEmail() ?? '—',
            ];
        }

        $io->table(['User ID', 'Name', 'Email'], $rows);
        if (count($recipients) > $listed) {
            $io->text(sprintf('...and %d more recipient(s).', count($recipients) - $listed));
        }
    }

    private function mapStatusKey(string $status): string
    {
        return match (strtolower($status)) {
            'sent' => 'sent',
            'skipped' => 'skipped',
            default => 'failed',
        };
    }

    private function recordLog(User $user, string $channel, NotificationContent $content, array $result, int $daysWindow): void
    {
        $log = (new NotificationLog())
            ->setUser($user)
            ->setType(self::LOG_TYPE)
            ->setChannel($channel)
            ->setFingerprint($this->buildFingerprint($user, $channel))
            ->setPayload([
                'subject' => $content->getSubject(),
                'daysWindow' => $daysWindow,
                'channels' => $content->getChannels(),
            ])
            ->setStatus(strtoupper($result['status'] ?? 'sent'))
            ->setErrorMessage($result['error'] ?? null);

        $this->entityManager->persist($log);
    }

    private function buildFingerprint(User $user, string $channel): string
    {
        $raw = implode('|', ['newsletter', $user->getId(), $channel, microtime(true), random_int(0, PHP_INT_MAX)]);
        return substr(hash('sha256', $raw), 0, 64);
    }
}
