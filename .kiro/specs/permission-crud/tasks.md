# Tasks: Permission CRUD API

- [ ] 1. Tạo `PermissionRequestDto` với validation `name` (NotBlank, Length max 255)
- [ ] 2. Tạo `PermissionMapper` (toArray + collection)
- [ ] 3. Tạo `PermissionService` (list/create/update/delete + check unique name)
- [ ] 4. Tạo `PermissionController` (5 route + IsGranted)
- [ ] 5. Thêm `permission.view`, `permission.manage` vào `PermissionFixtures`
- [ ] 6. Chạy `composer dump-autoload` + `cache:clear`
- [ ] 7. Xác nhận route bằng `debug:router | grep permission`
- [ ] 8. Viết test: index/show/store/update/destroy (theo testing.md, gồm case 403/404/409/422)
- [ ] 9. Test tay qua curl/Postman đủ 5 endpoint
- [ ] 10. Review lại Mapper — đảm bảo không lộ field thừa
