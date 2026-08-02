---
inclusion: always
---

# Tech Stack & Lệnh thao tác

## Stack

- PHP 8.4, Symfony 8.1
- Doctrine ORM (PostgreSQL)
- LexikJWTAuthenticationBundle (JWT auth)
- Docker Compose: service `php` (PHP-FPM), `nginx`, `db` (Postgres)
- Test: PHPUnit + DataFixtures

## Quy tắc cứng — luôn chạy lệnh QUA Docker, không chạy PHP/Composer trên host

```bash
# Composer
docker compose exec php composer require <package>
docker compose exec php composer dump-autoload

# Console
docker compose exec php php bin/console <command>
docker compose exec php php bin/console cache:clear
docker compose exec php php bin/console debug:router
docker compose exec php php bin/console make:entity
docker compose exec php php bin/console make:migration
docker compose exec php php bin/console doctrine:migrations:migrate
docker compose exec php php bin/console doctrine:fixtures:load

# Test
docker compose exec php php bin/phpunit

# Static analysis / style (nếu có cài)
docker compose exec php vendor/bin/phpstan analyse
docker compose exec php vendor/bin/php-cs-fixer fix
```

## Sau khi tạo entity/resource mới, luôn chạy theo thứ tự

1. `composer dump-autoload`
2. `cache:clear`
3. `debug:router | grep <resource>` — xác nhận route đã đăng ký đúng
4. Nếu có entity mới → `make:migration` rồi `doctrine:migrations:migrate`
