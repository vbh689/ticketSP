@extends('layouts.app')

@section('title', 'Quản lý Tags | ticketSP')

@section('body')
    <main class="shell stack">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">IT</div>
                <div>
                    <h1>Quản lý tags</h1>
                    <p>Quản lý các danh mục dùng chung cho ticket và các luồng mở rộng sau này.</p>
                </div>
            </div>

            <div class="nav">
                <a class="button button-muted" href="{{ route('tickets.index') }}">Quay lại tickets</a>
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
                <h2 class="section-title">Loại ticket</h2>
                <p class="section-copy">Quản lý danh mục loại ticket bằng tên đơn giản để dùng ngay trong form tạo ticket.</p>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Tên</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ticketCategories as $ticketCategory)
                            <tr>
                                <td colspan="2" style="padding: 0;">
                                    <div class="simple-row-form">
                                        <form method="POST" action="{{ route('ticket-categories.update', $ticketCategory) }}" class="inline-form" style="display: contents;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="name" value="{{ $ticketCategory->name }}" aria-label="Tên loại ticket {{ $ticketCategory->name }}">
                                            <button class="button button-muted" type="submit">Chỉnh sửa</button>
                                        </form>
                                        <form method="POST" action="{{ route('ticket-categories.destroy', $ticketCategory) }}" class="inline-form">
                                            @csrf
                                            @method('DELETE')
                                            <button class="button button-danger" type="submit">Xóa</button>
                                        </form>
                                        </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="empty">Chưa có loại ticket nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <form method="POST" action="{{ route('ticket-categories.store') }}" class="grid grid-2">
                @csrf
                <label>
                    Tên loại ticket
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Phần mềm" required>
                </label>

                <div class="nav" style="grid-column: 1 / -1;">
                    <button class="button-primary" type="submit">Thêm loại ticket</button>
                </div>
            </form>
        </section>

        @foreach ($tagTypes as $type => $label)
            <section class="card panel stack">
                <div>
                    <h2 class="section-title">{{ $label }}</h2>
                    <p class="section-copy">Danh sách gợi ý dùng cho nhập liệu nhanh trên form ticket và các màn hình quản trị khác.</p>
                </div>

                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Tên</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($tagsByType[$type] ?? collect()) as $tag)
                                <tr>
                                    <td colspan="2" style="padding: 0;">
                                        <div class="simple-row-form">
                                            <form method="POST" action="{{ route('tags.update', $tag) }}" class="inline-form" style="display: contents;">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="type" value="{{ $type }}">
                                                <input type="text" name="name" value="{{ $tag->name }}" aria-label="Tên tag {{ $tag->name }}">
                                                <button class="button button-muted" type="submit">Chỉnh sửa</button>
                                            </form>
                                            <form method="POST" action="{{ route('tags.destroy', $tag) }}" class="inline-form">
                                                @csrf
                                                @method('DELETE')
                                                <button class="button button-danger" type="submit">Xóa</button>
                                            </form>
                                            </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="empty">Chưa có tag nào trong nhóm này.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="{{ route('tags.store') }}" class="grid grid-2">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">

                    <label>
                        Tên tag
                        <input type="text" name="name" placeholder="Telegram" required>
                    </label>

                    <div class="nav" style="grid-column: 1 / -1;">
                        <button class="button-primary" type="submit">Thêm {{ \Illuminate\Support\Str::lower($label) }}</button>
                    </div>
                </form>
            </section>
        @endforeach
    </main>
@endsection
