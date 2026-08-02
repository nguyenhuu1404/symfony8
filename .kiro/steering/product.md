---
inclusion: always
---

# Dự án: Symfony API (RBAC Backend)

REST API backend dùng Symfony 8.1 / PHP 8.4, phục vụ dự án outsourcing
(client US/UK/Asia). API-only, không có Twig/view — mọi response đều JSON.

## Mục tiêu kiến trúc

- Backend RBAC: User / Role / Permission, phân quyền qua Voter
- Auth: JWT (LexikJWTAuthenticationBundle)
- Toàn bộ resource CRUD phải theo đúng 1 pattern duy nhất (xem structure.md)
  — không tự sáng tạo kiến trúc khác cho từng resource

## Response format chuẩn (bắt buộc, mọi endpoint)

```json
{
  "success": true,
  "message": "string",
  "data": {}
}
```

Lỗi cũng phải theo format này (success: false), xử lý tập trung qua
`ApiExceptionListener` lắng nghe `kernel.exception` — Controller và Service
KHÔNG tự try/catch để format lỗi tay.

## Người dùng dự án

Senior Laravel/PHP dev đang chuyển hướng sang NestJS/Symfony để nhắm thị
trường outsourcing US/UK/Asia. Khi giải thích khái niệm Symfony mới, có thể
đối chiếu với khái niệm Laravel tương ứng (Service Container ~ Container,
Middleware ~ Middleware, Eloquent ~ Doctrine ORM) nếu điều đó giúp hiểu nhanh hơn.
