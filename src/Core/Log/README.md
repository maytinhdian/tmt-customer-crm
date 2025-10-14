# TMT CRM — Shared Logger + Core/Log Module

## Cấu trúc thư mục

```Folder
src/
    Shared/
        Logging/
            LoggerInterface.php
            Logger.php
            LogLevel.php
            ContextSanitizer.php
            Writers/
                FileLogWriter.php
    Core/
        Log/
            LogModule.php
            Application/
                DTO/
                    LogEntryDTO.php
            Infrastructure/
                Setup/
                    Installer.php
                Persistence/
                    WpdbLogRepository.php
            Presentation/
                Admin/
                    Screen/
                        LogScreen.php
    Domain/
        Repositories/
            LogRepositoryInterface.php
README.md

```

## Luồng chạy

1. **Plugin chính (`tmt-customer-crm.php`)**  
   Gọi bootstrap:

```php
   \TMT\CRM\Core\Log\LogModule::bootstrap();
```

2. **LogModule (file chính)**  
   - Chạy `Installer::maybe_install()` để tạo bảng `tmt_crm_logs`.  
   - Đăng ký repository: `LogRepositoryInterface -> WpdbLogRepository`.  
   - Bind `LoggerInterface` mặc định (ghi file).  
   - Đăng ký màn hình admin `LogScreen`.  

3. **Shared Logger**  
   - Namespace: `TMT\\CRM\\Shared\\Logging`.  
   - Các class: `LoggerInterface`, `Logger`, `LogLevel`, `ContextSanitizer`.  
   - Writer mặc định: `FileLogWriter` ghi vào `/uploads/tmt-crm/logs/app-YYYY-MM-DD.log`.  
   - Có thể thêm writer DB (thông qua repository).  

4. **Sử dụng**  
   - Lấy logger từ Container:

     ```php
     $logger = Container::get(LoggerInterface::class);
     $logger->info('Company created', ['company_id' => 123]);
     ```

   - Log sẽ được ghi vào file hoặc DB tuỳ cấu hình.  

5. **Xem log**  
   - WP Admin → TMT CRM → Logs  
   - Có bộ lọc theo `level`, `channel`, `search`.  

6. **Retention (khuyến nghị)**  
   - Dùng cron `tmt_crm_log_retention_daily` để xoá log file/DB quá hạn.  

---

## Ghi chú

- Shared Logger hoạt động kể cả khi Core/Log module tắt.  
- Core/Log chủ yếu cung cấp UI và Installer DB.  
- Tất cả tuân thủ chuẩn: class PascalCase, DTO đuôi `DTO`, Repository Interface trong `TMT\\CRM\\Domain\\Repositories`.  
