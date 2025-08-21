<?php

namespace TMT\CRM\Application\Services;


use TMT\CRM\Application\DTO\CompanyDTO;
use TMT\CRM\Domain\Repositories\CompanyRepositoryInterface;
use WP_Error;


/**
 * Nơi đặt rule nghiệp vụ + validate + chống trùng
 */
class CompanyService
{
    public function __construct(private CompanyRepositoryInterface $repo) {}


    /** Tạo mới, trả về ID hoặc WP_Error */
    public function create(CompanyDto $dto): int|WP_Error
    {
        $e = $this->validate($dto);
        if (is_wp_error($e)) return $e;


        $dup = $this->repo->find_duplicate(null, $dto->name, $dto->taxCode, $dto->phone, $dto->email);
        if ($dup) {
            return new WP_Error('duplicate_company', 'Trùng MST/điện thoại/email hoặc tên công ty.');
        }
        return $this->repo->insert($dto);
    }


    /** Cập nhật, trả về bool hoặc WP_Error */
    public function update(CompanyDto $dto): bool|WP_Error
    {
        if (!$dto->id) return new WP_Error('invalid_id', 'Thiếu ID công ty.');
        $e = $this->validate($dto);
        if (is_wp_error($e)) return $e;


        $dup = $this->repo->find_duplicate($dto->id, $dto->name, $dto->taxCode, $dto->phone, $dto->email);
        if ($dup) {
            return new WP_Error('duplicate_company', 'Trùng MST/điện thoại/email hoặc tên công ty.');
        }
        return $this->repo->update($dto);
    }


    /**
     * Validate dữ liệu công ty.
     *
     * @param CompanyDTO $dto
     * @return true|\WP_Error  Trả về true nếu hợp lệ, hoặc \WP_Error khi lỗi.
     */
    private function validate(CompanyDTO $dto)
    {
        if ($dto->name === '') return new WP_Error('invalid_name', 'Tên công ty bắt buộc.');
        if ($dto->email && !is_email($dto->email)) return new WP_Error('invalid_email', 'Email không hợp lệ.');
        if ($dto->website && !filter_var($dto->website, FILTER_VALIDATE_URL)) return new WP_Error('invalid_website', 'Website không hợp lệ.');
        return true;
    }
}
