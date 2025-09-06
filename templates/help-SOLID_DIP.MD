
# 1.SQL chỉ nên nằm ở lớp implementation của Repository (Infrastructure)

Service không viết SQL — Service chứa nghiệp vụ (quy tắc, kiểm tra, orchestration) và gọi Repository interface để đọc/ghi DB.
Controller/Screen thì chỉ điều phối HTTP/UI và gọi Service.

```txt

    Quan hệ 3 lớp (DIP)
    
    Admin Screen / Controller
            ↓ (gọi)
    Application Service  (không SQL, chỉ nghiệp vụ)
            ↓ (qua interface)
    Domain\Repositories\*RepositoryInterface
            ↓ (bind trong Container)
    Infrastructure\Persistence\Wpdb*Repository (CÓ SQL)
            ↓
        MySQL
```

- Interface (Domain): định nghĩa hợp đồng (các hàm: insert, update, close, exists_active, …).

- Implementation (Infrastructure): thực thi hợp đồng bằng SQL (wpdb, $wpdb->prepare, transaction…).

- Service (Application): thực thi quy tắc: kiểm trùng, set/unset primary, đóng hiệu lực, đổi role…; không truy cập DB trực tiếp, chỉ gọi repository.