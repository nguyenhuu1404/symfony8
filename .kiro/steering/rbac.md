---
inclusion: fileMatch
fileMatchPattern: "src/{Controller,Security}/**/*.php"
---

# RBAC & Phân quyền

Áp dụng khi sửa/tạo file trong `src/Controller/` hoặc `src/Security/`.

## Nguyên tắc

- User → Role → Permission (many-to-many qua Role)
- Check quyền LUÔN qua `#[IsGranted('resource.action')]` ở Controller, hoặc
  Voter nếu logic phức tạp hơn (VD: chỉ owner mới sửa được resource của mình)
- KHÔNG check role/permission thủ công trong Controller
  (VD: `if ($user->hasRole('admin'))`) — luôn qua Voter/IsGranted để logic
  phân quyền tập trung, dễ audit

## Convention permission string

`{resource}.{action}` — action chuẩn là `view` (đọc) và `manage` (tạo/sửa/xoá).
Nếu cần action đặc thù hơn (VD: `permission.toggle-status`), thêm entry mới
vào `PermissionFixtures` và document lý do trong PR.

## Khi thêm permission mới

1. Thêm vào `PermissionFixtures`
2. Chạy `docker compose exec php php bin/console doctrine:fixtures:load`
   (nhắc user: lệnh này XOÁ DATA hiện có nếu không có `--append`)
3. Gán permission vào Role phù hợp trong fixture, không để permission "mồ côi"

## Voter — chỉ tạo khi cần

Chỉ tạo Voter riêng (`src/Security/Voter/{Resource}Voter.php`) khi quyền phụ
thuộc vào DATA (VD: user chỉ sửa được record do chính mình tạo). Nếu quyền
chỉ phụ thuộc role — dùng `#[IsGranted('resource.action')]` đơn giản, không
cần Voter.
