<?php
// namespace TMT\CRM\Shared;

// final class Container {
//     private static array $instances = [];

//     public static function set(string $id, callable $factory): void {
//         self::$instances[$id] = $factory;
//     }

//     public static function get(string $id) {
//         if (!isset(self::$instances[$id])) {
//             throw new \RuntimeException("Service not found: {$id}");
//         }
//         $val = self::$instances[$id];
//         return is_callable($val) ? $val() : $val;
//     }
// }

// <?php

namespace TMT\CRM\Shared;

use TMT\CRM\Application\Services\CustomerService;
use TMT\CRM\Infrastructure\Persistence\WpdbCustomerRepository;

class Container
{
    private static array $instances = [];

    public static function set(string $id, callable $factory): void {
        self::$instances[$id] = $factory;
    }

    public static function get(string $id) {
        if (!isset(self::$instances[$id])) {
            throw new \RuntimeException("Service not found: {$id}");
        }
        $val = self::$instances[$id];
        return is_callable($val) ? $val() : $val;
    }
    // public static function customer_service(): CustomerService
    // {
    //     return self::$instances[CustomerService::class] ??= new CustomerService(
    //         new WpdbCustomerRepository($GLOBALS['wpdb'])
    //     );
    // }
}
