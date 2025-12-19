<?php
namespace App\Controller\Traits;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

trait ExceptionHandlingTrait
{
    protected function unwrapThrowable(\Throwable $e): \Throwable
    {
        if ($e instanceof HandlerFailedException) {
            return $e->getPrevious() ?? $e;
        }

        return $e;
    }

    protected function jsonFromHttpException(\Throwable $e): ?JsonResponse
    {
        if ($e instanceof HttpExceptionInterface) {
            return $this->json(['error' => $e->getMessage()], $e->getStatusCode());
        }

        if ($e instanceof \InvalidArgumentException) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return null;
    }
}
