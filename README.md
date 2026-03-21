# ticketSP

Ứng dụng quản lý ticket nội bộ cho đội IT support, được triển khai theo định hướng MVP trong thư mục [`docs/`](./docs/README.md).

## Phạm vi hiện tại

- Đăng nhập bằng tài khoản nội bộ đơn giản.
- Tạo ticket mới hộ người dùng cuối.
- Theo dõi backlog chung với tìm kiếm và bộ lọc cơ bản.
- Nhận ticket về xử lý.
- Cập nhật trạng thái `Open`, `In Progress`, `Resolved`, `Closed`.
- Thêm ghi chú xử lý và lưu activity log.
- Chia sẻ link xem ticket read-only bằng `view_key`.

## Stack

- Laravel 12
- Blade server-rendered UI
- MySQL cho môi trường triển khai chính
- PHPUnit cho test nghiệp vụ

`phpunit.xml` vẫn dùng SQLite in-memory để test chạy nhanh và độc lập.

## Cài đặt nhanh

1. Cài dependency:

```bash
composer install
npm install
```

2. Tạo file môi trường:

```bash
cp .env.example .env
php artisan key:generate
```

3. Cập nhật cấu hình database trong `.env`.

Mặc định `.env.example` đang để MySQL theo định hướng kỹ thuật của dự án. Nếu cần chạy local thật nhanh, bạn có thể đổi sang SQLite.

4. Chạy migration và seed:

```bash
php artisan migrate:fresh --seed
```

5. Khởi động ứng dụng:

```bash
composer run dev
```

Hoặc nếu chỉ cần backend:

```bash
php artisan serve
```

## Tài khoản seed mẫu

Sau khi chạy `php artisan migrate:fresh --seed`, có thể đăng nhập bằng:

- `support.lead@internal.local` / `password`
- `support.agent@internal.local` / `password`

## Lệnh hữu ích

```bash
php artisan test
php artisan migrate:fresh --seed
php artisan route:list
```

## Cấu trúc chính

- `app/Http/Controllers/`:
  luồng đăng nhập, danh sách ticket, chi tiết ticket, ghi chú xử lý.
- `app/Models/`:
  `Ticket`, `TicketCategory`, `TicketComment`, `TicketActivity`, `User`.
- `database/migrations/`:
  schema cho auth, backlog, comment và activity log.
- `resources/views/`:
  giao diện Blade cho login, backlog, tạo ticket và chi tiết ticket.
- `tests/Feature/`:
  test bám theo `docs/test-plan.md`.

## Tài liệu nghiệp vụ

Đọc theo thứ tự:

1. [`docs/README.md`](./docs/README.md)
2. [`docs/product-overview.md`](./docs/product-overview.md)
3. [`docs/workflows.md`](./docs/workflows.md)
4. [`docs/data-model.md`](./docs/data-model.md)
5. [`docs/screens-and-features.md`](./docs/screens-and-features.md)
6. [`docs/technical-direction.md`](./docs/technical-direction.md)
7. [`docs/test-plan.md`](./docs/test-plan.md)
