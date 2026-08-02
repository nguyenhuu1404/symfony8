---
inclusion: always
---

# Pattern CRUD Resource (BẮT BUỘC — không tự sáng tạo pattern khác)

Đã áp dụng thành công cho `Permission`. Mọi resource mới copy đúng cấu trúc
này, chỉ đổi tên. Không dùng FormRequest tự viết, không dùng Serializer+Groups.

## Layer và vị trí file

| Layer | Đường dẫn | Vai trò |
|---|---|---|
| DTO | `src/Dto/{Resource}/{Resource}RequestDto.php` | Validate input bằng Symfony Validator constraints |
| Mapper | `src/Http/Mapper/{Resource}Mapper.php` | Format entity → array cho response, chỉ expose field cần thiết |
| Service | `src/Service/{Resource}Service.php` | Business logic, thao tác Doctrine (persist/flush) |
| Controller | `src/Controller/Api/V1/{Resource}Controller.php` | Mỏng — chỉ gọi Service + Mapper, không chứa logic |
| Voter | `src/Security/Voter/{Resource}Voter.php` | Chỉ tạo nếu quyền phức tạp hơn `#[IsGranted('{resource}.action')]` đơn giản |

## Quy tắc DTO

- Dùng CHUNG 1 DTO cho create/update, trừ khi 2 action cần field khác biệt rõ
  rệt (update cho phép partial, create bắt buộc đủ field) — lúc đó tách
  `Create{Resource}RequestDto` / `Update{Resource}RequestDto`
- Validate bằng `Symfony\Component\Validator\Constraints`, không validate tay
  trong Service

## Quy tắc Service

- Check unique/conflict TRƯỚC khi persist, throw `ConflictHttpException`
  (built-in) — không tự định nghĩa exception class riêng cho việc này
- Method chuẩn: `list()`, `create(dto)`, `update(entity, dto)`, `delete(entity)`
- Service KHÔNG try/catch để format response lỗi — để `ApiExceptionListener`
  xử lý tập trung

## Quy tắc Controller

- 5 route chuẩn: `index` (GET), `show` (GET /{id}), `store` (POST),
  `update` (PUT/PATCH /{id}), `destroy` (DELETE /{id})
- Mọi route phải có `#[IsGranted('{resource}.view')]` hoặc
  `#[IsGranted('{resource}.manage')]` — convention permission string là
  `resource.action`
- Dùng `#[MapRequestPayload]` để bind DTO, không tự parse `$request->getContent()`

## Sau khi scaffold xong — checklist bắt buộc

- [ ] Permission string (`{resource}.view`, `{resource}.manage`) đã thêm vào
      `PermissionFixtures` chưa? Nếu chưa → thêm + nhắc chạy lại
      `doctrine:fixtures:load`
- [ ] Mapper KHÔNG expose field nhạy cảm không cần thiết
- [ ] Đã test đủ 5 endpoint qua curl/Postman
- [ ] Response khớp đúng format `{success, message, data}` toàn app
