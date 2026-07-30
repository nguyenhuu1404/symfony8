<?php

namespace App\Http\Listener;

use App\Http\Exception\ValidationException;
use App\Http\Response\ApiResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Bắt ValidationException và trả về JSON 422 chuẩn ApiResponse.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
final class ValidationExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof ValidationException) {
            return;
        }

        $event->setResponse(
            ApiResponse::validationError($exception->getErrors()),
        );
    }
}
