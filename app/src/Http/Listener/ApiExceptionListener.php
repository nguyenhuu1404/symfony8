<?php

namespace App\Http\Listener;

use App\Http\Response\ApiResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Bắt TOÀN BỘ exception trong app, format lại theo đúng chuẩn ApiResponse.
 * Nhờ đó, Controller không cần try/catch từng exception thủ công nữa —
 * Service tự do throw exception, listener này lo phần convert sang JSON.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // 1. Lỗi validate từ #[MapRequestPayload] (built-in Symfony)
        if ($exception instanceof HttpExceptionInterface
            && $exception->getPrevious() instanceof ValidationFailedException
        ) {
            $errors = [];
            foreach ($exception->getPrevious()->getViolations() as $violation) {
                $field = ltrim($violation->getPropertyPath(), '.');
                $errors[$field] = $violation->getMessage();
            }

            $event->setResponse(ApiResponse::validationError($errors));
            return;
        }

        // 2. Lỗi xác thực — bao gồm BadCredentialsException, DisabledException,
        //    và mọi exception khác implement AuthenticationException của Symfony Security
        if ($exception instanceof AuthenticationException) {
            $event->setResponse(ApiResponse::unauthorized($exception->getMessage() ?: 'Unauthorized.'));
            return;
        }

        if ($exception instanceof BadRequestHttpException) {
            $event->setResponse(ApiResponse::badRequest($exception->getMessage() ?: 'Bad request.'));
            return;
        }

        // 3. Lỗi phân quyền (VD: #[IsGranted] fail)
        if ($exception instanceof AccessDeniedException) {
            $event->setResponse(ApiResponse::forbidden());
            return;
        }

        // 4. Route/resource không tồn tại
        if ($exception instanceof NotFoundHttpException) {
            $event->setResponse(ApiResponse::notFound());
            return;
        }

        // 5. Fallback cho mọi lỗi khác (500) — KHÔNG lộ message thật ra ngoài,
        //    tránh leak thông tin nội bộ (stack trace, tên class, query SQL...)
        //    Log lỗi thật ở nơi khác (monolog), chỉ trả message chung cho client.
        $event->setResponse(ApiResponse::error('Internal server error.', 500));
    }
}
