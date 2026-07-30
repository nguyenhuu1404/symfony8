<?php

namespace App\Service;

use App\Http\Request\Auth\LoginRequest;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\DisabledException;

final class AuthService
{
    public function __construct(
        private readonly UserRepository              $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface    $jwtManager,
        #[Autowire('%lexik_jwt_authentication.token_ttl%')]
        private readonly int                         $tokenTtl,
    ) {}

    /**
     * Xác thực user và trả về JWT token cùng thông tin user.
     *
     * @throws DisabledException      khi tài khoản bị khoá
     * @throws BadCredentialsException khi email/password sai
     */
    public function login(LoginRequest $request): array
    {
        $user = $this->userRepository->findOneBy(['email' => $request->email]);

        if ($user === null) {
            throw new BadCredentialsException();
        }

        if (!$user->isActive()) {
            throw new DisabledException();
        }

        if (!$this->passwordHasher->isPasswordValid($user, $request->password)) {
            throw new BadCredentialsException();
        }

        $token = $this->jwtManager->create($user);

        return [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => $this->tokenTtl,
            'user'         => [
                'id'        => $user->getId(),
                'email'     => $user->getEmail(),
                'full_name' => $user->getFullName(),
                'roles'     => $user->getRoles(),
            ],
        ];
    }
}
