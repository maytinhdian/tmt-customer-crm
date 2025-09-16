# Core/Records Module (Skeleton)

Lưu ý nhanh:
- Class = PascalCase; hàm có thể snake_case.
- DTO đuôi **DTO**.
- CoreRecordsModule.php có ghi chú **(bootstrap - file chính)**.
- Bạn có thể cần chạy `composer dump-autoload -o` sau khi thêm file.

## PSR-4 & Repository Interfaces
Hai interface `AuditLogRepositoryInterface.php` và `ArchiveRepositoryInterface.php` đang đặt trong:

`src/Modules/Core/Records/Domain/Repositories/`

Nếu dự án của bạn **bắt buộc** các interface domain đặt tại `TMT\CRM\Domain\Repositories\` thì hãy DI CHUYỂN 2 file này sang:

`src/Domain/Repositories/`

và đổi namespace tương ứng. (Hoặc cấu hình thêm PSR-4 mapping khác nếu muốn giữ trong module.)
