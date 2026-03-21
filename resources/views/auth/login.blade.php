@extends('layouts.app')

@section('title', 'Đăng nhập | ticketSP')

@section('body')
    <main class="login-shell">
        <section class="card login-card stack">
            <div>
                <div class="brand-mark">IT</div>
                <h1 class="section-title" style="margin-top: 18px;">Đăng nhập hệ thống ticket nội bộ</h1>
                <p class="section-copy">Dành cho đội IT support vận hành backlog chung, tiếp nhận và xử lý ticket nhanh.</p>
            </div>

            <div class="helper">
                Tài khoản mẫu sau khi chạy seed:
                <strong>support.lead@internal.local</strong> / <strong>password</strong>
            </div>

            @if ($errors->any())
                <div class="flash flash-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="stack">
                @csrf

                <label>
                    Email hoặc username
                    <input type="text" name="login" value="{{ old('login') }}" required autofocus>
                </label>

                <label>
                    Mật khẩu
                    <input type="password" name="password" required>
                </label>

                <label style="grid-template-columns: auto 1fr; align-items: center; gap: 12px; color: var(--text);">
                    <input type="checkbox" name="remember" value="1" style="width: auto;">
                    Ghi nhớ đăng nhập trên trình duyệt này
                </label>

                <button class="button-primary" type="submit">Đăng nhập</button>
            </form>
        </section>
    </main>
@endsection
