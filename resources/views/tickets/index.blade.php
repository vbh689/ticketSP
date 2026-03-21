@extends('layouts.app')

@section('title', 'Backlog Ticket | ticketSP')

@section('body')
    <main class="shell stack">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">IT</div>
                <div>
                    <h1>ticketSP</h1>
                    <p>Backlog chung cho đội IT support</p>
                </div>
            </div>

            <div class="nav">
                <a class="button button-primary" href="{{ route('tickets.create') }}">Tạo ticket</a>
                <form method="POST" action="{{ route('logout') }}" class="inline-form">
                    @csrf
                    <button class="button button-secondary" type="submit">Đăng xuất</button>
                </form>
            </div>
        </header>

        @if (session('status'))
            <div class="flash flash-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="flash flash-error">{{ $errors->first() }}</div>
        @endif

        <section class="hero card">
            <div>
                <h2 class="section-title" style="margin: 0;">Theo dõi backlog và nhận ticket ngay trên một màn hình</h2>
                <p>Lọc theo trạng thái, loại sự cố hoặc người phụ trách để kiểm soát toàn bộ vòng đời ticket trong đội support.</p>
            </div>

            <div class="metrics">
                <div class="metric">
                    Open
                    <strong>{{ $tickets->where('status', 'Open')->count() }}</strong>
                </div>
                <div class="metric">
                    In Progress
                    <strong>{{ $tickets->where('status', 'In Progress')->count() }}</strong>
                </div>
                <div class="metric">
                    Resolved
                    <strong>{{ $tickets->where('status', 'Resolved')->count() }}</strong>
                </div>
                <div class="metric">
                    Trang hiện tại
                    <strong>{{ $tickets->count() }}</strong>
                </div>
            </div>
        </section>

        <section class="card panel stack">
            <div>
                <h2 class="section-title">Bộ lọc backlog</h2>
                <p class="section-copy">Tìm nhanh theo mã ticket, người yêu cầu hoặc tiêu đề. Có thể kết hợp nhiều bộ lọc cùng lúc.</p>
            </div>

            <form method="GET" action="{{ route('tickets.index') }}" class="toolbar">
                <label>
                    Tìm kiếm
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="IT-000001, VPN, tên người dùng">
                </label>

                <label>
                    Trạng thái
                    <select name="status">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Loại ticket
                    <select name="category_id">
                        <option value="">Tất cả loại</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Người phụ trách
                    <select name="assignee_id">
                        <option value="">Tất cả</option>
                        <option value="unassigned" @selected(($filters['assignee_id'] ?? '') === 'unassigned')>Chưa có người nhận</option>
                        @foreach ($assignees as $assignee)
                            <option value="{{ $assignee->id }}" @selected((string) ($filters['assignee_id'] ?? '') === (string) $assignee->id)>{{ $assignee->name }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="toolbar-actions">
                    <button class="button-primary" type="submit">Áp dụng</button>
                    <a class="button button-muted" href="{{ route('tickets.index') }}">Xóa lọc</a>
                </div>
            </form>
        </section>

        <section class="card panel">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Mã ticket</th>
                            <th>Nội dung</th>
                            <th>Requester</th>
                            <th>Loại</th>
                            <th>Phụ trách</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td>
                                    <strong>{{ $ticket->ticket_code }}</strong>
                                    <div class="meta">{{ $ticket->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td>
                                    <a href="{{ route('tickets.show', $ticket) }}"><strong>{{ $ticket->title }}</strong></a>
                                    <div class="meta">{{ \Illuminate\Support\Str::limit($ticket->description, 90) }}</div>
                                </td>
                                <td>
                                    <strong>{{ $ticket->requester_name }}</strong>
                                    <div class="meta">{{ $ticket->requester_contact ?: 'Chưa có liên hệ' }}</div>
                                </td>
                                <td>{{ $ticket->category->name }}</td>
                                <td>{{ $ticket->assignee?->name ?? 'Chưa có người nhận' }}</td>
                                <td>@include('tickets.partials.status-badge', ['status' => $ticket->status])</td>
                                <td class="nav">
                                    <a class="button button-muted" href="{{ route('tickets.show', $ticket) }}">Chi tiết</a>
                                    @if (! $ticket->assignee_id)
                                        <form method="POST" action="{{ route('tickets.claim', $ticket) }}" class="inline-form">
                                            @csrf
                                            <button class="button-primary" type="submit">Nhận ticket</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty">Chưa có ticket nào khớp với bộ lọc hiện tại.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination">{{ $tickets->links() }}</div>
        </section>
    </main>
@endsection
