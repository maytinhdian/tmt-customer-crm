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

