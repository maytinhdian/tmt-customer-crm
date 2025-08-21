# === Tree tổng quan (plugin: tmt-customer-crm) ===
# Gợi ý đặt trong wp-content/plugins/tmt-customer-crm

.
├─ tmt-customer-crm.php                # Bootstrap plugin chính
├─ composer.json                       # PSR-4 autoload cho src/
├─ package.json                        # build assets (nếu cần)
├─ phpcs.xml                           # coding standard
├─ README.md
├─ .gitignore
├─ assets/
│  ├─ css/
│  │  └─ admin.css
│  ├─ js/
│  │  ├─ admin.js
│  │  └─ api-client.js
│  └─ scss/
│     └─ admin.scss
├─ languages/
│  └─ tmt-customer-crm-vi_VN.po
├─ templates/                          # View cho Admin/Page/Email
│  ├─ admin/
│  │  ├─ customers-list.php
│  │  ├─ customer-edit.php
│  │  └─ dashboard.php
│  ├─ emails/
│  │  └─ reminder-debt.php
│  └─ public/
│     └─ shortcode-customer-profile.php
├─ src/                                # Code OOP chia theo tầng
│  ├─ Application/                     # Services (use-case level)
│  │  ├─ Services/
│  │  │  ├─ customer-service.php
│  │  │  ├─ company-service.php
│  │  │  ├─ quotation-service.php
│  │  │  ├─ invoice-service.php
│  │  │  └─ debt-service.php
│  │  └─ DTO/
│  │     ├─ customer-dto.php
│  │     ├─ company-dto.php
│  │     ├─ quotation-dto.php
│  │     ├─ invoice-dto.php
│  │     └─ payment-dto.php
│  ├─ Domain/                          # Entities + Interfaces
│  │  ├─ Entities/
│  │  │  ├─ customer.php
│  │  │  ├─ company.php
│  │  │  ├─ quotation.php
│  │  │  ├─ invoice.php
│  │  │  └─ debt.php
│  │  └─ Repositories/
│  │     ├─ customer-repository-interface.php
│  │     ├─ company-repository-interface.php
│  │     ├─ quotation-repository-interface.php
│  │     ├─ invoice-repository-interface.php
│  │     └─ debt-repository-interface.php
│  ├─ Infrastructure/                   # Triển khai cụ thể (wpdb)
│  │  ├─ Persistence/
│  │  │  ├─ wpdb-customer-repository.php
│  │  │  ├─ wpdb-company-repository.php
│  │  │  ├─ wpdb-quotation-repository.php
│  │  │  ├─ wpdb-invoice-repository.php
│  │  │  └─ wpdb-debt-repository.php
│  │  ├─ Migrations/
│  │  │  └─ installer.php              # Tạo/Update bảng
│  │  └─ Integration/
│  │     ├─ woocommerce-sync.php
│  │     └─ email-sender.php
│  ├─ Presentation/                     # Giao diện + REST + Admin
│  │  ├─ Admin/
│  │  │  ├─ menu.php
│  │  │  ├─ customers-screen.php
│  │  │  ├─ customer-edit-screen.php
│  │  │  └─ dashboard-screen.php
│  │  ├─ REST/
│  │  │  ├─ routes.php
│  │  │  └─ customer-controller.php
│  │  └─ Shortcodes/
│  │     └─ customer-profile-shortcode.php
│  └─ Shared/
│     ├─ container.php                 # Service Container nhỏ gọn
│     ├─ hooks.php                     # Đăng ký action/filter
│     └─ utils.php
└─ tests/
   ├─ unit/
   └─ integration/

# Coding Guidelines – TMT CRM

> Chuẩn hoá cho plugin **TMT Customer CRM** trên WordPress (OOP + PSR‑4).

## 1) Mục đích

* Tránh lỗi PSR‑4/Composer *Skipping* khi autoload.
* Giữ code thống nhất, dễ bảo trì, mở rộng.

## 2) Autoload & PSR‑4

