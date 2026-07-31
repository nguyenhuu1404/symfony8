<?php

namespace App\Http\Listener;

use App\Http\Response\ApiResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Bắt lỗi validate do #[MapRequestPayload] tự throw (built-in Symfony),
 * format lại theo đúng chuẩn ApiResponse của app thay vì để mặc định
 * trả về RFC 7807 Problem Details.
 *
 * Cơ chế: RequestPayloadValueResolver khi validate fail sẽ throw
 * HttpException (422), với ValidationFailedException nằm ở getPrevious()
 * (chứa danh sách ConstraintViolation chi tiết từng field).
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
final class ValidationExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof HttpExceptionInterface) {
            return;
        }

        $previous = $exception->getPrevious();

        if (!$previous instanceof ValidationFailedException) {
            return;
        }

        $errors = [];
        foreach ($previous->getViolations() as $violation) {
            $field = ltrim($violation->getPropertyPath(), '.');
            $errors[$field] = $violation->getMessage();
        }

        $event->setResponse(ApiResponse::validationError($errors));
    }
}
