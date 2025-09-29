# Module License – Hướng dẫn sử dụng & Flow

Module **License** cho phép quản lý các loại license/credentials:  

- Key bản quyền (Windows, Office, Antivirus, …)  
- Email account (Office 365 Family, Gmail business, …)  
- SaaS account, API token, WiFi account, …  

## 1. Chức năng chính

- **CRUD Credentials**: Thêm/sửa/xóa các license.  
- **Seat Allocations**: Chia quota license cho công ty, khách hàng, contact hoặc email.  
- **Activations**: Theo dõi thiết bị/máy tính đã sử dụng license.  
- **Deliveries**: Lịch sử bàn giao key cho khách hàng.  
- **Reminder Expiring**: Nhắc khi license sắp hết hạn (cron + admin notice + màn Expiring Soon).  
- **Policy + Reveal Secret**: Mask secret, chỉ người có quyền mới được phép reveal.  

---

## 2. Flow tổng quan

1. **Tạo Credential (General tab)**  
   - Nhập `number` (mã key hoặc tên tài khoản), `label`, loại (License Key, Email Account…), ngày hết hạn, seats total, …  
   - Secret (key/password) được lưu mã hóa.  

2. **Chia ghế (Allocations tab)**  
   - Ví dụ: Key ESET 3PC → chia cho `customer #10` (2 seats) và `customer #15` (1 seat).  
   - Mỗi allocation có trạng thái (pending, active, revoked).  

3. **Kích hoạt (Activations tab)**  
   - Khi một máy sử dụng key → ghi lại activation: hostname, fingerprint, user email, location, …  
   - Có thể deactivate hoặc transfer sang allocation khác.  

4. **Bàn giao (Deliveries tab)**  
   - Log khi bạn gửi key cho khách: qua email, zalo, file, hoặc printed.  
   - Lưu cả thời gian, note.  

5. **Nhắc sắp hết hạn (Reminder)**  
   - Cron daily check license sắp hết hạn trong X ngày (configurable).  
   - Admin notice hiển thị số lượng + link tới màn “Expiring Soon”.  
   - Có thể filter theo số ngày để xem danh sách.  

6. **Reveal secret (chính sách bảo mật)**  
   - Trường secret hiển thị `********`.  
   - Nút **Reveal** chỉ hiện cho user có quyền `tmt_license_reveal_secret`.  
   - Khi nhấn → AJAX kiểm tra quyền → giải mã secret → log hành động.  

---

## 3. Ví dụ thực tế

### Trường hợp: License Office 365 Family**

1. Admin mua 1 license Office 365 Family, add vào email `main@company.com`.  
2. Tạo credential:
   - Number: `main@company.com`  
   - Type: Email Account  
   - Label: Office 365 Family – Main Account  
   - Seats total: 6  
   - Sharing mode: `family_share`  
   - Expires at: `2025-12-31`  

3. Chia Allocations:  
   - Allocation 1 → `customer_id = 101` (ghế cho khách hàng A, quota 3).  
   - Allocation 2 → `customer_id = 102` (ghế cho khách hàng B, quota 2).  
   - Allocation 3 → `customer_id = 103` (ghế cho khách hàng C, quota 1).  

4. Activations:  
   - Máy tính của nhân viên A (hostname: LAPTOP-A, email: <a@client.com>).  
   - Máy tính của nhân viên B (hostname: PC-B, email: <b@client.com>).  

5. Deliveries:  
   - Gửi thông tin tài khoản `main@company.com` cho `customer #101` qua email.  
   - Log lại thời gian và note “Đã gửi kèm hướng dẫn cài đặt”.  

6. Reminder:  
   - Cron check → tháng 12/2025 hiện admin notice: “Có 1 license sẽ hết hạn trong 30 ngày tới”.  
   - Admin click → màn Expiring Soon hiển thị chi tiết.  

---

## 4. Quyền hạn (Policy)

- `tmt_license_manage`: CRUD credentials.  
- `tmt_license_reveal_secret`: Reveal secret.  
- `tmt_license_delete`: Xóa credentials.  

Nếu user không có quyền → sẽ không thấy hoặc không thực hiện được hành động.  

---

## 5. Lưu ý triển khai

- Mọi secret lưu trong DB **đã mã hóa**.  
- Reveal luôn được **ghi log** để audit sau này (sẽ tích hợp Core/AuditLog).  
- Các sub-tab (Allocations, Activations, Deliveries) chỉ hiện khi credential đã được tạo (có ID).  
- Cron chạy daily lúc 2:00 sáng, có thể chạy thủ công bằng WP-CLI:  
