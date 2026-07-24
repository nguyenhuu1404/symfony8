# Symfony + Docker Compose Setup

Stack: PHP-FPM 8.3 (Alpine) + Nginx + PostgreSQL 16 + Redis + Mailtrap (test email)

## Cấu trúc thư mục

```
symfony-docker/
├── docker-compose.yml
├── docker/
│   ├── php/
│   │   ├── Dockerfile
│   │   └── php.ini
│   └── nginx/
│       └── default.conf
└── app/              <- code Symfony sẽ nằm ở đây (tạo ở bước 2)
```

## Bước 1: Build image PHP trước (chưa có code Symfony cũng build được)

```bash
docker compose build php
```

## Bước 2: Tạo project Symfony vào thư mục `app/`

Vì `app/` đang trống, dùng chính container vừa build để chạy `composer create-project`
(khỏi cần cài PHP/Composer trên máy host):

```bash
docker compose run --rm --no-deps \
  -v "$(pwd):/scaffold" -w /scaffold \
  php composer create-project symfony/skeleton:"8.1.*" app
```

> Dự án là **API-only** (giống kiến trúc NestJS bạn đang dùng, không render view server-side)
> nên dùng `symfony/skeleton` — bộ khung tối thiểu, không kéo theo Twig/Asset Mapper/Form
> theming như `symfony/webapp`. Cài thêm từng bundle API cần ở Bước 4, tương tự cách bạn
> thêm `@nestjs/typeorm`, `@nestjs/jwt` theo yêu cầu thay vì cài sẵn hết.

## Bước 3: Khởi động toàn bộ stack

```bash
docker compose up -d
```

Kiểm tra:

- App: http://localhost:8080
- PostgreSQL: localhost:5432 (user: `symfony` / pass: `symfony` / db: `symfony_db`)
- Redis: localhost:6379

## Bước 4: Cài các package cho REST API

```bash
# Vào container php
docker compose exec php sh

# ORM + Migrations
composer require symfony/orm-pack
composer require --dev symfony/maker-bundle

# Validator, Serializer (bắt buộc cho API — convert Entity <-> JSON)
composer require symfony/validator
composer require symfony/serializer-pack

# HTTP Client (gọi API bên ngoài, ví dụ webhook, third-party service)
composer require symfony/http-client

# Security (Auth/JWT) - tương đương Sanctum/Passport bên Laravel,
# hoặc @nestjs/passport + @nestjs/jwt bên NestJS
composer require symfony/security-bundle
composer require lexik/jwt-authentication-bundle

# CORS - bắt buộc nếu frontend (Next.js) gọi API từ domain khác
composer require nelmio/cors-bundle

# Redis cache/session
composer require snc/redis-bundle

# Messenger (tương đương Queue bên Laravel / @nestjs/bull bên NestJS)
composer require symfony/messenger
```

> **Không cần** cài `symfony/twig-bundle`, `symfony/asset-mapper`, hay `symfony/form` —
> đây là các package cho server-rendered view, không dùng cho API JSON thuần.

### Lưu ý: ép response lỗi luôn trả JSON

Mặc định Symfony trả trang lỗi **HTML** khi exception xảy ra (404, 500...) — khác với
NestJS vốn trả JSON exception filter sẵn. Cần tự thêm Exception Listener, ví dụ:

```php
// src/EventListener/ExceptionListener.php
#[AsEventListener(event: KernelEvents::EXCEPTION)]
class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $event->setResponse(new JsonResponse([
            'error' => $exception->getMessage(),
        ], $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500));
    }
}
```

### Tùy chọn: API Platform

Nếu muốn tốc độ dev nhanh hơn nữa (tự sinh CRUD REST/GraphQL từ Doctrine Entity, kèm
OpenAPI docs, pagination, filtering có sẵn — gần giống mức độ "magic" của Laravel),
cân nhắc dùng **API Platform** thay vì tự viết controller thủ công:

```bash
composer require api
```

Đánh đổi: API Platform áp đặt convention riêng khá mạnh (giống Laravel hơn Symfony thuần),
phù hợp nếu CRUD chiếm phần lớn API. Nếu API có nhiều business logic phức tạp, tự viết
Controller + Doctrine như hướng dẫn ở trên sẽ linh hoạt hơn.

## Bước 4.5: Cấu hình Mailtrap (test email)

Mailtrap là dịch vụ cloud (không chạy trong Docker như Mailpit), nên cần:

1. Đăng ký/tạo inbox tại https://mailtrap.io → vào **Email Testing** → chọn inbox →
   tab **SMTP Settings**, chọn **Symfony Mailer** để lấy DSN mẫu, dạng:

   ```
   MAILER_DSN=smtp://<username>:<password>@sandbox.smtp.mailtrap.io:2525
   ```

2. Cài package Mailer nếu chưa có:

   ```bash
   docker compose exec php composer require symfony/mailer
   ```

3. **Không** để DSN thật trong `app/.env` (file này commit lên git). Thay vào đó tạo
   `app/.env.local` (đã bị `.gitignore` mặc định của Symfony bỏ qua) và dán dòng
   `MAILER_DSN` ở trên vào đó.

4. Test gửi thử:

   ```bash
   docker compose exec php php bin/console mailer:test your-email@example.com
   ```

   Vào lại Mailtrap inbox trên web để xem email vừa gửi.

> Muốn dùng Mailtrap cho **production** (Sending domains) thay vì Email Testing, DSN sẽ
> khác một chút (dùng API token qua `MAILER_DSN=mailtrap+api://<api_token>@default`) —
> cần cài thêm `symfony/mailtrap-mailer`.

## Bước 5: Cấu hình DATABASE_URL

File `app/.env` mặc định sẽ có dòng `DATABASE_URL` — **xóa hoặc comment dòng đó**,
vì docker-compose.yml đã inject qua biến môi trường của container rồi. Nếu muốn override
riêng cho local, tạo `app/.env.local` (không commit file này).

## Bước 6: Chạy migration đầu tiên

```bash
docker compose exec php php bin/console doctrine:database:create --if-not-exists
docker compose exec php php bin/console make:migration
docker compose exec php php bin/console doctrine:migrations:migrate
```

## Các lệnh hữu ích

```bash
# Tạo entity mới (tương tự php artisan make:model)
docker compose exec php php bin/console make:entity

# Tạo controller
docker compose exec php php bin/console make:controller

# Xem toàn bộ route (tương tự php artisan route:list)
docker compose exec php php bin/console debug:router

# Xem service container (tương tự nhìn vào Service Provider)
docker compose exec php php bin/console debug:container

# Clear cache
docker compose exec php php bin/console cache:clear

# Xem log realtime
docker compose logs -f php
```

## So sánh nhanh với setup NestJS bạn đang dùng

|               | NestJS project               | Symfony project (setup này)      |
| ------------- | ---------------------------- | -------------------------------- |
| ORM           | Prisma                       | Doctrine                         |
| Migration     | `prisma migrate`             | `doctrine:migrations:migrate`    |
| CLI scaffold  | Nest CLI (`nest g`)          | `bin/console make:*`             |
| Cache/session | Redis (ioredis)              | Redis (snc/redis-bundle)         |
| API docs      | thường thêm Swagger thủ công | NelmioApiDocBundle (tương đương) |