* Namespace gốc: `TMT\CRM\` → map với thư mục `src/`.
* Mỗi segment namespace = một thư mục con (phân biệt HOA/thường):

  * `TMT\CRM\Application\DTO\CompanyDto` ↔ `src/Application/DTO/CompanyDto.php`.
  * `TMT\CRM\Infrastructure\Integration\WooCommerceSync` ↔ `src/Infrastructure/Integration/WooCommerceSync.php`.
* **ClassName = FileName.php** (đúng **case-sensitive**).
* Không dùng ký tự `_` hoặc `-` trong tên **class/file**.
* Sau khi thêm/sửa file: chạy `composer dump-autoload -o`.

### composer.json (chuẩn)

```json
{
  "autoload": {
    "psr-4": {
      "TMT\\CRM\\": "src/"
    }
  }
}
```

## 3) Quy tắc đặt tên

* **DTO**: `SomethingDto`
  Ví dụ: `CompanyDto`, `CustomerDto`.
* **Service**: `SomethingService`
  Ví dụ: `InvoiceService`, `CustomerService`.
* **Entity**: Danh từ số ít, PascalCase
  Ví dụ: `Company`, `Customer`, `Invoice`.
* **Repository**:
  Interface → `SomethingRepositoryInterface`
  Implementation → `DbSomethingRepository` hoặc `WpdbSomethingRepository`
* **Controller**: `SomethingController`
* **Khác**: `Hooks`, `Status`, `Container`, `Routes`…
* **Thư mục**: đúng chính tả, PascalCase cho từ viết tắt (ví dụ: `Integration`, không phải `Intergration`).

## 4) Quy tắc ghi chú **địa chỉ file** khi thêm/sửa code

* Mọi snippet phải chú thích **đường dẫn file** ngay phía trên đoạn code.
* Ký hiệu:

  * **📄 Tạo file mới:** `src/Path/FileName.php`
  * **✏️ Sửa file:** `src/Path/FileName.php` *(thêm dòng … / thay thế block … nếu cần)*

**Ví dụ**

📄 **Tạo file mới**: `src/Application/DTO/CustomerDto.php`

```php
<?php
namespace TMT\CRM\Application\DTO;

final class CustomerDto
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone
    ) {}
}
```

✏️ **Sửa file**: `src/Application/Services/CustomerService.php`

```php
// thêm phương thức tìm theo email
public function findByEmail(string $email): ?CustomerDto
{
    // ...
}
```

## 5) Phong cách code (OOP)

* **Visibility** rõ ràng: `public/protected/private`.
* **final** cho class/value object khi hợp lý; **interface** cho hợp đồng.
* **Type-hint** đầy đủ + **return type**.
* **DTO**: immutable (ưu tiên property promotion), không chứa logic nghiệp vụ.
* **Service**: chứa use-case; không truy cập DB trực tiếp.
* **Repository**: chịu trách nhiệm truy xuất dữ liệu (WPDB/Meta/WC...).
* **Controller**: mỏng, gọi Service/Repository; không nhúng SQL.

## 6) Hook timing & Bootstrap

* File plugin chính **require** `vendor/autoload.php` trước.
* Khởi động qua `add_action('plugins_loaded', ...)`.
* Tránh gọi WooCommerce API trước `plugins_loaded`.
* `register_activation_hook` chỉ kiểm tra yêu cầu tối thiểu + thiết lập nhẹ.

## 7) Quy trình thêm file mới

1. Tạo class với **tên chuẩn** (PascalCase).
2. Đặt vào **đúng thư mục** theo namespace.
3. Chạy `composer dump-autoload -o`.
4. Viết test/usage tối thiểu hoặc admin notice kiểm tra nạp hook.
5. Commit theo template (dưới đây).

## 8) Checklist trước commit

* [ ] Class = FileName.php (đúng hoa/thường).
* [ ] Không có `-` hoặc `_` trong tên class/file.
* [ ] Đường dẫn thư mục khớp namespace (bao gồm chính tả `Integration`, `Persistence`, …).
* [ ] `composer dump-autoload -o` chạy **không còn** “does not comply / Skipping”.

## 9) Mẫu commit message

```
feat(dto): add CustomerDto and CompanyDto

- Create src/Application/DTO/CustomerDto.php
- Create src/Application/DTO/CompanyDto.php
- Wire up Services to use new DTOs
```

```
refactor(repository): rename WPDB_* to Wpdb* (PSR-4 compliance)

- Rename src/Infrastructure/Persistence/wpdb-customer-repository.php -> WpdbCustomerRepository.php
- Update namespace imports and type hints
- Run composer dump-autoload -o
```

---

**Lưu ý:** nếu rename hàng loạt, ưu tiên `CompanyDTO -> CompanyDto`, `WPDB_* -> Wpdb*` để thống nhất PascalCase.
