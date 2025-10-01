# Core/ExportImport (MVP) — TMT CRM

> Module xuất/nhập dữ liệu CSV cho các entity chính (Company, Customer, Company Contact).  
> **Bootstrap (file chính):** `ExportImportModule.php`

## Mục tiêu
- **Export CSV** theo entity + bộ lọc đơn giản.
- **Import CSV** theo 2 bước: Upload → Map/Commit.
- Lưu lịch sử job Export/Import, và cấu hình Mapping Profiles (mở rộng sau MVP).

## Cấu trúc nhanh
```
src/Core/ExportImport/
├── ExportImportModule.php        # bootstrap (file chính)
├── Application/
│   ├── DTO/ (ExportJobDTO, ImportJobDTO, MappingRuleDTO)
│   └── Services/ (ExportService, ImportService, MappingService, ValidationService)
├── Infrastructure/
│   ├── IO/ (CsvReader, CsvWriter)
│   ├── Persistence/ (WpdbExportJobRepository, WpdbImportJobRepository, WpdbMappingRuleRepository)
│   └── Setup/ (Installer, Migrator)
└── Presentation/Admin/
    ├── Screen/ (ExportImportScreen)
    └── Controller/ (ExportImportController)
templates/admin/core/export-import/
└── index.php  # view chính (tab Export/Import)
```

## Hướng dẫn nhanh
1. **Khai báo boot trong plugin chính** `tmt-customer-crm.php`:
   ```php
   use TMT\CRM\Core\ExportImport\ExportImportModule;
   ExportImportModule::boot(\TMT\CRM\Shared\Container\Container::instance());
   ```

2. **Tạo bảng**: Module tự cài qua `Installer::maybe_install()` & `Migrator` (dbDelta).

3. **Xem màn hình**: Admin → TMT CRM → *Export / Import*.

4. **Export CSV**:
   - Chọn *Entity*, nhập danh sách cột (tùy chọn) hoặc để trống để dùng mặc định.
   - Bộ lọc demo theo `created_from`/`created_to` (có thể mở rộng).
   - **Kết quả**: file CSV lưu tại `wp-content/uploads/tmt-crm-exports/`.

5. **Import CSV**:
   - Bước 1: Upload file `.csv`, chọn có header hay không.
   - Bước 2: Map cột `source_col:target_field` (mỗi dòng 1 cặp), sau đó Commit.
   - Service gọi `upsert_from_array()` tương ứng entity (cần có trong Service entity).

## Lưu ý PSR-4 & Quy ước dự án
- Class = FileName.php, **PascalCase**, DTO hậu tố **DTO**.
- Hàm có thể `snake_case`.
- Namespace repo domain: `TMT\CRM\Domain\Repositories\`.
- Khi nói tới *bootstrap* ở đây nghĩa là **file khởi động chính (file chính)** của module.
- Sau khi thêm/sửa: `composer dump-autoload -o`.

## TODO mở rộng (sau MVP)
- Hỗ trợ XLSX.
- Hàng đợi (Queue) cho job dài.
- Lưu & chọn Mapping Profiles theo entity.
- Trang chi tiết Job + log lỗi từng dòng.
- Phân quyền chi tiết theo Capability.
