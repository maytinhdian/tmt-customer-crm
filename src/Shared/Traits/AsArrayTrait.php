<?php

namespace TMT\CRM\Shared\Traits;

/**
 * Trait hỗ trợ chuyển object -> array
 * - Tự động lấy tất cả property public
 * - Có thể chọn camelCase hoặc snake_case
 */
trait AsArrayTrait
{
    public function to_array(bool $snake_case = true): array
    {
        $vars = get_object_vars($this);

        if (!$snake_case) {
            return $vars;
        }

        $out = [];
        foreach ($vars as $k => $v) {
            $snake = strtolower(preg_replace('/[A-Z]/', '_$0', $k));
            $out[$snake] = $v;
        }
        return $out;
    }
}
