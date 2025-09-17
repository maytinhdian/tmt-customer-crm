<?php
// src/Modules/Company/Application/Snapshot/CompanySnapshotBuilder.php
declare(strict_types=1);

namespace TMT\CRM\Modules\Company\Application\Snapshot;

final class CompanySnapshotBuilder
{
    public static function build(int $company_id): array
    {
        // Tự lấy dữ liệu cần thiết
        $company   = ['id' => $company_id /*, ...*/];
        $relations = ['contacts' => [/* ids */]];
        $attachments = null;

        return [
            'snapshot'    => $company,
            'relations'   => $relations,
            'attachments' => $attachments,
        ];
    }
}


// $trash = \TMT\CRM\Shared\Container::get('core.records.trash_service');
// $trash->purge('Company', $company_id, get_current_user_id(), 
//     fn(int $id) => \TMT\CRM\Modules\Company\Application\Snapshot\CompanySnapshotBuilder::build($id),
//     'Yêu cầu dọn dữ liệu'
// );