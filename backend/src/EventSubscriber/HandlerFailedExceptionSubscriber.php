<?php
declare(strict_types=1);
namespace App\EventSubscriber;

use App\Exception\AppException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class HandlerFailedExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!$throwable instanceof HandlerFailedException) {
            return;
        }

        $previous = $throwable->getPrevious() ?? $throwable;

        // AppException carries its own HTTP status code — unwrap it
        if ($previous instanceof AppException) {
            $event->setThrowable($previous);
            return;
        }

        if ($previous instanceof HttpExceptionInterface) {
            $event->setThrowable($previous);
            return;
        }

        if ($previous instanceof \InvalidArgumentException) {
            $event->setThrowable(new BadRequestHttpException($previous->getMessage(), $previous));
        }
    }
}
