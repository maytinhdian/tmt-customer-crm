# Core/Numbering Module

## Mục đích
Module **Numbering** chịu trách nhiệm quản lý việc đánh số tự động cho các bản ghi (Company, Customer, Quote, Invoice, ...).  
Đảm bảo mỗi record có một mã duy nhất, theo quy tắc cấu hình được trong Settings.

---

## Chức năng chính
1. **Sinh số tự động**
   - Mỗi entity (ví dụ: Company, Customer, Quote) sẽ có một quy tắc numbering riêng.
   - Format có thể chứa các placeholder:  
     - `{prefix}`: tiền tố (ví dụ: CUS, COM, QUO).  
     - `{yyyy}`: năm 4 chữ số.  
     - `{yy}`: năm 2 chữ số.  
     - `{mm}`: tháng.  
     - `{dd}`: ngày.  
     - `{seq}`: số thứ tự tăng dần.

   Ví dụ: `CUS-{yyyy}-{seq}` → `CUS-2025-0001`

2. **Reset sequence**
   - Có thể reset theo năm, tháng, hoặc không reset.  
   - Ví dụ: `QUO-{yy}{mm}-{seq}` → `QUO-2509-0001` (reset mỗi tháng).

3. **Tùy biến theo module**
   - Mỗi module khi tạo record mới sẽ gọi sang NumberingService để xin số tiếp theo.  
   - Module không cần tự quản lý sequence.

4. **Cấu hình trong Settings**
   - Admin có thể thay đổi format cho từng loại record trong trang Settings.  
   - Ví dụ:  
     - Company code: `COM-{yyyy}-{seq}`  
     - Customer code: `CUS-{yyyy}-{seq}`  
     - Quote code: `QUO-{yy}{mm}-{seq}`

---

## Cấu trúc
```
src/Core/Numbering/
├── NumberingService.php
├── NumberingRegistry.php
├── NumberingFormatter.php
├── NumberingSequenceRepositoryInterface.php
├── WpdbNumberingSequenceRepository.php
└── bootstrap.php   # file chính
```

- **NumberingService**: Cung cấp API chính `next_number($entity_type)` để module gọi.  
- **NumberingRegistry**: Đăng ký các loại entity cần numbering (company, customer, quote, …).  
- **NumberingFormatter**: Render số theo format + sequence + placeholder.  
- **Repository**: Lưu sequence hiện tại trong DB (theo entity, theo năm/tháng nếu có reset).  

---

## Flow hoạt động
1. Module (vd: CompanyService) gọi `NumberingService::next_number('company')`.  
2. NumberingService kiểm tra trong NumberingRegistry → lấy format + rule reset.  
3. Repository lấy sequence hiện tại từ DB → tăng lên.  
4. NumberingFormatter thay thế placeholder → sinh ra mã hoàn chỉnh.  
5. Trả kết quả về cho module → module gán vào record khi insert.

---

## Ví dụ sử dụng
```php
use TMT\CRM\Core\Numbering\NumberingService;

$code = NumberingService::next_number('company'); 
// Kết quả: COM-2025-0001

$quoteCode = NumberingService::next_number('quote');
// Kết quả: QUO-2509-0001
```

---

## Kế hoạch mở rộng
- Cho phép định nghĩa nhiều sequence khác nhau trong cùng một entity (vd: Invoice nội địa, Invoice xuất khẩu).  
- Hỗ trợ reset theo quý (Q1, Q2…).  
- Cho phép xem lại log sequence đã cấp.

```
sequenceDiagram
    participant Module as CompanyService (hoặc QuoteService)
    participant Service as NumberingService
    participant Registry as NumberingRegistry
    participant Repo as NumberingSequenceRepository
    participant Formatter as NumberingFormatter

    Module->>Service: next_number("company")
    Service->>Registry: lấy format + rule reset
    Service->>Repo: lấy sequence hiện tại
    Repo-->>Service: trả về sequence
    Service->>Formatter: thay thế placeholder
    Formatter-->>Service: mã hoàn chỉnh (COM-2025-0001)
    Service-->>Module: trả mã về
```