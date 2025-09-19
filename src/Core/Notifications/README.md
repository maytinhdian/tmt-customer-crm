# TMT CRM – Core/Notifications (P0 Skeleton)

> Lưu ý “bootstrap” ở đây là **file khởi động chính** của module.

## Mục tiêu
- Lắng nghe **event domain** (CompanyCreated, CompanySoftDeleted, …) và tạo Notification.
- Hỗ trợ kênh **Admin Notice** và **Email** (khung sẵn).
- Template/Preference/Delivery theo DTO, Repository tách riêng.
- Chuẩn hoá quyền xem bằng PolicyGuard (placeholder gọi PolicyService sau).

## Cấu trúc
```
src/Core/Notifications/
  NotificationsModule.php                 (bootstrap – file chính)
  Domain/
    EventKeys.php
    DTO/
      NotificationDTO.php
      DeliveryDTO.php
      TemplateDTO.php
      RecipientDTO.php
      PreferenceDTO.php
      EventContextDTO.php
  Application/Services/
    PolicyGuard.php
    TemplateRenderer.php
    PreferenceService.php
    DeliveryService.php
    NotificationDispatcher.php
  Infrastructure/
    Installer.php
    Channels/
      ChannelInterface.php
      AdminNoticeChannel.php
      EmailChannel.php
  Presentation/Admin/
    Screen/
      NotificationCenterScreen.php
      SettingsScreen.php
    Controller/
      NotificationController.php
demos/notifications-demo.html             (wireframe UI)
```

## Ghi chú đặt tên
- Class/file **PascalCase**, `DTO` đuôi **DTO** (không phải Dto).
- Hàm **snake_case**.
- Interface Repository đặt dưới namespace: `TMT\CRM\Domain\Repositories\`.

## Tích hợp
- **View**: sử dụng `View::render_admin_module()` / `View::render_admin_partial()`.
- **Events**: đăng ký `EventBus::listen(EventKeys::..., [NotificationDispatcher::class, 'on_event'])` (placeholder).
- **Settings**: SMTP/sender và bật/tắt kênh nên cấu hình tại Core/Settings.

## Tiếp theo
1) Tạo bảng `notifications`, `notification_deliveries`, `notification_templates`, `notification_preferences` trong `Installer.php`.
2) Map event mẫu `CompanyCreated` → tạo 1 notification + deliveries theo kênh.
3) Render Notification Center (Admin) bằng View helper của dự án.
