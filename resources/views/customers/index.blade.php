@extends('layouts.app')

@section('title', 'Khách hàng | ticketSP')

@section('body')
    <main class="shell stack">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">IT</div>
                <div>
                    <h1>Quản lý khách hàng</h1>
                    <p>Lưu đầu mối khách để support dễ tra cứu liên hệ và tình trạng license.</p>
                </div>
            </div>

            <div class="nav">
                <a class="button button-muted" href="{{ route('tickets.index') }}">Quay lại tickets</a>
                <a class="button button-primary" href="{{ route('customers.create') }}">Thêm khách</a>
            </div>
        </header>

        @if (session('status'))
            <div class="flash flash-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="flash flash-error">{{ $errors->first() }}</div>
        @endif

        <section class="card panel stack">
            <div>
                <h2 class="section-title">Danh sách khách hàng</h2>
                <p class="section-copy">Tập trung thông tin công ty, đầu mối liên hệ và số lượng license đang quản lý.</p>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Tên</th>
                            <th>Điện thoại</th>
                            <th>Email</th>
                            <th>Nhân viên đại diện</th>
                            <th>Điện thoại đại diện</th>
                            <th>Số license</th>
                            <th>Địa chỉ</th>
                            <th>Ghi chú</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer)
                            <tr>
                                <td><strong>{{ $customer->name }}</strong></td>
                                <td>{{ $customer->phone ?: 'Chưa cập nhật' }}</td>
                                <td>{{ $customer->email ?: 'Chưa cập nhật' }}</td>
                                <td>{{ $customer->representative_name ?: 'Chưa cập nhật' }}</td>
                                <td>{{ $customer->representative_phone ?: 'Chưa cập nhật' }}</td>
                                <td>{{ $customer->license_count ?? 'Chưa cập nhật' }}</td>
                                <td>{{ $customer->address ?: 'Chưa cập nhật' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($customer->notes ?: 'Chưa cập nhật', 80) }}</td>
                                <td>
                                    <a class="button button-muted" href="{{ route('customers.edit', $customer) }}">Chỉnh sửa</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="empty">Chưa có khách hàng nào trong danh sách.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination">{{ $customers->links() }}</div>
        </section>
    </main>
@endsection
