<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Capabilities\Application\Services;

use TMT\CRM\Domain\Repositories\CapabilitiesRepositoryInterface;

final class PolicyService
{
    public function __construct(
        private CapabilitiesRepositoryInterface $repo
    ) {}

    /**
     * Kiểm tra quyền cấp cao nhất dựa trên:
     * 1) current_user_can($cap) nếu cap đã được add vào role của user.
     * 2) fallback: đọc ma trận từ repo theo vai trò user (phòng trường hợp role chưa sync).
     */
    public function can(string $capability, int $user_id, array $context = []): bool
    {
        // 1) WP core check
        if (user_can($user_id, $capability)) {
            return true;
        }

        // 2) Fallback bằng ma trận trong repo
        $user = get_user_by('id', $user_id);
        if (!$user || empty($user->roles)) {
            return false;
        }
        $matrix = $this->repo->get_matrix();
        foreach ($user->roles as $role) {
            if (!empty($matrix[$role]) && in_array($capability, $matrix[$role], true)) {
                return true;
            }
        }
        return false;
    }

    /** Tiện ích chặn truy cập nếu không có quyền. */
    public function ensure_capability(string $capability, int $user_id, string $error_message): void
    {
        if (!$this->can($capability, $user_id)) {
            wp_die(esc_html($error_message));
        }
    }
}
