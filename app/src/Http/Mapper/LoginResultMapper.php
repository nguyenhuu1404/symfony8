<?php

namespace App\Http\Mapper;

use App\Dto\Auth\LoginResultDto;

final class LoginResultMapper
{
    public static function toArray(LoginResultDto $result): array
    {
        return [
            'access_token' => $result->token,
            'token_type' => 'Bearer',
            'expires_in' => $result->expiresIn,
            'user' => UserMapper::toArray($result->user),
        ];
    }
}
