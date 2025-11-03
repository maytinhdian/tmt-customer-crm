<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\License\Application\Validation;

use TMT\CRM\Core\Validation\BaseValidator;
use TMT\CRM\Core\Validation\ValidationResult;
use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Domain\Repositories\CredentialRepositoryInterface;
use TMT\CRM\Modules\License\Application\Services\CryptoService;

final class LicenseValidator extends BaseValidator
{
    public function __construct(
        private ?CredentialRepositoryInterface $repo = null,
        private ?CryptoService $crypto = null,
    ) {
        $this->repo   ??= Container::get(CredentialRepositoryInterface::class);
        $this->crypto ??= Container::get(CryptoService::class);
    }

    /** Chuẩn hoá nhẹ input */
    protected function normalize(array $input): array
    {
        $out = $input;
        $out['id']           = (int)($input['id'] ?? 0);
        $out['number']       = trim((string)($input['number'] ?? ''));
        $out['label']        = trim((string)($input['label'] ?? ''));
        $out['type']         = trim((string)($input['type'] ?? ''));
        $out['status']       = trim((string)($input['status'] ?? 'active'));
        $out['subject']      = in_array(($input['subject'] ?? ''), ['company','customer'], true) ? $input['subject'] : '';
        $out['company_id']   = (int)($input['company_id'] ?? 0);
        $out['customer_id']  = (int)($input['customer_id'] ?? 0);
        $out['expires_at']   = trim((string)($input['expires_at'] ?? ''));
        $out['secret_primary']   = (string)($input['secret_primary'] ?? '');
        $out['secret_secondary'] = (string)($input['secret_secondary'] ?? '');
        return $out;
    }

    /** Khai báo rule */
    protected function rules(array $d, ValidationResult $res): void
    {
        // 1) Bắt buộc chọn đúng 1 trong 2: company/customer
        $companySelected  = ($d['subject'] === 'company'  && $d['company_id']  > 0);
        $customerSelected = ($d['subject'] === 'customer' && $d['customer_id'] > 0);
        if (!$this->requireXor($companySelected, $customerSelected)) {
            $res->addError('subject', __('Bạn phải chọn đúng 1 đối tượng: Công ty hoặc Khách hàng.', 'tmt-crm'));
        }

        // 2) Label bắt buộc
        if (!$this->required($d['label'])) {
            $res->addError('label', __('Label không được để trống.', 'tmt-crm'));
        }

        // 3) Expires At (nếu nhập) phải đúng format
        if ($d['expires_at'] !== '' && !$this->isDateTime($d['expires_at'])) {
            $res->addError('expires_at', __('Sai định dạng thời gian. Dùng YYYY-MM-DD hoặc YYYY-MM-DD HH:MM:SS', 'tmt-crm'));
        }

        // 4) Unique Number (nếu có nhập tay)
        if ($d['number'] !== '' && method_exists($this->repo, 'existsNumber')) {
            $exists = $this->repo->existsNumber($d['number'], $d['id'] ?: null);
            if ($exists) {
                $res->addError('number', __('Số hiệu (Number) đã tồn tại.', 'tmt-crm'));
            }
        }

        // 5) Chống trùng Secret (nếu nhập mới)
        if ($d['secret_primary'] !== '') {
            $hmac = $this->hmac($d['secret_primary']);
            if ($this->repo->existsSecretHmac($hmac, $d['id'] ?: null)) {
                $res->addError('secret_primary', __('License/Key này đã tồn tại trong hệ thống.', 'tmt-crm'));
            }
        }
        if ($d['secret_secondary'] !== '') {
            $hmac2 = $this->hmac($d['secret_secondary']);
            if ($this->repo->existsSecretHmac($hmac2, $d['id'] ?: null, 'secondary')) {
                $res->addError('secret_secondary', __('Secret Secondary đã tồn tại trong hệ thống.', 'tmt-crm'));
            }
        }
    }

    private function hmac(string $plain): string
    {
        // Bạn có thể thay AUTH_SALT bằng key riêng của module trong Settings
        return hash_hmac('sha256', $plain, \defined('AUTH_SALT') ? \AUTH_SALT : 'tmt_crm_license_secret');
    }
}
