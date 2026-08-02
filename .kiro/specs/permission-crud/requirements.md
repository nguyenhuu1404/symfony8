# Requirements: Permission CRUD API

## Bối cảnh

API quản lý Permission cho hệ thống RBAC — admin cần tạo/sửa/xoá/xem danh
sách permission để gán vào Role.

## Yêu cầu chức năng

1. **List permissions** — GET trả về toàn bộ permission, yêu cầu quyền
   `permission.view`
2. **Show permission** — GET theo id, 404 nếu không tồn tại, yêu cầu quyền
   `permission.view`
3. **Create permission** — POST tạo mới, field `name` bắt buộc, unique
   (409 nếu trùng), yêu cầu quyền `permission.manage`
4. **Update permission** — PUT/PATCH theo id, check unique khi đổi `name`,
   yêu cầu quyền `permission.manage`
5. **Delete permission** — DELETE theo id, yêu cầu quyền `permission.manage`,
   trả 204

## Tiêu chí chấp nhận (EARS format)

- WHEN user không có quyền `permission.view` gọi GET, THE system SHALL trả
  403
- WHEN user tạo permission với `name` đã tồn tại, THE system SHALL trả 409
  với message rõ nguyên nhân
- WHEN request thiếu field `name` bắt buộc, THE system SHALL trả 422 với
  chi tiết lỗi validation
- WHEN thao tác thành công, THE system SHALL trả response đúng format
  `{success, message, data}`

## Ngoài phạm vi

- Không cần soft delete (xoá cứng)
- Không cần versioning/audit log cho thay đổi permission ở giai đoạn này
