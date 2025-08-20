<?php
// src/Application/Services/payment-service.php
namespace TMT\CRM\Application\Services;

use TMT\CRM\Application\DTO\PaymentDTO;
use TMT\CRM\Domain\Repositories\PaymentRepositoryInterface as PayRepo;
use TMT\CRM\Domain\Repositories\InvoiceRepositoryInterface as InvRepo;
use TMT\CRM\Shared\Status;

final class PaymentService {
    public function __construct(private PayRepo $payments, private InvRepo $invoices) {}

    /**
     * Ghi nhận thanh toán: tạo payment record, cộng dồn paid cho invoice và cập nhật trạng thái.
     */
    public function add(PaymentDTO $dto): bool {
        $invoice = $this->invoices->find_by_id($dto->invoice_id);
        if (!$invoice) return false;

        // 1) lưu payment
        $this->payments->create($dto->invoice_id, $dto->amount, $dto->note);

        // 2) cập nhật invoice
        $invoice->paid += $dto->amount;
        if ($invoice->paid <= 0) {
            $invoice->status = Status::INVOICE_UNPAID;
        } elseif ($invoice->paid < $invoice->total) {
            $invoice->status = Status::INVOICE_PARTIAL;
        } else {
            $invoice->status = Status::INVOICE_PAID;
        }
        return $this->invoices->update($invoice);
    }

    public function list_by_invoice(int $invoice_id): array {
        return $this->payments->find_by_invoice($invoice_id);
    }
}