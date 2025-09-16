<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Quotation\Presentation\Admin\Controller;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Shared\Presentation\AdminNoticeService;
use TMT\CRM\Modules\Quotation\Presentation\Admin\Screen\QuoteScreen;

// Tuỳ bạn đang đặt DTO ở đâu, import đúng FQCN:
use TMT\CRM\Modules\Quotation\Application\DTO\QuoteDTO;
use TMT\CRM\Modules\Quotation\Application\DTO\QuoteItemDTO;

final class QuoteController
{
    public const ACTION_SAVE    = 'tmt_crm_quote_save';
    public const ACTION_TRASH   = 'tmt_crm_quote_trash';
    public const ACTION_RESTORE = 'tmt_crm_quote_restore';
    // (tuỳ chọn) public const ACTION_SEND = 'tmt_crm_quote_send';

    /** Đăng ký admin-post actions của module */
    public static function register(): void
    {
        add_action('admin_post_' . self::ACTION_SAVE,    [self::class, 'handle_save']);
        add_action('admin_post_' . self::ACTION_TRASH,   [self::class, 'handle_trash']);
        add_action('admin_post_' . self::ACTION_RESTORE, [self::class, 'handle_restore']);
        // add_action('admin_post_' . self::ACTION_SEND,    [self::class, 'handle_send']);
    }

    /** POST: tạo/cập nhật báo giá */
    public static function handle_save(): void
    {
        check_admin_referer(self::ACTION_SAVE);
        // TODO: kiểm tra capability cụ thể (vd: current_user_can('tmt_crm_quote_update'))

        /** @var \TMT\CRM\Application\Services\QuoteService $svc */
        $svc = Container::get('quote-service');

        // --- Lấy dữ liệu từ POST ---
        $id          = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
        $owner_id    = isset($_POST['owner_id']) ? (int) $_POST['owner_id'] : 0;
        $note        = isset($_POST['note']) ? wp_kses_post($_POST['note']) : '';

        // Items: giả định POST dưới dạng mảng items[0][sku|name|qty|unit_price|discount|tax_rate]
        $items = (array) ($_POST['items'] ?? []);

        // --- Map sang DTO ---
        $dto = new QuoteDTO();
        if ($id > 0) $dto->id = $id;
        $dto->customer_id = $customer_id > 0 ? $customer_id : null;
        $dto->owner_id    = $owner_id > 0 ? $owner_id : null;
        $dto->note        = $note;

        $dto->items = [];
        foreach ($items as $row) {
            $item = new QuoteItemDTO();
            $item->sku         = sanitize_text_field($row['sku'] ?? '');
            $item->name        = sanitize_text_field($row['name'] ?? '');
            $item->qty         = (float) ($row['qty'] ?? 0);
            $item->unit_price  = (float) ($row['unit_price'] ?? 0);
            $item->discount    = (float) ($row['discount'] ?? 0);
            $item->tax_rate    = (float) ($row['tax_rate'] ?? 0);
            $dto->items[] = $item;
        }

        // --- Lưu ---
        if ($dto->id) {
            $svc->update($dto);
            AdminNoticeService::success_for_screen(QuoteScreen::hook_suffix(), __('Đã cập nhật báo giá.', 'tmt-crm'));
        } else {
            $new_id = $svc->create($dto);
            AdminNoticeService::success_for_screen(QuoteScreen::hook_suffix(), __('Đã tạo báo giá.', 'tmt-crm'));
        }

        // Quay lại trang danh sách (hoặc form)
        wp_safe_redirect(
            wp_get_referer() ?: admin_url('admin.php?page=' . QuoteScreen::PAGE_SLUG)
        );
        exit;
    }

    /** GET: xoá mềm báo giá */
    public static function handle_trash(): void
    {
        check_admin_referer(self::ACTION_TRASH);
        // TODO: capability

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id > 0) {
            /** @var \TMT\CRM\Domain\Repositories\QuoteRepositoryInterface $repo */
            $repo = Container::get('quote-repository'); // hoặc dùng service nếu bạn quấn logic
            $repo->trash($id);
            AdminNoticeService::success_for_screen(QuoteScreen::hook_suffix(), __('Đã chuyển vào thùng rác.', 'tmt-crm'));
        }

        wp_safe_redirect(wp_get_referer() ?: admin_url('admin.php?page=' . QuoteScreen::PAGE_SLUG));
        exit;
    }

    /** GET: khôi phục báo giá */
    public static function handle_restore(): void
    {
        check_admin_referer(self::ACTION_RESTORE);
        // TODO: capability

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id > 0) {
            /** @var \TMT\CRM\Domain\Repositories\QuoteRepositoryInterface $repo */
            $repo = Container::get('quote-repository');
            $repo->restore($id);
            AdminNoticeService::success_for_screen(QuoteScreen::hook_suffix(), __('Đã khôi phục báo giá.', 'tmt-crm'));
        }

        wp_safe_redirect(wp_get_referer() ?: admin_url('admin.php?page=' . QuoteScreen::PAGE_SLUG));
        exit;
    }

    // /** (tuỳ chọn) POST: gửi báo giá cho khách */
    // public static function handle_send(): void
    // {
    //     check_admin_referer(self::ACTION_SEND);
    //     // TODO: capability + gọi service gửi mail/PDF
    // }
}
