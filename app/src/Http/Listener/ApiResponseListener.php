<?php

namespace App\Http\Listener;

use App\Http\Response\ApiResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * kernel.view CHỈ chạy khi Controller trả về giá trị KHÔNG PHẢI Response.
 * Controller tự map data (qua Mapper) thành array TRƯỚC khi return,
 * listener này chỉ có 1 nhiệm vụ: bọc array đó vào envelope {success, data}.
 * Không magic, không Reflection, không Serializer Group.
 */
#[AsEventListener(event: KernelEvents::VIEW, priority: 0)]
final class ApiResponseListener
{
    public function __invoke(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if ($result instanceof Response) {
            return;
        }

        $event->setResponse(ApiResponse::success($result));
    }
}
