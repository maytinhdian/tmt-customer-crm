<?php
/**
 * SecurityBootstrap (file chính)
 * Khởi động phân quyền: Role packs + map_meta_cap (own/any, DIP).
 */
declare(strict_types=1);

namespace TMT\CRM\Shared\Infrastructure\Security;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Modules\Customer\Repositories\CustomerRepositoryInterface;

defined('ABSPATH') || exit;

final class SecurityBootstrap
{
    public static function init(): void
    {
        add_action('init', [RoleService::class, 'install']);
        add_filter('map_meta_cap', [self::class, 'map_meta_cap_handler'], 10, 4);
    }

    /**
     * Map meta capability → primitive theo ngữ cảnh (owner/any).
     * current_user_can(Capability::CUSTOMER_UPDATE, $customer_id)
     */
    public static function map_meta_cap_handler(array $caps, string $cap, int $user_id, array $args): array
    {
        switch ($cap) {
            case Capability::CUSTOMER_UPDATE:
            case Capability::CUSTOMER_DELETE:
                $customer_id = isset($args[0]) ? (int)$args[0] : 0;
                if ($customer_id <= 0) return ['do_not_allow'];

                /** @var CustomerRepositoryInterface $repo */
                $repo = Container::get('customer-repo'); // đúng DIP
                $owner_id = (int)($repo->get_owner_id($customer_id) ?? 0);
                $is_owner = ($owner_id > 0 && $owner_id === $user_id);

                if ($cap === Capability::CUSTOMER_UPDATE) {
                    return [ $is_owner ? Capability::CUSTOMER_UPDATE_OWN : Capability::CUSTOMER_UPDATE_ANY ];
                }
                return [ $is_owner ? Capability::CUSTOMER_DELETE_OWN : Capability::CUSTOMER_DELETE_ANY ];
        }
        return $caps;
    }
}
