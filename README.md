# ticketSP

Ứng dụng quản lý ticket nội bộ cho đội IT support.

## Phạm vi hiện tại

- Đăng nhập bằng tài khoản nội bộ đơn giản.
- Tạo ticket mới hộ người dùng.
- Theo dõi backlog chung với tìm kiếm và bộ lọc cơ bản.
- Nhận ticket về xử lý.
- Cập nhật trạng thái `Open`, `In Progress`, `Resolved`, `Closed`.
- Thêm ghi chú xử lý và lưu activity log.
- Chia sẻ link xem ticket (read-only) bằng `view_key`.

## Stack

- Laravel 12
- Blade UI (server-rendered)
- MySQL cho môi trường triển khai chính
- PHPUnit cho test nghiệp vụ

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

Mặc định `.env.example` sử dụng SQLite, cập nhật MySQL connection nếu cần thiết.

4. Chạy migration và seed:

```bash
php artisan migrate:fresh --seed
```

Nếu muốn import thêm dữ liệu demo từ file CSV `docs/SupportTickets_Demo.csv`, chạy thêm:

```bash
php artisan db:seed --class=SupportTicketsDemoSeeder
```

5. Serve:

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

## Seeders

- `DatabaseSeeder`:
  seed dữ liệu nền cho hệ thống, gồm tài khoản nội bộ mẫu, loại ticket cơ bản, phương thức liên hệ mẫu và một vài ticket demo nhỏ để kiểm tra nhanh.

```bash
php artisan migrate:fresh --seed
```

## Meilisearch Local

Tìm kiếm khách hàng và ticket sử dụng `Meilisearch` qua `Laravel Scout`, fallback về DB search nếu search service chưa chạy.

1. Khởi động Meilisearch:

```bash
docker compose -f docker-compose.meilisearch.yml up -d
```

2. Cập nhật `.env`:

```bash
SCOUT_DRIVER=meilisearch
SCOUT_QUEUE=true
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=ticketsp-master-key
```

3. Đồng bộ index settings và import dữ liệu:

```bash
php artisan scout:sync-index-settings
php artisan scout:import "App\\Models\\Customer"
php artisan scout:import "App\\Models\\Ticket"
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
