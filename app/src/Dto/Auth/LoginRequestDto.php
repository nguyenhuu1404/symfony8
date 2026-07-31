<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Tương đương LoginRequest extends FormRequest bên Laravel.
 * Symfony tự deserialize JSON body -> object này, tự validate theo các
 * Assert attribute bên dưới, trước khi Controller được gọi.
 */
final class LoginRequestDto
{
    #[Assert\NotBlank(message: 'Email không được để trống')]
    #[Assert\Email(message: 'Email không đúng định dạng')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Mật khẩu không được để trống')]
    #[Assert\Length(min: 6, minMessage: 'Mật khẩu phải có ít nhất {{ limit }} ký tự')]
    public string $password = '';
}
