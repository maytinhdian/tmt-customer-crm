# Core/Event — Kiến trúc & Flow (bản đã bàn trước)

> Tài liệu này chuẩn hoá kiến trúc, luồng chạy và cách sử dụng **Core/Event** cho TMT CRM.

---

## Mục lục

- [Mục tiêu & vai trò](#mục-tiêu--vai-trò-của-coreevent)
- [Các khối chính](#các-khối-chính-files--responsibilities)
  - [Contracts (Domain\\Contracts)](#21-contracts-domaincontracts)
  - [Value Objects](#22-value-objects)
  - [Events (Domain\\Events)](#23-events-domainevents)
  - [Application\\Services](#24-applicationservices)
  - [Infrastructure\\Buses](#25-infrastructurebuses)
  - [Infrastructure\\Providers](#26-infrastructureproviders)
  - [Persistence (tuỳ chọn)](#27-persistence-tuỳ-chọn)
- [Dòng chảy (runtime flow)](#3-dòng-chảy-runtime-flow)
- [API sử dụng nhanh](#4-api-sử-dụng-nhanh-developer-cheat-sheet)
  - [Định nghĩa subscriber](#41-định-nghĩa-subscriber)
  - [Phát sự kiện trong Service](#42-phát-sự-kiện-trong-service-không-phát-ở-controller)
  - [Nạp subscriber ở ServiceProvider](#43-nạp-subscriber-ở-serviceprovider)
- [Quy ước đặt tên & payload](#5-quy-ước-đặt-tên--payload)
- [Tích hợp với Notifications & Log](#6-tích-hợp-với-notifications--log-phối-hợp-3-phần)
- [Best practices & lưu ý](#7-best-practices--lưu-ý-quan-trọng)
- [Catalog event mẫu](#8-kiểu-dùng-phổ-biến-catalog)
- [Debug nhanh](#9-debug--xử-lý-sự-cố-nhanh)
- [Tích hợp vào plugin](#10-tóm-tắt-ngắn-gọn-cách-bậttích-hợp-trong-plugin)

---

## 1) Mục tiêu & vai trò của Core/Event

- **Tách rời (decouple)** các module: Company tạo xong thì chỉ “phát sự kiện”; ai quan tâm (Notifications, Log, Numbering, …) tự subscribe và xử lý.  
- **Chuẩn hóa dữ liệu sự kiện**: mọi event đều có `name`, `payload`, `metadata` (id, occurred_at, actor_id, correlation_id, tenant).  
- **Linh hoạt lưu vết**: có thể phát “in-memory” cho hiệu năng, hoặc “storing” để **ghi DB** (audit, retry, DLQ…).  
- **An toàn & testable**: event là object nhỏ gọn, subscriber là lớp độc lập, dễ unit test.

## 2) Các khối chính (files & responsibilities)

### 2.1. Contracts (Domain\\Contracts)

- `EventInterface`: chuẩn tối thiểu của 1 event  
  - `name(): string` — tên chuẩn (ví dụ: `company.created`)  
  - `payload(): object` — dữ liệu domain (DTO hoặc VO)  
  - `metadata(): EventMetadata` — thông tin phát sinh.
- `EventSubscriberInterface`: chuẩn cho subscriber  
  - `subscribed_events(): array` — map `event_name => callable`.
- `EventBusInterface`: cổng phát sự kiện  
  - `publish(EventInterface $event): void`.

### 2.2. Value Objects

- `EventMetadata`  
  - `event_id: string` (UUID)  
  - `occurred_at: DateTimeImmutable`  
  - `actor_id?: int`, `correlation_id?: string`, `tenant?: string`.  
  - Dùng để truy vết xuyên suốt (1 request ⇒ 1 correlation_id).

### 2.3. Events (Domain\\Events)

- `DefaultEvent implements EventInterface`  
  - Dùng khi muốn phát event nhanh, không cần tạo class event riêng.  
  - `name` là chuỗi; `payload` là object (DTO); `metadata` là `EventMetadata`.

### 2.4. Application\\Services

- `EventFactory` (nếu có): tiện ích tạo `DefaultEvent` có đủ metadata, auto-UUID, auto-time.  
- `EventDispatcher` (tuỳ kiến trúc): đôi khi ánh xạ event vào bus, hoặc gom logic dispatch.

### 2.5. Infrastructure\\Buses

- `InMemoryEventBus implements EventBusInterface`  
  - Chạy vòng lặp gọi các subscriber trong RAM (nhanh, không lưu DB).
- `StoringEventBus implements EventBusInterface`  
  - Vừa gọi subscriber, vừa **ghi DB** (bảng `events` hoặc `event_logs`) để audit / retry.  
  - Lưu ý: `publish()` **nhận `EventInterface`**, **không phải string** → lỗi trước đây là do truyền sai kiểu.

### 2.6. Infrastructure\\Providers

- `SubscriberLoader`  
  - Nhận mảng class subscriber, resolve từ Container, đọc `subscribed_events()`, và đăng ký với Bus.  
  - Action: Thường sẽ chạy ở `ServiceProvider::register()` của module.

### 2.7. Persistence (tuỳ chọn)

- `EventRepositoryInterface` + `WpdbEventRepository`  
  - Lưu/lấy event log, phục vụ audit/troubleshoot/retry.

## 3) Dòng chảy (runtime flow)

### Flow cơ bản (happy path)

1) **Service** (ví dụ `CompanyService::create()`) thực hiện nghiệp vụ → thành công.  
2) Service **tạo event** (dùng `EventFactory` hoặc `new DefaultEvent(...)`).  
3) Gọi `EventBus->publish($event)`.  
4) Bus **nhìn vào danh bạ subscriber** (nạp bởi `SubscriberLoader`).  
5) Bus lần lượt gọi các callback tương ứng với `event.name`.  
6) **Subscriber** chạy xử lý (ví dụ: tạo Notification, viết Log, cập nhật Numbering…).  
7) Nếu là `StoringEventBus`: đồng thời **ghi DB** (event log) trước/hoặc sau khi dispatch tuỳ chính sách.

### Flow nạp subscriber (boot time)

- Tại bootstrap của module (ví dụ `Core/Events` hoặc plugin main), gọi:  
  - Đăng ký Bus vào Container.  
  - Chạy `SubscriberLoader::load([...class list...])` để map `event_name → [callbacks]`.

## 4) API sử dụng nhanh (developer cheat-sheet)

### 4.1. Định nghĩa subscriber

```php
<?php

final class CompanyAuditSubscriber implements EventSubscriberInterface
{
    public static function subscribed_events(): array
    {
        return [
            'company.created' => [self::class, 'on_company_created'],
        ];
    }

    public function __construct(
        private \TMT\CRM\Core\Log\Application\Services\LogWriter $logger
    ) {}

    public function on_company_created(EventInterface $event): void
    {
        $data = $event->payload();   // DTO
        $meta = $event->metadata();  // EventMetadata

        $this->logger->info('Company created', [
            'company_id'   => $data->id ?? null,
            'actor_id'     => $meta->actor_id,
            'correlation'  => $meta->correlation_id,
            'occurred_at'  => $meta->occurred_at->format('c'),
        ]);
    }
}
```

### 4.2. Phát sự kiện trong Service (không phát ở Controller)

```php
<?php

public function create(CompanyDTO $input, int $actor_id): CompanyDTO
{
    $company = $this->repo->create($input);

    $event = new DefaultEvent(
        'company.created',
        $company, // payload là DTO
        new EventMetadata(
            event_id: \wp_generate_uuid4(), // hoặc helper UUID
            occurred_at: new \DateTimeImmutable('now'),
            actor_id: $actor_id,
            correlation_id: $this->context->correlation_id() // nếu có
        )
    );

    $this->event_bus->publish($event);
    return $company;
}
```

### 4.3. Nạp subscriber ở ServiceProvider

```php
<?php

final class EventsServiceProvider
{
    public static function register(): void
    {
        // 1) Bind EventBus
        Container::set(EventBusInterface::class, function (Container $c) {
            // Chọn InMemoryEventBus hoặc StoringEventBus tuỳ môi trường
            return new \TMT\CRM\Core\Events\Infrastructure\Buses\StoringEventBus(
                $c->get(\TMT\CRM\Core\Events\Domain\Repositories\EventRepositoryInterface::class)
            );
        });

        // 2) Load subscribers (từ nhiều module)
        SubscriberLoader::load([
            \TMT\CRM\Modules\Company\Application\Subscribers\CompanyAuditSubscriber::class,
            \TMT\CRM\Core\Notifications\Infrastructure\Subscribers\NotificationSubscriber::class,
        ], Container::instance()->get(EventBusInterface::class));
    }
}
```

## 5) Quy ước đặt tên & payload

- **Tên event**: `context.entity.action` (vd. `crm.company.created`) hoặc rút gọn `company.created`. Giữ ổn định.  
- **Payload**: ưu tiên `DTO` thuần (không đính kèm repository/service) → dễ serialize & test.  
- **Metadata**: luôn có `occurred_at`, nên có `actor_id`, và **dùng `correlation_id`** cho chuỗi hành động.

## 6) Tích hợp với Notifications & Log (phối hợp 3 phần)

- **Notifications**: `NotificationSubscriber` lắng nghe event domain (vd. `company.created`) → render template → gửi (mail/sms/…); đồng thời ghi **notification log** riêng.  
- **Log (App/Activity)**: subscriber khác viết **application audit log** (ai làm gì, lúc nào).  
  - Không trùng với Notification log vì mục đích khác nhau.  
- Nếu muốn, có thể gom vào 1 UI: “Event Log Screen” với filter `type=activity|notification|error`.

## 7) Best practices & lưu ý quan trọng

- **Phát event trong Service** (sau khi nghiệp vụ thành công).  
- **Controller** chỉ nhận request, validate sơ bộ, gọi service, trả response.  
- **Không** truyền string vào `publish()` — **bắt buộc** `EventInterface`.  
- **Idempotency**: guard bằng `event_id` nếu có retry.  
- **Error handling**: catch theo **từng subscriber** để không ảnh hưởng nhau.  
- **Performance**: tác vụ nặng (mail/webhook) nên đẩy sang queue (P2).  
- **Migration** (nếu dùng `StoringEventBus`): bảng gợi ý  

  ```text
  tmtcrm_events(
    id, name, payload_json, actor_id, correlation_id,
    occurred_at, status, error, created_at
  )
  ```

## 8) Kiểu dùng phổ biến (catalog)

- `company.created`, `company.updated`, `company.soft_deleted`, `company.restored`, `company.purged`  
- `customer.created`, …  
- `license.issued`, `license.revoked`  
- `file.uploaded`, `file.versioned`  
- `notification.sent`, `notification.failed`

## 9) Debug & xử lý sự cố nhanh

- **Subscriber không chạy**: kiểm tra `SubscriberLoader::load([...])` đã gọi ở bootstrap chưa; tên event có khớp với `subscribed_events()` không.  
- **Type error**: `publish()` phải nhận `EventInterface`.  
- **Thiếu correlation_id**: khởi tạo ở `admin_init` và đưa vào `EventMetadata`.  
- **Trùng event**: unique theo `event_id` trong log repo.

## 10) Tóm tắt ngắn gọn cách bật/tích hợp trong plugin

1) Bind `EventBusInterface` (InMemory hoặc Storing).  
2) Đăng ký **SubscriberLoader** với danh sách subscriber của toàn hệ thống.  
3) Trong **Service** nghiệp vụ, sau khi thao tác thành công → **publish** `DefaultEvent`.  
4) Module Notifications/Log tự subscribe để làm việc của mình (render & gửi / ghi audit).

---

**Gợi ý**: Có thể sinh **mẫu code đầy đủ** cho: `InMemoryEventBus`, `StoringEventBus`, `EventFactory`, `SubscriberLoader`, `CompanyAuditSubscriber`, `NotificationSubscriber`, và `ServiceProvider` gắn vào plugin main `tmt-customer-crm.php` theo convention hiện tại.
