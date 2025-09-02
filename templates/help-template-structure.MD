# 📂 Hướng dẫn tổ chức Template (Admin UI)

Dự án này sử dụng cấu trúc module cho các template hiển thị trong WP Admin.  
Mục tiêu: dễ maintain, tách biệt business logic và giao diện.

---

## 1. Cấu trúc thư mục

```txt
templates/
├── admin/
│   ├── company/
│   │   ├── form.php              # Form thêm/sửa công ty
│   │   ├── list.php              # Bảng danh sách công ty
│   │   └── partials/
│   │       ├── company-contacts-box.php   # Box người liên hệ
│   │       └── company-meta.php           # Partial metadata
│   │
│   ├── customer/
│   │   ├── form.php
│   │   ├── list.php
│   │   └── partials/...
│   │
│   ├── quote/
│   │   ├── form.php
│   │   ├── list.php
│   │   └── partials/
│   │       ├── quote-items.php
│   │       └── quote-status.php
│   │
│   └── layouts/
│       ├── header.php     # Layout header chung
│       ├── footer.php     # Layout footer chung
│       └── notices.php    # Hiển thị thông báo (success, error)
│
└── frontend/
    └── (nếu có giao diện public)
```

## 2. Giúp đỡ thêm

- layouts/ = layout chung cho admin.

- partials/ = mảnh nhỏ có thể include nhiều nơi.

- company/ , customer/ , quote/ = module riêng.

## 3. Quy tắc viết template

- Không query DB trực tiếp trong file template.

- 👉 Repository/Service xử lý xong → truyền data xuống.

- Trong partials/, chỉ nhận $vars và hiển thị HTML.

- Đặt tên file gắn với module:

- company-contacts-box.php → box người liên hệ trong màn hình Company.

- quote-items.php → list sản phẩm trong báo giá.
