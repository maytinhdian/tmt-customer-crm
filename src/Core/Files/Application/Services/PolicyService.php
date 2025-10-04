<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Application\Services;

use TMT\CRM\Core\Capabilities\Domain\Capability;
use TMT\CRM\Domain\Repositories\CapabilitiesRepositoryInterface;

/**
 * Core/Files PolicyService
 * - Kiểm tra quyền cho các thao tác Files dựa trên capability & ownership.
 * - Ưu tiên current_user_can(); fallback: đọc ma trận từ repo theo vai trò user.
 *
 * Gợi ý bind trong FilesServiceProvider nếu muốn dùng qua Container:
 * Container::set(FilesPolicyService::class, fn() => new FilesPolicyService(Container::get(CapabilitiesRepositoryInterface::class)));
 */
final class PolicyService
{
    public function __construct(
        private CapabilitiesRepositoryInterface $repo
    ) {}

    /**
     * Kiểm tra quyền thô theo capability.
     * - Ưu tiên current_user_can($cap).
     * - Fallback: kiểm tra trong ma trận role→caps lấy từ repo.
     */
    public function can(string $capability, int $user_id): bool
    {
        // 1) Nếu role đã được add_cap → WP core xử lý luôn.
        if (function_exists('user_can') && user_can($user_id, $capability)) {
            return true;
        }

        // 2) Fallback: tra cứu ma trận role → caps từ repo
        $user = get_user_by('id', $user_id);
        if (!$user || empty($user->roles) || !is_array($user->roles)) {
            return false;
        }

        $matrix = $this->repo->get_matrix(); // ['role_slug' => ['cap_a','cap_b',...]]
        foreach ($user->roles as $role) {
            if (!empty($matrix[$role]) && in_array($capability, $matrix[$role], true)) {
                return true;
            }
        }
        return false;
    }

    /** Chặn truy cập nếu không có quyền. */
    public function ensure(string $capability, int $user_id, string $error_message = 'Không có quyền thực hiện thao tác này.'): void
    {
        if (!$this->can($capability, $user_id)) {
            wp_die(esc_html($error_message));
        }
    }

    // ---------------------------------------------------------------------
    // Quyền mức nghiệp vụ cho Core/Files
    // ---------------------------------------------------------------------

    /** Quyền xem danh sách/tải file gắn với một entity. */
    public function can_read_entity(string $entity_type, int $entity_id, int $user_id): bool
    {
        // Nếu sau này có rule theo ownership của entity → bổ sung ở đây.
        return $this->can(Capability::FILE_READ, $user_id);
    }

    /** Quyền đính kèm file vào một entity. */
    public function can_attach_to_entity(string $entity_type, int $entity_id, int $user_id): bool
    {
        // Có thể bổ sung kiểm tra entity ownership trước khi cho phép attach
        return $this->can(Capability::FILE_ATTACH, $user_id);
    }

    /**
     * Quyền xóa file:
     * - Có capability FILE_DELETE
     *   HOẶC
     * - Là owner đã upload file (uploaded_by == user_id)
     */
    public function can_delete_file(int $uploaded_by, int $user_id): bool
    {
        if ($this->can(Capability::FILE_DELETE, $user_id)) {
            return true;
        }
        return $uploaded_by > 0 && $uploaded_by === $user_id;
    }

    // ---------------------------------------------------------------------
    // Helper ensure_* cho luồng controller/service
    // ---------------------------------------------------------------------

    public function ensure_can_read_entity(string $entity_type, int $entity_id, int $user_id): void
    {
        if (!$this->can_read_entity($entity_type, $entity_id, $user_id)) {
            wp_die(esc_html__('Bạn không có quyền xem tệp đính kèm của đối tượng này.', 'tmt-crm'));
        }
    }

    public function ensure_can_attach_to_entity(string $entity_type, int $entity_id, int $user_id): void
    {
        if (!$this->can_attach_to_entity($entity_type, $entity_id, $user_id)) {
            wp_die(esc_html__('Bạn không có quyền đính kèm tệp cho đối tượng này.', 'tmt-crm'));
        }
    }

    public function ensure_can_delete_file(int $uploaded_by, int $user_id): void
    {
        if (!$this->can_delete_file($uploaded_by, $user_id)) {
            wp_die(esc_html__('Bạn không có quyền xóa tệp này.', 'tmt-crm'));
        }
    }
}
