---
inclusion: fileMatch
fileMatchPattern: "tests/**/*.php"
---

# Testing Conventions

Áp dụng khi tạo/sửa file trong `tests/`.

## Quy ước

- PHPUnit, đặt tên `{Resource}ControllerTest.php` / `{Resource}ServiceTest.php`
- Seed data qua DataFixtures có sẵn, không tự tạo entity rời rạc trong từng test
  trừ khi test case cần data đặc thù không có trong fixture chung
- Test API: gọi qua `WebTestClient`, assert cả status code lẫn cấu trúc JSON
  `{success, message, data}`
- Với endpoint có `#[IsGranted]`: LUÔN có ít nhất 1 test case user KHÔNG đủ
  quyền → assert 403, không chỉ test happy path

## Mỗi resource CRUD mới cần tối thiểu các test case

- [ ] index — trả đúng danh sách, user thiếu quyền `.view` → 403
- [ ] show — 200 khi tồn tại, 404 khi không tồn tại
- [ ] store — 201 khi input hợp lệ, 422 khi validation fail, 409 khi conflict
      (nếu có check unique), 403 khi thiếu quyền `.manage`
- [ ] update — tương tự store, thêm case 404 khi entity không tồn tại
- [ ] destroy — 204 khi thành công, 403 khi thiếu quyền, 404 khi không tồn tại

## Chạy test

```bash
docker compose exec php php bin/phpunit
docker compose exec php php bin/phpunit --filter={Resource}ControllerTest
```
