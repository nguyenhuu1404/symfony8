<?php

namespace App\Dto\Auth;

use App\Entity\User;

/**
 * Kết quả login ở tầng DOMAIN — chưa quan tâm tới hình dạng JSON trả ra.
 * AuthController dùng LoginResultMapper để quyết định format cuối cùng.
 */
final class LoginResultDto
{
    public function __construct(
        public readonly string $token,
        public readonly int $expiresIn,
        public readonly User $user,
    ) {
    }
}
