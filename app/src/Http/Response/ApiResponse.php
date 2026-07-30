<?php

namespace App\Http\Response;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wrapper response dùng chung cho toàn app — tương tự response() helper của Laravel.
 *
 * Dùng:
 *   ApiResponse::success($data)
 *   ApiResponse::error('message', 422, ['field' => 'error'])
 *   ApiResponse::created($data)
 *   ApiResponse::noContent()
 */
final class ApiResponse
{
    // -------------------------------------------------------------------------
    // Success
    // -------------------------------------------------------------------------

    public static function success(mixed $data = null, string $message = 'OK', int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function created(mixed $data = null, string $message = 'Created'): JsonResponse
    {
        return self::success($data, $message, Response::HTTP_CREATED);
    }

    public static function noContent(): JsonResponse
    {
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    // -------------------------------------------------------------------------
    // Error
    // -------------------------------------------------------------------------

    public static function error(
        string $message,
        int    $status = Response::HTTP_BAD_REQUEST,
        array  $errors = [],
    ): JsonResponse {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return new JsonResponse($body, $status);
    }

    public static function validationError(array $errors, string $message = 'Validation failed.'): JsonResponse
    {
        return self::error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    public static function unauthorized(string $message = 'Unauthorized.'): JsonResponse
    {
        return self::error($message, Response::HTTP_UNAUTHORIZED);
    }

    public static function forbidden(string $message = 'Forbidden.'): JsonResponse
    {
        return self::error($message, Response::HTTP_FORBIDDEN);
    }

    public static function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return self::error($message, Response::HTTP_NOT_FOUND);
    }
}
