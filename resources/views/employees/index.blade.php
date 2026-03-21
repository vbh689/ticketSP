@extends('layouts.app')

@section('title', 'Nhân viên | ticketSP')

@section('body')
    <main class="shell stack">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">IT</div>
                <div>
                    <h1>Quản lý nhân viên</h1>
                    <p>Quản lý danh bạ support nội bộ dùng cho vận hành ticket và liên hệ xử lý.</p>
                </div>
            </div>

            <div class="nav">
                <a class="button button-muted" href="{{ route('tickets.index') }}">Quay lại tickets</a>
                <a class="button button-primary" href="{{ route('employees.create') }}">Thêm nhân viên</a>
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
                <h2 class="section-title">Danh sách nhân viên</h2>
                <p class="section-copy">Thông tin liên hệ giúp đội support dễ phối hợp khi cùng xử lý ticket.</p>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Tên</th>
                            <th>Số điện thoại</th>
                            <th>Phòng ban</th>
                            <th>Liên lạc chính</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            <tr>
                                <td>{{ $employee->email }}</td>
                                <td>{{ $employee->username }}</td>
                                <td><strong>{{ $employee->name }}</strong></td>
                                <td>{{ $employee->phone ?: 'Chưa cập nhật' }}</td>
                                <td>{{ $employee->department ?: 'Chưa cập nhật' }}</td>
                                <td>{{ $employee->primary_contact_method ?: 'Chưa cập nhật' }}</td>
                                <td>
                                    <span class="badge {{ $employee->status === 'active' ? 'badge-progress' : 'badge-closed' }}">
                                        {{ $employee->status === 'active' ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <a class="button button-muted" href="{{ route('employees.edit', $employee) }}">Chỉnh sửa</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="empty">Chưa có nhân viên nào trong danh sách.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination">{{ $employees->links() }}</div>
        </section>
    </main>
@endsection
