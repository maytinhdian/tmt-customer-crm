<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Core\Capabilities;

use TMT\CRM\Shared\Container;
use TMT\CRM\Modules\Core\Capabilities\Infrastructure\Persistence\WpOptionsCapabilitiesRepository;
use TMT\CRM\Modules\Core\Capabilities\Application\Services\PolicyService;
use TMT\CRM\Modules\Core\Capabilities\Presentation\Admin\Settings\CapabilitiesMatrixScreen;
use TMT\CRM\Modules\Core\Capabilities\Infrastructure\Role\RoleSynchronizer;

/**
 * CoreCapabilitiesModule (bootstrap - file chính)
 * Đăng ký repository, policy service, settings screen, và đồng bộ role↔capabilities.
 */
final class CoreCapabilitiesModule
{
    /** Gọi 1 lần ở bootstrap (file chính) */
    public static function register(): void
    {
        // 1) Container wiring
        Container::set('core.capabilities.repo', fn() => new WpOptionsCapabilitiesRepository());
        Container::set('core.capabilities.policy_service', fn() => new PolicyService(
            Container::get('core.capabilities.repo')
        ));
        Container::set('core.capabilities.role_sync', fn() => new RoleSynchronizer(
            Container::get('core.capabilities.repo')
        ));

        // 2) Đăng ký settings screen
        add_action('admin_menu', function () {
            CapabilitiesMatrixScreen::register();
        }, 20);

        // 3) Đồng bộ roles mỗi lần plugins_loaded (an toàn, nhẹ)
        add_action('plugins_loaded', function () {
            /** @var RoleSynchronizer $sync */
            $sync = Container::get('core.capabilities.role_sync');
            $sync->sync_all();
        }, 15);
    }
}
