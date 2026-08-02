<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

/**
 * Voter chung cho toàn bộ hệ thống RBAC.
 *
 * Xử lý mọi attribute dạng "resource.action" (VD: permission.view,
 * user.create, role.assign_permission...) — tương đương Gate::define()
 * hoặc Policy trong Laravel, nhưng dùng 1 class duy nhất vì logic
 * check đơn giản: duyệt Role → Permission của User.
 *
 * Flow: #[IsGranted('permission.view')]
 *   → Symfony gọi supports() → true
 *   → voteOnAttribute() → duyệt user->roles->permissions
 *   → trả về true/false
 */
class RbacVoter extends Voter
{
    /**
     * Voter này nhận bất kỳ attribute nào chứa dấu chấm (resource.action).
     * Subject không cần thiết cho permission-level check (khác với
     * object-level check như "user có sửa post của chính mình không").
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        // Chỉ xử lý string dạng "something.something", bỏ qua ROLE_* và IS_*
        return str_contains($attribute, '.') && !str_starts_with($attribute, 'ROLE_');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // Chưa đăng nhập → từ chối (firewall đã chặn trước, đây là safety net)
        if (!$user instanceof User) {
            return false;
        }

        // Duyệt qua từng Role user đang có, kiểm tra Role đó có Permission không
        foreach ($user->getRoleEntities() as $role) {
            if ($role->hasPermission($attribute)) {
                return true;
            }
        }

        return false;
    }
}
