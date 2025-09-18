<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Capabilities\Infrastructure\Persistence;

use TMT\CRM\Domain\Repositories\CapabilitiesRepositoryInterface;
use TMT\CRM\Core\Capabilities\Domain\Capability;

final class WpOptionsCapabilitiesRepository implements CapabilitiesRepositoryInterface
{
    private const OPTION_KEY = 'tmt_crm_capabilities_matrix_v1';

    public function get_matrix(): array
    {
        $matrix = get_option(self::OPTION_KEY, []);
        if (!is_array($matrix)) {
            $matrix = [];
        }

        // Nếu chưa có, tạo mặc định: administrator có tất cả
        if (empty($matrix['administrator'])) {
            $matrix['administrator'] = Capability::all();
        }
        return $matrix;
    }

    public function set_matrix(array $matrix): void
    {
        // Lọc input: chỉ giữ capability hợp lệ
        $valid = array_flip(Capability::all());
        $clean = [];
        foreach ($matrix as $role => $caps) {
            $caps = array_values(array_unique(array_filter((array) $caps, fn($c) => isset($valid[$c]))));
            $clean[$role] = $caps;
        }
        update_option(self::OPTION_KEY, $clean);
    }
}
