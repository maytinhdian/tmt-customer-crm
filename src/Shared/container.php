<?php

namespace TMT\CRM\Shared;

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
 
}
