# Core/Capabilities Module (Skeleton)

- Class = PascalCase; tên hàm có thể snake_case.
- Không cần DB table; ma trận quyền (role => caps) lưu trong `wp_options`.
- Trang quản trị: **Settings → TMT CRM - Capabilities**.
- Khi lưu ma trận, hệ thống sẽ **đồng bộ** capability vào các WP roles.

## Container keys
- `core.capabilities.repo` → WpOptionsCapabilitiesRepository
- `core.capabilities.policy_service` → PolicyService
- `core.capabilities.role_sync` → RoleSynchronizer

## Sử dụng nhanh
```php
use TMT\CRM\Modules\Core\Capabilities\CoreCapabilitiesModule;
add_action('plugins_loaded', fn() => CoreCapabilitiesModule::register(), 1);

// Kiểm tra quyền trong Screen/Controller:
use TMT\CRM\Modules\Core\Capabilities\Application\Services\PolicyService;
use TMT\CRM\Modules\Core\Capabilities\Domain\Capability;

$policy = \TMT\CRM\Shared\Container::get('core.capabilities.policy_service');
$policy->ensure_capability(Capability::COMPANY_READ, get_current_user_id(), 'Bạn không có quyền xem công ty');
```
