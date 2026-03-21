@extends('layouts.app')

@section('title', 'Tạo Ticket | ticketSP')

@section('body')
    <main class="shell stack">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">IT</div>
                <div>
                    <h1>Tạo ticket mới</h1>
                    <p>Ghi nhận nhanh yêu cầu hỗ trợ và đưa ngay vào backlog chung.</p>
                </div>
            </div>

            <div class="nav">
                <a class="button button-muted" href="{{ route('tickets.index') }}">Quay lại backlog</a>
            </div>
        </header>

        @if ($errors->any())
            <div class="flash flash-error">{{ $errors->first() }}</div>
        @endif

        <section class="card panel stack">
            <div>
                <h2 class="section-title">Thông tin ticket</h2>
                <p class="section-copy">Ticket mới sẽ mặc định ở trạng thái Open và chưa có người phụ trách.</p>
            </div>

            <form method="POST" action="{{ route('tickets.store') }}" class="stack">
                @csrf

                <div class="grid grid-2">
                    <label>
                        Người yêu cầu
                        <input type="text" name="requester_name" value="{{ old('requester_name') }}" required>
                    </label>

                    <label>
                        Liên hệ
                        <input type="text" name="requester_contact" value="{{ old('requester_contact') }}" placeholder="Email, số điện thoại hoặc Teams">
                    </label>
                </div>

                <label>
                    Tiêu đề
                    <input type="text" name="title" value="{{ old('title') }}" required>
                </label>

                <label>
                    Loại ticket
                    <select name="category_id" required>
                        <option value="">Chọn loại ticket</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Mô tả chi tiết
                    <textarea name="description" required>{{ old('description') }}</textarea>
                </label>

                <div class="nav">
                    <button class="button-primary" type="submit">Lưu ticket</button>
                    <a class="button button-secondary" href="{{ route('tickets.index') }}">Hủy</a>
                </div>
            </form>
        </section>
    </main>
@endsection
